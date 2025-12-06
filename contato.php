<?php
$titulo = "Contato";
$descricao = "Entre em contato conosco";
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Seus estilos -->
    <link rel="stylesheet" href="css/index.css" />
    <link rel="stylesheet" href="css/contato.css" />

    <title>Contato</title>
</head>

<body>

    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <!-- Painel de acessibilidade -->
    <div class="painel-flutuante">
        <button id="btnAbrir">⚙️</button>
        <div class="painel-acessibilidade" id="painelAcessibilidade">
            <h4>Painel de Acessibilidade</h4>
            <button onclick="contraste()"><i class="bi bi-brightness-high-fill"></i> Alto contraste</button>
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

    <header>
        <?php require_once "includes/_segundo_menu.php"; ?>
    </header>

    <section class="contato-header">
        <h1>Fale Conosco</h1>
    </section>

    <div class="contato-card">
        <h3 class="text-center mb-4">Entre em contato</h3>

        <form onsubmit="enviarWhats(event)">

            <div class="mb-3">
                <label class="form-label">Nome completo</label>
                <input type="text" name="nome" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Assunto</label>
                <input type="text" name="assunto" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mensagem</label>
                <textarea name="mensagem" rows="5" class="form-control" required></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">Enviar Mensagem</button>
        </form>
    </div>

    <footer>
        <?php require_once "includes/_footer.php"; ?>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Função WhatsApp -->
    <script>
        function enviarWhats(event) {
            event.preventDefault();
            let nome = document.querySelector('input[name="nome"]').value;
            let assunto = document.querySelector('input[name="assunto"]').value;
            let mensagem = document.querySelector('textarea[name="mensagem"]').value;
            let numero = "5569992153975";
            let texto =
                "📩 *Olá, gostaria de ser atendido pela CoopAgro!*\n\n" +
                "*Nome:* " + nome + "\n" +
                "*Assunto:* " + assunto + "\n" +
                "*Mensagem:* " + mensagem;
            let url = "https://wa.me/" + numero + "?text=" + encodeURIComponent(texto);
            window.open(url, "_blank");
        }
    </script>

    <!-- VLibras Plugin -->
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>

</body>
</html>
