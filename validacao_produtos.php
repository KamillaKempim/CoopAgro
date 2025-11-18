<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verificar se o usuário tem permissão de administrador
$usuario_id = $_SESSION['user_id'];

// Buscar propostas pendentes
try {
    $conn = getDBConnection();
    
    // Buscar todas as propostas com informações do produtor
    $stmtPropostas = $conn->prepare("
        SELECT 
            pp.*,
            u.nome as produtor_nome,
            u.email as produtor_email,
            u.celular as produtor_celular,
            DATE_FORMAT(pp.data_criacao, '%d/%m/%Y às %H:%i') as data_criacao_formatada
        FROM produtos_propostos pp 
        INNER JOIN usuarios u ON pp.produtor_id = u.id 
        WHERE pp.status = 'pendente'
        ORDER BY pp.data_criacao ASC
    ");
    $stmtPropostas->execute();
    $propostas = $stmtPropostas->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar ações de aprovação/rejeição
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            die('Token CSRF inválido.');
        }
        
        $proposta_id = $_POST['proposta_id'];
        $acao = $_POST['acao'];
        $preco_final = $_POST['preco_final'] ?? null;
        $observacoes_admin = $_POST['observacoes_admin'] ?? '';
        
        if ($acao === 'aprovar' && $preco_final) {
            // Buscar dados da proposta
            $stmtProposta = $conn->prepare("SELECT * FROM produtos_propostos WHERE id = ?");
            $stmtProposta->execute([$proposta_id]);
            $proposta = $stmtProposta->fetch(PDO::FETCH_ASSOC);
            
            if ($proposta) {
                // Mover a imagem do diretório de propostas para produtos
                $novaImagemUrl = '';
                if (!empty($proposta['imagem_url'])) {
                    $origem = 'uploads/propostas/' . $proposta['imagem_url'];
                    
                    // Gerar novo nome para a imagem (trocar prefixo "proposta" por "produto")
                    $nomeArquivo = $proposta['imagem_url'];
                    $novoNomeArquivo = preg_replace('/^proposta_/', 'produto_', $nomeArquivo);
                    
                    $destino = 'uploads/produtos/' . $novoNomeArquivo;
                    
                    if (file_exists($origem)) {
                        // Copiar a imagem para o diretório de produtos com novo nome
                        if (copy($origem, $destino)) {
                            $novaImagemUrl = $novoNomeArquivo;
                            
                            // Excluir a imagem original do diretório de propostas
                            if (file_exists($origem)) {
                                unlink($origem);
                            }
                        } else {
                            // Se não conseguir copiar, usar a imagem original
                            $novaImagemUrl = $proposta['imagem_url'];
                        }
                    } else {
                        // Se a imagem não existir, usar placeholder
                        $novaImagemUrl = '';
                    }
                }
                
                // Inserir na tabela de produtos
                $stmtInserir = $conn->prepare("
                    INSERT INTO produtos 
                    (nome, descricao, tipo, preco, preco_custo, quantidade_estoque, imagem_url, disponivel) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                
                $stmtInserir->execute([
                    $proposta['nome'],
                    $proposta['descricao'],
                    $proposta['tipo'],
                    $preco_final,
                    $proposta['preco_sugerido'], // preco_custo = preco_sugerido
                    $proposta['quantidade_disponivel'],
                    $novaImagemUrl
                ]);
                
                // Atualizar status da proposta
                $stmtAtualizar = $conn->prepare("
                    UPDATE produtos_propostos 
                    SET status = 'aprovado', 
                        data_avaliacao = NOW(),
                        observacoes = ?
                    WHERE id = ?
                ");
                $stmtAtualizar->execute([$observacoes_admin, $proposta_id]);
                
                $success = "Produto aprovado e publicado com sucesso!";
            }
            
        } elseif ($acao === 'rejeitar') {
            // Buscar dados da proposta para excluir a imagem
            $stmtProposta = $conn->prepare("SELECT * FROM produtos_propostos WHERE id = ?");
            $stmtProposta->execute([$proposta_id]);
            $proposta = $stmtProposta->fetch(PDO::FETCH_ASSOC);
            
            if ($proposta && !empty($proposta['imagem_url'])) {
                $caminhoImagem = 'uploads/propostas/' . $proposta['imagem_url'];
                
                // Excluir a imagem da proposta rejeitada
                if (file_exists($caminhoImagem)) {
                    unlink($caminhoImagem);
                }
            }
            
            // Atualizar status para rejeitado
            $stmtRejeitar = $conn->prepare("
                UPDATE produtos_propostos 
                SET status = 'rejeitado', 
                    data_avaliacao = NOW(),
                    observacoes = ?
                WHERE id = ?
            ");
            $stmtRejeitar->execute([$observacoes_admin, $proposta_id]);
            
            $success = "Proposta rejeitada com sucesso!";
        }
        
        // Recarregar a página para atualizar a lista
        header("Location: validacao_produtos.php");
        exit();
    }
    
} catch(PDOException $e) {
    $error = "Erro ao carregar propostas: " . $e->getMessage();
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .proposta-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
        
        .btn-aprovar:hover, .btn-rejeitar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        }
        
        .price-comparison {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 10px;
            padding: 15px;
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
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-clipboard-check"></i> Validação de Produtos
                    </h1>
                    <p class="lead mb-0">Analise e aprove as propostas de produtos dos produtores</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-shield-check" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
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
                                <h4 class="text-warning">0</h4>
                                <p class="text-muted mb-0">Aprovados Hoje</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-danger">0</h4>
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
                    <p class="text-muted">Todas as propostas foram analisadas.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($propostas as $proposta): ?>
                <div class="card proposta-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Imagem e Informações Básicas -->
                            <div class="col-md-4 mb-3">
                                <?php 
                                $imagemSrc = !empty($proposta['imagem_url']) ? 
                                    'uploads/propostas/' . htmlspecialchars($proposta['imagem_url']) : 
                                    'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                ?>
                                <img src="<?php echo $imagemSrc; ?>" 
                                     alt="<?php echo htmlspecialchars($proposta['nome']); ?>"
                                     class="proposta-image mb-3"
                                     onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                                
                                <div class="produtor-info">
                                    <h6 class="fw-bold mb-2">Informações do Produtor</h6>
                                    <p class="mb-1"><strong>Nome:</strong> <?php echo htmlspecialchars($proposta['produtor_nome']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($proposta['produtor_email']); ?></p>
                                    <p class="mb-0"><strong>Celular:</strong> <?php echo htmlspecialchars($proposta['produtor_celular']); ?></p>
                                </div>
                            </div>

                            <!-- Detalhes do Produto -->
                            <div class="col-md-5">
                                <h4 class="card-title text-success"><?php echo htmlspecialchars($proposta['nome']); ?></h4>
                                
                                <p class="card-text"><?php echo htmlspecialchars($proposta['descricao']); ?></p>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <span class="info-badge">
                                            <i class="bi bi-tag"></i> 
                                            <?php echo htmlspecialchars($proposta['tipo']); ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <span class="info-badge">
                                            <i class="bi bi-box-seam"></i> 
                                            <?php echo $proposta['quantidade_disponivel']; ?> unidades
                                        </span>
                                    </div>
                                </div>

                                <!-- Comparação de Preços -->
                                <div class="price-comparison">
                                    <h6 class="fw-bold mb-2">Análise de Preços</h6>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Sugerido pelo Produtor</small>
                                            <p class="h5 text-warning mb-1">
                                                R$ <?php echo number_format($proposta['preco_sugerido'], 2, ',', '.'); ?>
                                            </p>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Preço Final Sugerido</small>
                                            <p class="h5 text-success mb-1" id="preco-final-<?php echo $proposta['id']; ?>">
                                                R$ <?php echo number_format($proposta['preco_sugerido'] * 1.2, 2, ',', '.'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($proposta['observacoes'])): ?>
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>Observações do Produtor:</strong> 
                                            <?php echo htmlspecialchars($proposta['observacoes']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        Proposta enviada em <?php echo $proposta['data_criacao_formatada']; ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="col-md-3">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-aprovar btn-lg" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalAprovar"
                                            data-proposta-id="<?php echo $proposta['id']; ?>"
                                            data-proposta-nome="<?php echo htmlspecialchars($proposta['nome']); ?>"
                                            data-preco-sugerido="<?php echo $proposta['preco_sugerido']; ?>">
                                        <i class="bi bi-check-lg"></i> Aprovar
                                    </button>
                                    
                                    <button type="button" class="btn btn-rejeitar btn-lg"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalRejeitar"
                                            data-proposta-id="<?php echo $proposta['id']; ?>"
                                            data-proposta-nome="<?php echo htmlspecialchars($proposta['nome']); ?>">
                                        <i class="bi bi-x-lg"></i> Rejeitar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal para Aprovar -->
    <div class="modal fade" id="modalAprovar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Aprovar Produto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="proposta_id" id="aprovar-proposta-id">
                    <input type="hidden" name="acao" value="aprovar">
                    
                    <div class="modal-body">
                        <p>Você está prestes a aprovar o produto: <strong id="aprovar-produto-nome"></strong></p>
                        
                        <div class="mb-3">
                            <label for="preco_final" class="form-label">Preço Final de Venda (R$)</label>
                            <input type="number" step="0.01" class="form-control modal-price-input" 
                                   id="preco_final" name="preco_final" required
                                   placeholder="0,00">
                            <div class="form-text">
                                Preço sugerido: R$ <span id="preco-sugerido"></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_admin" class="form-label">Observações (Opcional)</label>
                            <textarea class="form-control" id="observacoes_admin" name="observacoes_admin" 
                                      rows="3" placeholder="Observações para o produtor..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Rejeitar -->
    <div class="modal fade" id="modalRejeitar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Rejeitar Proposta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="proposta_id" id="rejeitar-proposta-id">
                    <input type="hidden" name="acao" value="rejeitar">
                    
                    <div class="modal-body">
                        <p>Você está prestes a rejeitar a proposta: <strong id="rejeitar-produto-nome"></strong></p>
                        
                        <div class="mb-3">
                            <label for="observacoes_rejeicao" class="form-label">Motivo da Rejeição</label>
                            <textarea class="form-control" id="observacoes_rejeicao" name="observacoes_admin" 
                                      rows="4" required placeholder="Explique o motivo da rejeição para o produtor..."></textarea>
                            <div class="form-text">Esta observação será enviada ao produtor.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal de Aprovação
            const modalAprovar = document.getElementById('modalAprovar');
            modalAprovar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const propostaId = button.getAttribute('data-proposta-id');
                const produtoNome = button.getAttribute('data-proposta-nome');
                const precoSugerido = button.getAttribute('data-preco-sugerido');
                
                document.getElementById('aprovar-proposta-id').value = propostaId;
                document.getElementById('aprovar-produto-nome').textContent = produtoNome;
                document.getElementById('preco-sugerido').textContent = 
                    parseFloat(precoSugerido).toFixed(2).replace('.', ',');
                
                // Sugerir preço com margem
                const precoFinal = (parseFloat(precoSugerido) * 1.2).toFixed(2);
                document.getElementById('preco_final').value = precoFinal;
            });
            
            // Modal de Rejeição
            const modalRejeitar = document.getElementById('modalRejeitar');
            modalRejeitar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const propostaId = button.getAttribute('data-proposta-id');
                const produtoNome = button.getAttribute('data-proposta-nome');
                
                document.getElementById('rejeitar-proposta-id').value = propostaId;
                document.getElementById('rejeitar-produto-nome').textContent = produtoNome;
            });
            
            // Formatação automática do preço
            const precoInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
            precoInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value) {
                        this.value = parseFloat(this.value).toFixed(2);
                    }
                });
            });
        });
    </script>
</body>
</html>