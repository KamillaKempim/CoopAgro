<?php
// Arquivo: recuperar_senha.php - VERSÃO CORRIGIDA
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verificar se config/email.php existe
if (!file_exists('config/email.php')) {
    die("Erro: Arquivo config/email.php não encontrado!");
}

require_once 'config/database.php';
require_once 'config/email.php';

// Configurações de segurança
define('TOKEN_EXPIRATION', 3600); // 1 hora em segundos
define('MAX_ATTEMPTS_PER_HOUR', 5); // Máximo de tentativas por hora por IP
define('MIN_PASSWORD_LENGTH', 8);

// Inicializar variáveis
$message = '';
$error = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'email';
$client_ip = $_SERVER['REMOTE_ADDR'];

// Inicializar conexão com o banco
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
}

// Função para validar email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 255;
}

// Função para registrar tentativa de recuperação
function logRecoveryAttempt($conn, $email, $ip, $success, $details = '')
{
    try {
        $sql = "INSERT INTO logs_seguranca 
                (acao, detalhes, ip_address, user_agent, data_criacao) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $action = $success ? 'recuperacao_solicitada' : 'recuperacao_falhou';
        $stmt->execute([$action, $details . " | Email: " . $email, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
        return true;
    } catch (Exception $e) {
        error_log("Erro ao logar tentativa: " . $e->getMessage());
        return false;
    }
}

// Função para verificar limite de tentativas
function checkRateLimit($conn, $ip)
{
    try {
        $sql = "SELECT COUNT(*) as attempts 
                FROM logs_seguranca 
                WHERE ip_address = ? 
                AND acao IN ('recuperacao_solicitada', 'recuperacao_falhou')
                AND data_criacao > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['attempts'] < MAX_ATTEMPTS_PER_HOUR;
    } catch (Exception $e) {
        error_log("Erro ao verificar rate limit: " . $e->getMessage());
        return true; // Em caso de erro, permite a tentativa
    }
}

// Passo 1: Solicitação de email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email) || !isValidEmail($email)) {
        $error = "Por favor, insira um email válido.";
        logRecoveryAttempt($conn, $email, $client_ip, false, "Email inválido");
    } elseif (!checkRateLimit($conn, $client_ip)) {
        $error = "Muitas tentativas recentes. Aguarde 1 hora antes de tentar novamente.";
        logRecoveryAttempt($conn, $email, $client_ip, false, "Rate limit excedido");
    } else {
        try {
            // Buscar usuário (por segurança, não revelar se existe ou não)
            $sql = "SELECT id, nome, email, ativo FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user['ativo'] != 1) {
                    $error = "Esta conta está desativada. Entre em contato com o suporte.";
                    logRecoveryAttempt($conn, $email, $client_ip, false, "Conta inativa");
                } else {
                    // Verificar se já existe token não expirado
                    $checkTokenSql = "SELECT token_recuperacao FROM usuarios 
                                     WHERE id = ? AND token_expiracao > NOW()";
                    $checkTokenStmt = $conn->prepare($checkTokenSql);
                    $checkTokenStmt->execute([$user['id']]);

                    if ($checkTokenStmt->rowCount() > 0) {
                        $message = "Já existe uma solicitação pendente. Verifique seu email ou aguarde 1 hora para solicitar novamente.";
                        logRecoveryAttempt($conn, $email, $client_ip, true, "Token já existe");
                    } else {
                        // Gerar token único e seguro
                        $token = bin2hex(random_bytes(32));
                        $expiration = date('Y-m-d H:i:s', time() + TOKEN_EXPIRATION);

                        // Salvar token no banco
                        $updateSql = "UPDATE usuarios SET 
                                     token_recuperacao = :token, 
                                     token_expiracao = :expiration,
                                     tentativas_login = 0,
                                     bloqueado_ate = NULL
                                     WHERE id = :id";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->execute([
                            ':token' => $token,
                            ':expiration' => $expiration,
                            ':id' => $user['id']
                        ]);

                        // Criar link de reset seguro
                        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                            . "://" . $_SERVER['HTTP_HOST']
                            . dirname($_SERVER['SCRIPT_NAME'])
                            . "/recuperar_senha.php?step=reset&token=" . urlencode($token);

                        // Tentar enviar email
                        $emailSent = false;
                        if (function_exists('sendPasswordResetEmail')) {
                            $emailSent = sendPasswordResetEmail($user['email'], $user['nome'], $resetLink);
                        }

                        if ($emailSent) {
                            // Registrar sucesso
                            logRecoveryAttempt($conn, $email, $client_ip, true, "Email enviado com sucesso");

                            // Limpar dados de sessão anteriores
                            unset($_SESSION['recovery_data']);

                            // Redirecionar para confirmação
                            header("Location: recuperar_senha.php?step=confirm");
                            exit();
                        } else {
                            // Se falhou o email, mostrar link na página
                            $_SESSION['recovery_data'] = [
                                'email' => $user['email'],
                                'nome' => $user['nome'],
                                'token' => $token,
                                'link' => $resetLink,
                                'expires' => $expiration,
                                'email_sent' => false
                            ];

                            logRecoveryAttempt($conn, $email, $client_ip, true, "Falha no envio de email, link exibido na tela");

                            header("Location: recuperar_senha.php?step=show_link");
                            exit();
                        }
                    }
                }
            } else {
                // Por segurança, mostrar mensagem genérica mesmo se email não existir
                $message = "Se o email existir em nosso sistema, você receberá um link de recuperação em breve.";
                logRecoveryAttempt($conn, $email, $client_ip, true, "Email não encontrado (resposta genérica)");
            }
        } catch (PDOException $e) {
            error_log("Erro recuperação senha: " . $e->getMessage());
            $error = "Erro no sistema. Por favor, tente novamente mais tarde.";
            logRecoveryAttempt($conn, $email, $client_ip, false, "Erro de banco: " . $e->getMessage());
        }
    }
}

