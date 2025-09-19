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
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
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
                <a href="politica-privacidade.php">Política de Privacidade</a>
                <a href="termos-uso.php">Termos de Uso</a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Estilos do rodapé */
    .coopagro-footer {
        background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
        color: white;
        margin-top: auto; /* Para fixar no final da página */
    }
    
    .footer-content {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .footer-section {
        flex: 1;
        min-width: 250px;
        margin-bottom: 30px;
        padding: 0 15px;
    }
    
    .footer-section h3 {
        color: #FFC107;
        margin-bottom: 15px;
        font-size: 24px;
    }
    
    .footer-section h4 {
        color: #FFC107;
        margin-bottom: 15px;
        font-size: 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 10px;
    }
    
    .footer-section p {
        margin-bottom: 10px;
        line-height: 1.6;
    }
    
    .footer-section i {
        margin-right: 10px;
        color: #FFC107;
        width: 20px;
    }
    
    .footer-section ul {
        list-style: none;
        padding: 0;
    }
    
    .footer-section ul li {
        margin-bottom: 10px;
    }
    
    .footer-section ul li a {
        color: white;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer-section ul li a:hover {
        color: #FFC107;
    }
    
    .social-icons {
        display: flex;
        margin-bottom: 20px;
    }
    
    .social-icons a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        margin-right: 10px;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .social-icons a:hover {
        background: #FFC107;
        transform: translateY(-3px);
    }
    
    .newsletter-form {
        display: flex;
        margin-top: 10px;
    }
    
    .newsletter-form input {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 4px 0 0 4px;
        outline: none;
    }
    
    .newsletter-form button {
        background: #FFC107;
        color: #2E7D32;
        border: none;
        padding: 10px 15px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s;
    }
    
    .newsletter-form button:hover {
        background: #FFD54F;
    }
    
    .footer-bottom {
        background: rgba(0, 0, 0, 0.2);
        padding: 20px 0;
    }
    
    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        flex-wrap: wrap;
    }
    
    .footer-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer-links a:hover {
        color: #FFC107;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
        }
        
        .footer-section {
            min-width: 100%;
            margin-bottom: 30px;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            margin: 0 10px;
        }
        
        .newsletter-form {
            flex-direction: column;
        }
        
        .newsletter-form input {
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .newsletter-form button {
            border-radius: 4px;
        }
    }
</style>

<!-- Font Awesome para ícones -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">