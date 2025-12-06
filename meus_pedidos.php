<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

try {
    $conn = getDBConnection();
    $usuario_id = intval($_SESSION['user_id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_entrega'])) {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header("Location: meus_pedidos.php");
            exit();
        }

        $pedido_id = intval($_POST['pedido_id']);
        $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$pedido_id, $usuario_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $updateStmt = $conn->prepare("UPDATE pedidos SET status = 'entregue' WHERE id = ?");
            $updateStmt->execute([$pedido_id]);

            logSecurity($usuario_id, 'pedido_confirmado', "Pedido #{$pedido_id} confirmado como entregue");

            $_SESSION['success'] = "Entrega confirmada com sucesso!";
            header("Location: meus_pedidos.php");
            exit();
        } else {
            $_SESSION['error'] = "Pedido não encontrado ou você não tem permissão para esta ação.";
        }
    }

    $stmt = $conn->prepare("
        SELECT p.*, pr.nome as produto_nome, pr.imagem_url, pr.unidade_medida
        FROM pedidos p 
        INNER JOIN produtos pr ON p.produto_id = pr.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.data_pedido DESC
    ");
    $stmt->execute([$usuario_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erro ao carregar pedidos: " . htmlspecialchars($e->getMessage());
    logSecurity($usuario_id ?? null, 'erro_pedidos', "Erro: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }

        .card-pedido {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .card-pedido:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .btn-confirmar:hover {
            transform: scale(1.05);
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
        }

        .empty-state {
            padding: 3rem 1rem;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--verde-principal), var(--verde-secundario));
            color: white;
            font-weight: 600;
        }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
        }

        .badge-pendente {
            background-color: #ffc107;
            color: #000;
        }

        .badge-confirmado {
            background-color: #17a2b8;
            color: #fff;
        }

        .badge-preparando {
            background-color: #fd7e14;
            color: #fff;
        }

        .badge-enviado {
            background-color: #0dcaf0;
            color: #000;
        }

        .badge-entregue {
            background-color: var(--verde-principal);
            color: #fff;
        }

        .badge-cancelado {
            background-color: #dc3545;
            color: #fff;
        }

        .unidade-info {
            font-size: 0.85rem;
            color: #666;
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

        /* Botão de acessibilidade */
       
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
        <h2 class="mb-4 text-success">
            <i class="bi bi-bag-check"></i> Meus Pedidos
        </h2>

        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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
                <?php foreach ($pedidos as $pedido):
                    $imagemSrc = !empty($pedido['imagem_url']) ?
                        'uploads/produtos/' . htmlspecialchars($pedido['imagem_url']) :
                        'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';

                    $unidade_texto = htmlspecialchars($pedido['unidade_medida'] ?? 'UN');
                    $quantidade_texto = $pedido['quantidade'] . ' ' . $unidade_texto;
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="card card-pedido h-100">
                            <img src="<?php echo htmlspecialchars($imagemSrc); ?>" class="product-image"
                                alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>"
                                onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">

                            <div class="card-header card-header-custom">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($pedido['produto_nome']); ?></h5>
                            </div>

                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge status-badge badge-<?php echo htmlspecialchars($pedido['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($pedido['status'])); ?>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Quantidade:</span>
                                    <strong><?php echo $quantidade_texto; ?></strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Preço unitário:</span>
                                    <strong>R$ <?php echo number_format($pedido['preco_unitario'], 2, ',', '.'); ?>
                                        /<?php echo $unidade_texto; ?></strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total:</span>
                                    <strong class="text-success">R$
                                        <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Data:</span>
                                    <small><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></small>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1"><strong>Endereço de entrega:</strong></small>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($pedido['endereco_entrega']); ?></small>
                                </div>

                                <?php if ($pedido['status'] != 'entregue' && $pedido['status'] != 'cancelado'): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="pedido_id" value="<?php echo intval($pedido['id']); ?>">
                                        <button type="submit" name="confirmar_entrega" class="btn btn-confirmar w-100">
                                            <i class="fas fa-check-circle me-2"></i>Confirmar Entrega
                                        </button>
                                    </form>
                                <?php elseif ($pedido['status'] == 'entregue'): ?>
                                    <div class="alert alert-success text-center py-2 mt-2">
                                        <i class="fas fa-check me-2"></i>Entregue em
                                        <?php echo date('d/m/Y', strtotime($pedido['data_atualizacao'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($pedido['observacoes'])): ?>
                                    <div class="alert alert-info mt-2 p-2 small">
                                        <strong>Observações:</strong> <?php echo htmlspecialchars($pedido['observacoes']); ?>
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
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>

</html>