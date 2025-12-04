<?php
// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg sticky-top" style="background-color: #143d0f;">
  <div class="container-fluid">

    <a class="navbar-brand" href="index.php">
      <img src="images/logo.png" alt="Logo da empresa" style="width: 100px; height: auto;" />
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegação">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav gap-4 me-auto">
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="produtos.php">Produtos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="cooperativismo.php">Cooperativismo</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="contato.php">Contato</a>
        </li>
      </ul>

      <!-- Área do usuário -->
      <div class="navbar-nav">
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Menu do usuário logado -->
          <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i>
              Minha Conta
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="perfil.php">Meu Perfil</a></li>
              <li><a class="dropdown-item" href="meus_pedidos.php">Meus Pedidos</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Sair</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Usuário não logado -->
          <a class="nav-link text-white fw-semibold" href="login.php">
            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">