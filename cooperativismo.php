<?php
$titulo = "Cooperativismo no Agronegócio - Unindo o Campo";
$descricao = "Cooperativismo agrícola: fortalecendo produtores rurais através da união e cooperação";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- CSS Personalizado -->
     <link rel="stylesheet" href="css/cooperativismo.css" />
     
</head>
<body>

    <!-- VLibras -->
  <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper></div>
  </div>

    <!-- Menu -->
    <?php include 'includes/_segundo_menu.php'; ?>

    <!-- Hero Section -->
   <section class="hero-section">
    <div id="carouselHero" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">

            <div class="carousel-item active">
                <img src="Images/carrosselCoop.png" class="d-block w-100" style="height: 450px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                    
                </div>
            </div>

            <div class="carousel-item">
                <img src="Images/carrosselCoop2.png" class="d-block w-100" style="height: 450px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                </div>
            </div>

        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#carouselHero" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>

        <button class="carousel-control-next" type="button" data-bs-target="#carouselHero" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>


    <!-- Conteúdo Principal -->
    <div class="container" id="entenda-mais">
        <!-- Definição -->
        <section class="mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="https://www.gov.br/mda/pt-br/noticias/2024/10/o-brasil-que-alimenta-uma-celebracao-a-agricultura-familiar/ao-6239-1.jpg" 
                         alt="Cooperativismo Agrícola" class="img-fluid agricultura-img">
                </div>
                <div class="col-lg-6">
                    <h2 class="mb-4 text-agro">Cooperativismo no Campo</h2>
                    <p class="fs-5">
                        O cooperativismo agrícola é um sistema que une produtores rurais em torno de objetivos comuns, 
                        fortalecendo a agricultura familiar e promovendo o desenvolvimento sustentável do agronegócio.
                    </p>
                    <p>
                        Através da união, os agricultores ganham força para negociar melhores preços, 
                        acessar tecnologias, compartilhar conhecimentos e conquistar mercados que individualmente seriam inacessíveis.
                    </p>
                    <a href="cadastro.php" class="btn btn-success">Junte se a nós</a>
                </div>
            </div>
        </section>

        <!-- Estatísticas -->
        <section class="stats-section mb-5 rounded">
            <div class="container">
                <h2 class="text-center mb-5">O Cooperativismo Agrícola em Números</h2>
                <div class="row text-center">
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">1.000+</div>
                        <p>Cooperativas Agrícolas no Brasil</p>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">1 Milhão</div>
                        <p>Produtores Rurais Cooperados</p>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">R$ 50 Bi</div>
                        <p>Faturamento Anual do Setor</p>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">70%</div>
                        <p>Da Produção Agrícola Passa por Cooperativas</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Princípios do Cooperativismo Agrícola -->
        <section class="mb-5">
            <h2 class="text-center mb-5 text-agro">Princípios do Cooperativismo Agrícola</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-people-fill benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Associação Voluntária</h5>
                            <p class="card-text">Produtores rurais unem-se livremente para fortalecer sua atividade agrícola e melhorar suas condições de trabalho.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-graph-up-arrow benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Gestão Democrática</h5>
                            <p class="card-text">Cada produtor tem um voto, independente do tamanho de sua propriedade ou volume de produção.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-currency-dollar benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Participação Econômica</h5>
                            <p class="card-text">Os excedentes são reinvestidos na cooperativa ou distribuídos entre os cooperados proporcionalmente.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-shield-check benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Autonomia</h5>
                            <p class="card-text">As cooperativas agrícolas são controladas por seus membros, mantendo sua independência.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-book benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Educação e Formação</h5>
                            <p class="card-text">Promoção de capacitação técnica e gerencial para os cooperados e comunidades rurais.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 principios-card card-agro">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-handshake benefit-icon"></i>
                            </div>
                            <h5 class="card-title">Cooperação entre Cooperativas</h5>
                            <p class="card-text">Trabalho em rede para fortalecer todo o setor cooperativista agrícola.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Agricultura Familiar -->
        <section class="agricultura-familiar mb-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h2 class="mb-4 text-agro">Agricultura Familiar e Cooperativismo</h2>
                        <p class="fs-5">
                            O cooperativismo é fundamental para a sustentabilidade da agricultura familiar, 
                            permitindo que pequenos produtores acessem mercados, tecnologias e financiamentos.
                        </p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent">
                                <i class="bi bi-check-circle-fill text-agro me-2"></i>
                                Acesso a insumos com melhores preços
                            </li>
                            <li class="list-group-item bg-transparent">
                                <i class="bi bi-check-circle-fill text-agro me-2"></i>
                                Comercialização conjunta da produção
                            </li>
                            <li class="list-group-item bg-transparent">
                                <i class="bi bi-check-circle-fill text-agro me-2"></i>
                                Assistência técnica especializada
                            </li>
                            <li class="list-group-item bg-transparent">
                                <i class="bi bi-check-circle-fill text-agro me-2"></i>
                                Capacitação e troca de conhecimentos
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <img src="https://agro.insper.edu.br/storage/articles/March2024/aGvNpQJoURzpUDOiqUfc.jpg" 
                             alt="Agricultura Familiar" class="img-fluid agricultura-img">
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefícios -->
        <section class="coop-benefits mb-5">
            <div class="container">
                <h2 class="text-center mb-5 text-agro">Vantagens do Cooperativismo Agrícola</h2>
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <i class="bi bi-arrow-up-right-circle benefit-icon"></i>
                        <h4 class="text-agro">Maior Poder de Negociação</h4>
                        <p>Compra coletiva de insumos e venda conjunta da produção garantem melhores preços</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-tools benefit-icon"></i>
                        <h4 class="text-agro">Acesso a Tecnologia</h4>
                        <p>Maquinário, sementes melhoradas e técnicas modernas compartilhadas entre os cooperados</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-graph-up benefit-icon"></i>
                        <h4 class="text-agro">Mercado Expandido</h4>
                        <p>Acesso a mercados nacional e internacional que individualmente seriam inacessíveis</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-cash-coin benefit-icon"></i>
                        <h4 class="text-agro">Crédito Facilitado</h4>
                        <p>Linhas de financiamento específicas para cooperativas com condições diferenciadas</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-shield-check benefit-icon"></i>
                        <h4 class="text-agro">Redução de Riscos</h4>
                        <p>Proteção contra variações de preços e condições climáticas adversas</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-people benefit-icon"></i>
                        <h4 class="text-agro">Fortalecimento Comunitário</h4>
                        <p>Desenvolvimento regional sustentável e manutenção do agricultor no campo</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Valores -->
        <section class="mb-5">
            <div class="coop-values">
                <h2 class="text-center mb-4">Valores do Cooperativismo Agrícola</h2>
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <i class="bi bi-heart-fill display-6 mb-2"></i>
                        <h5>Solidariedade</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <i class="bi bi-check2-square display-6 mb-2"></i>
                        <h5>Responsabilidade</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <i class="bi bi-person-check display-6 mb-2"></i>
                        <h5>Democracia</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <i class="bi bi-arrow-left-right display-6 mb-2"></i>
                        <h5>Transparência</h5>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Personalizado -->
    <script>
        // Animação suave para links internos
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Adiciona classe active ao menu durante scroll
            window.addEventListener('scroll', function() {
                const sections = document.querySelectorAll('section');
                const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
                
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (pageYOffset >= sectionTop - 60) {
                        current = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                    }
                });
            });
        });

        // Animação para cards quando entram na viewport
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplica animação aos cards
        document.querySelectorAll('.principios-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
</body>
</html>