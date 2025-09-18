<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="images/logo.png" type="image/x-icon" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="CSS/index.css" />
  

<body>
  <header>
    <?php require_once "_parts/_menu.php"; ?>
  </header>
  
<!--Imagem Principal-->
<main class="hero">
  <img src="images/Principal.png" alt="Imagem Principal" class="hero-img">
  <div class="conteudo">
    
  </div>
</main>
  

      
        <div class="container"></div>
        <section id="sobre"></section>
        <h2 class="text-center">Uma cooperativa que transforma <p>
          a agricultura familiar!</h2>
       <div class="container">
  <img src="Images/missao.png" alt="" class="foto">
  <div class="texto">
    <div class="bloco">
      <h3>Missao</h3>
      <p>Promover o fortalecimento da agricultura familiar, conectando produtores rurais a consumidores e vendedores, garantindo acesso a alimentos orgânicos de qualidade, saudáveis e produzidos de forma sustentável.</p>
    </div>

    <div class="bloco">
      <h3>Valores</h3>
      <p>Sustentabilidade – Produzir respeitando o meio ambiente e garantindo a preservação para as futuras gerações.<br>
      Cooperação – Trabalhar de forma coletiva, valorizando a união e o protagonismo dos agricultores familiares.<br>
      Transparência – Garantir relações justas e claras entre produtores, vendedores e consumidores.<br>
      Inovação social – Utilizar a tecnologia como meio de democratizar o acesso a alimentos saudáveis.<br>
      Valorização cultural – Resgatar e manter vivas as tradições e saberes da agricultura local.</p>
    </div>

    <div class="bloco">
      <h3>Visao</h3>
      <p>Ser referência em cooperativismo digital no campo da agricultura familiar, promovendo um mercado justo, sustentável e acessível, contribuindo para a transformação social, econômica e ambiental das comunidades rurais e urbanas.</p>
    </div>
  </div>
</div>
  </div>
</div>

  <section id="produtos" class="mt-5">
  <h2 class="text-center">Produtos em Destaque</h2>
  <h6 class="text-center">
    Confira alguns produtos orgânicos frescos disponíveis em nossa plataforma, diretamente dos produtores para você
  </h6>

  <div id="carouselProdutos" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">

      <!--Produtos -->
      <div class="carousel-item active">
        <div class="d-flex justify-content-center gap-3">

          <!-- Card Tomate -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Tomate Cereja">
            <div class="card-body text-center">
              <h5 class="card-title">Tomate Cereja</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$2,99</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

          <!-- Card Alface -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Alface">
            <div class="card-body text-center">
              <h5 class="card-title">Alface</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$2,99</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

          <!-- Card Pitaya -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Pitaya">
            <div class="card-body text-center">
              <h5 class="card-title">Pitaya</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$2,99</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

        </div>
      </div>

      <!--produtos em Destaque-->
      <div class="carousel-item">
        <div class="d-flex justify-content-center gap-3">

          <!--Maçã -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Maçã">
            <div class="card-body text-center">
              <h5 class="card-title">Maçã</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$3,50</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

          <!--Banana -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Banana">
            <div class="card-body text-center">
              <h5 class="card-title">Banana</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$4,20</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

          <!--Laranja -->
          <div class="card" style="width: 18rem;">
            <img src="" class="card-img-top" alt="Laranja">
            <div class="card-body text-center">
              <h5 class="card-title">Laranja</h5>
              <p class="card-text">Descrição...</p>
              <p class="fw-bold text-success">R$3,90</p>
              <a href="#" class="btn btn-success">Encomendar</a>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Controle -->
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselProdutos" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselProdutos" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>

        <!--Sobre Nós-->
      <section id="SobreNos"></section>
        <h2 class="text-center">Sobre Nós</h2>
        <div class="msn">
          <img src="images/estruturas.png" alt="Imagem de estruturas" />
          <div>
            <p>Texto Texto Texto Texto Texto</p>
            <p>Texto Texto Texto Texto Texto</p>
            <p>Texto Texto Texto Texto Texto</p>
            <p>Texto Texto Texto Texto Texto</p>
          </div>
        </div>
      </section>


    </main>
    </section>

  </main>

  <footer>
    <?php require_once "_parts/_footer.php" ?>
  </footer>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>