// Passo 2: Redefinir senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $token = $_GET['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || strlen($token) !== 64) {
        $error = "Token inválido.";
        logRecoveryAttempt($conn, '', $client_ip, false, "Token inválido ou malformado");
    } elseif (empty($password) || empty($confirm_password)) {
        $error = "Por favor, preencha todos os campos.";
    } elseif ($password !== $confirm_password) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $error = "A senha deve ter pelo menos " . MIN_PASSWORD_LENGTH . " caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "A senha deve conter letras maiúsculas, minúsculas e números.";
    } else {
        try {
            // Verificar token válido e não expirado
            $sql = "SELECT id, email FROM usuarios 
                    WHERE token_recuperacao = :token 
                    AND token_expiracao > NOW() 
                    AND ativo = 1
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token' => $token]);

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificar se a senha não é muito similar à anterior (opcional)
                // Hash da nova senha
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Iniciar transação para garantir consistência
                $conn->beginTransaction();

                try {
                    // Atualizar senha e limpar token
                    $updateSql = "UPDATE usuarios SET 
                                 senha = :senha, 
                                 token_recuperacao = NULL, 
                                 token_expiracao = NULL,
                                 ultimo_login = NOW(),
                                 tentativas_login = 0,
                                 bloqueado_ate = NULL
                                 WHERE id = :id";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->execute([':senha' => $hashedPassword, ':id' => $user['id']]);

                    // Registrar log de segurança
                    $logSql = "INSERT INTO logs_seguranca 
                              (usuario_id, acao, detalhes, ip_address, user_agent) 
                              VALUES (:user_id, :acao, :detalhes, :ip, :agent)";
                    $logStmt = $conn->prepare($logSql);
                    $logStmt->execute([
                        ':user_id' => $user['id'],
                        ':acao' => 'senha_redefinida',
                        ':detalhes' => 'Senha redefinida via recuperação',
                        ':ip' => $client_ip,
                        ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);

                    // Confirmar transação
                    $conn->commit();

                    $message = "✅ Senha redefinida com sucesso! Você será redirecionado para o login em 5 segundos.";

                    // Limpar qualquer sessão de recuperação
                    unset($_SESSION['recovery_data']);

                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            } else {
                $error = "Token inválido ou expirado. Por favor, solicite um novo link.";
                logRecoveryAttempt($conn, '', $client_ip, false, "Token inválido ou expirado");
            }
        } catch (PDOException $e) {
            error_log("Erro reset senha: " . $e->getMessage());
            $error = "Erro no sistema. Tente novamente.";
            logRecoveryAttempt($conn, '', $client_ip, false, "Erro de banco: " . $e->getMessage());
        }
    }
}

