<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossos Produtos - CoopAgro</title>
    <link rel="stylesheet" href="./css/produtos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header>
        <div>             
            <?php require_once "includes/_menu.php"; ?>
        </div>
    </header>

    <main class="container">
        <div class="search-bar-container">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Pesquisar...">
        </div>

        <section class="product-section">
            <h2>Hortaliças</h2>
            <div class="product-grid">
                <?php
                    // Array de produtos para demonstração
                    $produtos = [
                        [
                            'nome' => 'Alface americana',
                            'unidade' => 'KG',
                            'preco' => '5.25',
                            'imagem' => 'img/alface-americana.jpg' // Substitua pelo caminho da sua imagem
                        ],
                        [
                            'nome' => 'Alface barba de verão',
                            'unidade' => 'KG',
                            'preco' => '5.18',
                            'imagem' => 'img/alface-verao.jpg'
                        ],
                        [
                            'nome' => 'Rúcula comum',
                            'unidade' => 'KG',
                            'preco' => '5.15',
                            'imagem' => 'img/rucula.jpg'
                        ],
                        [
                            'nome' => 'Couve',
                            'unidade' => 'KG',
                            'preco' => '5.22',
                            'imagem' => 'img/couve.jpg'
                        ],
                        [
                            'nome' => 'Espinafre',
                            'unidade' => 'KG',
                            'preco' => '5.25',
                            'imagem' => 'img/espinafre.jpg'
                        ],
                         // Adicione mais produtos aqui
                        [
                            'nome' => 'Brócolis',
                            'unidade' => 'KG',
                            'preco' => '7.50',
                            'imagem' => 'img/brocolis.jpg'
                        ],
                        [
                            'nome' => 'Cenoura',
                            'unidade' => 'KG',
                            'preco' => '4.80',
                            'imagem' => 'img/cenoura.jpg'
                        ],
                        [
                            'nome' => 'Tomate',
                            'unidade' => 'KG',
                            'preco' => '6.99',
                            'imagem' => 'img/tomate.jpg'
                        ]
                    ];

                    // Loop para exibir cada produto
                    foreach ($produtos as $produto) {
                        echo '<div class="product-card">';
                        echo '  <img src="' . htmlspecialchars($produto['imagem']) . '" alt="' . htmlspecialchars($produto['nome']) . '">';
                        echo '  <div class="product-info">';
                        echo '      <div class="product-name-unit">';
                        echo '          <span class="product-name">' . htmlspecialchars($produto['nome']) . '</span>';
                        echo '          <span class="product-unit">' . htmlspecialchars($produto['unidade']) . '</span>';
                        echo '      </div>';
                        echo '      <div class="product-price-button">';
                        echo '          <span class="product-price">R$' . number_format($produto['preco'], 2, ',', '.') . '</span>';
                        echo '          <button class="product-button">Encontrar</button>';
                        echo '      </div>';
                        echo '  </div>';
                        echo '</div>';
                    }
                ?>
            </div>
        </section>
    </main>

</body>
</html>