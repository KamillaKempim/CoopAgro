<?php
// Arquivo: admin_pedidos.php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$conn = getDBConnection();

// Processar ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pedido_id = $_POST['pedido_id'] ?? 0;
    
    if ($pedido_id > 0) {
        switch ($action) {
            case 'cancelar':
                cancelarPedido($conn, $pedido_id);
                break;
                
            case 'atualizar_status':
                $novo_status = $_POST['novo_status'] ?? '';
                atualizarStatusPedido($conn, $pedido_id, $novo_status);
                break;
                
            case 'atualizar_pedido':
                atualizarPedido($conn, $pedido_id);
                break;
        }
    }
}

// Função para cancelar pedido
function cancelarPedido($conn, $pedido_id) {
    try {
        $stmt = $conn->prepare("UPDATE pedidos SET status = 'cancelado', data_atualizacao = NOW() WHERE id = ?");
        $stmt->execute([$pedido_id]);
        
        // Log da ação
        
        $_SESSION['success'] = "Pedido #{$pedido_id} cancelado com sucesso!";
        header("Location: admin_pedidos.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao cancelar pedido: " . htmlspecialchars($e->getMessage());
        header("Location: admin_pedidos.php");
        exit();
    }
}

// Função para atualizar status do pedido
function atualizarStatusPedido($conn, $pedido_id, $novo_status) {
    $status_permitidos = ['pendente', 'confirmado', 'preparando', 'enviado', 'entregue', 'cancelado'];
    
    if (!in_array($novo_status, $status_permitidos)) {
        $_SESSION['error'] = "Status inválido!";
        header("Location: admin_pedidos.php");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("UPDATE pedidos SET status = ?, data_atualizacao = NOW() WHERE id = ?");
        $stmt->execute([$novo_status, $pedido_id]);
        
        // Log da ação
        logSecurity($_SESSION['user_id'], 'pedido_status_atualizado', "Pedido #{$pedido_id} atualizado para {$novo_status}");
        
        $_SESSION['success'] = "Status do pedido #{$pedido_id} atualizado para " . ucfirst($novo_status) . "!";
        header("Location: admin_pedidos.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao atualizar status: " . htmlspecialchars($e->getMessage());
        header("Location: admin_pedidos.php");
        exit();
    }
}

// Função para atualizar dados do pedido
function atualizarPedido($conn, $pedido_id) {
    // Aqui você pode adicionar mais campos conforme necessário
    $quantidade = $_POST['quantidade'] ?? 0;
    $preco_unitario = $_POST['preco_unitario'] ?? 0;
    $observacoes = $_POST['observacoes'] ?? '';
    
    try {
        // Recalcular total
        $total = $quantidade * $preco_unitario;
        
        $stmt = $conn->prepare("UPDATE pedidos SET quantidade = ?, preco_unitario = ?, total = ?, observacoes = ?, data_atualizacao = NOW() WHERE id = ?");
        $stmt->execute([$quantidade, $preco_unitario, $total, $observacoes, $pedido_id]);
        
        // Log da ação
        
        $_SESSION['success'] = "Pedido #{$pedido_id} atualizado com sucesso!";
        header("Location: admin_pedidos.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao atualizar pedido: " . htmlspecialchars($e->getMessage());
        header("Location: admin_pedidos.php");
        exit();
    }
}

// Verificar se há mensagens de sucesso ou erro
$mensagem_sucesso = $_SESSION['success'] ?? '';
$mensagem_erro = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Lógica para buscar pedidos do banco de dados
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'todos';

// Construir query com JOIN para obter dados completos
$query = "SELECT 
            p.*,
            pr.nome as produto_nome,
            pr.tipo as produto_tipo,
            pr.imagem_url as produto_imagem,
            u.nome as cliente_nome,
            u.email as cliente_email
          FROM pedidos p
          LEFT JOIN produtos pr ON p.produto_id = pr.id
          LEFT JOIN usuarios u ON p.usuario_id = u.id";
$params = [];

if ($status_filter !== 'todos' && $status_filter !== '') {
    $query .= " WHERE p.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY p.data_pedido DESC, p.id DESC";
$stmt = $conn->prepare($query);

if ($params) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar pedidos por status para os filtros
$contagem_status = [
    'todos' => 0,
    'pendente' => 0,
    'confirmado' => 0,
    'preparando' => 0,
    'enviado' => 0,
    'entregue' => 0,
    'cancelado' => 0
];

$stmt_count = $conn->prepare("SELECT status, COUNT(*) as total FROM pedidos GROUP BY status");
$stmt_count->execute();
$resultados_count = $stmt_count->fetchAll(PDO::FETCH_ASSOC);

$total_geral = 0;
foreach ($resultados_count as $row) {
    $contagem_status[$row['status']] = $row['total'];
    $total_geral += $row['total'];
}
$contagem_status['todos'] = $total_geral;

// Calcular total monetário dos pedidos
$stmt_total = $conn->prepare("SELECT SUM(total) as total_valor FROM pedidos");
$stmt_total->execute();
$total_valor = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_valor'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pedidos - Agricultura Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --cinza-claro: #f8f9fa;
            --cinza-borda: #dee2e6;
        }
        
        /* Estilo para o menu de ações */
        .dropdown-actions {
            position: relative;
            display: inline-block;
        }
        
        .btn-actions {
            background: none;
            border: none;
            color: #6c757d;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-actions:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        
        .dropdown-menu-actions {
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 1050;
            display: none;
            min-width: 200px;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 0.875rem;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-menu-actions.show {
            display: block;
        }
        
        .dropdown-item-action {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.5rem 1rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            transition: background-color 0.2s;
            cursor: pointer;
        }
        
        .dropdown-item-action:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        
        .dropdown-item-action i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        .dropdown-divider {
            height: 0;
            margin: 0.5rem 0;
            overflow: hidden;
            border-top: 1px solid rgba(0, 0, 0, 0.15);
        }
        
        /* Modal styles */
        .modal-content {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background-color: var(--verde-principal);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        
        /* Célula de ações na tabela */
        .table-actions-cell {
            position: relative;
            min-width: 80px;
            text-align: center;
        }
        
        /* Para evitar corte em telas pequenas */
        @media (max-width: 768px) {
            .dropdown-menu-actions {
                position: fixed !important;
                right: 10px !important;
                left: auto !important;
                min-width: 180px;
                z-index: 1050 !important;
                max-width: calc(100vw - 20px);
            }
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: var(--verde-principal);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            color: white;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 0.375rem;
        }
        
        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmado {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-preparando {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-enviado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-entregue {
            background-color: #198754;
            color: white;
        }
        
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .filter-btn {
            border-radius: 20px;
            padding: 0.375rem 1rem;
            margin: 0.125rem;
            transition: all 0.2s;
        }
        
        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-btn.active {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .valor-total {
            font-weight: 600;
            color: var(--verde-principal);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .resumo-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .resumo-card:hover {
            transform: translateY(-2px);
        }
        
        .resumo-card.total {
            border-left-color: #2e7d32;
        }
        
        .resumo-card.pendentes {
            border-left-color: #ffc107;
        }
        
        .resumo-card.preparando {
            border-left-color: #0dcaf0;
        }
        
        .resumo-card.entregue {
            border-left-color: #198754;
        }
        
        .resumo-card .card-body {
            padding: 1rem;
        }
        
        .resumo-titulo {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .resumo-valor {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .resumo-card.total .resumo-valor {
            color: #2e7d32;
        }
        
        .resumo-card.pendentes .resumo-valor {
            color: #ffc107;
        }
        
        .resumo-card.preparando .resumo-valor {
            color: #0dcaf0;
        }
        
        .resumo-card.entregue .resumo-valor {
            color: #198754;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        @media (max-width: 768px) {
            .filter-btn {
                margin-bottom: 0.5rem;
            }
            
            .resumo-card .card-body {
                padding: 0.75rem;
            }
            
            .resumo-valor {
                font-size: 1.1rem;
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .status-badge {
                font-size: 0.7em;
                padding: 0.25em 0.5em;
            }
            
            .btn-actions {
                width: 32px;
                height: 32px;
                padding: 0;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        .filter-btn.active {
            position: relative;
        }
        
        .filter-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background-color: #2e7d32;
            border-radius: 3px;
        }
        
        .produto-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .endereco-entrega {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .endereco-entrega:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            border: 1px solid #dee2e6;
            padding: 5px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-success:hover {
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
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
    
    <div class="container-fluid mt-4">
        <!-- Mensagens de sucesso/erro -->
        <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($mensagem_sucesso); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($mensagem_erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 text-success mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Gestão de Pedidos
                </h1>
                <p class="text-muted">Gerencie todos os pedidos do sistema</p>
            </div>
        </div>
        
        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card resumo-card total h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="resumo-titulo">Valor Total</div>
                                <div class="resumo-valor">R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></div>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-currency-dollar fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card resumo-card pendentes h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="resumo-titulo">Pendentes</div>
                                <div class="resumo-valor"><?php echo $contagem_status['pendente']; ?></div>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-clock-history fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card resumo-card preparando h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="resumo-titulo">Em Preparação</div>
                                <div class="resumo-valor"><?php echo $contagem_status['preparando']; ?></div>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-basket fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card resumo-card entregue h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="resumo-titulo">Entregues</div>
                                <div class="resumo-valor"><?php echo $contagem_status['entregue']; ?></div>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-check-circle fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap">
                    <a href="?status=todos" class="btn btn-outline-secondary filter-btn <?php echo $status_filter === 'todos' ? 'active' : ''; ?>">
                        Todos (<?php echo $contagem_status['todos']; ?>)
                    </a>
                    <a href="?status=pendente" class="btn btn-outline-warning filter-btn <?php echo $status_filter === 'pendente' ? 'active' : ''; ?>">
                        Pendente (<?php echo $contagem_status['pendente']; ?>)
                    </a>
                    <a href="?status=confirmado" class="btn btn-outline-info filter-btn <?php echo $status_filter === 'confirmado' ? 'active' : ''; ?>">
                        Confirmado (<?php echo $contagem_status['confirmado']; ?>)
                    </a>
                    <a href="?status=preparando" class="btn btn-outline-primary filter-btn <?php echo $status_filter === 'preparando' ? 'active' : ''; ?>">
                        Preparando (<?php echo $contagem_status['preparando']; ?>)
                    </a>
                    <a href="?status=enviado" class="btn btn-outline-success filter-btn <?php echo $status_filter === 'enviado' ? 'active' : ''; ?>">
                        Enviado (<?php echo $contagem_status['enviado']; ?>)
                    </a>
                    <a href="?status=entregue" class="btn btn-success filter-btn <?php echo $status_filter === 'entregue' ? 'active' : ''; ?>">
                        Entregue (<?php echo $contagem_status['entregue']; ?>)
                    </a>
                    <a href="?status=cancelado" class="btn btn-outline-danger filter-btn <?php echo $status_filter === 'cancelado' ? 'active' : ''; ?>">
                        Cancelado (<?php echo $contagem_status['cancelado']; ?>)
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Lista de Pedidos -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Lista de Pedidos</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-success" onclick="exportarPedidos()">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                    <button class="btn btn-sm btn-success" onclick="novoPedido()">
                        <i class="bi bi-plus-circle me-1"></i> Novo Pedido
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th>Produto</th>
                                <th>Cliente</th>
                                <th width="100">Quantidade</th>
                                <th width="120">Total</th>
                                <th width="120">Data</th>
                                <th width="120">Status</th>
                                <th width="80" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1"></i>
                                            <p class="mt-2">Nenhum pedido encontrado</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <?php
                                    // Determinar classe do status
                                    $status_class = 'status-' . $pedido['status'];
                                    $status_text = ucfirst($pedido['status']);
                                    
                                    // Formatar data
                                    $data_formatada = date('d/m/Y', strtotime($pedido['data_pedido']));
                                    $hora_formatada = date('H:i', strtotime($pedido['data_pedido']));
                                    
                                    // Formatar valor (usando 'total' da tabela)
                                    $valor_formatado = 'R$ ' . number_format($pedido['total'], 2, ',', '.');
                                    
                                    // Verificar se as chaves existem antes de usar
                                    $produto_tipo = isset($pedido['produto_tipo']) ? $pedido['produto_tipo'] : 'N/A';
                                    $cliente_email = isset($pedido['cliente_email']) ? $pedido['cliente_email'] : 'N/A';
                                    $produto_imagem = isset($pedido['produto_imagem']) ? $pedido['produto_imagem'] : null;
                                    ?>
                                    <tr>
                                        <td class="fw-semibold">#<?php echo $pedido['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($produto_imagem && file_exists('uploads/produtos/' . $produto_imagem)): ?>
                                                <img src="uploads/produtos/<?php echo htmlspecialchars($produto_imagem); ?>" 
                                                     alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>"
                                                     class="produto-img">
                                                <?php else: ?>
                                                <div class="produto-img bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($pedido['produto_nome'] ?? 'Produto não encontrado'); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($produto_tipo); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?php echo htmlspecialchars($pedido['cliente_nome'] ?? 'Cliente não encontrado'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($cliente_email); ?></small>
                                            <?php if (!empty($pedido['endereco_entrega'])): ?>
                                            <div class="endereco-entrega text-muted small mt-1" title="<?php echo htmlspecialchars($pedido['endereco_entrega']); ?>">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars(substr($pedido['endereco_entrega'], 0, 30)) . '...'; ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $pedido['quantidade']; ?> <?php echo $pedido['unidade_medida']; ?>
                                            </span>
                                        </td>
                                        <td class="valor-total"><?php echo $valor_formatado; ?></td>
                                        <td>
                                            <div><?php echo $data_formatada; ?></div>
                                            <small class="text-muted"><?php echo $hora_formatada; ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="text-center table-actions-cell">
                                            <div class="dropdown-actions">
                                                <button type="button" class="btn-actions" onclick="toggleActionsMenu(this, <?php echo $pedido['id']; ?>)">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu-actions" id="actionsMenu-<?php echo $pedido['id']; ?>">
    
                                                    <button type="button" class="dropdown-item-action" onclick="editarPedidoModal(<?php echo $pedido['id']; ?>, <?php echo htmlspecialchars(json_encode($pedido)); ?>)">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </button>
                                                    <?php if ($pedido['status'] !== 'entregue' && $pedido['status'] !== 'cancelado'): ?>
                                                    <button type="button" class="dropdown-item-action" onclick="atualizarStatusModal(<?php echo $pedido['id']; ?>, '<?php echo $pedido['status']; ?>')">
                                                        <i class="bi bi-arrow-clockwise"></i> Atualizar Status
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($pedido['status'] === 'pendente' || $pedido['status'] === 'confirmado'): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <button type="button" class="dropdown-item-action text-danger" onclick="confirmarCancelamento(<?php echo $pedido['id']; ?>)">
                                                        <i class="bi bi-x-circle"></i> Cancelar Pedido
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($pedidos)): ?>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <?php echo count($pedidos); ?> pedido(s)
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Anterior</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal para atualizar status -->
    <div class="modal fade" id="modalStatus" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="admin_pedidos.php">
                    <input type="hidden" name="action" value="atualizar_status">
                    <input type="hidden" name="pedido_id" id="pedidoIdStatus">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Atualizar Status do Pedido</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="novo_status" class="form-label">Novo Status:</label>
                            <select class="form-select" id="novo_status" name="novo_status" required>
                                <option value="pendente">Pendente</option>
                                <option value="confirmado">Confirmado</option>
                                <option value="preparando">Preparando</option>
                                <option value="enviado">Enviado</option>
                                <option value="entregue">Entregue</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Ao atualizar o status, o pedido será movido para a categoria correspondente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Atualizar Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para editar pedido -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="admin_pedidos.php">
                    <input type="hidden" name="action" value="atualizar_pedido">
                    <input type="hidden" name="pedido_id" id="pedidoIdEditar">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Pedido</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="quantidade" class="form-label">Quantidade:</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="preco_unitario" class="form-label">Preço Unitário (R$):</label>
                            <input type="number" class="form-control" id="preco_unitario" name="preco_unitario" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações:</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            O valor total será recalculado automaticamente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Controle do menu de ações
        function toggleActionsMenu(button, pedidoId) {
            event.stopPropagation();
            
            // Fecha todos os outros menus abertos
            const allMenus = document.querySelectorAll('.dropdown-menu-actions.show');
            allMenus.forEach(menu => {
                if (menu !== button.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });
            
            // Alterna o menu atual
            const menu = button.nextElementSibling;
            menu.classList.toggle('show');
            
            // Ajusta posição se necessário
            ajustarPosicaoMenu(menu);
        }
        
        function ajustarPosicaoMenu(menu) {
            const viewportHeight = window.innerHeight;
            const menuRect = menu.getBoundingClientRect();
            const menuBottom = menuRect.bottom;
            
            // Se o menu ultrapassar a parte inferior da tela
            if (menuBottom > viewportHeight) {
                const overflow = menuBottom - viewportHeight;
                menu.style.top = `calc(100% - ${overflow + 10}px)`;
            }
        }
        
        // Fecha todos os menus quando clicar em qualquer lugar da página
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown-actions')) {
                const allMenus = document.querySelectorAll('.dropdown-menu-actions.show');
                allMenus.forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // Funções para as ações
        
        
        function editarPedidoModal(id, pedidoData) {
            document.getElementById('pedidoIdEditar').value = id;
            document.getElementById('quantidade').value = pedidoData.quantidade || 1;
            document.getElementById('preco_unitario').value = pedidoData.preco_unitario || 0;
            document.getElementById('observacoes').value = pedidoData.observacoes || '';
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
        }
        
        function atualizarStatusModal(id, statusAtual) {
            document.getElementById('pedidoIdStatus').value = id;
            document.getElementById('novo_status').value = statusAtual;
            
            const modal = new bootstrap.Modal(document.getElementById('modalStatus'));
            modal.show();
        }
        
        function confirmarCancelamento(id) {
            if (confirm(`Tem certeza que deseja cancelar o pedido #${id}?\n\nEsta ação não pode ser desfeita.`)) {
                // Criar formulário dinâmico para cancelamento
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_pedidos.php';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'cancelar';
                form.appendChild(inputAction);
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'pedido_id';
                inputId.value = id;
                form.appendChild(inputId);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function novoPedido() {
            alert('Funcionalidade de novo pedido em desenvolvimento');
            // window.location.href = `novo_pedido.php`;
        }
        
        function exportarPedidos() {
            alert('Funcionalidade de exportação em desenvolvimento');
        }
        
        // Fecha o menu ao pressionar ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const allMenus = document.querySelectorAll('.dropdown-menu-actions.show');
                allMenus.forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // Ajusta menu em telas pequenas
        function ajustarMenuMobile() {
            const menus = document.querySelectorAll('.dropdown-menu-actions');
            const isMobile = window.innerWidth < 768;
            
            menus.forEach(menu => {
                if (isMobile) {
                    menu.style.position = 'fixed';
                    menu.style.right = '10px';
                    menu.style.left = 'auto';
                    menu.style.zIndex = '1050';
                } else {
                    menu.style.position = 'absolute';
                    menu.style.right = '0';
                    menu.style.left = 'auto';
                    menu.style.zIndex = '1000';
                }
            });
        }
        
        // Executar ajuste inicial e ao redimensionar
        ajustarMenuMobile();
        window.addEventListener('resize', ajustarMenuMobile);
        
        // Tooltip para endereços
        document.addEventListener('DOMContentLoaded', function() {
            const enderecos = document.querySelectorAll('.endereco-entrega');
            enderecos.forEach(endereco => {
                endereco.addEventListener('mouseenter', function(e) {
                    const title = this.getAttribute('title');
                    if (title) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'endereco-tooltip';
                        tooltip.textContent = title;
                        tooltip.style.position = 'absolute';
                        tooltip.style.background = '#fff';
                        tooltip.style.border = '1px solid #dee2e6';
                        tooltip.style.padding = '5px 10px';
                        tooltip.style.borderRadius = '4px';
                        tooltip.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                        tooltip.style.zIndex = '1000';
                        
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = (rect.left + window.scrollX) + 'px';
                        tooltip.style.top = (rect.top + window.scrollY - 10) + 'px';
                        
                        document.body.appendChild(tooltip);
                        this._tooltip = tooltip;
                    }
                });
                
                endereco.addEventListener('mouseleave', function() {
                    if (this._tooltip) {
                        this._tooltip.remove();
                        delete this._tooltip;
                    }
                });
            });
            
            // Auto-fechar alertas após 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>