<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Buscar todos os tipos disponíveis
    $stmtTipos = $conn->prepare("SELECT DISTINCT tipo FROM produtos WHERE disponivel = 1 ORDER BY tipo");
    $stmtTipos->execute();
    $tipos = $stmtTipos->fetchAll(PDO::FETCH_COLUMN);
    
    // Buscar produtos por tipo
    $produtosPorTipo = [];
    foreach ($tipos as $tipo) {
        $stmtProdutos = $conn->prepare("SELECT * FROM produtos WHERE tipo = ? AND disponivel = 1 ORDER BY nome");
        $stmtProdutos->execute([$tipo]);
        $produtosPorTipo[$tipo] = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    $error = "Erro ao carregar produtos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Agricultura Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }
        
        .hero-section {
            background: linear-gradient(rgba(46, 125, 50, 0.9), rgba(76, 175, 80, 0.9)), 
                        url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .product-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .carousel-section {
            margin-bottom: 60px;
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            background: rgba(0,0,0,0.3);
        }
        
        .section-title {
            color: var(--verde-principal);
            border-bottom: 3px solid var(--verde-claro);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .btn-encomendar {
            background-color: var(--verde-principal);
            color: white;
            border: none;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
        }
        
        .btn-encomendar:hover {
            background-color: var(--verde-secundario);
            color: white;
        }
        
        .estoque-baixo {
            position: relative;
        }
        
        .estoque-baixo::after {
            content: "Estoque Baixo";
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .tipo-badge {
            background-color: var(--verde-claro);
            color: var(--verde-principal);
        }
        
        .unidade-badge {
            background-color: #6c757d;
            color: white;
            font-size: 0.75rem;
        }
        
        .price {
            color: var(--verde-principal);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .carousel-indicators {
            bottom: -50px;
        }
        
        .carousel-indicators button {
            background-color: var(--verde-principal);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 5px;
        }
        
        .unidade-info {
            font-size: 0.85rem;
            color: #666;
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
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Produtos da Agricultura Familiar</h1>
                    <p class="lead mb-4">Alimentos frescos e saudáveis direto do produtor para sua mesa</p>
                    <a href="#produtos" class="btn btn-light btn-lg">Ver Produtos</a>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="bi bi-basket3" style="font-size: 8rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Produtos por Tipo -->
    <div class="container" id="produtos">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (empty($tipos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h3 class="text-muted mt-3">Nenhum produto disponível</h3>
                <p class="text-muted">Volte em breve para conhecer nossos produtos.</p>
            </div>
        <?php else: ?>
            <?php foreach ($produtosPorTipo as $tipo => $produtos): ?>
                <?php if (!empty($produtos)): ?>
                    <section class="carousel-section">
                        <h2 class="section-title">
                            <i class="bi bi-tags-fill me-2"></i>
                            <?php echo htmlspecialchars(ucfirst($tipo)); ?>s
                        </h2>
                        
                        <div id="carousel-<?php echo htmlspecialchars($tipo); ?>" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php 
                                // Dividir produtos em grupos de 3 para o carrossel
                                $chunks = array_chunk($produtos, 3);
                                foreach ($chunks as $index => $chunk): 
                                ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="row g-4">
                                            <?php foreach ($chunk as $produto): 
                                                $imagemSrc = !empty($produto['imagem_url']) ? 
                                                    'uploads/produtos/' . htmlspecialchars($produto['imagem_url']) : 
                                                    'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                                
                                                $estoqueClasse = $produto['quantidade_estoque'] <= 5 ? 'estoque-baixo' : '';
                                                
                                                // Formatar unidade de medida
                                                $unidade_texto = htmlspecialchars($produto['unidade_medida']);
                                                $quantidade_texto = $produto['quantidade_estoque'] . ' ' . $unidade_texto;
                                            ?>
                                                <div class="col-md-4">
                                                    <div class="card product-card <?php echo $estoqueClasse; ?>">
                                                        <img src="<?php echo htmlspecialchars($imagemSrc); ?>" 
                                                             class="card-img-top product-image" 
                                                             alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                                             onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                                                        <div class="card-body d-flex flex-column">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                                                <div>
                                                                    <span class="badge tipo-badge"><?php echo htmlspecialchars($produto['tipo']); ?></span>
                                                                    <span class="badge unidade-badge"><?php echo $unidade_texto; ?></span>
                                                                </div>
                                                            </div>
                                                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                                            <div class="mt-auto">
                                                                <p class="price mb-2">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?> /<?php echo $unidade_texto; ?></p>
                                                                <p class="card-text">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-box-seam"></i>
                                                                        <?php echo $quantidade_texto; ?> disponíveis
                                                                    </small>
                                                                </p>
                                                                <?php if ($produto['quantidade_estoque'] > 0): ?>
                                                                    <a href="encomenda.php?id=<?php echo intval($produto['id']); ?>" 
                                                                       class="btn btn-encomendar w-100">
                                                                        <i class="bi bi-cart-plus"></i> Encomendar
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-secondary w-100" disabled>
                                                                        <i class="bi bi-x-circle"></i> Indisponível
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($chunks) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($tipo); ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($tipo); ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Próximo</span>
                                </button>
                                
                                <!-- Indicadores -->
                                <div class="carousel-indicators">
                                    <?php for ($i = 0; $i < count($chunks); $i++): ?>
                                        <button type="button" 
                                                data-bs-target="#carousel-<?php echo htmlspecialchars($tipo); ?>" 
                                                data-bs-slide-to="<?php echo $i; ?>" 
                                                class="<?php echo $i === 0 ? 'active' : ''; ?>" 
                                                aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>" 
                                                aria-label="Slide <?php echo $i + 1; ?>">
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Call to Action -->
    <section class="bg-light py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h3 class="mb-3">Não encontrou o que procurava?</h3>
                    <p class="lead mb-4">Entre em contato conosco para encomendas especiais ou maiores informações</p>
                    <a href="contato.php" class="btn btn-success btn-lg">
                        <i class="bi bi-whatsapp"></i> Entrar em Contato
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inicializar todos os carrosseis
        document.addEventListener('DOMContentLoaded', function() {
            var carousels = document.querySelectorAll('.carousel');
            carousels.forEach(function(carousel) {
                new bootstrap.Carousel(carousel, {
                    interval: 5000,
                    wrap: true
                });
            });
            
            // Smooth scroll para links internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
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