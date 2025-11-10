<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];

// Verifica se o usuário é produtor
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Produtor') {
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    die("Erro ao verificar tipo de usuário: " . $e->getMessage());
}

// Configurações para upload de imagem
$uploadDir = 'uploads/propostas/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Criar diretório de uploads se não existir
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido.');
    }
    
    $nome = sanitizeInput($_POST['nome']);
    $descricao = sanitizeInput($_POST['descricao']);
    $tipo = sanitizeInput($_POST['tipo']);
    $quantidade_disponivel = intval($_POST['quantidade_disponivel']);
    $preco_sugerido = floatval(str_replace(',', '.', $_POST['preco_sugerido']));
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    // Validações
    if (empty($nome)) $errors[] = "Nome do produto é obrigatório.";
    if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
    if (empty($tipo)) $errors[] = "Tipo do produto é obrigatório.";
    if ($quantidade_disponivel <= 0) $errors[] = "Quantidade deve ser maior que zero.";
    if ($preco_sugerido <= 0) $errors[] = "Preço sugerido deve ser maior que zero.";
    
    // Processar upload da imagem
    $imagemNome = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagem = $_FILES['imagem'];
        
        // Verificar se há erro no upload
        if ($imagem['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erro no upload da imagem: " . getUploadError($imagem['error']);
        } else {
            // Verificar tipo do arquivo
            $fileType = mime_content_type($imagem['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.";
            }
            
            // Verificar tamanho do arquivo
            if ($imagem['size'] > $maxFileSize) {
                $errors[] = "Arquivo muito grande. Tamanho máximo permitido: 5MB.";
            }
            
            if (empty($errors)) {
                // Gerar nome único para o arquivo
                $fileExtension = pathinfo($imagem['name'], PATHINFO_EXTENSION);
                $imagemNome = uniqid('proposta_') . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $imagemNome;
                
                // Mover arquivo para o diretório de uploads
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
                (produtor_id, nome, descricao, tipo, quantidade_disponivel, imagem_url, preco_sugerido, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $nome,
                $descricao,
                $tipo,
                $quantidade_disponivel,
                $imagemNome,
                $preco_sugerido,
                $observacoes
            ]);
            
            $success = true;
            
        } catch(PDOException $e) {
            // Se houve erro, remove a imagem que foi salva
            if (!empty($imagemNome) && file_exists($uploadDir . $imagemNome)) {
                unlink($uploadDir . $imagemNome);
            }
            $errors[] = "Erro ao propor produto: " . $e->getMessage();
        }
    }
}

// Função para obter mensagem de erro de upload
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
    </style>
</head>
<body>
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
                                    <li><?php echo $error; ?></li>
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
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nome" class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required 
                                               value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                               placeholder="Ex: Tomate Cereja, Alface Crespa, etc.">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="descricao" class="form-label">Descrição Detalhada</label>
                                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required 
                                                  placeholder="Descreva o produto, variedade, características, método de cultivo, etc."><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="tipo" class="form-label">Tipo do Produto</label>
                                        <select class="form-select" id="tipo" name="tipo" required>
                                            <option value="">Selecione o tipo...</option>
                                            <option value="hortaliça" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'hortaliça') ? 'selected' : ''; ?>>Hortaliça</option>
                                            <option value="legume" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'legume') ? 'selected' : ''; ?>>Legume</option>
                                            <option value="fruta" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'fruta') ? 'selected' : ''; ?>>Fruta</option>
                                            <option value="tubérculo" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'tubérculo') ? 'selected' : ''; ?>>Tubérculo</option>
                                            <option value="vagem" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'vagem') ? 'selected' : ''; ?>>Vagem</option>
                                            <option value="outros" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'outros') ? 'selected' : ''; ?>>Outros</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="quantidade_disponivel" class="form-label">Quantidade Disponível (unidades)</label>
                                        <input type="number" class="form-control" id="quantidade_disponivel" name="quantidade_disponivel" 
                                               min="1" required value="<?php echo isset($_POST['quantidade_disponivel']) ? htmlspecialchars($_POST['quantidade_disponivel']) : '1'; ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="preco_sugerido" class="form-label">Preço Sugerido (R$)</label>
                                        <input type="text" class="form-control" id="preco_sugerido" name="preco_sugerido" 
                                               required value="<?php echo isset($_POST['preco_sugerido']) ? htmlspecialchars($_POST['preco_sugerido']) : ''; ?>"
                                               placeholder="0,00">
                                        <div class="form-text">Preço sugerido por unidade</div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="observacoes" class="form-label">Observações Adicionais</label>
                                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                                                  placeholder="Informações adicionais que possam ajudar na análise do produto..."><?php echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; ?></textarea>
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
                                <label class="form-label">Foto do Produto</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1">Clique para selecionar ou arraste uma imagem do produto</p>
                                    <p class="text-muted small">Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                                    <input type="file" class="d-none" id="imagem" name="imagem" accept="image/*" required>
                                </div>
                                <img id="preview" class="preview-imagem d-none" alt="Preview da imagem">
                                <div class="form-text">A imagem deve mostrar claramente o produto proposto.</div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem');
            const preview = document.getElementById('preview');
            const precoInput = document.getElementById('preco_sugerido');
            
            // Formatação do preço
            precoInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                e.target.value = value;
            });
            
            // Upload de imagem
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Verificar tamanho do arquivo (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Criar preview da imagem
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
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                uploadArea.classList.add('dragover');
            }
            
            function unhighlight() {
                uploadArea.classList.remove('dragover');
            }
            
            uploadArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            }
        });
    </script>
</body>
</html>