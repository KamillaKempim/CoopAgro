<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = intval($_SESSION['user_id']);

// Verifica se o usuário é produtor
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Produtor') {
        $_SESSION['error'] = 'Acesso restrito a produtores.';
        header("Location: perfil.php");
        exit();
    }
    
    // Busca as propostas do usuário
    $stmtPropostas = $conn->prepare("
        SELECT 
            pp.*,
            DATE_FORMAT(pp.data_criacao, '%d/%m/%Y às %H:%i') as data_formatada,
            DATE_FORMAT(pp.data_avaliacao, '%d/%m/%Y às %H:%i') as data_avaliacao_formatada,
            u.nome as avaliador_nome
        FROM produtos_propostos pp 
        LEFT JOIN usuarios u ON pp.avaliado_por = u.id
        WHERE pp.produtor_id = ? 
        ORDER BY 
            CASE 
                WHEN pp.status = 'pendente' THEN 1
                WHEN pp.status = 'aprovado' THEN 2
                WHEN pp.status = 'rejeitado' THEN 3
            END,
            pp.data_criacao DESC
    ");
    $stmtPropostas->execute([$usuario_id]);
    $propostas = $stmtPropostas->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $totalPropostas = count($propostas);
    $pendentes = array_filter($propostas, function($p) { return $p['status'] === 'pendente'; });
    $aprovadas = array_filter($propostas, function($p) { return $p['status'] === 'aprovado'; });
    $rejeitadas = array_filter($propostas, function($p) { return $p['status'] === 'rejeitado'; });
    
} catch(PDOException $e) {
    $error = "Erro ao carregar propostas: " . htmlspecialchars($e->getMessage());
    error_log("Erro minhas_propostas.php - Usuário: $usuario_id - Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Propostas - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --verde-escuro: #1b5e20;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
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
        }
        
        .proposta-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .proposta-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .badge-pendente { 
            background-color: #fff3cd; 
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .badge-aprovado { 
            background-color: #d1edff; 
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .badge-rejeitado { 
            background-color: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-nova-proposta {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-nova-proposta:hover {
            background: linear-gradient(135deg, var(--verde-escuro) 0%, var(--verde-principal) 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.4);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .proposta-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #dee2e6;
        }
        
        .proposta-image-container {
            position: relative;
            display: inline-block;
        }
        
        .info-badge {
            background-color: var(--verde-claro);
            color: var(--verde-escuro);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        
        .section-title {
            color: var(--verde-principal);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .unidade-badge {
            background-color: #6c757d;
            color: white;
            font-size: 0.75rem;
        }
        
        .imagem-error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #dc3545;
            font-size: 0.8rem;
            text-align: center;
            width: 90px;
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-clock-history"></i> Minhas Propostas
                    </h1>
                    <p class="lead mb-0">Acompanhe o status das suas propostas de produtos</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-clipboard2-data" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <?php 
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        
        if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-clipboard2-data stat-icon text-primary"></i>
                    <div class="stat-number text-primary"><?php echo $totalPropostas; ?></div>
                    <p class="text-muted mb-0">Total de Propostas</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-clock-history stat-icon text-warning"></i>
                    <div class="stat-number text-warning"><?php echo count($pendentes); ?></div>
                    <p class="text-muted mb-0">Aguardando Análise</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-check-circle stat-icon text-success"></i>
                    <div class="stat-number text-success"><?php echo count($aprovadas); ?></div>
                    <p class="text-muted mb-0">Aprovadas</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4">
                    <i class="bi bi-x-circle stat-icon text-danger"></i>
                    <div class="stat-number text-danger"><?php echo count($rejeitadas); ?></div>
                    <p class="text-muted mb-0">Rejeitadas</p>
                </div>
            </div>
        </div>

        <!-- Cabeçalho com Botão -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title mb-0">Minhas Propostas de Produtos</h3>
            <a href="propor_produto.php" class="btn btn-nova-proposta">
                <i class="bi bi-plus-circle"></i> Nova Proposta
            </a>
        </div>

        <!-- Lista de Propostas -->
        <?php if (empty($propostas)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="bi bi-clipboard2-x display-1 text-muted"></i>
                    <h3 class="text-muted mt-3">Nenhuma proposta encontrada</h3>
                    <p class="text-muted mb-4">Você ainda não enviou nenhuma proposta de produto.</p>
                    <a href="propor_produto.php" class="btn btn-success btn-lg">
                        <i class="bi bi-plus-circle"></i> Fazer Primeira Proposta
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($propostas as $proposta): 
                    $unidade_texto = htmlspecialchars($proposta['unidade_medida'] ?? 'KG');
                    
                    // DEBUG: Verificar informações da imagem
                    // echo "<!-- DEBUG: imagem_url = " . htmlspecialchars($proposta['imagem_url']) . " -->";
                ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card proposta-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <div class="proposta-image-container">
                                            <?php 
                                            // Verificar se a imagem existe
                                            $imagemPath = 'uploads/produtos/' . htmlspecialchars($proposta['imagem_url'] ?? '');
                                            $imagemSrc = '';
                                            $imagemExiste = false;
                                            
                                            if (!empty($proposta['imagem_url']) && file_exists($imagemPath)) {
                                                $imagemExiste = true;
                                                $imagemSrc = htmlspecialchars($imagemPath);
                                            } else {
                                                // Se a imagem não existe, usar placeholder
                                                $imagemSrc = 'https://via.placeholder.com/150x150/CCCCCC/969696?text=Sem+Imagem';
                                            }
                                            ?>
                                            
                                            <img src="<?php echo $imagemSrc; ?>" 
                                                 alt="<?php echo htmlspecialchars($proposta['nome']); ?>"
                                                 class="proposta-image w-100"
                                                 data-original="<?php echo htmlspecialchars($proposta['imagem_url'] ?? ''); ?>"
                                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/150x150/CCCCCC/969696?text=Imagem+Não+Encontrada';">
                                                 
                                            <?php if (!$imagemExiste && !empty($proposta['imagem_url'])): ?>
                                                <div class="imagem-error">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <br>
                                                    <small>Imagem não encontrada</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($proposta['nome']); ?></h5>
                                            <span class="status-badge badge-<?php echo htmlspecialchars($proposta['status']); ?>">
                                                <?php 
                                                $statusText = [
                                                    'pendente' => 'Aguardando Análise',
                                                    'aprovado' => 'Aprovado',
                                                    'rejeitado' => 'Rejeitado'
                                                ];
                                                echo htmlspecialchars($statusText[$proposta['status']]);
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <p class="card-text text-muted small mb-2">
                                            <?php echo htmlspecialchars($proposta['descricao']); ?>
                                        </p>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <span class="info-badge">
                                                    <i class="bi bi-tag"></i> 
                                                    <?php echo htmlspecialchars($proposta['tipo']); ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="badge unidade-badge">
                                                    <i class="bi bi-rulers"></i> 
                                                    <?php echo $unidade_texto; ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="info-badge">
                                                    <i class="bi bi-box-seam"></i> 
                                                    <?php echo $proposta['quantidade_disponivel']; ?> <?php echo $unidade_texto; ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="info-badge">
                                                    <i class="bi bi-currency-dollar"></i> 
                                                    R$ <?php echo number_format($proposta['preco_sugerido'], 2, ',', '.'); ?>/<?php echo $unidade_texto; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> 
                                                Enviada em <?php echo htmlspecialchars($proposta['data_formatada']); ?>
                                            </small>
                                            
                                            <?php if ($proposta['status'] !== 'pendente' && !empty($proposta['data_avaliacao_formatada'])): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> 
                                                    Avaliada em <?php echo htmlspecialchars($proposta['data_avaliacao_formatada']); ?>
                                                    <?php if (!empty($proposta['avaliador_nome'])): ?>
                                                        <br>
                                                        <i class="bi bi-person"></i> 
                                                        Por: <?php echo htmlspecialchars($proposta['avaliador_nome']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($proposta['observacoes'])): ?>
                                            <div class="mt-3 p-2 bg-light rounded">
                                                <small class="text-muted">
                                                    <strong>Observações da avaliação:</strong> 
                                                    <?php echo htmlspecialchars($proposta['observacoes']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($proposta['status'] === 'aprovado'): ?>
                                            <div class="mt-3 p-2 bg-success bg-opacity-10 rounded border border-success">
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle"></i> 
                                                    <strong>Esta proposta foi aprovada!</strong> O produto já está disponível no catálogo.
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Informações Adicionais -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h5 class="card-title text-success mb-3">
                            <i class="bi bi-info-circle"></i> Informações sobre o Processo
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="text-success">📋 Aguardando Análise</h6>
                                <p class="small text-muted mb-0">
                                    Sua proposta foi recebida e está na fila para análise pela nossa equipe.
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-success">✅ Aprovado</h6>
                                <p class="small text-muted mb-0">
                                    Parabéns! Seu produto foi aprovado e está disponível para venda.
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-success">❌ Rejeitado</h6>
                                <p class="small text-muted mb-0">
                                    Sua proposta não foi aprovada. Entre em contato para mais informações.
                                </p>
                            </div>
                        </div>
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
            
            // Animação para os cards de propostas
            const propostaCards = document.querySelectorAll('.proposta-card');
            propostaCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, index * 100 + 400);
            });
            
            // Debug: Verificar informações das imagens
            const imagens = document.querySelectorAll('.proposta-image');
            imagens.forEach(img => {
                const original = img.getAttribute('data-original');
                console.log('Imagem original:', original, 'Src atual:', img.src);
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>