// Se chegou aqui via GET com token, verificar se é válido
if ($step === 'reset' && empty($_POST) && isset($_GET['token'])) {
    $token = $_GET['token'];

    if (strlen($token) !== 64) {
        $error = "Token inválido.";
        $step = 'invalid';
    } else {
        try {
            $sql = "SELECT id FROM usuarios 
                    WHERE token_recuperacao = :token 
                    AND token_expiracao > NOW()
                    AND ativo = 1
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token' => $token]);

            if ($stmt->rowCount() !== 1) {
                $error = "Token inválido ou expirado.";
                $step = 'invalid';
                logRecoveryAttempt($conn, '', $client_ip, false, "Token inválido na verificação GET");
            }
        } catch (PDOException $e) {
            $error = "Erro ao verificar token.";
            $step = 'invalid';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --vermelho: #dc3545;
            --amarelo: #ffc107;
        }

        body {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .recovery-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .recovery-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .recovery-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .recovery-body {
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

        .btn-recovery {
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

        .btn-recovery:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }

        .btn-recovery:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .password-strength {
            height: 5px;
            border-radius: 2px;
            margin-top: 5px;
            background: #e9ecef;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }

        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            font-weight: bold;
            color: #666;
            font-size: 0.9rem;
        }

        .step.active {
            background: var(--verde-principal);
            color: white;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        }

        .step.completed {
            background: var(--verde-claro);
            color: var(--verde-principal);
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e9ecef;
            margin: 0 10px;
            max-width: 100px;
        }

        .security-note {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--verde-principal);
            font-size: 0.9rem;
        }

        .copy-link-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            border: 1px dashed #dee2e6;
        }

        @media (max-width: 576px) {
            .recovery-container {
                margin: 20px;
                border-radius: 15px;
            }

            .recovery-header {
                padding: 20px;
            }

            .recovery-header h1 {
                font-size: 1.8rem;
            }

            .recovery-body {
                padding: 30px 20px;
            }

            .step {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .step-line {
                max-width: 80px;
            }
        }

        /* Botão de acessibilidade */
       
    </style>
</head>

<body>

    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>
    <?php include_once 'includes/_acessibilidade.php'; ?>
    

    <?php
    if (file_exists('includes/_menu.php')) {
        require_once 'includes/_menu.php';
    }
    ?>

    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="recovery-container">
                    <div class="recovery-header">
                        <h1><i class="bi bi-shield-lock"></i> Recuperar Senha</h1>
                        <p>Siga os passos para redefinir sua senha</p>
                    </div>

                    <div class="recovery-body">
                        <div class="step-indicator">
                            <div
                                class="step <?php echo in_array($step, ['email', 'confirm', 'show_link', 'reset', 'invalid']) ? 'completed' : ''; ?>">
                                1</div>
                            <div class="step-line"></div>
                            <div
                                class="step <?php echo in_array($step, ['reset', 'invalid']) ? 'active' : ''; ?> <?php echo $step === 'invalid' ? '' : ''; ?>">
                                2</div>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($step === 'email'): ?>
                            <div class="security-note">
                                <i class="bi bi-shield-check me-2"></i>
                                <strong>Segurança:</strong> Por medida de segurança, não revelaremos se o email existe ou
                                não em nosso sistema.
                            </div>

                            <form method="POST" action="" id="emailForm">
                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope me-1"></i> Email da Conta
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required autofocus
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        placeholder="seu@email.com">
                                    <div class="form-text">Enviaremos um link de recuperação para este email.</div>
                                </div>

                                <button type="submit" class="btn btn-recovery mb-3" id="submitEmailBtn">
                                    <i class="bi bi-send me-2"></i> Enviar Link de Recuperação
                                </button>

                                <div class="text-center">
                                    <a href="login.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-left me-1"></i> Voltar para o Login
                                    </a>
                                </div>
                            </form>

                        <?php elseif ($step === 'confirm'): ?>
                            <!-- Confirmação de envio -->
                            <div class="alert alert-success">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill fs-3 me-3 text-success mt-1"></i>
                                    <div>
                                        <h5 class="alert-heading">Solicitação recebida!</h5>
                                        <p>
                                            Se o email informado existir em nosso sistema, você receberá um link de
                                            recuperação em breve.
                                        </p>
                                        <p class="mb-0">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Verifique sua caixa de entrada e também a pasta de spam.
                                                O link é válido por 1 hora.
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="security-note">
                                <h6><i class="bi bi-clock me-2"></i>Próximos passos:</h6>
                                <ol class="mb-0">
                                    <li>Verifique seu email (incluindo spam/lixo eletrônico)</li>
                                    <li>Clique no link recebido</li>
                                    <li>Crie uma nova senha segura</li>
                                    <li>Faça login com a nova senha</li>
                                </ol>
                            </div>

                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Voltar para o Login
                                </a>
                            </div>

                        <?php elseif ($step === 'show_link' && isset($_SESSION['recovery_data'])): ?>
                            <!-- Mostrar link diretamente (quando email falha) -->
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning mt-1"></i>
                                    <div>
                                        <h5 class="alert-heading">Atenção</h5>
                                        <p>Não foi possível enviar o email para
                                            <strong><?php echo htmlspecialchars($_SESSION['recovery_data']['email']); ?></strong>.
                                        </p>
                                        <p class="mb-0">Use o link abaixo para redefinir sua senha:</p>
                                    </div>
                                </div>
                            </div>

                            <div class="copy-link-box">
                                <p class="mb-2"><strong>Copie este link e cole no seu navegador:</strong></p>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="recoveryLink"
                                        value="<?php echo htmlspecialchars($_SESSION['recovery_data']['link']); ?>"
                                        readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">
                                        <i class="bi bi-clipboard"></i> Copiar
                                    </button>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-clock me-1"></i>
                                    Expira em:
                                    <?php echo date('d/m/Y H:i:s', strtotime($_SESSION['recovery_data']['expires'])); ?>
                                </p>
                                <div class="text-center">
                                    <a href="<?php echo htmlspecialchars($_SESSION['recovery_data']['link']); ?>"
                                        class="btn btn-success">
                                        <i class="bi bi-arrow-right-circle me-2"></i> Ir para Redefinição de Senha
                                    </a>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Voltar para o Login
                                </a>
                            </div>

                        <?php elseif ($step === 'reset'): ?>
                            <!-- Passo 2: Redefinir senha -->
                            <div class="security-note">
                                <i class="bi bi-key me-2"></i>
                                <strong>Requisitos da senha:</strong> Mínimo 8 caracteres, com letras maiúsculas, minúsculas
                                e números.
                            </div>

                            <form method="POST"
                                action="?step=reset&token=<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>"
                                id="resetForm">
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock me-1"></i> Nova Senha
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" required
                                        minlength="<?php echo MIN_PASSWORD_LENGTH; ?>" placeholder="Digite sua nova senha">
                                    <div class="password-strength mt-2">
                                        <div class="strength-fill" id="strengthBar"></div>
                                    </div>
                                    <div class="form-text" id="strengthText">Força da senha: </div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="bi bi-lock-fill me-1"></i> Confirmar Nova Senha
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required minlength="<?php echo MIN_PASSWORD_LENGTH; ?>"
                                        placeholder="Digite a senha novamente">
                                    <div id="passwordMatch" class="form-text mt-1"></div>
                                </div>

                                <button type="submit" class="btn btn-recovery mb-3" id="submitResetBtn">
                                    <i class="bi bi-check-circle me-2"></i> Redefinir Senha
                                </button>

                                <div class="text-center">
                                    <a href="login.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-left me-1"></i> Voltar para o Login
                                    </a>
                                </div>
                            </form>

                        <?php else: ?>
                            <!-- Token inválido ou página desconhecida -->
                            <div class="alert alert-danger">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 mt-1"></i>
                                    <div>
                                        <h5 class="alert-heading">Link Inválido ou Expirado</h5>
                                        <p class="mb-0">Este link de recuperação não é válido ou já expirou. Por favor,
                                            solicite um novo link.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="recuperar_senha.php" class="btn btn-primary me-2">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Solicitar Novo Link
                                </a>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Voltar para Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-center py-3 border-top" style="background: #f8f9fa;">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            Sistema seguro • <?php echo date('Y'); ?> CoopAgro
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Prevenir reenvio de formulário
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            function copyLink() {
                const linkInput = document.getElementById('recoveryLink');
                if (linkInput) {
                    linkInput.select();
                    linkInput.setSelectionRange(0, 99999);
                    navigator.clipboard.writeText(linkInput.value)
                        .then(() => {
                            const originalText = linkInput.value;
                            linkInput.value = '✅ Link copiado!';
                            setTimeout(() => {
                                linkInput.value = originalText;
                            }, 2000);
                        })
                        .catch(err => {
                            console.error('Erro ao copiar: ', err);
                            alert('Não foi possível copiar o link. Tente copiar manualmente.');
                        });
                }
            }

            function checkPasswordStrength(password) {
                let strength = 0;
                let text = '';
                let color = '';

                // Verificar comprimento
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;

                // Verificar caracteres mistos
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;

                // Verificar números
                if (/\d/.test(password)) strength++;

                // Verificar símbolos
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                // Definir texto e cor
                switch (strength) {
                    case 0:
                    case 1:
                        text = 'Muito fraca';
                        color = '#dc3545';
                        break;
                    case 2:
                        text = 'Fraca';
                        color = '#fd7e14';
                        break;
                    case 3:
                        text = 'Razoável';
                        color = '#ffc107';
                        break;
                    case 4:
                        text = 'Forte';
                        color = '#20c997';
                        break;
                    case 5:
                    case 6:
                        text = 'Muito forte';
                        color = '#198754';
                        break;
                }

                // Atualizar UI
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');

                if (strengthBar && strengthText) {
                    strengthBar.style.width = (strength * 16.66) + '%';
                    strengthBar.style.backgroundColor = color;
                    strengthText.innerHTML = `Força da senha: <strong>${text}</strong>`;
                }

                return strength >= 3; 
            }

            function checkPasswordMatch() {
                const password = document.getElementById('password');
                const confirm = document.getElementById('confirm_password');
                const matchText = document.getElementById('passwordMatch');
                const submitBtn = document.getElementById('submitResetBtn');

                if (!password || !confirm) return false;

                const pass = password.value;
                const conf = confirm.value;

                if (conf.length === 0) {
                    if (matchText) matchText.innerHTML = '';
                    return false;
                }

                if (pass === conf) {
                    if (matchText) {
                        matchText.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>As senhas coincidem</span>';
                    }
                    if (submitBtn) submitBtn.disabled = false;
                    return true;
                } else {
                    if (matchText) {
                        matchText.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>As senhas não coincidem</span>';
                    }
                    if (submitBtn) submitBtn.disabled = true;
                    return false;
                }
            }

            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');

            if (passwordInput) {
                passwordInput.addEventListener('input', function () {
                    checkPasswordStrength(this.value);
                    checkPasswordMatch();
                });
            }

            if (confirmInput) {
                confirmInput.addEventListener('input', checkPasswordMatch);
            }

            const emailForm = document.getElementById('emailForm');
            if (emailForm) {
                emailForm.addEventListener('submit', function (e) {
                    const emailInput = document.getElementById('email');
                    const submitBtn = document.getElementById('submitEmailBtn');

                    if (emailInput && !isValidEmail(emailInput.value)) {
                        e.preventDefault();
                        alert('Por favor, insira um email válido.');
                        emailInput.focus();
                        return false;
                    }

                    // Desabilitar botão para evitar reenvio
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processando...';
                    }
                });
            }

            const resetForm = document.getElementById('resetForm');
            if (resetForm) {
                resetForm.addEventListener('submit', function (e) {
                    const password = document.getElementById('password');
                    const confirm = document.getElementById('confirm_password');
                    const submitBtn = document.getElementById('submitResetBtn');

                    let isValid = true;
                    let errorMessage = '';

                    if (password && password.value.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
                        isValid = false;
                        errorMessage += `A senha deve ter pelo menos <?php echo MIN_PASSWORD_LENGTH; ?> caracteres.\n`;
                    }

                    if (password && confirm && password.value !== confirm.value) {
                        isValid = false;
                        errorMessage += 'As senhas não coincidem.\n';
                    }

                    if (password && (!/[A-Z]/.test(password.value) || !/[a-z]/.test(password.value) || !/\d/.test(password.value))) {
                        isValid = false;
                        errorMessage += 'A senha deve conter letras maiúsculas, minúsculas e números.\n';
                    }

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, corrija os seguintes erros:\n\n' + errorMessage);
                        return false;
                    }

                    // Desabilitar botão para evitar reenvio
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Redefinindo...';
                    }
                });
            }

            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email) && email.length <= 255;
            }

            // ========== AUTO-REDIRECIONAMENTO APÓS SUCESSO ==========
            const successAlert = document.querySelector('.alert-success');
            if (successAlert && successAlert.textContent.includes('redirecionado')) {
                let seconds = 5;
                const countdownElement = document.createElement('div');
                countdownElement.className = 'mt-2 text-center';
                countdownElement.innerHTML = `<small>Redirecionando em <span id="countdown">${seconds}</span> segundos...</small>`;
                successAlert.appendChild(countdownElement);

                const countdownInterval = setInterval(() => {
                    seconds--;
                    document.getElementById('countdown').textContent = seconds;

                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = 'login.php';
                    }
                }, 1000);
            }

            // ========== LIMPAR MENSAGENS APÓS 10 SEGUNDOS ==========
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('show')) {
                        const closeBtn = alert.querySelector('.btn-close');
                        if (closeBtn) closeBtn.click();
                    }
                }, 10000);
            });

            // ========== FOCO AUTOMÁTICO ==========
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }

            const passwordInputFocus = document.getElementById('password');
            if (passwordInputFocus && window.location.search.includes('step=reset')) {
                passwordInputFocus.focus();
            }
        });
    </script>

    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>

</body>

</html>