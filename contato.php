<?php
$titulo = "Contato";
$descricao = "Entre em contato conosco";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="CSS/index.css" />
    <link rel="stylesheet" href="CSS/contato.css" />

    <title>Contato</title>
</head>

<body>

<header>
    <?php require_once "includes/_segundo_menu.php"; ?>
</header>

<section class="contato-header">
    <h1>Fale Conosco</h1>
</section>

<div class="contato-card">
    <h3 class="text-center mb-4">Entre em contato</h3>

    <!-- FORMULÁRIO ENVIANDO PARA O WHATSAPP -->
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
function enviarWhats(event) {
    event.preventDefault(); // impede o envio normal

    let nome = document.querySelector('input[name="nome"]').value;
    let assunto = document.querySelector('input[name="assunto"]').value;
    let mensagem = document.querySelector('textarea[name="mensagem"]').value;

    // SEU NÚMERO
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
