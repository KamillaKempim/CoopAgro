<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

try {
    $conn = getDBConnection();
    $usuario_id = $_SESSION['user_id'];
    
    // Busca os pedidos do usuário
    $stmt = $conn->prepare("
        SELECT p.*, pr.nome as produto_nome, pr.imagem_url 
        FROM pedidos p 
        INNER JOIN produtos pr ON p.produto_id = pr.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.data_pedido DESC
    ");
    $stmt->execute([$usuario_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar pedidos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <h2>Meus Pedidos</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (empty($pedidos)): ?>
            <div class="alert alert-info text-center">
                <h4>Nenhum pedido encontrado</h4>
                <p>Você ainda não realizou nenhum pedido.</p>
                <a href="produtos.php" class="btn btn-success">Fazer Primeira Encomenda</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($pedido['produto_nome']); ?></h5>
                                <p class="card-text">
                                    <strong>Quantidade:</strong> <?php echo $pedido['quantidade']; ?><br>
                                    <strong>Total:</strong> R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?><br>
                                    <strong>Status:</strong> 
                                    <span class="badge 
                                        <?php echo $pedido['status'] == 'entregue' ? 'bg-success' : 
                                              ($pedido['status'] == 'cancelado' ? 'bg-danger' : 'bg-warning'); ?>">
                                        <?php echo ucfirst($pedido['status']); ?>
                                    </span><br>
                                    <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <strong>Endereço de entrega:</strong><br>
                                        <?php echo htmlspecialchars($pedido['endereco_entrega']); ?>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
</body>
</html>