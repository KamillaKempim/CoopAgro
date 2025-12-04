<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Verifica se o usuário é produtor
    $stmtUser = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Produtor') {
        header("Location: perfil.php");
        exit();
    }
    
    // Busca produtos do produtor
    $stmtProdutos = $conn->prepare("SELECT * FROM produtos ORDER BY data_criacao DESC");
    $stmtProdutos->execute();
    $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar produtos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Produtos - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- VLibras -->
  <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper></div>
  </div>

    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Meus Produtos</h2>
            <a href="adicionar_produto.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Adicionar Produto
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (empty($produtos)): ?>
            <div class="alert alert-info text-center">
                <h4>Nenhum produto cadastrado</h4>
                <p>Comece cadastrando seu primeiro produto.</p>
                <a href="adicionar_produto.php" class="btn btn-success">Cadastrar Primeiro Produto</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($produtos as $produto): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php 
                            $imagemSrc = !empty($produto['imagem_url']) ? 
                                'uploads/produtos/' . htmlspecialchars($produto['imagem_url']) : 
                                'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                            ?>
                            <img src="<?php echo $imagemSrc; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                <p class="card-text">
                                    <strong>Preço:</strong> R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?><br>
                                    <strong>Estoque:</strong> <?php echo $produto['quantidade_estoque']; ?> unidades<br>
                                    <strong>Tipo:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($produto['tipo']); ?></span>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Cadastrado em: <?php echo date('d/m/Y', strtotime($produto['data_criacao'])); ?>
                                    </small>
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="editar_produto.php?id=<?php echo $produto['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="excluir_produto.php?id=<?php echo $produto['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
</body>
</html>