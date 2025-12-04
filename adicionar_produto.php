<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
$nome = $descricao = $preco = $quantidade = $tipo = $unidade_medida = '';
$errors = [];

// Configurações para upload de imagem
$uploadDir = 'uploads/produtos/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Criar diretório de uploads se não existir
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Token CSRF inválido.';
        header("Location: adicionar_produto.php");
        exit();
    }
    
    $nome = sanitizeInput($_POST['nome']);
    $descricao = sanitizeInput($_POST['descricao']);
    $preco = sanitizeInput($_POST['preco']);
    $quantidade = sanitizeInput($_POST['quantidade']);
    $tipo = sanitizeInput($_POST['tipo']);
    $unidade_medida = sanitizeInput($_POST['unidade_medida']);
    
    // Validações
    if (empty($nome) || strlen($nome) > 255) $errors[] = "Nome é obrigatório e deve ter no máximo 255 caracteres.";
    if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
    if (!is_numeric($preco) || $preco <= 0) $errors[] = "Preço deve ser um número positivo.";
    if (!is_numeric($quantidade) || $quantidade < 0) $errors[] = "Quantidade deve ser um número não negativo.";
    if (empty($tipo)) $errors[] = "Tipo do produto é obrigatório.";
    if (!in_array($unidade_medida, ['KG', 'UN', 'Dúzia', 'Penca', 'Outros'])) {
        $errors[] = "Unidade de medida inválida.";
    }
    
    // Processar upload da imagem
    $imagemNome = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagem = $_FILES['imagem'];
        
        // Verificar se há erro no upload
        if ($imagem['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erro no upload da imagem: " . getUploadError($imagem['error']);
        } else {
            // Verificar tipo do arquivo usando extensão e MIME type
            $fileExtension = strtolower(pathinfo($imagem['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.";
            }
            
            // Verificar MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagem['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "Tipo MIME do arquivo não é permitido.";
            }
            
            // Verificar tamanho do arquivo
            if ($imagem['size'] > $maxFileSize) {
                $errors[] = "Arquivo muito grande. Tamanho máximo permitido: 5MB.";
            }
            
            // Gerar nome único para o arquivo
            $imagemNome = uniqid('produto_', true) . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $imagemNome;
            
            // Validar se é realmente uma imagem
            $imageInfo = getimagesize($imagem['tmp_name']);
            if (!$imageInfo) {
                $errors[] = "O arquivo enviado não é uma imagem válida.";
            }
            
            // Mover arquivo para o diretório de uploads
            if (empty($errors) && !move_uploaded_file($imagem['tmp_name'], $uploadPath)) {
                $errors[] = "Erro ao salvar a imagem. Tente novamente.";
            }
        }
    } else {
        $errors[] = "Imagem do produto é obrigatória.";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, imagem_url, tipo, unidade_medida, disponivel) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $imagemNome, $tipo, $unidade_medida]);
            
            // Log da ação
            logSecurity($_SESSION['user_id'], 'produto_adicionado', "Produto '{$nome}' adicionado");
            
            $_SESSION['success'] = "Produto adicionado com sucesso!";
            header("Location: telaadministrativa.php");
            exit();
        } catch(PDOException $e) {
            // Se houve erro, remove a imagem que foi salva
            if (!empty($imagemNome) && file_exists($uploadDir . $imagemNome)) {
                unlink($uploadDir . $imagemNome);
            }
            $errors[] = "Erro ao adicionar produto: " . htmlspecialchars($e->getMessage());
            logSecurity($_SESSION['user_id'], 'erro_produto', "Erro ao adicionar produto: " . $e->getMessage());
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
    <title>Adicionar Produto - Agricultura Familiar</title>
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
            display: none;
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
    </style>
</head>
<body>
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="text-success mb-4">
                    <i class="bi bi-plus-circle"></i> Adicionar Novo Produto
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
                                       required
                                       maxlength="255">
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
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Imagem do Produto</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Foto do Produto *</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1">Clique para selecionar ou arraste uma imagem</p>
                                    <p class="text-muted small">Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                                    <input type="file" class="d-none" id="imagem" name="imagem" accept="image/*" required>
                                </div>
                                <img id="preview" class="preview-imagem mt-3" alt="Preview da imagem">
                                <div class="form-text mt-2">A imagem será redimensionada automaticamente se necessário.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="telaadministrativa.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Adicionar Produto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem');
            const preview = document.getElementById('preview');
            const form = document.getElementById('formProduto');
            
            // Clique na área de upload
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Alteração no input de arquivo
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Verificar tamanho do arquivo (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Verificar extensão
                    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    
                    if (!allowedExtensions.includes(fileExtension)) {
                        alert('Tipo de arquivo não permitido. Use apenas JPG, PNG, GIF ou WebP.');
                        this.value = '';
                        return;
                    }
                    
                    // Criar preview da imagem
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        uploadArea.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
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
                    const file = files[0];
                    
                    // Verificar se é imagem
                    if (!file.type.match('image.*')) {
                        alert('Por favor, selecione apenas arquivos de imagem.');
                        return;
                    }
                    
                    // Verificar tamanho
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
                        return;
                    }
                    
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            }
            
            // Remover preview e mostrar área de upload novamente
            preview.addEventListener('click', function() {
                fileInput.value = '';
                preview.style.display = 'none';
                uploadArea.style.display = 'block';
            });
            
            // Validação do formulário
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