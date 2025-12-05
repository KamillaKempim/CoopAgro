<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = intval($_SESSION['user_id']);

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Produtor') {
        $_SESSION['error'] = 'Acesso restrito a produtores.';
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    die("Erro ao verificar tipo de usuário: " . htmlspecialchars($e->getMessage()));
}

$uploadDir = 'uploads/produtos/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; 

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];
$success = false;
$dadosForm = [
    'nome' => '',
    'descricao' => '',
    'tipo' => '',
    'unidade_medida' => 'KG',
    'quantidade_disponivel' => 1,
    'preco_sugerido' => '',
    'observacoes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Token CSRF inválido.';
        header("Location: propor_produto.php");
        exit();
    }
    
    $dadosForm['nome'] = sanitizeInput($_POST['nome'] ?? '');
    $dadosForm['descricao'] = sanitizeInput($_POST['descricao'] ?? '');
    $dadosForm['tipo'] = sanitizeInput($_POST['tipo'] ?? '');
    $dadosForm['unidade_medida'] = sanitizeInput($_POST['unidade_medida'] ?? 'KG');
    $dadosForm['quantidade_disponivel'] = intval($_POST['quantidade_disponivel'] ?? 1);
    $dadosForm['observacoes'] = sanitizeInput($_POST['observacoes'] ?? '');
    
    $precoInput = $_POST['preco_sugerido'] ?? '';
    $precoInput = str_replace(',', '.', $precoInput);
    $dadosForm['preco_sugerido'] = floatval($precoInput);
    
    if (empty($dadosForm['nome']) || strlen($dadosForm['nome']) > 255) {
        $errors[] = "Nome do produto é obrigatório e deve ter no máximo 255 caracteres.";
    }
    
    if (empty($dadosForm['descricao'])) {
        $errors[] = "Descrição é obrigatória.";
    }
    
    if (empty($dadosForm['tipo'])) {
        $errors[] = "Tipo do produto é obrigatório.";
    }
    
    if (!in_array($dadosForm['unidade_medida'], ['KG', 'UN', 'Dúzia', 'Penca', 'Outros'])) {
        $errors[] = "Unidade de medida inválida.";
    }
    
    if ($dadosForm['quantidade_disponivel'] <= 0) {
        $errors[] = "Quantidade deve ser maior que zero.";
    }
    
    if ($dadosForm['preco_sugerido'] <= 0) {
        $errors[] = "Preço sugerido deve ser maior que zero.";
    }
    
    $imagemNome = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagem = $_FILES['imagem'];
        
        if ($imagem['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erro no upload da imagem: " . getUploadError($imagem['error']);
        } else {
            $fileExtension = strtolower(pathinfo($imagem['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.";
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagem['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "Tipo MIME do arquivo não é permitido.";
            }
            
            if ($imagem['size'] > $maxFileSize) {
                $errors[] = "Arquivo muito grande. Tamanho máximo permitido: 5MB.";
            }
            
            $imageInfo = getimagesize($imagem['tmp_name']);
            if (!$imageInfo) {
                $errors[] = "O arquivo enviado não é uma imagem válida.";
            }
            
            if (empty($errors)) {
                $imagemNome = uniqid('produto_', true) . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $imagemNome;
                
                if (!move_uploaded_file($imagem['tmp_name'], $uploadPath)) {
                    $errors[] = "Erro ao salvar a imagem. Tente novamente.";
                }
            }
        }
    } else {
        $errors[] = "Imagem do produto é obrigatória.";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("
                INSERT INTO produtos_propostos 
                (produtor_id, nome, descricao, tipo, unidade_medida, quantidade_disponivel, imagem_url, preco_sugerido, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $dadosForm['nome'],
                $dadosForm['descricao'],
                $dadosForm['tipo'],
                $dadosForm['unidade_medida'],
                $dadosForm['quantidade_disponivel'],
                $imagemNome,
                $dadosForm['preco_sugerido'],
                $dadosForm['observacoes']
            ]);
            
            $success = true;
            
        } catch(PDOException $e) {
            if (!empty($imagemNome) && file_exists($uploadDir . $imagemNome)) {
                unlink($uploadDir . $imagemNome);
            }
            $errors[] = "Erro ao propor produto: " . htmlspecialchars($e->getMessage());
            error_log("Erro ao propor produto - Usuário: $usuario_id - Erro: " . $e->getMessage());
        }
    }
}

function getUploadError($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Arquivo muito grande.';
        case UPLOAD_ERR_PARTIAL:
            return 'Upload parcial do arquivo.';
        case UPLOAD_ERR_NO_FILE:
            return 'Nenhum arquivo foi enviado.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Pasta temporária não encontrada.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Falha ao escrever no disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload interrompido por extensão.';
        default:
            return 'Erro desconhecido.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propor Produto - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --verde-escuro: #1b5e20;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-card .card-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .form-card .card-body {
            padding: 30px;
        }
        
        .btn-propor {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-propor:hover {
            background: linear-gradient(135deg, var(--verde-escuro) 0%, var(--verde-principal) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
            color: white;
        }
        
        .preview-imagem {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: var(--verde-principal);
            background-color: #e8f5e8;
        }
        
        .upload-area.dragover {
            border-color: var(--verde-principal);
            background-color: #d1e7dd;
        }
        
        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #f5f5dc 100%);
            border: none;
            border-radius: 15px;
            border-left: 4px solid var(--verde-principal);
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }
        
        .progress-info {
            background: linear-gradient(135deg, var(--verde-claro) 0%, #e8f5e8 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        #preco_sugerido {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
        }
        
        .input-group .form-control {
            z-index: 1;
        }
        /* Botão de acessibilidade */
.painel-flutuante {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
}

#btnAbrir {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: none;
  background-color: #0c7534;
  color: #fff;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.2s;
}

#btnAbrir:hover {
  background-color: #0b7d44;
}


.painel-acessibilidade {
  display: none; 
  flex-direction: column;
  gap: 6px;
  margin-bottom: 10px;
  background: #ffffff;
  color:rgb(11, 66, 5);
  padding: 12px 15px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}


.painel-acessibilidade button {
  padding: 8px 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  transition: all 0.2s;
  background-color: #f0f0f0;
}

.painel-acessibilidade button:hover {
  background-color: #d4d4d4;
}

.modo-contraste, 
.modo-contraste * {
  background-color: #000 !important;
  color: #fff !important;
  border-color: #fff !important;
}

.modo-contraste a {
  color: #FFD700 !important;
  text-decoration: underline;
}

.modo-contraste img {
  filter: brightness(0.8) !important;
}

.modo-contraste, 
.modo-contraste * {
  background-color: #000 !important;
  color: #fff !important;
  border-color: #fff !important;
  fill: #fff !important;
  stroke: #fff !important;
}

.modo-contraste a,
.modo-contraste a * {
  color: #FFD700 !important;
  text-decoration: underline !important;
}

.modo-contraste * {
  background-image: none !important;
}

.modo-contraste img,
.modo-contraste [style*="background-image"] {
  filter: grayscale(1) brightness(0.4) !important;
}

.modo-contraste .card,
.modo-contraste .container,
.modo-contraste section,
.modo-contraste .row,
.modo-contraste .col,
.modo-contraste footer,
.modo-contraste header,
.modo-contraste nav {
  background-color: #000 !important;
  color: #fff !important;
}

.modo-contraste button,
.modo-contraste .btn {
  background-color: #222 !important;
  color: #fff !important;
  border: 1px solid #fff !important;
}

.modo-contraste .bi,
.modo-contraste i {
  color: #fff !important;
}

.modo-contraste .carousel-item,
.modo-contraste .carousel-caption {
  background-color: #000 !important;
}

@media (max-width: 768px) {
  .container img {
    display: none !important;
  }
}
@media (max-width: 768px) {
  .texto {
    width: 100% !important;
  }
}
@media (max-width: 768px) {
  .hero {
    height: auto;
  }

  .hero-img {
    width: 100%;
    height: auto;
    object-fit: contain; 
  }
}
.modo-contraste img,
.modo-contraste [style*="background-image"] {
  filter: grayscale(0.2) brightness(0.8) !important; 
}
    </style>
</head>
<body>
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <!-- Painel acessibilidade -->
<div class="painel-flutuante">
  <button id="btnAbrir">⚙️</button>
  <div class="painel-acessibilidade" id="painelAcessibilidade">
    <h4>Painel de Acessibilidade</h4> 
    <button onclick="contraste()"><i class="bi bi-brightness-high-fill">  </i>Alto contraste</button>
    <button onclick="fonteMais()"><i class="bi bi-type-bold"></i></button>
    <button onclick="fonteMenos()"><i class="bi bi-type"></i></button>
    <button onclick="resetar()"><i class="bi bi-arrow-counterclockwise"></i>  Padrão</button>
  </div>
</div>

<script>
let tamanho = localStorage.getItem("fonte") || 16;
document.body.style.fontSize = tamanho + "px";

if(localStorage.getItem("contraste") === "ativo") {
  document.body.classList.add("modo-contraste");
}

function contraste() {
  document.body.classList.toggle("modo-contraste");
  let ativo = document.body.classList.contains("modo-contraste");
  localStorage.setItem("contraste", ativo ? "ativo" : "inativo");
}

function fonteMais() {
  tamanho = parseInt(tamanho) + 2;
  document.body.style.fontSize = tamanho + "px";
  localStorage.setItem("fonte", tamanho);
}

function fonteMenos() {
  tamanho = parseInt(tamanho) - 2;
  document.body.style.fontSize = tamanho + "px";
  localStorage.setItem("fonte", tamanho);
}

function resetar() {
  document.body.classList.remove("modo-contraste");
  document.body.style.fontSize = "16px";
  localStorage.clear();
}

const btnAbrir = document.getElementById('btnAbrir');
const painel = document.getElementById('painelAcessibilidade');

btnAbrir.addEventListener('click', () => {
  painel.style.display = painel.style.display === 'flex' ? 'none' : 'flex';
});
</script>


    <?php include 'includes/_menu.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-megaphone"></i> Propor Novo Produto
                    </h1>
                    <p class="lead mb-0">Envie sua proposta de produto para análise da cooperativa</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-clipboard2-plus" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if ($success): ?>
            <!-- Tela de Sucesso -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-success">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle-fill text-success display-1 mb-3"></i>
                            <h2 class="text-success mb-3">Proposta Enviada com Sucesso!</h2>
                            <p class="lead mb-4">Sua proposta de produto foi enviada para análise da cooperativa.</p>
                            <p class="text-muted mb-4">
                                Nossa equipe irá analisar sua proposta e em breve entraremos em contato. 
                                Você pode acompanhar o status da sua proposta na página "Minhas Propostas".
                            </p>
                            <div class="d-grid gap-2 d-md-flex justify-content-center">
                                <a href="propor_produto.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-plus-circle"></i> Propor Outro Produto
                                </a>
                                <a href="minhas_propostas.php" class="btn btn-outline-success btn-lg">
                                    <i class="bi bi-clock-history"></i> Ver Minhas Propostas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-custom">
                            <h5><i class="bi bi-exclamation-triangle"></i> Erros no formulário:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Informações do Processo -->
                    <div class="progress-info">
                        <h5 class="fw-bold text-success mb-3"><i class="bi bi-info-circle"></i> Processo de Proposta</h5>
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="border rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <strong>1</strong>
                                </div>
                                <p class="small mb-0">Proposta</p>
                            </div>
                            <div class="col-3">
                                <div class="border rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <strong>2</strong>
                                </div>
                                <p class="small mb-0">Análise</p>
                            </div>
                            <div class="col-3">
                                <div class="border rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <strong>3</strong>
                                </div>
                                <p class="small mb-0">Aprovação</p>
                            </div>
                            <div class="col-3">
                                <div class="border rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <strong>4</strong>
                                </div>
                                <p class="small mb-0">Vendas</p>
                            </div>
                        </div>
                    </div>

                    <div class="card form-card">
                        <div class="card-header">
                            <i class="bi bi-clipboard2-data"></i> Informações do Produto
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="formProposta">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nome" class="form-label">Nome do Produto *</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required 
                                               value="<?php echo htmlspecialchars($dadosForm['nome']); ?>"
                                               maxlength="255"
                                               placeholder="Ex: Tomate Cereja, Alface Crespa, etc.">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="descricao" class="form-label">Descrição Detalhada *</label>
                                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required 
                                                  placeholder="Descreva o produto, variedade, características, método de cultivo, etc."><?php echo htmlspecialchars($dadosForm['descricao']); ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="tipo" class="form-label">Tipo do Produto *</label>
                                        <select class="form-select" id="tipo" name="tipo" required>
                                            <option value="">Selecione o tipo...</option>
                                            <option value="hortaliça" <?php echo $dadosForm['tipo'] === 'hortaliça' ? 'selected' : ''; ?>>Hortaliça</option>
                                            <option value="legume" <?php echo $dadosForm['tipo'] === 'legume' ? 'selected' : ''; ?>>Legume</option>
                                            <option value="fruta" <?php echo $dadosForm['tipo'] === 'fruta' ? 'selected' : ''; ?>>Fruta</option>
                                            <option value="tubérculo" <?php echo $dadosForm['tipo'] === 'tubérculo' ? 'selected' : ''; ?>>Tubérculo</option>
                                            <option value="vagem" <?php echo $dadosForm['tipo'] === 'vagem' ? 'selected' : ''; ?>>Vagem</option>
                                            <option value="outros" <?php echo $dadosForm['tipo'] === 'outros' ? 'selected' : ''; ?>>Outros</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="unidade_medida" class="form-label">Unidade de Medida *</label>
                                        <select class="form-select" id="unidade_medida" name="unidade_medida" required>
                                            <option value="">Selecione...</option>
                                            <option value="KG" <?php echo $dadosForm['unidade_medida'] === 'KG' ? 'selected' : ''; ?>>Quilograma (KG)</option>
                                            <option value="UN" <?php echo $dadosForm['unidade_medida'] === 'UN' ? 'selected' : ''; ?>>Unidade (UN)</option>
                                            <option value="Dúzia" <?php echo $dadosForm['unidade_medida'] === 'Dúzia' ? 'selected' : ''; ?>>Dúzia</option>
                                            <option value="Penca" <?php echo $dadosForm['unidade_medida'] === 'Penca' ? 'selected' : ''; ?>>Penca</option>
                                            <option value="Outros" <?php echo $dadosForm['unidade_medida'] === 'Outros' ? 'selected' : ''; ?>>Outros</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="quantidade_disponivel" class="form-label">Quantidade Disponível *</label>
                                        <input type="number" class="form-control" id="quantidade_disponivel" name="quantidade_disponivel" 
                                               min="1" required value="<?php echo htmlspecialchars($dadosForm['quantidade_disponivel']); ?>">
                                        <div class="form-text" id="quantidade-texto">Quantidade disponível em <?php echo htmlspecialchars($dadosForm['unidade_medida']); ?></div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="preco_sugerido" class="form-label">Preço Sugerido (R$) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="preco_sugerido" 
                                                   name="preco_sugerido" 
                                                   required 
                                                   value="<?php echo isset($dadosForm['preco_sugerido']) && $dadosForm['preco_sugerido'] > 0 ? 
                                                           htmlspecialchars(number_format($dadosForm['preco_sugerido'], 2, ',', '.')) : ''; ?>"
                                                   placeholder="0,00">
                                        </div>
                                        <div class="form-text" id="preco-texto">Preço sugerido por <?php echo htmlspecialchars($dadosForm['unidade_medida']); ?></div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="observacoes" class="form-label">Observações Adicionais</label>
                                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                                                  placeholder="Informações adicionais que possam ajudar na análise do produto..."><?php echo htmlspecialchars($dadosForm['observacoes']); ?></textarea>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- Upload de Imagem -->
                    <div class="card form-card">
                        <div class="card-header">
                            <i class="bi bi-image"></i> Imagem do Produto
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Foto do Produto *</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1">Clique para selecionar ou arraste uma imagem do produto</p>
                                    <p class="text-muted small">Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                                    <input type="file" class="d-none" id="imagem" name="imagem" accept="image/*" required>
                                </div>
                                <img id="preview" class="preview-imagem d-none mt-3" alt="Preview da imagem">
                                <div class="form-text mt-2">A imagem deve mostrar claramente o produto proposto.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-propor btn-lg">
                            <i class="bi bi-send-check"></i> Enviar Proposta para Análise
                        </button>
                    </div>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="bi bi-info-circle"></i> Como Funciona?</h5>
                            <div class="mb-3">
                                <h6 class="text-success"><i class="bi bi-1-circle"></i> Proposta</h6>
                                <p class="small mb-2">Você envia os dados do produto que deseja oferecer</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-success"><i class="bi bi-2-circle"></i> Análise</h6>
                                <p class="small mb-2">Nossa equipe analisa qualidade, preço e demanda</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-success"><i class="bi bi-3-circle"></i> Aprovação</h6>
                                <p class="small mb-2">Produto aprovado é incluído no catálogo da cooperativa</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-success"><i class="bi bi-4-circle"></i> Vendas</h6>
                                <p class="small mb-0">Comerciantes compram através da plataforma</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card info-card mt-4">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="bi bi-lightbulb"></i> Dicas para Aprovação</h5>
                            <ul class="small">
                                <li>Forneça fotos de boa qualidade</li>
                                <li>Descreva bem as características</li>
                                <li>Informe o método de cultivo</li>
                                <li>Sugira um preço competitivo</li>
                                <li>Mencione certificações (se houver)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card info-card mt-4">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="bi bi-person"></i> Produtor</h5>
                            <p class="mb-2"><strong><?php echo htmlspecialchars($usuario['nome']); ?></strong></p>
                            <p class="small text-muted mb-0">Suas propostas ficarão disponíveis em "Minhas Propostas"</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem');
            const preview = document.getElementById('preview');
            const precoInput = document.getElementById('preco_sugerido');
            const form = document.getElementById('formProposta');
            const unidadeSelect = document.getElementById('unidade_medida');
            const quantidadeTexto = document.getElementById('quantidade-texto');
            const precoTexto = document.getElementById('preco-texto');
            
            function atualizarTextosUnidade() {
                const unidade = unidadeSelect.value || 'unidade';
                
                if (quantidadeTexto) {
                    quantidadeTexto.textContent = `Quantidade disponível em ${unidade}`;
                }
                
                if (precoTexto) {
                    precoTexto.textContent = `Preço sugerido por ${unidade}`;
                }
                
                // Atualizar placeholder do preço
                if (precoInput) {
                    precoInput.placeholder = `0,00 por ${unidade}`;
                }
            }
            unidadeSelect.addEventListener('change', atualizarTextosUnidade);
            
            // Upload de imagem
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    
                    if (!allowedExtensions.includes(fileExtension)) {
                        alert('Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('d-none');
                        uploadArea.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Remover preview
            preview.addEventListener('click', function() {
                fileInput.value = '';
                preview.classList.add('d-none');
                uploadArea.style.display = 'block';
            });
            
            // Validação simples do preço (permite números, vírgula e ponto)
            precoInput.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                
                // Permite números
                if (/[0-9]/.test(char)) {
                    return true;
                }
                
                // Permite vírgula (apenas uma)
                if (char === ',' && this.value.indexOf(',') === -1) {
                    return true;
                }
                
                // Permite backspace, delete, tab, etc.
                if (e.which === 8 || e.which === 9 || e.which === 0) {
                    return true;
                }
                
                e.preventDefault();
                return false;
            });
            
            // Validação do formulário
            form.addEventListener('submit', function(e) {
                let valid = true;
                const quantidadeInput = document.getElementById('quantidade_disponivel');
                
                // Validar quantidade
                if (parseInt(quantidadeInput.value) < 1) {
                    alert('A quantidade deve ser maior que zero.');
                    quantidadeInput.focus();
                    valid = false;
                }
                
                // Validar preço
                const precoValue = precoInput.value.replace(',', '.');
                if (isNaN(parseFloat(precoValue)) || parseFloat(precoValue) <= 0) {
                    alert('O preço sugerido deve ser maior que zero e em formato válido (ex: 10,50).');
                    precoInput.focus();
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
            
            // Inicializar textos
            atualizarTextosUnidade();
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>