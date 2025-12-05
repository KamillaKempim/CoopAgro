<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="Images/logo.png" type="image/x-icon" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/index.css" />
</head>

<body>

  <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper></div>
  </div>

  <header>
    <?php require_once "includes/_menu.php"; ?>
  </header>

  <main class="hero">
    <img src="Images/Principal.png" alt="Imagem Principal" class="hero-img">
  </main>

  <!-- Painel acessibilidade -->
  <div class="painel-flutuante">
    <button id="btnAbrir">⚙️</button>
    <div class="painel-acessibilidade" id="painelAcessibilidade">
      <h4>Painel de Acessibilidade</h4>
      <button onclick="contraste()"><i class="bi bi-brightness-high-fill"> </i>Alto contraste</button>
      <button onclick="fonteMais()"><i class="bi bi-type-bold"></i></button>
      <button onclick="fonteMenos()"><i class="bi bi-type"></i></button>
      <button onclick="resetar()"><i class="bi bi-arrow-counterclockwise"></i> Padrão</button>
    </div>
  </div>


  <script>
    let tamanho = localStorage.getItem("fonte") || 16;
    document.body.style.fontSize = tamanho + "px";

    if (localStorage.getItem("contraste") === "ativo") {
      document.body.classList.add("modo-contraste");
    }

    function contraste() {
      document.body.classList.toggle("modo-contraste");
      let ativo = document.body.classList.contains("modo-contraste");
      localStorage.setItem("contraste", ativo ? "ativo" : "inativo");
    }

    function fonteMais() {
      tamanho = parseInt(tamanho) + 2;
      document.body.style.fontSize = tamanho + "px";
      localStorage.setItem("fonte", tamanho);
    }

    function fonteMenos() {
      tamanho = parseInt(tamanho) - 2;
      document.body.style.fontSize = tamanho + "px";
      localStorage.setItem("fonte", tamanho);
    }

    function resetar() {
      document.body.classList.remove("modo-contraste");
      document.body.style.fontSize = "16px";
      localStorage.clear();
    }

    const btnAbrir = document.getElementById('btnAbrir');
    const painel = document.getElementById('painelAcessibilidade');

    btnAbrir.addEventListener('click', () => {
      painel.style.display = painel.style.display === 'flex' ? 'none' : 'flex';
    });
  </script>


  <div class="container"></div>

  <section id="sobre"></section>

  <h2 class="text-center">
    Uma cooperativa que transforma <br>
    a agricultura familiar!
  </h2>

  <div class="container">
    <img src="Images/missao.png" alt="" class="foto">
    <div class="texto">
      <div class="bloco">
        <h3>Missão</h3>
        <p>Promover o fortalecimento da agricultura familiar, conectando produtores rurais a consumidores e vendedores,
          garantindo acesso a alimentos orgânicos de qualidade, saudáveis e produzidos de forma sustentável.</p>
      </div>

      <div class="bloco">
        <h3>Valores</h3>
        <p>Sustentabilidade – Produzir respeitando o meio ambiente e garantindo a preservação para as futuras
          gerações.<br>
          Cooperação – Trabalhar de forma coletiva, valorizando a união e o protagonismo dos agricultores
          familiares.<br>
          Transparência – Garantir relações justas e claras entre produtores, vendedores e consumidores.<br>
          Inovação social – Utilizar a tecnologia como meio de democratizar o acesso a alimentos saudáveis.<br>
          Valorização cultural – Resgatar e manter vivas as tradições e saberes da agricultura local.</p>
      </div>

      <div class="bloco">
        <h3>Visão</h3>
        <p>Ser referência em cooperativismo digital no campo da agricultura familiar, promovendo um mercado justo,
          sustentável e acessível, contribuindo para a transformação social, econômica e ambiental das comunidades
          rurais e urbanas.</p>
      </div>
    </div>
  </div>

  <!-- Produtos -->
  <section id="produtos" class="mt-5">
    <h2 class="text-center mb-3">Alguns do produtos que você pode encontrar na plataforma</h2>
    <h6 class="text-center">
      Confira alguns produtos orgânicos frescos em nossa plataforma, <br> diretamente dos produtores para você!
    </h6>

    <div id="carouselProdutos" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">

        <div class="carousel-item active">
          <div class="d-flex justify-content-center gap-3">

            <div class="card" style="width: 18rem;">
              <img src="Images/hortaliças.jpg" class="card-img-top" alt="Hortaliças">
              <div class="card-body text-center">
                <h5 class="card-title">Hortaliças</h5>
                <p class="card-text">Verduras e legumes fresquinhos direto do produtor.</p>
              </div>
            </div>

            <div class="card" style="width: 18rem;">
              <img src="Images/Frutas.jpg" class="card-img-top" alt="Frutas">
              <div class="card-body text-center">
                <h5 class="card-title">Frutas</h5>
                <p class="card-text">Diversas frutas frescas e nutritivas para sua mesa.</p>
              </div>
            </div>

            <div class="card" style="width: 18rem;">
              <img src="Images/Grãos.jpg" class="card-img-top" alt="Grãos">
              <div class="card-body text-center">
                <h5 class="card-title">Grãos</h5>
                <p class="card-text">Arroz, feijão, milho e muito mais, todos orgânicos.</p>
              </div>
            </div>

          </div>
        </div>

        <div class="carousel-item">
          <div class="d-flex justify-content-center gap-3">

            <div class="card" style="width: 18rem;">
              <img src="Images/Produtos Orgânicos.jpg" class="card-img-top" alt="Produtos Orgânicos">
              <div class="card-body text-center">
                <h5 class="card-title">Produtos Orgânicos</h5>
                <p class="card-text">Alimentos cultivados de forma sustentável.</p>
              </div>
            </div>

            <div class="card" style="width: 18rem;">
              <img src="Images/Temperos.jpg" class="card-img-top" alt="Temperos">
              <div class="card-body text-center">
                <h5 class="card-title">Temperos</h5>
                <p class="card-text">Ervas e especiarias naturais para suas receitas.</p>
              </div>
            </div>

            <div class="card" style="width: 18rem;">
              <img src="Images/Confira agora.png" class="card-img-top" alt="Confira Agora">
              <div class="card-body text-center">
                <p class="card-text">Venha conferir agora!</p>
                <a href="produtos.php" class="btn btn-success w-100">Ver produtos</a>
              </div>
            </div>

          </div>
        </div>
      </div>

      <button class="carousel-control-prev" type="button" data-bs-target="#carouselProdutos" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselProdutos" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </section>

  <!-- Sobre Nós -->
  <section id="sobre-nos">
    <h2 class="titulo-sobre">Sobre Nós</h2>
    <div class="cards-equipe">

      <div class="card-equipe">
        <img src="Images/alisson.png" alt="Alison" class="foto-perfil">
        <h3>Alison</h3>
        <p>
          Designer cursando o 3º ano do ensino médio na Escola Cordeiro, responsável pelo desenvolvimento dos protótipos
          e criação das artes do site, o “rapaz da arte”.
        </p>
        <img src="Images/icon-design.png" alt="Ícone design" class="icone-card">
      </div>

      <div class="card-equipe">
        <img src="Images/kamilla.jpeg" alt="Kamilla" class="foto-perfil">
        <h3>Kamilla</h3>
        <p>
          Estudante de informática pelo IFRO cursando o 3º ano do ensino médio, à frente do front-end responsável pela
          estilização do site, a “menina do front”.
        </p>
        <img src="Images/icon-front.png" alt="Ícone front" class="icone-card">
      </div>

      <div class="card-equipe">
        <img src="Images/jhiewertton.png" alt="Jhiewertton" class="foto-perfil">
        <h3>Jhiewertton</h3>
        <p>
          Dev Sec-ops acadêmico de Sistemas de Informação, cursando o 7º período, responsável pelo backend, banco de
          dados e infraestrutura, o “carinha dos servidores e das gambiarras”.
        </p>
        <img src="Images/icon-backend.png" alt="Ícone backend" class="icone-card">
      </div>

    </div>
  </section>

  <footer>
    <?php require_once "includes/_footer.php" ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
  <script>
    const btnAbrir = document.getElementById('btnAbrir');
    const painel = document.getElementById('painelAcessibilidade');

    btnAbrir.addEventListener('click', () => {
      painel.style.display = painel.style.display === 'flex' ? 'none' : 'flex';
    });
  </script>

</body>

</html>