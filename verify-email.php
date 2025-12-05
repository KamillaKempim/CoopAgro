<?php

session_start();
require_once 'config/database.php';
require_once 'config/email.php';

$message = '';
$messageClass = '';
$redirectToLogin = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = "❌ Token de verificação não fornecido.";
    $messageClass = "danger";
} else {
    try {
        $conn = getDBConnection();

        // Buscar usuário com token válido
        $stmt = $conn->prepare("
            SELECT id, nome, email, verification_expires 
            FROM usuarios 
            WHERE verification_token = :token 
            AND email_verificado = 0
            AND verification_expires > NOW()
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Atualizar usuário como verificado
            $updateStmt = $conn->prepare("
                UPDATE usuarios 
                SET email_verificado = 1, 
                    verification_token = NULL,
                    verification_expires = NULL,
                    data_verificacao = NOW()
                WHERE id = :id
            ");
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();

            sendWelcomeEmail($user['email'], $user['nome']);

            $message = "🎉 <strong>Email verificado com sucesso!</strong><br><br>";
            $message .= "✅ Sua conta foi ativada com sucesso!<br>";
            $message .= "👋 Bem-vindo(a) à CoopAgro, " . htmlspecialchars($user['nome']) . "!<br><br>";
            $message .= "📧 <strong>Email verificado:</strong> " . htmlspecialchars($user['email']) . "<br>";
            $message .= "⏰ <strong>Data da verificação:</strong> " . date('d/m/Y H:i:s') . "<br><br>";
            $message .= "👉 <a href='login.php' class='text-success fw-bold'>Clique aqui para fazer login</a>";
            $messageClass = "success";
            $redirectToLogin = true;

            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmtLog = $conn->prepare("
                INSERT INTO logs_seguranca (usuario_id, acao, ip_address, user_agent)
                VALUES (?, 'email_verificado', ?, ?)
            ");
            $stmtLog->execute([$user['id'], $ip, $userAgent]);

        } else {
            // Verificar se já está verificado
            $stmt2 = $conn->prepare("
                SELECT id, nome, email_verificado 
                FROM usuarios 
                WHERE verification_token = :token 
                OR (email_verificado = 1 AND verification_token IS NULL)
            ");
            $stmt2->bindParam(':token', $token);
            $stmt2->execute();

            if ($stmt2->rowCount() > 0) {
                $user = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($user['email_verificado'] == 1) {
                    $message = "ℹ️ <strong>Email já verificado!</strong><br><br>";
                    $message .= "✅ Este email já foi verificado anteriormente.<br>";
                    $message .= "👉 <a href='login.php' class='text-success fw-bold'>Faça login para acessar sua conta</a>";
                    $messageClass = "info";
                } else {

                    $message = "⚠️ <strong>Link expirado!</strong><br><br>";
                    $message .= "❌ Este link de verificação expirou (válido por 24 horas).<br>";
                    $message .= "🔗 <a href='resend-verification.php?email=" . urlencode($user['email']) . "' class='text-warning fw-bold'>Solicitar novo link de verificação</a><br><br>";
                    $message .= "📧 Ou entre em contato com o suporte.";
                    $messageClass = "warning";
                }
            } else {
                $message = "❌ <strong>Token inválido!</strong><br><br>";
                $message .= "⚠️ O token de verificação fornecido é inválido ou não existe.<br>";
                $message .= "🔗 <a href='cadastro.php' class='text-success fw-bold'>Faça um novo cadastro</a> ou entre em contato com o suporte.";
                $messageClass = "danger";
            }
        }
    } catch (PDOException $e) {
        error_log("Erro na verificação de email: " . $e->getMessage());
        $message = "🚨 <strong>Erro ao processar verificação!</strong><br><br>";
        $message .= "❌ Ocorreu um erro no sistema. Tente novamente mais tarde.<br>";
        $message .= "📧 Se o problema persistir, entre em contato com o suporte.";
        $messageClass = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Email - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
        }

        body {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .verification-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        .icon-container {
            font-size: 5rem;
            margin-bottom: 30px;
        }

        .icon-success {
            color: #28a745;
            animation: successPulse 2s infinite;
        }

        .icon-warning {
            color: #ffc107;
        }

        .icon-danger {
            color: #dc3545;
        }

        .icon-info {
            color: #17a2b8;
        }

        @keyframes successPulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        h1 {
            color: var(--verde-principal);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            border: none;
            padding: 12px 35px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
            color: white;
        }

        .btn-outline-success {
            border: 2px solid var(--verde-principal);
            color: var(--verde-principal);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-success:hover {
            background: var(--verde-principal);
            color: white;
        }

        .alert-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
            border-left: 4px solid var(--verde-principal);
        }

        .email-highlight {
            background: #e8f5e8;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #c8e6c9;
            margin: 10px 0;
            font-family: monospace;
            font-weight: bold;
        }

        @media (max-width: 576px) {
            .verification-container {
                padding: 30px 20px;
                border-radius: 15px;
            }

            .icon-container {
                font-size: 4rem;
            }

            .btn-success,
            .btn-outline-success {
                width: 100%;
                margin: 5px 0;
            }
        }

        /* Botão de acessibilidade */
        .painel-flutuante {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        #btnAbrir {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background-color: #0c7534;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }

        #btnAbrir:hover {
            background-color: #0b7d44;
        }


        .painel-acessibilidade {
            display: none;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 10px;
            background: #ffffff;
            color: rgb(11, 66, 5);
            padding: 12px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }


        .painel-acessibilidade button {
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.2s;
            background-color: #f0f0f0;
        }

        .painel-acessibilidade button:hover {
            background-color: #d4d4d4;
        }


        .modo-contraste,
        .modo-contraste * {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #fff !important;
        }

        .modo-contraste a {
            color: #FFD700 !important;
            text-decoration: underline;
        }

        .modo-contraste img {
            filter: brightness(0.8) !important;
        }

        .modo-contraste,
        .modo-contraste * {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #fff !important;
            fill: #fff !important;
            stroke: #fff !important;
        }

        .modo-contraste a,
        .modo-contraste a * {
            color: #FFD700 !important;
            text-decoration: underline !important;
        }

        .modo-contraste * {
            background-image: none !important;
        }

        .modo-contraste img,
        .modo-contraste [style*="background-image"] {
            filter: grayscale(1) brightness(0.4) !important;
        }

        .modo-contraste .card,
        .modo-contraste .container,
        .modo-contraste section,
        .modo-contraste .row,
        .modo-contraste .col,
        .modo-contraste footer,
        .modo-contraste header,
        .modo-contraste nav {
            background-color: #000 !important;
            color: #fff !important;
        }

        .modo-contraste button,
        .modo-contraste .btn {
            background-color: #222 !important;
            color: #fff !important;
            border: 1px solid #fff !important;
        }

        .modo-contraste .bi,
        .modo-contraste i {
            color: #fff !important;
        }

        .modo-contraste .carousel-item,
        .modo-contraste .carousel-caption {
            background-color: #000 !important;
        }

        @media (max-width: 768px) {
            .container img {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .texto {
                width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .hero {
                height: auto;
            }

            .hero-img {
                width: 100%;
                height: auto;
                object-fit: contain;
            }
        }

        .modo-contraste img,
        .modo-contraste [style*="background-image"] {
            filter: grayscale(0.2) brightness(0.8) !important;
        }
    </style>
</head>

<body>

    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

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


    <div class="verification-container">
        <div class="icon-container">
            <?php if ($messageClass == 'success'): ?>
                <i class="bi bi-check-circle-fill icon-success"></i>
            <?php elseif ($messageClass == 'warning'): ?>
                <i class="bi bi-exclamation-triangle-fill icon-warning"></i>
            <?php elseif ($messageClass == 'danger'): ?>
                <i class="bi bi-x-circle-fill icon-danger"></i>
            <?php else: ?>
                <i class="bi bi-info-circle-fill icon-info"></i>
            <?php endif; ?>
        </div>

        <h1>
            <?php if ($messageClass == 'success'): ?>
                Verificação Concluída! 🎉
            <?php elseif ($messageClass == 'warning'): ?>
                Link Expirado ⚠️
            <?php elseif ($messageClass == 'danger'): ?>
                Token Inválido ❌
            <?php else: ?>
                Informação ℹ️
            <?php endif; ?>
        </h1>

        <div class="alert-content">
            <?php echo $message; ?>
        </div>

        <div class="mt-4">
            <?php if ($messageClass == 'success'): ?>
                <a href="login.php" class="btn-success">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Ir para Login
                </a>
                <p class="mt-3 text-muted">Redirecionando em <span id="countdown">5</span> segundos...</p>
                <script>
                    let countdown = 5;
                    const countdownElement = document.getElementById('countdown');
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                </script>
            <?php elseif ($messageClass == 'info'): ?>
                <a href="login.php" class="btn-success">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Fazer Login
                </a>
                <a href="index.php" class="btn-outline-success">
                    <i class="bi bi-house me-2"></i> Página Inicial
                </a>
            <?php elseif ($messageClass == 'warning'): ?>
                <a href="resend-verification.php" class="btn-success">
                    <i class="bi bi-envelope-arrow-up me-2"></i> Novo Link
                </a>
                <a href="index.php" class="btn-outline-success">
                    <i class="bi bi-house me-2"></i> Página Inicial
                </a>
            <?php else: ?>
                <a href="cadastro.php" class="btn-success">
                    <i class="bi bi-person-plus me-2"></i> Novo Cadastro
                </a>
                <a href="index.php" class="btn-outline-success">
                    <i class="bi bi-house me-2"></i> Página Inicial
                </a>
            <?php endif; ?>
        </div>

        <div class="mt-5 pt-4 border-top">
            <p class="text-muted small mb-0">
                <i class="bi bi-shield-check me-1"></i>
                Sistema seguro - CoopAgro © <?php echo date('Y'); ?>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>