<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID do produto inválido.';
    header("Location: telaadministrativa.php");
    exit();
}

$id = intval($_GET['id']);
$errors = [];
$uploadDir = 'uploads/produtos/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; 

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        $_SESSION['error'] = 'Produto não encontrado.';
        header("Location: telaadministrativa.php");
        exit();
    }
    
    $nome = $produto['nome'];
    $descricao = $produto['descricao'];
    $preco = $produto['preco'];
    $quantidade = $produto['quantidade_estoque'];
    $tipo = $produto['tipo'];
    $unidade_medida = $produto['unidade_medida'];
    $imagemNome = $produto['imagem_url'];
    $disponivel = $produto['disponivel'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header("Location: editar_produto.php?id=" . $id);
            exit();
        }
        
        $nome = sanitizeInput($_POST['nome']);
        $descricao = sanitizeInput($_POST['descricao']);
        $preco = sanitizeInput($_POST['preco']);
        $quantidade = sanitizeInput($_POST['quantidade']);
        $tipo = sanitizeInput($_POST['tipo']);
        $unidade_medida = sanitizeInput($_POST['unidade_medida']);
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;
        
        if (empty($nome) || strlen($nome) > 255) $errors[] = "Nome é obrigatório e deve ter no máximo 255 caracteres.";
        if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
        if (!is_numeric($preco) || $preco <= 0) $errors[] = "Preço deve ser um número positivo.";
        if (!is_numeric($quantidade) || $quantidade < 0) $errors[] = "Quantidade deve ser um número não negativo.";
        if (empty($tipo)) $errors[] = "Tipo do produto é obrigatório.";
        if (!in_array($unidade_medida, ['KG', 'UN', 'Dúzia', 'Penca', 'Outros'])) {
            $errors[] = "Unidade de medida inválida.";
        }
        
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagem = $_FILES['imagem'];
            
            if ($imagem['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erro no upload da imagem: " . getUploadError($imagem['error']);
            } else {
                $fileExtension = strtolower(pathinfo($imagem['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $imagem['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $errors[] = "Tipo MIME do arquivo não é permitido.";
                }
                
                if ($imagem['size'] > $maxFileSize) {
                    $errors[] = "Arquivo muito grande. Tamanho máximo permitido: 5MB.";
                }
                
                if (empty($errors)) {
    
                    $novaImagemNome = uniqid('produto_', true) . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $novaImagemNome;
                    $imageInfo = getimagesize($imagem['tmp_name']);
                    if (!$imageInfo) {
                        $errors[] = "O arquivo enviado não é uma imagem válida.";
                    }
                    
                    if (move_uploaded_file($imagem['tmp_name'], $uploadPath)) {
                        if (!empty($imagemNome) && $imagemNome !== $novaImagemNome && file_exists($uploadDir . $imagemNome)) {
                            unlink($uploadDir . $imagemNome);
                        }
                        $imagemNome = $novaImagemNome;
                    } else {
                        $errors[] = "Erro ao salvar a nova imagem. Tente novamente.";
                    }
                }
            }
        }
        
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, quantidade_estoque = ?, imagem_url = ?, tipo = ?, unidade_medida = ?, disponivel = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $imagemNome, $tipo, $unidade_medida, $disponivel, $id]);
            
            logSecurity($_SESSION['user_id'], 'produto_editado', "Produto #{$id} editado");
            
            $_SESSION['success'] = "Produto atualizado com sucesso!";
            header("Location: telaadministrativa.php");
            exit();
        }
    }
} catch(PDOException $e) {
    $errors[] = "Erro ao carregar produto: " . htmlspecialchars($e->getMessage());
    logSecurity($_SESSION['user_id'], 'erro_edicao_produto', "Erro: " . $e->getMessage());
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
    <title>Editar Produto - Agricultura Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
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
        .imagem-atual {
            max-width: 200px;
            border: 2px solid var(--verde-principal);
        }
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        .btn-success:hover {
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
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

 <?php include_once 'includes/_acessibilidade.php'; ?>
    
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="text-success mb-4">
                    <i class="bi bi-pencil-square"></i> Editar Produto
                </h2>
                
                <?php 
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                    unset($_SESSION['error']);
                }
                ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle"></i> Erros encontrados:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="formProduto">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações Básicas</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($nome); ?>" 
                                       required maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição *</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($descricao); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tipo" class="form-label">Tipo do Produto *</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Selecione o tipo...</option>
                                        <option value="hortaliça" <?php echo $tipo === 'hortaliça' ? 'selected' : ''; ?>>Hortaliça</option>
                                        <option value="legume" <?php echo $tipo === 'legume' ? 'selected' : ''; ?>>Legume</option>
                                        <option value="fruta" <?php echo $tipo === 'fruta' ? 'selected' : ''; ?>>Fruta</option>
                                        <option value="tubérculo" <?php echo $tipo === 'tubérculo' ? 'selected' : ''; ?>>Tubérculo</option>
                                        <option value="vagem" <?php echo $tipo === 'vagem' ? 'selected' : ''; ?>>Vagem</option>
                                        <option value="outros" <?php echo $tipo === 'outros' ? 'selected' : ''; ?>>Outros</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="unidade_medida" class="form-label">Unidade de Medida *</label>
                                    <select class="form-select" id="unidade_medida" name="unidade_medida" required>
                                        <option value="">Selecione...</option>
                                        <option value="KG" <?php echo $unidade_medida === 'KG' ? 'selected' : ''; ?>>Quilograma (KG)</option>
                                        <option value="UN" <?php echo $unidade_medida === 'UN' ? 'selected' : ''; ?>>Unidade (UN)</option>
                                        <option value="Dúzia" <?php echo $unidade_medida === 'Dúzia' ? 'selected' : ''; ?>>Dúzia</option>
                                        <option value="Penca" <?php echo $unidade_medida === 'Penca' ? 'selected' : ''; ?>>Penca</option>
                                        <option value="Outros" <?php echo $unidade_medida === 'Outros' ? 'selected' : ''; ?>>Outros</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="preco" class="form-label">Preço (R$) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control" id="preco" name="preco" 
                                               value="<?php echo htmlspecialchars($preco); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="quantidade" class="form-label">Quantidade em Estoque *</label>
                                    <input type="number" min="0" class="form-control" id="quantidade" name="quantidade" 
                                           value="<?php echo htmlspecialchars($quantidade); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="disponivel" name="disponivel" <?php echo ($disponivel ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="disponivel">Disponível para venda</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Imagem do Produto</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <?php if (!empty($imagemNome)): ?>
                                    <div class="mb-3">
                                        <p class="fw-bold">Imagem atual:</p>
                                        <img src="<?php echo htmlspecialchars('uploads/produtos/' . $imagemNome); ?>" 
                                             alt="Imagem atual do produto" 
                                             class="imagem-atual img-thumbnail"
                                             onerror="this.src='https://via.placeholder.com/200x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                                        <div class="form-text text-success mt-2">Imagem atual do produto</div>
                                    </div>
                                <?php endif; ?>
                                
                                <label class="form-label">Nova imagem (opcional):</label>
                                <div class="upload-area mb-2" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1">Clique para selecionar ou arraste uma nova imagem</p>
                                    <p class="text-muted small">Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                                    <input type="file" class="d-none" id="imagem" name="imagem" accept="image/*">
                                </div>
                                <img id="preview" class="preview-imagem d-none mt-3" alt="Preview da nova imagem">
                                <div class="form-text mt-2">Deixe em branco para manter a imagem atual. A nova imagem substituirá a atual.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="telaadministrativa.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem');
            const preview = document.getElementById('preview');
            const form = document.getElementById('formProduto');
            
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
            
            preview.addEventListener('click', function() {
                fileInput.value = '';
                preview.classList.add('d-none');
                uploadArea.style.display = 'block';
            });
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                const preco = document.getElementById('preco').value;
                const quantidade = document.getElementById('quantidade').value;
                
                if (parseFloat(preco) <= 0) {
                    alert('O preço deve ser maior que zero.');
                    valid = false;
                }
                
                if (parseInt(quantidade) < 0) {
                    alert('A quantidade não pode ser negativa.');
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>