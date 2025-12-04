<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verificar se o usuário é administrador
$usuario_id = intval($_SESSION['user_id']);

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Administrador') {
        $_SESSION['error'] = 'Acesso restrito a administradores.';
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    die("Erro ao verificar tipo de usuário: " . htmlspecialchars($e->getMessage()));
}

try {
    $conn = getDBConnection();
    
    // Processar atualização de status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
        // Verifica token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header("Location: admin_pedidos.php");
            exit();
        }
        
        $pedido_id = intval($_POST['pedido_id']);
        $novo_status = sanitizeInput($_POST['novo_status']);
        
        // Validar status
        $status_validos = ['pendente', 'confirmado', 'preparando', 'enviado', 'entregue', 'cancelado'];
        if (!in_array($novo_status, $status_validos)) {
            $_SESSION['error'] = 'Status inválido.';
            header("Location: admin_pedidos.php");
            exit();
        }
        
        // Verificar se o pedido existe
        $checkStmt = $conn->prepare("SELECT id FROM pedidos WHERE id = ?");
        $checkStmt->execute([$pedido_id]);
        
        if ($checkStmt->rowCount() === 0) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header("Location: admin_pedidos.php");
            exit();
        }
        
        // Atualizar status do pedido
        $updateStmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        $updateStmt->execute([$novo_status, $pedido_id]);
        
        // Log da ação
        logSecurity($usuario_id, 'status_pedido_atualizado', "Pedido #{$pedido_id} atualizado para {$novo_status}");
        
        $_SESSION['success'] = "Status do pedido #{$pedido_id} atualizado com sucesso!";
        header("Location: admin_pedidos.php");
        exit();
    }
    
    // Buscar todos os pedidos com informações do usuário
    $stmt = $conn->prepare("
        SELECT 
            p.*, 
            pr.nome as produto_nome, 
            pr.imagem_url,
            pr.unidade_medida,
            u.nome as usuario_nome,
            u.email as usuario_email,
            u.celular as usuario_celular
        FROM pedidos p 
        INNER JOIN produtos pr ON p.produto_id = pr.id 
        INNER JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.data_pedido DESC
    ");
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas para o dashboard
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
            SUM(CASE WHEN status = 'preparando' THEN 1 ELSE 0 END) as preparando,
            SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
            SUM(CASE WHEN status = 'entregue' THEN 1 ELSE 0 END) as entregues,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
            SUM(total) as valor_total
        FROM pedidos
    ");
    $statsStmt->execute();
    $estatisticas = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar pedidos: " . htmlspecialchars($e->getMessage());
    logSecurity($usuario_id, 'erro_admin_pedidos', "Erro: " . $e->getMessage());
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

// Garantir que todas as chaves do array de estatísticas existam
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }
        
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
            background-color: var(--verde-principal) !important;
            color: white !important;
            border-color: var(--verde-principal) !important;
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
        .badge-entregue { background-color: var(--verde-principal); color: #fff; }
        .badge-cancelado { background-color: #dc3545; color: #fff; }
        
        .unidade-info {
            font-size: 0.75rem;
            color: #666;
        }
        
        .btn-export {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
            color: white;
        }
        
        .btn-export:hover {
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
            color: white;
        }
        
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
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>
    
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-success">
                        <i class="fas fa-shopping-bag me-2"></i>Gestão de Pedidos
                    </h2>
                    <div class="btn-group">
                        <button class="btn btn-export" onclick="exportarRelatorio()">
                            <i class="fas fa-download me-1"></i>Exportar Relatório
                        </button>
                    </div>
                </div>
                
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
                        <h5 class="card-title text-success">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </h5>
                        <div class="btn-group flex-wrap" role="group">
                            <button type="button" class="btn btn-outline-success filter-btn active" data-status="todos">
                                Todos (<?php echo $estatisticas['total_pedidos']; ?>)
                            </button>
                            <?php foreach ($status_opcoes as $key => $label): 
                                $count = array_key_exists($key . 's', $estatisticas) ? $estatisticas[$key . 's'] : 0;
                            ?>
                                <button type="button" class="btn btn-outline-success filter-btn" data-status="<?php echo htmlspecialchars($key); ?>">
                                    <?php echo htmlspecialchars($label); ?> 
                                    (<?php echo $count; ?>)
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Pedidos -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Lista de Pedidos
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tabelaPedidos">
                                <thead class="table-success">
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
                                    <?php foreach ($pedidos as $pedido): 
                                        $unidade_texto = htmlspecialchars($pedido['unidade_medida'] ?? 'UN');
                                        $quantidade_texto = $pedido['quantidade'] . ' ' . $unidade_texto;
                                    ?>
                                        <tr class="pedido-row" data-status="<?php echo htmlspecialchars($pedido['status']); ?>">
                                            <td><strong>#<?php echo intval($pedido['id']); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($pedido['imagem_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars('uploads/produtos/' . $pedido['imagem_url']); ?>"
                                                             class="product-image me-2" 
                                                             alt="<?php echo htmlspecialchars($pedido['produto_nome']); ?>"
                                                             onerror="this.src='https://via.placeholder.com/60x60/CCCCCC/969696?text=Imagem'">
                                                    <?php else: ?>
                                                        <div class="product-image bg-light d-flex align-items-center justify-content-center me-2">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($pedido['produto_nome']); ?></div>
                                                        <small class="text-muted unidade-info"><?php echo $quantidade_texto; ?></small>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($pedido['endereco_entrega']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($pedido['usuario_nome']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($pedido['usuario_email']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($pedido['usuario_celular']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo $quantidade_texto; ?>
                                                <br>
                                                <small class="text-muted">R$ <?php echo number_format($pedido['preco_unitario'], 2, ',', '.'); ?>/<?php echo $unidade_texto; ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-success">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($pedido['data_pedido'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge status-badge badge-<?php echo htmlspecialchars($pedido['status']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($pedido['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-success dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <form method="POST" class="p-2" style="min-width: 250px;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="pedido_id" value="<?php echo intval($pedido['id']); ?>">
                                                                <div class="mb-2">
                                                                    <label class="form-label small mb-1">Alterar Status:</label>
                                                                    <select name="novo_status" class="form-select form-select-sm">
                                                                        <?php foreach ($status_opcoes as $key => $label): ?>
                                                                            <option value="<?php echo htmlspecialchars($key); ?>" 
                                                                                <?php echo $key == $pedido['status'] ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($label); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="d-grid">
                                                                    <button type="submit" name="atualizar_status" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-save me-1"></i>Atualizar
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="verDetalhes(<?php echo intval($pedido['id']); ?>)">
                                                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="contatarCliente('<?php echo htmlspecialchars($pedido['usuario_email']); ?>', '<?php echo htmlspecialchars($pedido['usuario_celular']); ?>')">
                                                                <i class="fas fa-phone me-1"></i>Contatar Cliente
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
        
        // Função para exportar relatório
        function exportarRelatorio() {
            // Criar dados para CSV
            let csv = 'ID;Produto;Cliente;Quantidade;Total;Data;Status;Endereço\n';
            
            document.querySelectorAll('.pedido-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const id = cells[0].textContent.trim();
                    const produto = cells[1].querySelector('.fw-bold').textContent.trim();
                    const cliente = cells[2].querySelector('.fw-bold').textContent.trim();
                    const quantidade = cells[3].textContent.trim();
                    const total = cells[4].textContent.trim();
                    const data = cells[5].textContent.trim();
                    const status = cells[6].textContent.trim();
                    const endereco = cells[1].querySelectorAll('small')[1]?.textContent.trim() || '';
                    
                    csv += `${id};"${produto}";"${cliente}";"${quantidade}";"${total}";"${data}";"${status}";"${endereco}"\n`;
                }
            });
            
            // Criar blob e download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `pedidos_${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            alert('Relatório exportado com sucesso!');
        }
        
        // Função para ver detalhes do pedido
        function verDetalhes(pedidoId) {
            alert(`Detalhes do pedido #${pedidoId}\n\nEsta funcionalidade pode ser expandida para mostrar um modal com informações completas do pedido, histórico de status, etc.`);
        }
        
        // Função para contatar cliente
        function contatarCliente(email, telefone) {
            const mensagem = `Contatar cliente:\n\nEmail: ${email}\nTelefone: ${telefone}\n\nClique em OK para copiar as informações.`;
            
            if (confirm(mensagem)) {
                // Copiar informações para área de transferência
                const texto = `Email: ${email}\nTelefone: ${telefone}`;
                navigator.clipboard.writeText(texto)
                    .then(() => alert('Informações copiadas para a área de transferência!'))
                    .catch(() => alert('Não foi possível copiar as informações.'));
            }
        }
        
        // Inicializar tooltips do Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>