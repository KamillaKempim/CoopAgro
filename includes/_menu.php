<?php
?>

<!-- Menu de Navegação -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="images/logo.png" alt="Logo CoopAgro" class="d-inline-block align-top">
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || isset($_GET['sobre'])) ? 'active' : ''; ?>" 
             href="index.php#sobre">Sobre</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'produtos.php') ? 'active' : ''; ?>" 
             href="produtos.php">Produtos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'cooperativismo.php') ? 'active' : ''; ?>" 
             href="cooperativismo.php">Cooperativismo</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contato.php') ? 'active' : ''; ?>" 
             href="contato.php">Contato</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'politica.php') ? 'active' : ''; ?>" 
             href="politica.php">Política de Privacidade</a>
        </li>
      </ul>
      
      <div class="navbar-nav">
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" 
               data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-2 fs-5"></i>
              <span>Minha Conta</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><h6 class="dropdown-header">Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></h6></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
              <li><a class="dropdown-item" href="meus_pedidos.php"><i class="bi bi-cart-check me-2"></i>Meus Pedidos</a></li>
              <li><a class="dropdown-item" href="meus_dados.php"><i class="bi bi-shield-lock me-2"></i>Meus Dados</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="nav-link d-flex align-items-center" href="login.php">
            <i class="bi bi-box-arrow-in-right me-2 fs-5"></i>
            <span>Entrar</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Estilos do Menu (mantidos inline para evitar conflito) -->
<style>
.navbar-custom {
    background-color: #143d0f !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
}

.navbar-custom .navbar-brand {
    padding: 0;
}

.navbar-custom .navbar-brand img {
    width: 100px;
    height: auto;
    transition: transform 0.3s ease;
}

.navbar-custom .navbar-brand img:hover {
    transform: scale(1.05);
}

.navbar-custom .nav-link {
    color: #ffffff !important;
    font-weight: 600;
    padding: 0.5rem 1rem !important;
    border-radius: 4px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-custom .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.navbar-custom .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background-color: #4CAF50;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.navbar-custom .nav-link:hover::after {
    width: 80%;
}

.navbar-custom .dropdown-menu {
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.navbar-custom .dropdown-item {
    color: #333;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.navbar-custom .dropdown-item:hover {
    background-color: #f1f8e9;
    color: #2e7d32;
}

.navbar-custom .navbar-toggler {
    border: 2px solid rgba(255, 255, 255, 0.5);
    padding: 0.25rem 0.5rem;
}

.navbar-custom .navbar-toggler:focus {
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.5);
}

.navbar-custom .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Estilo para o item ativo (página atual) */
.navbar-custom .nav-link.active {
    background-color: rgba(76, 175, 80, 0.2);
    color: #ffffff !important;
}

.navbar-custom .nav-link.active::after {
    width: 80%;
    background-color: #ffffff;
}

/* Ajuste para telas pequenas */
@media (max-width: 991px) {
    .navbar-custom .navbar-nav {
        padding: 1rem 0;
    }
    
    .navbar-custom .nav-link {
        padding: 0.75rem 1rem !important;
        margin: 0.25rem 0;
    }
    
    .navbar-custom .dropdown-menu {
        border: none;
        box-shadow: none;
        background-color: rgba(20, 61, 15, 0.95);
        margin-left: 1rem;
    }
    
    .navbar-custom .dropdown-item {
        color: #ffffff;
    }
    
    .navbar-custom .dropdown-item:hover {
        background-color: rgba(76, 175, 80, 0.3);
        color: #ffffff;
    }
}
</style>

<!-- Script para funcionalidade do menu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Remove a classe active de todos os links
    const navLinks = document.querySelectorAll('.navbar-custom .nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Adiciona a classe active no link da página atual
    const currentPage = window.location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref.includes(currentPage) && currentPage !== '') {
            link.classList.add('active');
        }
    });
    
    // Tratamento especial para a página inicial
    if (currentPage === '' || currentPage === 'index.php') {
        const sobreLink = document.querySelector('a[href="index.php#sobre"]');
        if (sobreLink) {
            sobreLink.classList.add('active');
        }
    }
    
    // Adiciona scroll suave para âncoras
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if(this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Atualiza a URL sem recarregar a página
                    history.pushState(null, null, targetId);
                }
            }
        });
    });
});
</script>