<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

try {
    $conn = getDBConnection();
    $usuario_id = $_SESSION['user_id'];
    
    // Processar confirmação de entrega
    if (isset($_POST['confirmar_entrega'])) {
        $pedido_id = $_POST['pedido_id'];
        
        // Verificar se o pedido pertence ao usuário atual
        $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$pedido_id, $usuario_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            // Atualizar status para "entregue"
            $updateStmt = $conn->prepare("UPDATE pedidos SET status = 'entregue' WHERE id = ?");
            $updateStmt->execute([$pedido_id]);
            
            // Recarregar os pedidos após atualização
            header("Location: meus_pedidos.php");
            exit();
        }
    }
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-pedido {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .card-pedido:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.5em 1em;
        }
        .product-image {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }
        .btn-confirmar {
            transition: all 0.3s;
        }
        .btn-confirmar:hover {
            transform: scale(1.05);
        }
        .empty-state {
            padding: 3rem 1rem;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            font-weight: 600;
        }
        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }
            .btn-confirmar {
                width: 100%;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Meus Pedidos</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (empty($pedidos)): ?>
            <div class="alert alert-info text-center empty-state">
                <div class="mb-3">
                    <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                </div>
                <h4>Nenhum pedido encontrado</h4>
                <p class="mb-3">Você ainda não realizou nenhum pedido.</p>
                <a href="produtos.php" class="btn btn-success">Fazer Primeira Encomenda</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="card card-pedido h-100">
                            <?php if (!empty($pedido['imagem_url'])): ?>
                               <img src="<?php echo 'uploads/produtos/' . htmlspecialchars($pedido['imagem_url']); ?>" 
                                     class="product-image" 
                                     alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>">
                            <?php else: ?>
                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-box-open fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-header card-header-custom">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($pedido['produto_nome']); ?></h5>
                            </div>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge status-badge 
                                        <?php echo $pedido['status'] == 'entregue' ? 'bg-success' : 
                                              ($pedido['status'] == 'cancelado' ? 'bg-danger' : 'bg-warning'); ?>">
                                        <?php echo ucfirst($pedido['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Quantidade:</span>
                                    <strong><?php echo $pedido['quantidade']; ?></strong>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total:</span>
                                    <strong>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Data:</span>
                                    <small><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1"><strong>Endereço de entrega:</strong></small>
                                    <small class="text-muted"><?php echo htmlspecialchars($pedido['endereco_entrega']); ?></small>
                                </div>
                                
                                <?php if ($pedido['status'] != 'entregue' && $pedido['status'] != 'cancelado'): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                        <button type="submit" name="confirmar_entrega" class="btn btn-success btn-confirmar w-100">
                                            <i class="fas fa-check-circle me-2"></i>Confirmar Entrega
                                        </button>
                                    </form>
                                <?php elseif ($pedido['status'] == 'entregue'): ?>
                                    <div class="alert alert-success text-center py-2 mt-2">
                                        <i class="fas fa-check me-2"></i>Entregue
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>