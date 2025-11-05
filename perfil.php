<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];
$stats = [];
$pedidos_recentes = [];
$produtos_pendentes = [];

try {
    $conn = getDBConnection();
    
    // Busca dados do usuário
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header("Location: login.php");
        exit();
    }
    
    // Busca estatísticas do usuário
    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(*) as total_pedidos,
            SUM(total) as total_gasto,
            AVG(total) as media_pedido,
            COUNT(CASE WHEN status = 'entregue' THEN 1 END) as pedidos_entregues,
            COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pedidos_pendentes
        FROM pedidos 
        WHERE usuario_id = ?
    ");
    $stmtStats->execute([$usuario_id]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Busca pedidos recentes
    $stmtPedidos = $conn->prepare("
        SELECT p.*, pr.nome as produto_nome, pr.imagem_url 
        FROM pedidos p 
        INNER JOIN produtos pr ON p.produto_id = pr.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.data_pedido DESC 
        LIMIT 5
    ");
    $stmtPedidos->execute([$usuario_id]);
    $pedidos_recentes = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
    
    // Se for produtor, busca produtos pendentes de aprovação
    if ($usuario['tipo_usuario'] === 'Produtor') {
        $stmtProdutosPendentes = $conn->prepare("
            SELECT COUNT(*) as total_pendentes 
            FROM produtos_propostos 
            WHERE produtor_id = ? AND status = 'pendente'
        ");
        $stmtProdutosPendentes->execute([$usuario_id]);
        $produtos_pendentes = $stmtProdutosPendentes->fetch(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --bege: #f5f5dc;
        }
        
        .profile-hero {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .user-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
            border: 4px solid white;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--verde-principal);
        }
        
        .btn-profile {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
        }
        
        .btn-proposals {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8e53 100%);
            color: white;
            border: none;
        }
        
        .btn-orders {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
            color: white;
            border: none;
        }
        
        .btn-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .order-card {
            border-left: 4px solid var(--verde-principal);
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            border-left-color: var(--verde-secundario);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-pendente { background-color: #fff3cd; color: #856404; }
        .badge-confirmado { background-color: #d1ecf1; color: #0c5460; }
        .badge-preparando { background-color: #d4edda; color: #155724; }
        .badge-enviado { background-color: #cce7ff; color: #004085; }
        .badge-entregue { background-color: #d1edff; color: #0c5460; }
        .badge-cancelado { background-color: #f8d7da; color: #721c24; }
        
        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, var(--bege) 100%);
            border: none;
            border-radius: 15px;
        }
        
        .section-title {
            color: var(--verde-principal);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .quick-actions {
            background: linear-gradient(135deg, var(--verde-claro) 0%, #e8f5e8 100%);
            border-radius: 15px;
            padding: 25px;
        }
        
        .producer-badge {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8e53 100%);
            color: white;
        }
        
        .trader-badge {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>

    <!-- Hero Section -->
    <section class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 text-center text-md-start">
                    <h1 class="display-5 fw-bold mb-3">Olá, <?php echo htmlspecialchars(explode(' ', $usuario['nome'])[0]); ?>!</h1>
                    <p class="lead mb-4">Bem-vindo(a) ao seu painel na CoopAgro</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge <?php echo $usuario['tipo_usuario'] === 'Produtor' ? 'producer-badge' : 'trader-badge'; ?> fs-6">
                            <i class="bi bi-person-badge"></i> 
                            <?php echo htmlspecialchars($usuario['tipo_usuario']); ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="bi bi-calendar-check"></i> 
                            Membro desde <?php echo date('m/Y', strtotime($usuario['data_cadastro'])); ?>
                        </span>
                        <?php if ($usuario['tipo_usuario'] === 'Produtor' && isset($produtos_pendentes['total_pendentes'])): ?>
                            <span class="badge bg-warning text-dark fs-6">
                                <i class="bi bi-clock-history"></i> 
                                <?php echo $produtos_pendentes['total_pendentes']; ?> produtos aguardando análise
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Estatísticas Rápidas -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-cart-check stat-icon text-primary"></i>
                    <div class="stat-number"><?php echo $stats['total_pedidos'] ?? 0; ?></div>
                    <p class="text-muted mb-0">Total de Pedidos</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-currency-dollar stat-icon text-success"></i>
                    <div class="stat-number">R$ <?php echo number_format($stats['total_gasto'] ?? 0, 2, ',', '.'); ?></div>
                    <p class="text-muted mb-0">Total Gasto</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-truck stat-icon text-warning"></i>
                    <div class="stat-number"><?php echo $stats['pedidos_entregues'] ?? 0; ?></div>
                    <p class="text-muted mb-0">Pedidos Entregues</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-clock-history stat-icon text-info"></i>
                    <div class="stat-number"><?php echo $stats['pedidos_pendentes'] ?? 0; ?></div>
                    <p class="text-muted mb-0">Pedidos Pendentes</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Ações Rápidas -->
            <div class="col-lg-4 mb-4">
                <div class="quick-actions h-100">
                    <h3 class="section-title">Ações Rápidas</h3>
                    
                    <div class="d-grid gap-3">
                        <a href="editar_perfil.php" class="btn btn-edit btn-profile">
                            <i class="bi bi-pencil-square"></i> Editar Perfil
                        </a>
                        
                        <a href="meus_pedidos.php" class="btn btn-orders btn-profile">
                            <i class="bi bi-list-check"></i> Ver Todos os Pedidos
                        </a>
                        
                        <a href="produtos.php" class="btn btn-success btn-profile">
                            <i class="bi bi-cart-plus"></i> Fazer Nova Encomenda
                        </a>
                        
                        <?php if ($usuario['tipo_usuario'] === 'Produtor'): ?>
                            <!-- Ações específicas para produtores -->
                            <a href="propor_produto.php" class="btn btn-proposals btn-profile">
                                <i class="bi bi-plus-circle"></i> Propor Novo Produto
                            </a>
                            
                            <a href="minhas_propostas.php" class="btn btn-outline-warning btn-profile">
                                <i class="bi bi-clock-history"></i> Minhas Propostas
                                <?php if (isset($produtos_pendentes['total_pendentes']) && $produtos_pendentes['total_pendentes'] > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $produtos_pendentes['total_pendentes']; ?></span>
                                <?php endif; ?>
                            </a>
                            
                            <a href="produtos_aprovados.php" class="btn btn-outline-success btn-profile">
                                <i class="bi bi-check-circle"></i> Produtos Aprovados
                            </a>
                        <?php else: ?>
                            <!-- Ações específicas para comerciantes -->
                            <a href="historico_compras.php" class="btn btn-outline-info btn-profile">
                                <i class="bi bi-graph-up"></i> Histórico de Compras
                            </a>
                            
                            <a href="favoritos.php" class="btn btn-outline-primary btn-profile">
                                <i class="bi bi-heart"></i> Produtos Favoritos
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <h5 class="fw-bold mb-3">Suporte</h5>
                        <div class="d-grid gap-2">
                            <a href="contato.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-headset"></i> Central de Ajuda
                            </a>
                            <a href="politica.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-shield-check"></i> Política de Privacidade
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do Perfil -->
            <div class="col-lg-4 mb-4">
                <div class="card info-card h-100">
                    <div class="card-body">
                        <h3 class="section-title">Informações Pessoais</h3>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-person me-2"></i>Nome Completo:</strong>
                            <p class="mb-2"><?php echo htmlspecialchars($usuario['nome']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-envelope me-2"></i>Email:</strong>
                            <p class="mb-2"><?php echo htmlspecialchars($usuario['email']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-telephone me-2"></i>Celular:</strong>
                            <p class="mb-2"><?php echo htmlspecialchars($usuario['celular']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-file-person me-2"></i>CPF/CNPJ:</strong>
                            <p class="mb-2"><?php echo htmlspecialchars($usuario['cpf_cnpj']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-tag me-2"></i>Tipo de Usuário:</strong>
                            <p class="mb-2">
                                <span class="badge <?php echo $usuario['tipo_usuario'] === 'Produtor' ? 'producer-badge' : 'trader-badge'; ?>">
                                    <?php echo htmlspecialchars($usuario['tipo_usuario']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="col-lg-4 mb-4">
                <div class="card info-card h-100">
                    <div class="card-body">
                        <h3 class="section-title">Endereço de Entrega</h3>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-geo-alt me-2"></i>Endereço:</strong>
                            <p class="mb-2">
                                <?php echo htmlspecialchars($usuario['rua']); ?>, 
                                <?php echo htmlspecialchars($usuario['numero']); ?>
                                <?php echo !empty($usuario['bairro']) ? ' - ' . htmlspecialchars($usuario['bairro']) : ''; ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-building me-2"></i>Cidade/Estado:</strong>
                            <p class="mb-2">
                                <?php echo htmlspecialchars($usuario['municipio']); ?> - 
                                <?php echo htmlspecialchars($usuario['estado']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="bi bi-postcard me-2"></i>CEP:</strong>
                            <p class="mb-2"><?php echo htmlspecialchars($usuario['cep']); ?></p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="editar_perfil.php" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-pencil"></i> Atualizar Endereço
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pedidos Recentes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="section-title mb-0">
                            <i class="bi bi-clock-history"></i> Pedidos Recentes
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pedidos_recentes)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-cart-x display-1 text-muted"></i>
                                <h4 class="text-muted mt-3">Nenhum pedido realizado</h4>
                                <p class="text-muted">Faça seu primeiro pedido e acompanhe o status aqui.</p>
                                <a href="produtos.php" class="btn btn-success">Fazer Primeiro Pedido</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Data</th>
                                            <th>Quantidade</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedidos_recentes as $pedido): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php 
                                                        $imagemSrc = !empty($pedido['imagem_url']) ? 
                                                            'uploads/produtos/' . htmlspecialchars($pedido['imagem_url']) : 
                                                            'https://via.placeholder.com/50x50/CCCCCC/969696?text=Produto';
                                                        ?>
                                                        <img src="<?php echo $imagemSrc; ?>" 
                                                             alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>"
                                                             class="rounded me-3" 
                                                             width="50" 
                                                             height="50"
                                                             onerror="this.src='https://via.placeholder.com/50x50/CCCCCC/969696?text=Produto'">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($pedido['produto_nome']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                                <td><?php echo $pedido['quantidade']; ?> un.</td>
                                                <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="status-badge badge-<?php echo $pedido['status']; ?>">
                                                        <?php echo ucfirst($pedido['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="detalhes_pedido.php?id=<?php echo $pedido['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-eye"></i> Detalhes
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="meus_pedidos.php" class="btn btn-outline-success">
                                    <i class="bi bi-list-ul"></i> Ver Todos os Pedidos
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação para os cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>