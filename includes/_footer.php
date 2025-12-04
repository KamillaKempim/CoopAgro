<?php
/**
 * Rodapé responsivo para o sistema CoopAgro
 * Deve ser incluído no final de todas as páginas
 */
?>

<footer class="coopagro-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>CoopAgro</h3>
            <p>Cooperativa de Agricultura Familiar</p>
            <p>Transformando o campo com tecnologia e cooperação</p>
        </div>
        
        <div class="footer-section">
            <h4>Contato</h4>
            <p><i class="fas fa-phone"></i> (00) 0000-0000</p>
            <p><i class="fas fa-envelope"></i> contato@coopagro.com.br</p>
            <p><i class="fas fa-map-marker-alt"></i> Rua Rural, 123 - Zona Rural</p>
        </div>
        
        <div class="footer-section">
            <h4>Links Rápidos</h4>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="sobre.php">Sobre Nós</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="contato.php">Contato</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Redes Sociais</h4>
            <div class="social-icons">
                <a href="https://www.instagram.com/coopagro.site?igsh=MXg5aGhpMmhwNmxlOA%3D%3D&utm_source=qr"><i class="fab fa-instagram"></i>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
            <div class="newsletter">
                <p>Assine nossa newsletter:</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Seu e-mail">
                    <button type="submit">Assinar</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <p>&copy; <?php echo date('Y'); ?> CoopAgro - Todos os direitos reservados</p>
            <div class="footer-links">
                <a href="politica.php">Política de Privacidade</a>
                <a href="politica.php">Termos de Uso</a>
            </div>
        </div>
    </div>
</footer>

<!-- Font Awesome para ícones -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="CSS/rodape.css" />