<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
// Verificar se o usuário é administrador
$usuario_id = $_SESSION['user_id'];

// Verifica se o usuário é produtor
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Administrador') {
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    die("Erro ao verificar tipo de usuário: " . $e->getMessage());
}

try {
    $conn = getDBConnection();
    
    // Processar atualização de status
    if (isset($_POST['atualizar_status'])) {
        $pedido_id = $_POST['pedido_id'];
        $novo_status = $_POST['novo_status'];
        
        // Atualizar status do pedido
        $updateStmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        $updateStmt->execute([$novo_status, $pedido_id]);
        
        // Recarregar a página após atualização
        header("Location: admin_pedidos.php");
        exit();
    }
    
    // Buscar todos os pedidos com informações do usuário
    $stmt = $conn->prepare("
        SELECT 
            p.*, 
            pr.nome as produto_nome, 
            pr.imagem_url,
            u.nome as usuario_nome,
            u.email as usuario_email
        FROM pedidos p 
        INNER JOIN produtos pr ON p.produto_id = pr.id 
        INNER JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.data_pedido DESC
    ");
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas para o dashboard - CORREÇÃO AQUI: mudado "preparandos" para "preparando"
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
            SUM(CASE WHEN status = 'preparando' THEN 1 ELSE 0 END) as preparando,  -- CORRIGIDO
            SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
            SUM(CASE WHEN status = 'entregue' THEN 1 ELSE 0 END) as entregues,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
            SUM(total) as valor_total
        FROM pedidos
    ");
    $statsStmt->execute();
    $estatisticas = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar pedidos: " . $e->getMessage();
}

// Opções de status disponíveis
$status_opcoes = [
    'pendente' => 'Pendente',
    'confirmado' => 'Confirmado', 
    'preparando' => 'Preparando',
    'enviado' => 'Enviado',
    'entregue' => 'Entregue',
    'cancelado' => 'Cancelado'
];

// Garantir que todas as chaves do array de estatísticas existam para evitar outros warnings
$estatisticas_defaults = [
    'total_pedidos' => 0,
    'pendentes' => 0,
    'confirmados' => 0,
    'preparando' => 0,
    'enviados' => 0,
    'entregues' => 0,
    'cancelados' => 0,
    'valor_total' => 0
];

// Mesclar com valores padrão para evitar undefined array key
if (isset($estatisticas)) {
    $estatisticas = array_merge($estatisticas_defaults, $estatisticas);
} else {
    $estatisticas = $estatisticas_defaults;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pedidos - Admin - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-dashboard {
            transition: transform 0.3s;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-status {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .filter-active {
            background-color: #0d6efd !important;
            color: white !important;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .stats-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .badge-pendente { background-color: #ffc107; color: #000; }
        .badge-confirmado { background-color: #17a2b8; color: #fff; }
        .badge-preparando { background-color: #fd7e14; color: #fff; }
        .badge-enviado { background-color: #0dcaf0; color: #000; }
        .badge-entregue { background-color: #198754; color: #fff; }
        .badge-cancelado { background-color: #dc3545; color: #fff; }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }
            .btn-status {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shopping-bag me-2"></i>Gestão de Pedidos</h2>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="exportarRelatorio()">
                            <i class="fas fa-download me-1"></i>Exportar
                        </button>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Dashboard de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart stats-icon mb-2"></i>
                                <h5><?php echo $estatisticas['total_pedidos']; ?></h5>
                                <small>Total de Pedidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-clock stats-icon mb-2"></i>
                                <h5><?php echo $estatisticas['pendentes']; ?></h5>
                                <small>Pendentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-cog stats-icon mb-2"></i>
                                <h5><?php echo $estatisticas['preparando']; ?></h5>
                                <small>Preparando</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle stats-icon mb-2"></i>
                                <h5><?php echo $estatisticas['entregues']; ?></h5>
                                <small>Entregues</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-danger text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-times-circle stats-icon mb-2"></i>
                                <h5><?php echo $estatisticas['cancelados']; ?></h5>
                                <small>Cancelados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                        <div class="card card-dashboard bg-dark text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign stats-icon mb-2"></i>
                                <h5>R$ <?php echo number_format($estatisticas['valor_total'], 2, ',', '.'); ?></h5>
                                <small>Valor Total</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filtros</h5>
                        <div class="btn-group flex-wrap" role="group">
                            <button type="button" class="btn btn-outline-primary filter-btn active" data-status="todos">
                                Todos (<?php echo $estatisticas['total_pedidos']; ?>)
                            </button>
                            <?php foreach ($status_opcoes as $key => $label): 
                                // Usar array_key_exists para evitar undefined index
                                $count = array_key_exists($key . 's', $estatisticas) ? $estatisticas[$key . 's'] : 0;
                            ?>
                                <button type="button" class="btn btn-outline-primary filter-btn" data-status="<?php echo $key; ?>">
                                    <?php echo $label; ?> 
                                    (<?php echo $count; ?>)
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Pedidos -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Lista de Pedidos</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tabelaPedidos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Produto</th>
                                        <th>Cliente</th>
                                        <th>Quantidade</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr class="pedido-row" data-status="<?php echo $pedido['status']; ?>">
                                            <td><strong>#<?php echo $pedido['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($pedido['imagem_url'])): ?>
                                                        <img src="<?php echo 'uploads/produtos/' . htmlspecialchars($pedido['imagem_url']); ?>"
                                                             class="product-image me-2" 
                                                             alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>">
                                                    <?php else: ?>
                                                        <div class="product-image bg-light d-flex align-items-center justify-content-center me-2">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($pedido['produto_nome']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($pedido['endereco_entrega']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($pedido['usuario_nome']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($pedido['usuario_email']); ?></small>
                                            </td>
                                            <td><?php echo $pedido['quantidade']; ?></td>
                                            <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($pedido['data_pedido'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge status-badge badge-<?php echo $pedido['status']; ?>">
                                                    <?php echo ucfirst($pedido['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                <div class="dropdown-item">
                                                                    <label class="form-label small mb-1">Alterar Status:</label>
                                                                    <select name="novo_status" class="form-select form-select-sm">
                                                                        <?php foreach ($status_opcoes as $key => $label): ?>
                                                                            <option value="<?php echo $key; ?>" 
                                                                                <?php echo $key == $pedido['status'] ? 'selected' : ''; ?>>
                                                                                <?php echo $label; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="dropdown-item">
                                                                    <button type="submit" name="atualizar_status" class="btn btn-primary btn-sm w-100">
                                                                        <i class="fas fa-save me-1"></i>Atualizar
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="verDetalhes(<?php echo $pedido['id']; ?>)">
                                                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtros por status
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const status = this.getAttribute('data-status');
                
                // Atualizar botões ativos
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active', 'filter-active');
                });
                this.classList.add('active', 'filter-active');
                
                // Filtrar tabela
                const rows = document.querySelectorAll('.pedido-row');
                rows.forEach(row => {
                    if (status === 'todos' || row.getAttribute('data-status') === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        // Função para exportar relatório (simulação)
        function exportarRelatorio() {
            alert('Funcionalidade de exportação será implementada!');
            // Aqui você pode implementar a lógica para exportar para CSV, PDF, etc.
        }
        
        // Função para ver detalhes do pedido
        function verDetalhes(pedidoId) {
            alert(`Detalhes do pedido #${pedidoId} - Esta funcionalidade pode ser expandida para mostrar um modal com informações completas.`);
            // Aqui você pode implementar um modal com detalhes completos do pedido
        }
        
        // Inicializar tooltips do Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>