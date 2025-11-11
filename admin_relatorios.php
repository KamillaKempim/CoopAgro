<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verificar se o usuário é administrador - CORREÇÃO
$usuario_id = $_SESSION['user_id'];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario || $usuario['tipo_usuario'] !== 'Administrador') {
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    // Em caso de erro, ainda permite acesso para não quebrar a página
    $error_permissao = "Erro ao verificar permissões: " . $e->getMessage();
}

$relatorio = [];
$periodo = $_GET['periodo'] ?? 'mes'; // mes, semana, ano, personalizado
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

try {
    $conn = getDBConnection();
    
    // Construir a query base
    $query = "
        SELECT 
            p.id,
            p.nome as produto_nome,
            pd.quantidade,
            pd.preco_unitario,
            pd.total as valor_bruto,
            p.preco_custo,
            (p.preco_custo * pd.quantidade) as custo_total,
            (pd.total - (p.preco_custo * pd.quantidade)) as valor_liquido,
            pd.data_pedido,
            u.nome as cliente_nome
        FROM pedidos pd
        INNER JOIN produtos p ON pd.produto_id = p.id
        INNER JOIN usuarios u ON pd.usuario_id = u.id
        WHERE pd.status = 'entregue'
    ";
    
    // Adicionar filtro de período
    $params = [];
    if ($periodo === 'semana') {
        $query .= " AND pd.data_pedido >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($periodo === 'mes') {
        $query .= " AND pd.data_pedido >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($periodo === 'ano') {
        $query .= " AND pd.data_pedido >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    } elseif ($periodo === 'personalizado' && $data_inicio && $data_fim) {
        $query .= " AND DATE(pd.data_pedido) BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
    }
    
    $query .= " ORDER BY pd.data_pedido DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totais - CORREÇÃO: Inicializar variáveis mesmo sem vendas
    $total_bruto = 0;
    $total_liquido = 0;
    $total_custo = 0;
    $total_pedidos = count($vendas);
    
    foreach ($vendas as $venda) {
        $total_bruto += $venda['valor_bruto'];
        $total_custo += $venda['custo_total'];
        $total_liquido += $venda['valor_liquido'];
    }
    
    // Estatísticas adicionais - CORREÇÃO: Query mais segura
    $queryStats = "
        SELECT 
            COUNT(DISTINCT usuario_id) as total_clientes,
            AVG(total) as ticket_medio,
            SUM(quantidade) as total_itens
        FROM pedidos 
        WHERE status = 'entregue'
    ";
    
    // Adicionar filtro de período nas estatísticas também
    if ($periodo === 'semana') {
        $queryStats .= " AND data_pedido >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($periodo === 'mes') {
        $queryStats .= " AND data_pedido >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($periodo === 'ano') {
        $queryStats .= " AND data_pedido >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    } elseif ($periodo === 'personalizado' && $data_inicio && $data_fim) {
        $queryStats .= " AND DATE(data_pedido) BETWEEN ? AND ?";
    }
    
    $stmtStats = $conn->prepare($queryStats);
    if ($periodo === 'personalizado' && $data_inicio && $data_fim) {
        $stmtStats->execute([$data_inicio, $data_fim]);
    } else {
        $stmtStats->execute();
    }
    $estatisticas = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Garantir valores padrão se não houver dados
    if (!$estatisticas) {
        $estatisticas = [
            'total_clientes' => 0,
            'ticket_medio' => 0,
            'total_itens' => 0
        ];
    }
    
} catch(PDOException $e) {
    $error = "Erro ao gerar relatório: " . $e->getMessage();
    // Inicializar variáveis mesmo com erro
    $vendas = [];
    $total_bruto = 0;
    $total_liquido = 0;
    $total_pedidos = 0;
    $estatisticas = [
        'total_clientes' => 0,
        'ticket_medio' => 0,
        'total_itens' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios de Vendas - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --roxo-admin: #6a1b9a;
        }
        
        .card-relatorio {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card-relatorio:hover {
            transform: translateY(-5px);
        }
        
        .valor-positivo {
            color: var(--verde-principal);
            font-weight: bold;
        }
        
        .valor-negativo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--roxo-admin) 0%, #9c4dcc 100%);
            color: white;
            border: none;
        }
        
        .btn-admin:hover {
            background: linear-gradient(135deg, #9c4dcc 0%, var(--roxo-admin) 100%);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(106, 27, 154, 0.05);
        }
        
        .badge-entregue {
            background-color: #d4edda;
            color: #155724;
        }
        
        .filtros {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-graph-up"></i> Relatórios de Vendas
                    </h1>
                    <div>
                        <a href="admin_relatorios.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <a href="perfil.php" class="btn btn-outline-primary ms-2">
                            <i class="bi bi-person"></i> Perfil
                        </a>
                    </div>
                </div>

                <?php if (isset($error_permissao)): ?>
                    <div class="alert alert-warning"><?php echo $error_permissao; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card filtros mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-funnel"></i> Filtros
                        </h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="periodo" class="form-label">Período</label>
                                <select class="form-select" id="periodo" name="periodo">
                                    <option value="semana" <?php echo $periodo === 'semana' ? 'selected' : ''; ?>>Última Semana</option>
                                    <option value="mes" <?php echo $periodo === 'mes' ? 'selected' : ''; ?>>Último Mês</option>
                                    <option value="ano" <?php echo $periodo === 'ano' ? 'selected' : ''; ?>>Último Ano</option>
                                    <option value="personalizado" <?php echo $periodo === 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                                </select>
                            </div>
                            
                            <?php if ($periodo === 'personalizado'): ?>
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                       value="<?php echo htmlspecialchars($data_inicio); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                       value="<?php echo htmlspecialchars($data_fim); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-<?php echo $periodo === 'personalizado' ? '3' : '9'; ?> d-flex align-items-end">
                                <button type="submit" class="btn btn-admin w-100">
                                    <i class="bi bi-filter"></i> Aplicar Filtros
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cards de Resumo -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-currency-dollar display-6 text-success mb-3"></i>
                                <h3 class="valor-positivo">R$ <?php echo number_format($total_bruto, 2, ',', '.'); ?></h3>
                                <p class="text-muted mb-0">Valor Bruto Total</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-cash-coin display-6 text-primary mb-3"></i>
                                <h3 class="valor-positivo">R$ <?php echo number_format($total_liquido, 2, ',', '.'); ?></h3>
                                <p class="text-muted mb-0">Valor Líquido Total</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-cart-check display-6 text-warning mb-3"></i>
                                <h3 class="text-dark"><?php echo $total_pedidos; ?></h3>
                                <p class="text-muted mb-0">Pedidos Entregues</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-people display-6 text-info mb-3"></i>
                                <h3 class="text-dark"><?php echo $estatisticas['total_clientes'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Clientes Únicos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas Detalhadas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Margem de Lucro</h6>
                            </div>
                            <div class="card-body text-center">
                                <?php
                                $margem_lucro = $total_bruto > 0 ? ($total_liquido / $total_bruto) * 100 : 0;
                                $classe_margem = $margem_lucro >= 20 ? 'valor-positivo' : ($margem_lucro >= 10 ? 'text-warning' : 'valor-negativo');
                                ?>
                                <h2 class="<?php echo $classe_margem; ?>"><?php echo number_format($margem_lucro, 1); ?>%</h2>
                                <p class="text-muted mb-0">Margem líquida sobre vendas</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-basket"></i> Ticket Médio</h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-primary">R$ <?php echo number_format($estatisticas['ticket_medio'] ?? 0, 2, ',', '.'); ?></h2>
                                <p class="text-muted mb-0">Valor médio por pedido</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-box-seam"></i> Itens Vendidos</h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-success"><?php echo $estatisticas['total_itens'] ?? 0; ?></h2>
                                <p class="text-muted mb-0">Total de unidades</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Vendas Detalhadas -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check"></i> Vendas Detalhadas
                        </h5>
                        <span class="badge bg-primary"><?php echo count($vendas); ?> registros</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vendas)): ?>
                            <div class="empty-state text-muted">
                                <i class="bi bi-receipt"></i>
                                <h4 class="mt-3">Nenhuma venda encontrada</h4>
                                <p class="mb-4">Não há pedidos entregues no período selecionado.</p>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Informação:</strong> Esta tela exibe apenas pedidos com status "Entregue". 
                                    Quando houver vendas concluídas, elas aparecerão aqui automaticamente.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Cliente</th>
                                            <th>Produto</th>
                                            <th>Quantidade</th>
                                            <th>Preço Unit.</th>
                                            <th>Custo Unit.</th>
                                            <th>Valor Bruto</th>
                                            <th>Valor Líquido</th>
                                            <th>Margem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vendas as $venda): ?>
                                            <?php
                                            $margem_item = $venda['valor_bruto'] > 0 ? 
                                                (($venda['valor_liquido'] / $venda['valor_bruto']) * 100) : 0;
                                            $classe_margem_item = $margem_item >= 20 ? 'valor-positivo' : 
                                                ($margem_item >= 10 ? 'text-warning' : 'valor-negativo');
                                            ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($venda['data_pedido'])); ?></td>
                                                <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($venda['produto_nome']); ?></td>
                                                <td><?php echo $venda['quantidade']; ?></td>
                                                <td>R$ <?php echo number_format($venda['preco_unitario'], 2, ',', '.'); ?></td>
                                                <td>R$ <?php echo number_format($venda['preco_custo'] ?? 0, 2, ',', '.'); ?></td>
                                                <td class="valor-positivo">R$ <?php echo number_format($venda['valor_bruto'], 2, ',', '.'); ?></td>
                                                <td class="<?php echo $venda['valor_liquido'] >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
                                                    R$ <?php echo number_format($venda['valor_liquido'], 2, ',', '.'); ?>
                                                </td>
                                                <td class="<?php echo $classe_margem_item; ?>">
                                                    <?php echo number_format($margem_item, 1); ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
</body>
</html>