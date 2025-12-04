<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
$nome = $descricao = $preco = $quantidade = $tipo = '';
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
        die('Token CSRF inválido.');
    }
    
    $nome = sanitizeInput($_POST['nome']);
    $descricao = sanitizeInput($_POST['descricao']);
    $preco = sanitizeInput($_POST['preco']);
    $quantidade = sanitizeInput($_POST['quantidade']);
    $tipo = sanitizeInput($_POST['tipo']);
    
    // Validações
    if (empty($nome)) $errors[] = "Nome é obrigatório.";
    if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
    if (!is_numeric($preco) || $preco <= 0) $errors[] = "Preço deve ser um número positivo.";
    if (!is_numeric($quantidade) || $quantidade < 0) $errors[] = "Quantidade deve ser um número não negativo.";
    if (empty($tipo)) $errors[] = "Tipo do produto é obrigatório.";
    
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
            
            // Gerar nome único para o arquivo
            $fileExtension = pathinfo($imagem['name'], PATHINFO_EXTENSION);
            $imagemNome = uniqid('produto_') . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $imagemNome;
            
            // Mover arquivo para o diretório de uploads
            if (!move_uploaded_file($imagem['tmp_name'], $uploadPath)) {
                $errors[] = "Erro ao salvar a imagem. Tente novamente.";
            }
        }
    } else {
        $errors[] = "Imagem do produto é obrigatória.";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, imagem_url, tipo, disponivel) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $imagemNome, $tipo]);
            
            header("Location: telaadministrativa.php?success=1");
            exit();
        } catch(PDOException $e) {
            // Se houve erro, remove a imagem que foi salva
            if (!empty($imagemNome) && file_exists($uploadDir . $imagemNome)) {
                unlink($uploadDir . $imagemNome);
            }
            $errors[] = "Erro ao adicionar produto: " . $e->getMessage();
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
    <link href="css/style.css" rel="stylesheet">
    <style>
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
            border-color: #198754;
            background-color: #e8f5e8;
        }
        .upload-area.dragover {
            border-color: #198754;
            background-color: #d1e7dd;
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
                <h2>Adicionar Novo Produto</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Produto</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($descricao); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="preco" class="form-label">Preço (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="preco" name="preco" value="<?php echo htmlspecialchars($preco); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="quantidade" class="form-label">Quantidade em Estoque</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?php echo htmlspecialchars($quantidade); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo do Produto</label>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Imagem do Produto</label>
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                            <p class="mt-2 mb-1">Clique para selecionar ou arraste uma imagem</p>
                            <p class="text-muted small">Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                            <input type="file" class="d-none" id="imagem" name="imagem" accept="image/*" required>
                        </div>
                        <img id="preview" class="preview-imagem" alt="Preview da imagem">
                        <div class="form-text">A imagem será redimensionada automaticamente se necessário.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Adicionar Produto</button>
                    <a href="telaadministrativa.php" class="btn btn-secondary">Cancelar</a>
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
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
</body>
</html>