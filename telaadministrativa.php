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
</head>
<body>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Produtos Disponíveis</h2>
                <a href="adicionar_produto.php" class="btn btn-success mb-3">Adicionar Novo Produto</a>
                
                <div class="row">
                    <?php
                    try {
                        $conn = getDBConnection();
                        $stmt = $conn->prepare("SELECT * FROM produtos WHERE disponivel = 1 ORDER BY nome");
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<div class="col-md-4 mb-4">';
                                echo '  <div class="card h-100">';
                                echo '    <img src="' . htmlspecialchars($row['imagem_url']) . '" class="card-img-top product-image" alt="' . htmlspecialchars($row['nome']) . '">';
                                echo '    <div class="card-body">';
                                echo '      <h5 class="card-title">' . htmlspecialchars($row['nome']) . '</h5>';
                                echo '      <p class="card-text">' . htmlspecialchars($row['descricao']) . '</p>';
                                echo '      <p class="card-text"><strong>Preço: R$ ' . number_format($row['preco'], 2, ',', '.') . '</strong></p>';
                                echo '      <p class="card-text"><small class="text-muted">Estoque: ' . htmlspecialchars($row['quantidade_estoque']) . ' unidades</small></p>';
                                echo '    </div>';
                                echo '    <div class="card-footer bg-white">';
                                echo '      <a href="editar_produto.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm">Editar</a>';
                                echo '      <a href="excluir_produto.php?id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir este produto?\')">Excluir</a>';
                                echo '    </div>';
                                echo '  </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-12"><div class="alert alert-info">Nenhum produto cadastrado.</div></div>';
                        }
                    } catch(PDOException $e) {
                        echo '<div class="col-12"><div class="alert alert-danger">Erro ao carregar produtos.</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
</body>
</html>