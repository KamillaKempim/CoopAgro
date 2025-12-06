<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = intval($_SESSION['user_id']);

try {
    $conn = getDBConnection();

    $stmtUser = $conn->prepare("SELECT tipo_usuario, nome FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($usuario['tipo_usuario'] !== 'Produtor') {
        $_SESSION['error'] = 'Acesso restrito a produtores.';
        header("Location: perfil.php");
        exit();
    }

    $stmtProdutos = $conn->prepare("
        SELECT * 
        FROM produtos 
        WHERE criado_por = ? 
        ORDER BY data_criacao DESC
    ");
    $stmtProdutos->execute([$usuario_id]);
    $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erro ao carregar produtos: " . htmlspecialchars($e->getMessage());
    logSecurity($usuario_id, 'erro_meus_produtos', "Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Produtos - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }

        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
        }

        .badge-disponivel {
            background-color: var(--verde-principal);
            color: white;
        }

        .badge-indisponivel {
            background-color: #6c757d;
            color: white;
        }

        .unidade-badge {
            background-color: #6c757d;
            color: white;
            font-size: 0.7rem;
        }

        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .btn-success:hover {
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
        }

        .btn-outline-success {
            color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .btn-outline-success:hover {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
            color: white;
        }

        .empty-state {
            padding: 4rem 1rem;
        }

        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="bi bi-box-seam"></i> Meus Produtos
            </h2>
            <a href="adicionar_produto.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Adicionar Produto
            </a>
        </div>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($produtos)): ?>
            <div class="alert alert-info text-center empty-state">
                <div class="mb-3">
                    <i class="bi bi-box display-1 text-muted"></i>
                </div>
                <h4>Nenhum produto cadastrado</h4>
                <p class="mb-3">Comece cadastrando seu primeiro produto.</p>
                <a href="adicionar_produto.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Cadastrar Primeiro Produto
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($produtos as $produto):
                    $imagemSrc = !empty($produto['imagem_url']) ?
                        'uploads/produtos/' . htmlspecialchars($produto['imagem_url']) :
                        'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';

                    $unidade_texto = htmlspecialchars($produto['unidade_medida'] ?? 'KG');
                    $quantidade_texto = $produto['quantidade_estoque'] . ' ' . $unidade_texto;
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card product-card h-100">
                            <img src="<?php echo htmlspecialchars($imagemSrc); ?>" class="card-img-top product-image"
                                alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                    <span
                                        class="badge <?php echo $produto['disponivel'] ? 'badge-disponivel' : 'badge-indisponivel'; ?>">
                                        <?php echo $produto['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                                    </span>
                                </div>

                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars($produto['descricao']); ?></p>

                                <div class="mt-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($produto['tipo']); ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="badge unidade-badge">
                                                <i class="bi bi-rulers"></i> <?php echo $unidade_texto; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Preço:</span>
                                        <strong class="text-success">R$
                                            <?php echo number_format($produto['preco'], 2, ',', '.'); ?>/<?php echo $unidade_texto; ?></strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Estoque:</span>
                                        <strong><?php echo $quantidade_texto; ?></strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Cadastrado em:</span>
                                        <small
                                            class="text-muted"><?php echo date('d/m/Y', strtotime($produto['data_criacao'])); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="editar_produto.php?id=<?php echo intval($produto['id']); ?>"
                                        class="btn btn-outline-success btn-sm me-md-1">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="excluir_produto.php?id=<?php echo intval($produto['id']); ?>"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirmarExclusao(<?php echo intval($produto['id']); ?>, '<?php echo htmlspecialchars(addslashes($produto['nome'])); ?>')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Estatísticas dos Meus Produtos</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="display-6 text-success"><?php echo count($produtos); ?></div>
                            <small class="text-muted">Total de Produtos</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-6 text-success">
                                <?php echo array_reduce($produtos, function ($carry, $produto) {
                                    return $carry + $produto['quantidade_estoque'];
                                }, 0); ?>
                            </div>
                            <small class="text-muted">Unidades em Estoque</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-6 text-success">
                                <?php echo count(array_filter($produtos, function ($produto) {
                                    return $produto['disponivel'] == 1;
                                })); ?>
                            </div>
                            <small class="text-muted">Produtos Disponíveis</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="display-6 text-success">
                                R$ <?php echo number_format(array_reduce($produtos, function ($carry, $produto) {
                                    return $carry + ($produto['preco'] * $produto['quantidade_estoque']);
                                }, 0), 2, ',', '.'); ?>
                            </div>
                            <small class="text-muted">Valor Total em Estoque</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <script>
        function confirmarExclusao(id, nome) {
            return confirm(`Tem certeza que deseja excluir o produto "${nome}"?\n\nEsta ação não pode ser desfeita.`);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>

</html>