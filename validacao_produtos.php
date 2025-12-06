<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

if ($_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: index.php?error=acesso_negado');
    exit();
}

$usuario_id = $_SESSION['user_id'];
$propostas = [];
$error = '';
$success = '';

// Buscar propostas pendentes
try {
    $conn = getDBConnection();
    $stmtPropostas = $conn->prepare("
        SELECT 
            pp.*,
            u.nome as produtor_nome,
            u.email as produtor_email,
            u.celular as produtor_celular
        FROM produtos_propostos pp 
        INNER JOIN usuarios u ON pp.produtor_id = u.id 
        WHERE pp.status = 'pendente'
        ORDER BY pp.data_criacao ASC
    ");

    $stmtPropostas->execute();
    $propostas = $stmtPropostas->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            $error = 'Token CSRF inválido.';
        } else {
            $proposta_id = filter_input(INPUT_POST, 'proposta_id', FILTER_VALIDATE_INT);
            $acao = filter_input(INPUT_POST, 'acao', FILTER_SANITIZE_SPECIAL_CHARS);
            $preco_final = filter_input(INPUT_POST, 'preco_final', FILTER_VALIDATE_FLOAT);
            $observacoes_admin = filter_input(INPUT_POST, 'observacoes_admin', FILTER_SANITIZE_SPECIAL_CHARS);

            if (!$proposta_id || !$acao) {
                $error = 'Parâmetros inválidos.';
            } elseif ($acao === 'aprovar') {
                if (!$preco_final || $preco_final <= 0) {
                    $error = 'Preço final inválido. Deve ser um valor maior que zero.';
                } else {
                
                    $stmtProposta = $conn->prepare("SELECT * FROM produtos_propostos WHERE id = ? AND status = 'pendente'");
                    $stmtProposta->execute([$proposta_id]);
                    $proposta = $stmtProposta->fetch(PDO::FETCH_ASSOC);

                    if (!$proposta) {
                        $error = 'Proposta não encontrada ou já processada.';
                    } else {
                    
                        $novaImagemUrl = '';
                        if (!empty($proposta['imagem_url']) && $proposta['imagem_url'] !== 'sem-imagem.jpg') {
                            $origem = 'uploads/produtos/' . $proposta['imagem_url'];

                            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $fileExtension = strtolower(pathinfo($proposta['imagem_url'], PATHINFO_EXTENSION));

                            if (in_array($fileExtension, $allowedExtensions) && file_exists($origem)) {
                                $novoNomeArquivo = 'produto_' . uniqid() . '_' . time() . '.' . $fileExtension;
                                $destino = 'uploads/produtos/' . $novoNomeArquivo;

                                if (copy($origem, $destino)) {
                                    $novaImagemUrl = $novoNomeArquivo;

                                    unlink($origem);
                                } else {
                                    $novaImagemUrl = $proposta['imagem_url'];
                                }
                            }
                        }

                        $stmtInserir = $conn->prepare("
                            INSERT INTO produtos 
                            (nome, descricao, tipo, unidade_medida, preco, preco_custo, quantidade_estoque, imagem_url, disponivel, aprovado_por, data_aprovacao, data_criacao) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
                        ");

                        $stmtInserir->execute([
                            htmlspecialchars($proposta['nome']),
                            htmlspecialchars($proposta['descricao']),
                            $proposta['tipo'],
                            $proposta['unidade_medida'],
                            $preco_final,
                            $proposta['preco_sugerido'] ?? 0,
                            $proposta['quantidade_disponivel'] ?? 0,
                            $novaImagemUrl,
                            $usuario_id
                        ]);

                        $produto_id = $conn->lastInsertId();

                        // Atualizar status da proposta
                        $stmtAtualizar = $conn->prepare("
                            UPDATE produtos_propostos 
                            SET status = 'aprovado', 
                                data_avaliacao = NOW(),
                                avaliado_por = ?,
                                observacoes = ?
                            WHERE id = ?
                        ");
                        $stmtAtualizar->execute([$usuario_id, $observacoes_admin, $proposta_id]);

                        $_SESSION['success'] = "Produto aprovado e publicado com sucesso!";
                        header("Location: validacao_produtos.php");
                        exit();
                    }
                }
            } elseif ($acao === 'rejeitar') {
                $stmtProposta = $conn->prepare("SELECT * FROM produtos_propostos WHERE id = ? AND status = 'pendente'");
                $stmtProposta->execute([$proposta_id]);
                $proposta = $stmtProposta->fetch(PDO::FETCH_ASSOC);

                if (!$proposta) {
                    $error = 'Proposta não encontrada ou já processada.';
                } else {
                    // Verificar se tem imagem para excluir
                    if (!empty($proposta['imagem_url']) && $proposta['imagem_url'] !== 'sem-imagem.jpg') {
                        $caminhoImagem = 'uploads/propostas/' . $proposta['imagem_url'];

                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $fileExtension = strtolower(pathinfo($proposta['imagem_url'], PATHINFO_EXTENSION));

                        if (in_array($fileExtension, $allowedExtensions) && file_exists($caminhoImagem)) {
                            unlink($caminhoImagem);
                        }
                    }

                    $stmtRejeitar = $conn->prepare("
                        UPDATE produtos_propostos 
                        SET status = 'rejeitado', 
                            data_avaliacao = NOW(),
                            avaliado_por = ?,
                            observacoes = ?
                        WHERE id = ?
                    ");
                    $stmtRejeitar->execute([$usuario_id, $observacoes_admin, $proposta_id]);

                    $_SESSION['success'] = "Proposta rejeitada com sucesso!";
                    header("Location: validacao_produtos.php");
                    exit();
                }
            } else {
                $error = 'Ação inválida.';
            }
        }
    }

    try {
        $stmtStats = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) as total_pendentes,
                COUNT(CASE WHEN DATE(data_avaliacao) = CURDATE() AND status = 'aprovado' THEN 1 END) as aprovados_hoje,
                COUNT(CASE WHEN DATE(data_avaliacao) = CURDATE() AND status = 'rejeitado' THEN 1 END) as rejeitados_hoje
            FROM produtos_propostos 
            WHERE status IN ('pendente', 'aprovado', 'rejeitado')
        ");
        $stmtStats->execute();
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stats = ['total_pendentes' => 0, 'aprovados_hoje' => 0, 'rejeitados_hoje' => 0];
    }

} catch (PDOException $e) {
    error_log("Erro PDO ao carregar propostas: " . $e->getMessage());
    $error = "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erro geral ao carregar propostas: " . $e->getMessage());
    $error = $e->getMessage();
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Produtos - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .proposta-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .proposta-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .proposta-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .btn-aprovar {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
        }

        .btn-rejeitar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
        }

        .btn-aprovar:hover,
        .btn-rejeitar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .info-badge {
            background-color: var(--verde-claro);
            color: var(--verde-principal);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .produtor-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .price-comparison {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .modal-price-input {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }

        .section-title {
            color: var(--verde-principal);
            border-bottom: 2px solid var(--verde-claro);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .badge-status-pendente {
            background-color: #ffc107;
            color: #000;
        }

        .margin-beneficio {
            font-size: 0.85rem;
            color: #28a745;
            font-weight: bold;
        }

        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px;
            font-size: 0.8rem;
            text-align: center;
        }

        .admin-badge {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .unidade-badge {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;

            /* Botão de acessibilidade */
            .painel-flutuante {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
            }

            #btnAbrir {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                border: none;
                background-color: #0c7534;
                color: #fff;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                transition: all 0.2s;
            }

            #btnAbrir:hover {
                background-color: #0b7d44;
            }


            .painel-acessibilidade {
                display: none;
                flex-direction: column;
                gap: 6px;
                margin-bottom: 10px;
                background: #ffffff;
                color: rgb(11, 66, 5);
                padding: 12px 15px;
                border-radius: 12px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            }


            .painel-acessibilidade button {
                padding: 8px 12px;
                border-radius: 8px;
                border: none;
                cursor: pointer;
                font-weight: bold;
                font-size: 14px;
                transition: all 0.2s;
                background-color: #f0f0f0;
            }

            .painel-acessibilidade button:hover {
                background-color: #d4d4d4;
            }


            .modo-contraste,
            .modo-contraste * {
                background-color: #000 !important;
                color: #fff !important;
                border-color: #fff !important;
            }

            .modo-contraste a {
                color: #FFD700 !important;
                text-decoration: underline;
            }

            .modo-contraste img {
                filter: brightness(0.8) !important;
            }

            .modo-contraste,
            .modo-contraste * {
                background-color: #000 !important;
                color: #fff !important;
                border-color: #fff !important;
                fill: #fff !important;
                stroke: #fff !important;
            }

            .modo-contraste a,
            .modo-contraste a * {
                color: #FFD700 !important;
                text-decoration: underline !important;
            }

            .modo-contraste * {
                background-image: none !important;
            }

            
            .modo-contraste img,
            .modo-contraste [style*="background-image"] {
                filter: grayscale(1) brightness(0.4) !important;
            }

            .modo-contraste .card,
            .modo-contraste .container,
            .modo-contraste section,
            .modo-contraste .row,
            .modo-contraste .col,
            .modo-contraste footer,
            .modo-contraste header,
            .modo-contraste nav {
                background-color: #000 !important;
                color: #fff !important;
            }

            .modo-contraste button,
            .modo-contraste .btn {
                background-color: #222 !important;
                color: #fff !important;
                border: 1px solid #fff !important;
            }

            .modo-contraste .bi,
            .modo-contraste i {
                color: #fff !important;
            }

            .modo-contraste .carousel-item,
            .modo-contraste .carousel-caption {
                background-color: #000 !important;
            }

            @media (max-width: 768px) {
                .container img {
                    display: none !important;
                }
            }

            @media (max-width: 768px) {
                .texto {
                    width: 100% !important;
                }
            }

            @media (max-width: 768px) {
                .hero {
                    height: auto;
                }

                .hero-img {
                    width: 100%;
                    height: auto;
                    object-fit: contain;
                }
            }

            .modo-contraste img,
            .modo-contraste [style*="background-image"] {
                filter: grayscale(0.2) brightness(0.8) !important;
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

   <?php include_once 'includes/_acessibilidade.php'; ?>
    


    <?php include 'includes/_menu.php'; ?>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-clipboard-check"></i> Validação de Produtos
                    </h1>
                    <p class="lead mb-0">Analise e aprove as propostas de produtos dos produtores</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="admin-badge">
                        <i class="bi bi-shield-check"></i> Administrador
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary"><?php echo count($propostas); ?></h4>
                                <p class="text-muted mb-0">Propostas Pendentes</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success"><?php echo count($propostas); ?></h4>
                                <p class="text-muted mb-0">Aguardando Análise</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning"><?php echo $stats['aprovados_hoje'] ?? 0; ?></h4>
                                <p class="text-muted mb-0">Aprovados Hoje</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-danger"><?php echo $stats['rejeitados_hoje'] ?? 0; ?></h4>
                                <p class="text-muted mb-0">Rejeitados Hoje</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Propostas Pendentes de Análise</h3>

        <?php if (empty($propostas)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="bi bi-clipboard2-check display-1 text-muted"></i>
                    <h3 class="text-muted mt-3">Nenhuma proposta pendente</h3>
                    <p class="text-muted">Todas as propostas foram analisadas ou não há novas propostas.</p>
                    <a href="dashboard.php" class="btn btn-success mt-3">
                        <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($propostas as $proposta): ?>
                <div class="card proposta-card">
                    <div class="card-body">
                        <div class="row">
                        
                            <div class="col-md-4 mb-3">
                                <div class="image-container">
                                    <?php
                                    $imagemSrc = !empty($proposta['imagem_url']) && $proposta['imagem_url'] !== 'sem-imagem.jpg'
                                        ? 'uploads/produtos/' . htmlspecialchars($proposta['imagem_url'])
                                        : 'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                    ?>
                                    <img src="<?php echo $imagemSrc; ?>"
                                        alt="<?php echo htmlspecialchars($proposta['nome']); ?>" class="proposta-image"
                                        onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                                    <div class="image-overlay">
                                        Proposta #<?php echo $proposta['id']; ?>
                                    </div>
                                </div>

                                <div class="produtor-info">
                                    <h6 class="fw-bold mb-2">
                                        <i class="bi bi-person-circle"></i> Informações do Produtor
                                    </h6>
                                    <p class="mb-1">
                                        <strong>Nome:</strong> <?php echo htmlspecialchars($proposta['produtor_nome']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Email:</strong>
                                        <a href="mailto:<?php echo htmlspecialchars($proposta['produtor_email']); ?>"
                                            class="text-decoration-none">
                                            <?php echo htmlspecialchars($proposta['produtor_email']); ?>
                                        </a>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Celular:</strong>
                                        <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $proposta['produtor_celular']); ?>"
                                            target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($proposta['produtor_celular']); ?>
                                            <i class="bi bi-whatsapp text-success"></i>
                                        </a>
                                    </p>
                                </div>
                            </div>

                        
                            <div class="col-md-5">
                                <h4 class="card-title text-success">
                                    <?php echo htmlspecialchars($proposta['nome']); ?>
                                    <span class="badge bg-warning text-dark rounded-pill">Pendente</span>
                                </h4>

                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars($proposta['descricao'] ?? 'Sem descrição')); ?></p>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <span class="info-badge">
                                            <i class="bi bi-tag"></i>
                                            <?php echo htmlspecialchars($proposta['tipo'] ?? 'Sem tipo'); ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <span class="info-badge">
                                            <i class="bi bi-box-seam"></i>
                                            <?php echo number_format($proposta['quantidade_disponivel'] ?? 0, 0, ',', '.'); ?>
                                            <span
                                                class="unidade-badge"><?php echo $proposta['unidade_medida'] ?? 'KG'; ?></span>
                                        </span>
                                    </div>
                                </div>

                        
                                <div class="price-comparison">
                                    <h6 class="fw-bold mb-2">
                                        <i class="bi bi-cash-coin"></i> Análise de Preços
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Preço do Produtor</small>
                                            <p class="h5 text-warning mb-1">
                                                R$ <?php echo number_format($proposta['preco_sugerido'] ?? 0, 2, ',', '.'); ?>
                                            </p>
                                            <small class="text-muted">(Custo)</small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Preço de Venda Sugerido</small>
                                            <p class="h5 text-success mb-1" id="preco-final-<?php echo $proposta['id']; ?>">
                                                R$
                                                <?php echo number_format(($proposta['preco_sugerido'] ?? 0) * 1.2, 2, ',', '.'); ?>
                                            </p>
                                            <small class="margin-beneficio">
                                                +20% de margem (R$
                                                <?php echo number_format(($proposta['preco_sugerido'] ?? 0) * 0.2, 2, ',', '.'); ?>)
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($proposta['observacoes'])): ?>
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <strong><i class="bi bi-chat-left-text"></i> Observações do Produtor:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($proposta['observacoes'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i>
                                        Proposta enviada em <?php
                                        $data = new DateTime($proposta['data_criacao']);
                                        echo $data->format('d/m/Y \à\s H:i');
                                        ?>
                                    </small>
                                </div>
                            </div>

    
                            <div class="col-md-3">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-aprovar btn-lg" data-bs-toggle="modal"
                                        data-bs-target="#modalAprovar" data-proposta-id="<?php echo $proposta['id']; ?>"
                                        data-proposta-nome="<?php echo htmlspecialchars($proposta['nome']); ?>"
                                        data-preco-sugerido="<?php echo $proposta['preco_sugerido'] ?? 0; ?>">
                                        <i class="bi bi-check-lg"></i> Aprovar Produto
                                    </button>

                                    <button type="button" class="btn btn-rejeitar btn-lg" data-bs-toggle="modal"
                                        data-bs-target="#modalRejeitar" data-proposta-id="<?php echo $proposta['id']; ?>"
                                        data-proposta-nome="<?php echo htmlspecialchars($proposta['nome']); ?>">
                                        <i class="bi bi-x-lg"></i> Rejeitar Proposta
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                                        data-bs-target="#modalDetalhes" data-proposta-id="<?php echo $proposta['id']; ?>"
                                        data-proposta-nome="<?php echo htmlspecialchars($proposta['nome']); ?>"
                                        data-proposta-descricao="<?php echo htmlspecialchars($proposta['descricao'] ?? 'Sem descrição'); ?>"
                                        data-proposta-tipo="<?php echo htmlspecialchars($proposta['tipo'] ?? 'Sem tipo'); ?>"
                                        data-proposta-unidade="<?php echo $proposta['unidade_medida'] ?? 'KG'; ?>"
                                        data-proposta-quantidade="<?php echo $proposta['quantidade_disponivel'] ?? 0; ?>"
                                        data-proposta-preco="<?php echo number_format($proposta['preco_sugerido'] ?? 0, 2, ',', '.'); ?>"
                                        data-proposta-obs="<?php echo htmlspecialchars($proposta['observacoes'] ?? ''); ?>"
                                        data-proposta-data="<?php
                                        $data = new DateTime($proposta['data_criacao']);
                                        echo $data->format('d/m/Y \à\s H:i');
                                        ?>">
                                        <i class="bi bi-info-circle"></i> Mais Detalhes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <div class="modal fade" id="modalAprovar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Aprovar Produto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formAprovar">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="proposta_id" id="aprovar-proposta-id">
                    <input type="hidden" name="acao" value="aprovar">

                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Ao aprovar, o produto será publicado na loja e o produtor será notificado.
                        </div>

                        <p>Produto: <strong id="aprovar-produto-nome"></strong></p>

                        <div class="mb-3">
                            <label for="preco_final" class="form-label fw-bold">
                                Preço Final de Venda (R$)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" min="0.01" max="999999.99"
                                    class="form-control modal-price-input" id="preco_final" name="preco_final" required
                                    placeholder="0,00">
                            </div>
                            <div class="form-text">
                                Preço sugerido pelo produtor: R$ <span id="preco-sugerido"></span><br>
                                Margem sugerida (20%): R$ <span id="margem-sugerida"></span><br>
                                <strong>Preço sugerido com margem: R$ <span id="preco-com-margem"></span></strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes_admin" class="form-label">
                                <i class="bi bi-chat-left-text"></i> Observações (Opcional)
                            </label>
                            <textarea class="form-control" id="observacoes_admin" name="observacoes_admin" rows="3"
                                placeholder="Observações para o produtor... (opcional)" maxlength="500"></textarea>
                            <div class="form-text">
                                Máximo 500 caracteres. Restam: <span id="contador-caracteres">500</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Confirmar Aprovação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRejeitar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle"></i> Rejeitar Proposta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formRejeitar">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="proposta_id" id="rejeitar-proposta-id">
                    <input type="hidden" name="acao" value="rejeitar">

                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Ao rejeitar, o produtor será notificado e poderá ajustar a proposta.
                        </div>

                        <p>Proposta: <strong id="rejeitar-produto-nome"></strong></p>

                        <div class="mb-3">
                            <label for="observacoes_rejeicao" class="form-label fw-bold">
                                <i class="bi bi-chat-left-text"></i> Motivo da Rejeição *
                            </label>
                            <textarea class="form-control" id="observacoes_rejeicao" name="observacoes_admin" rows="4"
                                required
                                placeholder="Explique claramente o motivo da rejeição para que o produtor possa fazer os ajustes necessários..."
                                maxlength="500"></textarea>
                            <div class="form-text">
                                Este campo é obrigatório. O produtor receberá esta justificativa.
                                Máximo 500 caracteres. Restam: <span id="contador-caracteres-rejeicao">500</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg"></i> Confirmar Rejeição
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle"></i> Detalhes da Proposta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h4 id="detalhes-produto-nome" class="text-success mb-3"></h4>

                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-card-text"></i> Descrição</h6>
                            <p id="detalhes-descricao" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-tags"></i> Informações</h6>
                            <ul class="list-unstyled">
                                <li><strong>Tipo:</strong> <span id="detalhes-tipo"></span></li>
                                <li><strong>Unidade:</strong> <span id="detalhes-unidade"></span></li>
                                <li><strong>Quantidade:</strong> <span id="detalhes-quantidade"></span></li>
                                <li><strong>Preço Sugerido:</strong> R$ <span id="detalhes-preco"></span></li>
                                <li><strong>Data da Proposta:</strong> <span id="detalhes-data"></span></li>
                            </ul>
                        </div>
                    </div>

                    <div id="detalhes-observacoes-container" class="mt-3" style="display: none;">
                        <h6><i class="bi bi-chat-left-text"></i> Observações do Produtor</h6>
                        <div class="p-3 bg-light rounded">
                            <p id="detalhes-observacoes" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalAprovar = document.getElementById('modalAprovar');
            if (modalAprovar) {
                modalAprovar.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const propostaId = button.getAttribute('data-proposta-id');
                    const produtoNome = button.getAttribute('data-proposta-nome');
                    const precoSugerido = parseFloat(button.getAttribute('data-preco-sugerido')) || 0;

                    document.getElementById('aprovar-proposta-id').value = propostaId;
                    document.getElementById('aprovar-produto-nome').textContent = produtoNome;

            
                    document.getElementById('preco-sugerido').textContent =
                        precoSugerido.toFixed(2).replace('.', ',');

                    const margem = precoSugerido * 0.2;
                    document.getElementById('margem-sugerida').textContent =
                        margem.toFixed(2).replace('.', ',');

                    const precoComMargem = precoSugerido * 1.2;
                    document.getElementById('preco-com-margem').textContent =
                        precoComMargem.toFixed(2).replace('.', ',');

                    document.getElementById('preco_final').value = precoComMargem.toFixed(2);

                    document.getElementById('observacoes_admin').value = '';
                    atualizarContador('observacoes_admin', 'contador-caracteres');
                });
            }

            const modalRejeitar = document.getElementById('modalRejeitar');
            if (modalRejeitar) {
                modalRejeitar.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const propostaId = button.getAttribute('data-proposta-id');
                    const produtoNome = button.getAttribute('data-proposta-nome');

                    document.getElementById('rejeitar-proposta-id').value = propostaId;
                    document.getElementById('rejeitar-produto-nome').textContent = produtoNome;

                    document.getElementById('observacoes_rejeicao').value = '';
                    atualizarContador('observacoes_rejeicao', 'contador-caracteres-rejeicao');
                });
            }

            const modalDetalhes = document.getElementById('modalDetalhes');
            if (modalDetalhes) {
                modalDetalhes.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;

                    document.getElementById('detalhes-produto-nome').textContent =
                        button.getAttribute('data-proposta-nome');
                    document.getElementById('detalhes-descricao').textContent =
                        button.getAttribute('data-proposta-descricao');
                    document.getElementById('detalhes-tipo').textContent =
                        button.getAttribute('data-proposta-tipo');
                    document.getElementById('detalhes-unidade').textContent =
                        button.getAttribute('data-proposta-unidade');
                    document.getElementById('detalhes-quantidade').textContent =
                        button.getAttribute('data-proposta-quantidade');
                    document.getElementById('detalhes-preco').textContent =
                        button.getAttribute('data-proposta-preco');
                    document.getElementById('detalhes-data').textContent =
                        button.getAttribute('data-proposta-data');

                    const observacoes = button.getAttribute('data-proposta-obs');
                    if (observacoes && observacoes.trim() !== '') {
                        document.getElementById('detalhes-observacoes').textContent = observacoes;
                        document.getElementById('detalhes-observacoes-container').style.display = 'block';
                    } else {
                        document.getElementById('detalhes-observacoes-container').style.display = 'none';
                    }
                });
            }

            function atualizarContador(textareaId, contadorId) {
                const textarea = document.getElementById(textareaId);
                const contador = document.getElementById(contadorId);

                if (textarea && contador) {
                    textarea.addEventListener('input', function () {
                        const maxLength = parseInt(this.getAttribute('maxlength')) || 500;
                        const currentLength = this.value.length;
                        contador.textContent = maxLength - currentLength;

                        if (currentLength > maxLength) {
                            this.value = this.value.substring(0, maxLength);
                            contador.textContent = 0;
                        }
                    });

                    const maxLength = parseInt(textarea.getAttribute('maxlength')) || 500;
                    contador.textContent = maxLength - textarea.value.length;
                }
            }

            atualizarContador('observacoes_admin', 'contador-caracteres');
            atualizarContador('observacoes_rejeicao', 'contador-caracteres-rejeicao');

            const formAprovar = document.getElementById('formAprovar');
            if (formAprovar) {
                formAprovar.addEventListener('submit', function (e) {
                    const precoFinal = document.getElementById('preco_final');
                    if (!precoFinal.value || parseFloat(precoFinal.value) <= 0) {
                        e.preventDefault();
                        alert('Por favor, insira um preço válido maior que zero.');
                        precoFinal.focus();
                    }
                });
            }

            const formRejeitar = document.getElementById('formRejeitar');
            if (formRejeitar) {
                formRejeitar.addEventListener('submit', function (e) {
                    const observacoes = document.getElementById('observacoes_rejeicao');
                    if (!observacoes.value.trim()) {
                        e.preventDefault();
                        alert('Por favor, informe o motivo da rejeição.');
                        observacoes.focus();
                    }
                });
            }

            const precoInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
            precoInputs.forEach(input => {
                input.addEventListener('blur', function () {
                    if (this.value) {
                        let value = parseFloat(this.value);
                        if (value < 0.01) value = 0.01;
                        if (value > 999999.99) value = 999999.99;
                        this.value = value.toFixed(2);
                    }
                });

                input.addEventListener('input', function () {
                    let value = parseFloat(this.value);
                    if (value > 999999.99) {
                        this.value = 999999.99;
                    }
                });
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>

</html>