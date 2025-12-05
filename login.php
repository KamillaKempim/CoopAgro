<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: perfil.php");
    exit();
}

$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Preencha todos os campos!";
    } else {
        try {
            $conn = getDBConnection();

            $sql = "SELECT id, nome, email, senha, tipo_usuario, email_verificado FROM usuarios WHERE email = ? OR nome = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $username]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user['email_verificado'] != 1) {
                    $error = "❌ <strong>Email não verificado!</strong><br><br>";
                    $error .= "📧 Para acessar sua conta, você precisa verificar seu email.<br>";
                    $error .= "🔗 <a href='resend-verification.php?email=" . urlencode($user['email']) . "' class='text-warning fw-bold'>Reenviar link de verificação</a><br><br>";
                    $error .= "📌 Verifique sua caixa de entrada <strong>(e a pasta de spam)</strong>.";
                    $info = "📩 <strong>Email cadastrado:</strong> " . htmlspecialchars($user['email']) . "<br>";
                    $info .= "👤 <strong>Nome:</strong> " . htmlspecialchars($user['nome']);
                } else if (password_verify($password, $user['senha'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['nome'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
                    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['login_time'] = time();

                    session_regenerate_id(true);
                    header("Location: perfil.php");
                    exit();
                } else {
                    $error = "Senha incorreta!";
                }
            } else {
                $error = "Usuário não encontrado!";
            }
        } catch (PDOException $e) {
            error_log("Erro de login: " . $e->getMessage());
            $error = "Erro no sistema. Tente novamente mais tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/login.css" />
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --bege: #f5f5dc;
            --roxo-admin: #6a1b9a;
            --roxo-admin-claro: #9c4dcc;
        }

        body {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            max-width: 450px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .login-body {
            padding: 40px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }

        .login-links {
            text-align: center;
            margin-top: 20px;
        }

        .login-links a {
            color: var(--verde-principal);
            text-decoration: none;
            font-weight: 500;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .toggle-password:hover {
            color: var(--verde-principal);
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input:checked {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .test-credentials {
            background: linear-gradient(135deg, var(--verde-claro) 0%, #e8f5e8 100%);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid var(--verde-principal);
        }

        .test-credentials h6 {
            color: var(--verde-principal);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .test-credentials p {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                border-radius: 15px;
            }

            .login-header {
                padding: 20px;
            }

            .login-header h1 {
                font-size: 1.8rem;
            }

            .login-body {
                padding: 30px 20px;
            }

            .login-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
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

    <?php if (file_exists('includes/_menu.php'))
        require_once "includes/_menu.php"; ?>

    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <h1><i class="bi bi-person-circle"></i> CoopAgro</h1>
                        <p>Faça login para acessar sua conta</p>
                    </div>

                    <div class="login-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($info)): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <?php echo $info; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-1"></i> Email ou Nome de Usuário
                                </label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    required autofocus placeholder="Digite seu email ou nome de usuário">
                            </div>

                            <div class="mb-4 password-container">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock me-1"></i> Senha
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Digite sua senha">
                                <button type="button" class="toggle-password" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            <div class="login-options mb-4">
                                <div class="remember-me">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Lembrar-me</label>
                                </div>
                                <div>
                                    <a href="recuperar_senha.php" class="text-decoration-none">
                                        <i class="bi bi-question-circle me-1"></i> Esqueceu a senha?
                                    </a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
                            </button>
                        </form>

                        <div class="login-links mt-4 pt-3 border-top">
                            <p class="mb-3">
                                <i class="bi bi-envelope me-1"></i>
                                <a href="resend-verification.php">Não recebeu o email de verificação?</a>
                            </p>

                            <p class="mt-4 mb-0">
                                <i class="bi bi-person-plus me-1"></i>
                                <a href="cadastro.php">Não tem uma conta? Cadastre-se</a>
                            </p>
                        </div>
                    </div>

                    <div class="login-footer">
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-shield-check me-1"></i>
                            © <?php echo date('Y'); ?> CoopAgro. Sistema seguro e protegido.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (file_exists('includes/_footer.php'))
        require_once "includes/_footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const icon = this.querySelector('i');
                    if (type === 'password') {
                        icon.className = 'bi bi-eye';
                        this.setAttribute('title', 'Mostrar senha');
                    } else {
                        icon.className = 'bi bi-eye-slash';
                        this.setAttribute('title', 'Ocultar senha');
                    }
                });
            }

            const form = document.querySelector('form');
            const usernameInput = document.getElementById('username');

            if (form) {
                form.addEventListener('submit', function (e) {
                    let isValid = true;
                    let errorMessage = '';

                    if (usernameInput.value.trim().length < 3) {
                        isValid = false;
                        errorMessage += 'O nome de usuário/email deve ter pelo menos 3 caracteres.\n';
                    }

                    if (passwordInput.value.length < 6) {
                        isValid = false;
                        errorMessage += 'A senha deve ter pelo menos 6 caracteres.\n';
                    }

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, corrija os seguintes erros:\n\n' + errorMessage);
                        return false;
                    }

                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Entrando...';
                    }
                });
            }

            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }

            const rememberCheckbox = document.getElementById('remember');
            if (localStorage.getItem('rememberLogin') === 'true') {
                rememberCheckbox.checked = true;
                const savedUsername = localStorage.getItem('loginUsername');
                if (savedUsername && usernameInput) {
                    usernameInput.value = savedUsername;
                }
            }

            if (form && rememberCheckbox) {
                form.addEventListener('submit', function () {
                    if (rememberCheckbox.checked && usernameInput.value) {
                        localStorage.setItem('loginUsername', usernameInput.value);
                        localStorage.setItem('rememberLogin', 'true');
                    } else {
                        localStorage.removeItem('loginUsername');
                        localStorage.removeItem('rememberLogin');
                    }
                });
            }

            const errorAlert = document.querySelector('.alert-danger');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.remove('show');
                    setTimeout(() => {
                        errorAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>

    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>

</body>

</html>