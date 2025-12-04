<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();
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

                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Produto adicionado com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Produto atualizado com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        Produto excluído com sucesso!
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
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $estoqueClasse = $row['quantidade_estoque'] <= 5 ? 'estoque-baixo' : 'estoque-normal';
                                $imagemSrc = !empty($row['imagem_url']) ? 
                                    'uploads/produtos/' . htmlspecialchars($row['imagem_url']) : 
                                    'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                
                                echo '<div class="col-md-4 mb-4">';
                                echo '  <div class="card h-100 ' . $estoqueClasse . '">';
                                echo '    <div class="position-relative">';
                                echo '      <img src="' . $imagemSrc . '" class="card-img-top product-image" alt="' . htmlspecialchars($row['nome']) . '" onerror="this.src=\'https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada\'">';
                                echo '      <span class="badge bg-secondary tipo-badge">' . htmlspecialchars($row['tipo']) . '</span>';
                                if ($row['quantidade_estoque'] <= 5) {
                                    echo '      <span class="badge bg-danger position-absolute top-0 start-0 m-2">Estoque Baixo</span>';
                                }
                                echo '    </div>';
                                echo '    <div class="card-body d-flex flex-column">';
                                echo '      <h5 class="card-title">' . htmlspecialchars($row['nome']) . '</h5>';
                                echo '      <p class="card-text flex-grow-1">' . htmlspecialchars($row['descricao']) . '</p>';
                                echo '      <div class="mt-auto">';
                                echo '        <p class="card-text"><strong class="fs-5 text-success">R$ ' . number_format($row['preco'], 2, ',', '.') . '</strong></p>';
                                echo '        <p class="card-text">';
                                echo '          <span class="badge ' . ($row['quantidade_estoque'] <= 5 ? 'bg-warning' : 'bg-info') . '">';
                                echo '            Estoque: ' . htmlspecialchars($row['quantidade_estoque']) . ' unidades';
                                echo '          </span>';
                                echo '        </p>';
                                echo '        <small class="text-muted">';
                                echo '          Cadastrado em: ' . date('d/m/Y', strtotime($row['data_criacao']));
                                echo '        </small>';
                                echo '      </div>';
                                echo '    </div>';
                                echo '    <div class="card-footer bg-white d-flex justify-content-between">';
                                echo '      <a href="editar_produto.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm">';
                                echo '        <i class="bi bi-pencil"></i> Editar';
                                echo '      </a>';
                                echo '      <a href="excluir_produto.php?id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir este produto?\')">';
                                echo '        <i class="bi bi-trash"></i> Excluir';
                                echo '      </a>';
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
                    } catch(PDOException $e) {
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
    
    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
</body>
</html>