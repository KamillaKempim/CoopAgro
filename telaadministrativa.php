<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Processar exclusão se o ID foi passado
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $produto_id = $_GET['excluir'];
    
    try {
        $conn = getDBConnection();
        
        // Verificar se existem pedidos pendentes para este produto
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_pedidos 
            FROM pedidos 
            WHERE produto_id = ? 
            AND status IN ('pendente', 'confirmado', 'preparando', 'enviado')
        ");
        $stmt->execute([$produto_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_pedidos'] > 0) {
            // Existem pedidos pendentes, mostrar erro
            $_SESSION['erro_exclusao'] = "Não é possível excluir o produto. Existem " . $result['total_pedidos'] . " pedido(s) pendente(s) associado(s) a este produto.";
            header("Location: produtos.php");
            exit();
        } else {
            // Não há pedidos pendentes, pode excluir
            $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt->execute([$produto_id]);
            
            $_SESSION['sucesso_exclusao'] = "Produto excluído com sucesso!";
            header("Location: produtos.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['erro_exclusao'] = "Erro ao excluir produto: " . htmlspecialchars($e->getMessage());
        header("Location: produtos.php");
        exit();
    }
}

// Verificar mensagens da sessão
$erro_exclusao = $_SESSION['erro_exclusao'] ?? '';
$sucesso_exclusao = $_SESSION['sucesso_exclusao'] ?? '';
unset($_SESSION['erro_exclusao'], $_SESSION['sucesso_exclusao']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultura Familiar - Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .card {
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .tipo-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .estoque-baixo {
            border-left: 4px solid #dc3545;
        }

        .estoque-normal {
            border-left: 4px solid #198754;
        }

        .card-indisponivel {
            opacity: 0.7;
            filter: grayscale(0.3);
        }

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

        .modo-contraste img,
        .modo-contraste [style*="background-image"] {
            filter: grayscale(0.2) brightness(0.8) !important;
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Produtos Disponíveis</h2>
                    <a href="adicionar_produto.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Adicionar Novo Produto
                    </a>
                </div>

                <!-- Mensagem de erro de exclusão -->
                <?php if (!empty($erro_exclusao)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Não foi possível excluir o produto!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($erro_exclusao); ?></p>
                                <small class="text-muted">Você pode tentar desativar o produto em vez de excluí-lo.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Mensagem de sucesso de exclusão -->
                <?php if (!empty($sucesso_exclusao)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Sucesso!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($sucesso_exclusao); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <span>Produto adicionado com sucesso!</span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <span>Produto atualizado com sucesso!</span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php
                    try {
                        $conn = getDBConnection();
                        $stmt = $conn->prepare("SELECT * FROM produtos WHERE disponivel = 1 ORDER BY nome");
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $estoqueClasse = $row['quantidade_estoque'] <= 5 ? 'estoque-baixo' : 'estoque-normal';
                                $imagemSrc = !empty($row['imagem_url']) ?
                                    'uploads/produtos/' . htmlspecialchars($row['imagem_url']) :
                                    'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                
                                // Verificar se produto tem pedidos pendentes
                                $stmt_pedidos = $conn->prepare("
                                    SELECT COUNT(*) as pedidos_pendentes 
                                    FROM pedidos 
                                    WHERE produto_id = ? 
                                    AND status IN ('pendente', 'confirmado', 'preparando', 'enviado')
                                ");
                                $stmt_pedidos->execute([$row['id']]);
                                $pedidos_info = $stmt_pedidos->fetch(PDO::FETCH_ASSOC);
                                $tem_pedidos_pendentes = $pedidos_info['pedidos_pendentes'] > 0;
                                
                                echo '<div class="col-md-4 mb-4">';
                                echo '  <div class="card h-100 ' . $estoqueClasse . ($tem_pedidos_pendentes ? ' border-warning' : '') . '">';
                                echo '    <div class="position-relative">';
                                echo '      <img src="' . $imagemSrc . '" class="card-img-top product-image" alt="' . htmlspecialchars($row['nome']) . '" onerror="this.src=\'https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada\'">';
                                echo '      <span class="badge bg-secondary tipo-badge">' . htmlspecialchars($row['tipo']) . '</span>';
                                
                                // Badge de pedidos pendentes
                                if ($tem_pedidos_pendentes) {
                                    echo '      <span class="badge bg-warning position-absolute top-0 start-0 m-2" title="Produto possui pedidos pendentes">';
                                    echo '        <i class="bi bi-exclamation-triangle me-1"></i>';
                                    echo '        ' . $pedidos_info['pedidos_pendentes'] . ' pedido(s)';
                                    echo '      </span>';
                                }
                                
                                if ($row['quantidade_estoque'] <= 5) {
                                    echo '      <span class="badge bg-danger position-absolute ' . ($tem_pedidos_pendentes ? 'top-0 end-0' : 'top-0 start-0') . ' m-2">Estoque Baixo</span>';
                                }
                                
                                echo '    </div>';
                                echo '    <div class="card-body d-flex flex-column">';
                                echo '      <h5 class="card-title">' . htmlspecialchars($row['nome']) . '</h5>';
                                echo '      <p class="card-text flex-grow-1">' . htmlspecialchars($row['descricao']) . '</p>';
                                echo '      <div class="mt-auto">';
                                echo '        <p class="card-text"><strong class="fs-5 text-success">R$ ' . number_format($row['preco'], 2, ',', '.') . '</strong></p>';
                                echo '        <p class="card-text">';
                                echo '          <span class="badge ' . ($row['quantidade_estoque'] <= 5 ? 'bg-warning' : 'bg-info') . '">';
                                echo '            Estoque: ' . htmlspecialchars($row['quantidade_estoque']) . ' ' . htmlspecialchars($row['unidade_medida']);
                                echo '          </span>';
                                echo '        </p>';
                                echo '        <small class="text-muted">';
                                echo '          Cadastrado em: ' . date('d/m/Y', strtotime($row['data_criacao']));
                                echo '        </small>';
                                
                                // Aviso sobre pedidos pendentes
                                if ($tem_pedidos_pendentes) {
                                    echo '        <div class="alert alert-warning mt-2 p-2 small mb-0">';
                                    echo '          <i class="bi bi-info-circle me-1"></i>';
                                    echo '          Este produto possui ' . $pedidos_info['pedidos_pendentes'] . ' pedido(s) pendente(s)';
                                    echo '        </div>';
                                }
                                
                                echo '      </div>';
                                echo '    </div>';
                                echo '    <div class="card-footer bg-white d-flex justify-content-between">';
                                echo '      <a href="editar_produto.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm">';
                                echo '        <i class="bi bi-pencil"></i> Editar';
                                echo '      </a>';
                                
                                // Botão de exclusão com validação JavaScript
                                echo '      <button type="button" class="btn btn-danger btn-sm" onclick="confirmarExclusao(' . $row['id'] . ', ' . ($tem_pedidos_pendentes ? 'true' : 'false') . ', ' . $pedidos_info['pedidos_pendentes'] . ')">';
                                echo '        <i class="bi bi-trash"></i> Excluir';
                                echo '      </button>';
                                echo '    </div>';
                                echo '  </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12">';
                            echo '  <div class="text-center py-5">';
                            echo '    <i class="bi bi-inbox display-1 text-muted"></i>';
                            echo '    <h3 class="text-muted mt-3">Nenhum produto cadastrado</h3>';
                            echo '    <p class="text-muted">Comece adicionando seu primeiro produto ao sistema.</p>';
                            echo '    <a href="adicionar_produto.php" class="btn btn-success btn-lg">';
                            echo '      <i class="bi bi-plus-circle"></i> Adicionar Primeiro Produto';
                            echo '    </a>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12">';
                        echo '  <div class="alert alert-danger">';
                        echo '    <h5>Erro ao carregar produtos</h5>';
                        echo '    <p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '  </div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 8000); // Aumentado para 8 segundos para dar tempo de ler
            });
        });

        function confirmarExclusao(produtoId, temPedidosPendentes, quantidadePedidos) {
            if (temPedidosPendentes) {
                // Mostrar modal de erro
                const modalHtml = `
                    <div class="modal fade" id="modalErroExclusao" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Não é possível excluir este produto
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-cart-x display-1 text-warning"></i>
                                    </div>
                                    <p>Este produto possui <strong class="text-warning">${quantidadePedidos} pedido(s) pendente(s)</strong> associado(s).</p>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Para excluir este produto, você precisa primeiro:
                                        <ul class="mt-2 mb-0">
                                            <li>Cancelar todos os pedidos pendentes relacionados</li>
                                            <li>Ou aguardar a entrega de todos os pedidos</li>
                                            <li>Ou alterar o status dos pedidos para "cancelado" ou "entregue"</li>
                                        </ul>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Sugestão: Em vez de excluir, você pode desativar o produto na página de edição.
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendi</button>
                                    <a href="admin_pedidos.php?status=pendente" class="btn btn-primary">
                                        <i class="bi bi-clipboard-check me-1"></i> Ver Pedidos Pendentes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Adicionar modal ao body
                const modalDiv = document.createElement('div');
                modalDiv.innerHTML = modalHtml;
                document.body.appendChild(modalDiv);
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalErroExclusao'));
                modal.show();
                
                // Remover modal do DOM quando fechar
                modal._element.addEventListener('hidden.bs.modal', function () {
                    modalDiv.remove();
                });
            } else {
                // Sem pedidos pendentes, pode excluir normalmente
                if (confirm('Tem certeza que deseja excluir este produto?\n\nEsta ação não pode ser desfeita.')) {
                    window.location.href = 'produtos.php?excluir=' + produtoId;
                }
            }
        }
        
        // Função para desativar produto (se implementada futuramente)
        function desativarProduto(produtoId) {
            if (confirm('Deseja desativar este produto?\n\nO produto ficará indisponível para novos pedidos, mas os pedidos existentes não serão afetados.')) {
                window.location.href = 'desativar_produto.php?id=' + produtoId;
            }
        }
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>

</html>