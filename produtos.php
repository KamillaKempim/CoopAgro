<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooperativa Agrícola - Nossos Produtos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/produtos.css" />
    <!-- CSS Personalizado -->
    
</head>
<body>
    <header>
    <?php require_once "includes/_menu.php"; ?>
  </header>
    <div class="main-container">
        <div class="container flex-grow-1">
            <!-- Produto em Destaque -->
            <?php
            // Incluir arquivo de conexão
            require_once './config/database.php';
            
            try {
                // Conectar ao banco usando a função do arquivo de conexão
                $conn = getDBConnection();
                
                // Buscar produto em destaque (primeiro produto disponível)
                $stmtDestaque = $conn->query("SELECT * FROM produtos WHERE disponivel = 1 ORDER BY id LIMIT 1");
                $produtoDestaque = $stmtDestaque->fetch(PDO::FETCH_ASSOC);
                
                // Buscar todos os produtos disponíveis para o carrossel
                $stmtProdutos = $conn->query("SELECT * FROM produtos WHERE disponivel = 1 ORDER BY nome");
                $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
                
            } catch(PDOException $e) {
                // Em caso de erro na conexão, usar dados de exemplo
                error_log("Erro ao buscar produtos: " . $e->getMessage());
                $produtoDestaque = [
                    'nome' => 'Alface Crespa Orgânica',
                    'descricao' => 'Alface cultivada sem agrotóxicos, colhida diariamente para garantir máxima frescura e sabor.',
                    'preco' => '3.50',
                    'imagem_url' => 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=500&h=300&fit=crop',
                    'quantidade_estoque' => 50
                ];
                
                $produtos = [
                    [
                        'nome' => 'Alface',
                        'descricao' => 'Alface fresca e crocante',
                        'preco' => '2.50',
                        'quantidade_estoque' => 50,
                        'imagem_url' => 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ],
                    [
                        'nome' => 'Couve',
                        'descricao' => 'Couve orgânica rica em nutrientes',
                        'preco' => '3.00',
                        'quantidade_estoque' => 30,
                        'imagem_url' => 'https://images.unsplash.com/photo-1594834740594-512ab9945b5c?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ],
                    [
                        'nome' => 'Rúcula',
                        'descricao' => 'Rúcula fresca com sabor característico',
                        'preco' => '2.80',
                        'quantidade_estoque' => 40,
                        'imagem_url' => 'https://images.unsplash.com/photo-1592417817098-8fd3d9eb14a5?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ],
                    [
                        'nome' => 'Almeirão',
                        'descricao' => 'Almeirão de folhas verdes escuras',
                        'preco' => '2.70',
                        'quantidade_estoque' => 25,
                        'imagem_url' => 'https://images.unsplash.com/photo-1594834740594-512ab9945b5c?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ],
                    [
                        'nome' => 'Agrião',
                        'descricao' => 'Agrião hidropônico de alta qualidade',
                        'preco' => '3.20',
                        'quantidade_estoque' => 35,
                        'imagem_url' => 'https://images.unsplash.com/photo-1592417817098-8fd3d9eb14a5?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ],
                    [
                        'nome' => 'Cebolinha',
                        'descricao' => 'Cebolinha verde para tempero',
                        'preco' => '1.80',
                        'quantidade_estoque' => 60,
                        'imagem_url' => 'https://images.unsplash.com/photo-1594834740594-512ab9945b5c?w=300&h=200&fit=crop',
                        'disponivel' => 1
                    ]
                ];
            }
            ?>
            
            <?php if ($produtoDestaque): ?>
            <div class="produto-destaque">
                <div class="row g-0 h-100">
                    <div class="col-md-6">
                        <img src="<?php echo $produtoDestaque['imagem_url']; ?>" alt="<?php echo $produtoDestaque['nome']; ?>" class="img-fluid">
                    </div>
                    <div class="col-md-6">
                        <div class="destaque-info">
                            <h2 class="h1 mb-3">Produto em Destaque</h2>
                            <h3 class="h2 text-success mb-3"><?php echo $produtoDestaque['nome']; ?></h3>
                            <p class="lead mb-4"><?php echo $produtoDestaque['descricao']; ?></p>
                            <div class="destaque-preco mb-4">R$ <?php echo number_format($produtoDestaque['preco'], 2, ',', '.'); ?></div>
                            <button class="btn btn-encomendar btn-lg">Encomendar Agora</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Carrossel de Produtos -->
            <div class="carousel-section">
                <h2 class="carousel-title">Nossos Produtos</h2>
                
                <?php if (count($produtos) > 0): ?>
                <div id="produtosCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-inner">
                        <?php
                        // Exibir um produto por slide no carrossel
                        foreach ($produtos as $index => $produto) {
                            $active = $index === 0 ? 'active' : '';
                            $status = $produto['disponivel'] && $produto['quantidade_estoque'] > 0 ? 'disponivel' : 'indisponivel';
                            $textoStatus = $produto['disponivel'] && $produto['quantidade_estoque'] > 0 ? 'Disponível' : 'Indisponível';
                            
                            echo '<div class="carousel-item ' . $active . '">';
                            echo '<div class="row justify-content-center">';
                            echo '
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="produto-card">
                                    <img src="' . $produto['imagem_url'] . '" alt="' . $produto['nome'] . '" class="produto-imagem">
                                    <h4 class="produto-nome">' . $produto['nome'] . '</h4>
                                    <p class="produto-descricao">' . $produto['descricao'] . '</p>
                                    <div class="estoque-info">
                                        <span class="' . $status . '">' . $textoStatus . '</span> | Estoque: ' . $produto['quantidade_estoque'] . ' unidades
                                    </div>
                                    <div class="produto-preco">R$ ' . number_format($produto['preco'], 2, ',', '.') . '</div>
                                    <div class="card-footer">
                                        <button class="btn btn-encomendar" ' . (!$produto['disponivel'] || $produto['quantidade_estoque'] <= 0 ? 'disabled' : '') . '>Encomendar</button>
                                    </div>
                                </div>
                            </div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <!-- Controles do Carrossel -->
                    <?php if (count($produtos) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#produtosCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#produtosCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Próximo</span>
                    </button>
                    
                    <!-- Indicadores -->
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($produtos); $i++): ?>
                            <button type="button" data-bs-target="#produtosCarousel" data-bs-slide-to="<?php echo $i; ?>" 
                                class="<?php echo $i === 0 ? 'active' : ''; ?>" aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>" 
                                aria-label="Slide <?php echo $i + 1; ?>"></button>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <h4>Nenhum produto disponível no momento</h4>
                    <p>Volte em breve para conferir nossos produtos frescos!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>&copy; 2024 Cooperativa Agrícola. Todos os direitos reservados.</p>
                <p>🌱 Cultivando qualidade para sua família 🌱</p>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inicializar carrossel com animação personalizada
        document.addEventListener('DOMContentLoaded', function() {
            var myCarousel = document.getElementById('produtosCarousel');
            if (myCarousel) {
                var carousel = new bootstrap.Carousel(myCarousel, {
                    interval: 5000,
                    wrap: true,
                    pause: false
                });
                
                // Adicionar efeito de fade personalizado
                myCarousel.addEventListener('slide.bs.carousel', function (e) {
                    var next = e.relatedTarget;
                    var items = next.parentElement.children;
                    
                    for (var i = 0; i < items.length; i++) {
                        items[i].classList.remove('active');
                    }
                    
                    next.classList.add('active');
                });
            }
        });
    </script>
</body>
</html>