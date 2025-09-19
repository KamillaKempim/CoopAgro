<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
$nome = $descricao = $preco = $quantidade = $imagem = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido.');
    }
    
    $nome = sanitizeInput($_POST['nome']);
    $descricao = sanitizeInput($_POST['descricao']);
    $preco = sanitizeInput($_POST['preco']);
    $quantidade = sanitizeInput($_POST['quantidade']);
    $imagem = sanitizeInput($_POST['imagem_url']);
    
    // Validações
    if (empty($nome)) $errors[] = "Nome é obrigatório.";
    if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
    if (!is_numeric($preco) || $preco <= 0) $errors[] = "Preço deve ser um número positivo.";
    if (!is_numeric($quantidade) || $quantidade < 0) $errors[] = "Quantidade deve ser um número não negativo.";
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, imagem_url, disponivel) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $imagem]);
            
            header("Location: telaadministrativa.php?success=1");
            exit();
        } catch(PDOException $e) {
            $errors[] = "Erro ao adicionar produto: " . $e->getMessage();
        }
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
</head>
<body>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2>Adicionar Novo Produto</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
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
                        <label for="imagem_url" class="form-label">URL da Imagem</label>
                        <input type="url" class="form-control" id="imagem_url" name="imagem_url" value="<?php echo htmlspecialchars($imagem); ?>" placeholder="https://exemplo.com/imagem.jpg">
                    </div>
                    
                    <button type="submit" class="btn btn-success">Adicionar Produto</button>
                    <a href="telaadministrativa.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
</body>
</html>