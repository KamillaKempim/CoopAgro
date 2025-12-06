<?php

session_start();
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/auth.php';


if (isLoggedIn()) {
    header("Location: perfil.php");
    exit();
}

$message = '';
$messageClass = '';
$redirectToLogin = false;
$csrf_token = generateCSRFToken('cadastro');
$emailVerificationRequired = true;

function generateEmailVerificationToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

function validarForcaSenha($senha)
{
    if (strlen($senha) < 8)
        return false;
    if (!preg_match('/[a-z]/', $senha))
        return false;
    if (!preg_match('/[A-Z]/', $senha))
        return false;
    if (!preg_match('/[0-9]/', $senha))
        return false;
    if (!preg_match('/[^A-Za-z0-9]/', $senha))
        return false;

    $commonPasswords = ['123456', 'password', '123456789', '12345678', '12345'];
    if (in_array(strtolower($senha), $commonPasswords))
        return false;

    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'cadastro')) {
        $message = "Token de segurança inválido. Tente novamente.";
        $messageClass = "danger";
    } else {
        if (!isset($_SESSION['cadastro_tentativas'])) {
            $_SESSION['cadastro_tentativas'] = 0;
        }
        if (!isset($_SESSION['cadastro_tempo'])) {
            $_SESSION['cadastro_tempo'] = time();
        }
        if (time() - $_SESSION['cadastro_tempo'] > 3600) {
            $_SESSION['cadastro_tentativas'] = 0;
            $_SESSION['cadastro_tempo'] = time();
        }
        if ($_SESSION['cadastro_tentativas'] > 5) {
            $message = "Muitas tentativas de cadastro. Tente novamente mais tarde.";
            $messageClass = "danger";
        } else {
            $_SESSION['cadastro_tentativas']++;

            if (!isset($_POST['aceitar_termos'])) {
                $message = "Você deve aceitar os Termos de Uso e Política de Privacidade para se cadastrar.";
                $messageClass = "warning";
            } else {
                $dados = [
                    'nome' => filter_var($_POST['nome'] ?? '', FILTER_SANITIZE_STRING),
                    'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                    'celular' => filter_var($_POST['celular'] ?? '', FILTER_SANITIZE_STRING),
                    'senha' => $_POST['senha'] ?? '',
                    'confirmar_senha' => $_POST['confirmar_senha'] ?? '',
                    'rua' => filter_var($_POST['rua'] ?? '', FILTER_SANITIZE_STRING),
                    'cep' => filter_var($_POST['cep'] ?? '', FILTER_SANITIZE_STRING),
                    'numero' => filter_var($_POST['numero'] ?? '', FILTER_SANITIZE_STRING),
                    'bairro' => filter_var($_POST['bairro'] ?? '', FILTER_SANITIZE_STRING),
                    'municipio' => filter_var($_POST['municipio'] ?? '', FILTER_SANITIZE_STRING),
                    'estado' => filter_var($_POST['estado'] ?? '', FILTER_SANITIZE_STRING),
                    'tipo_usuario' => filter_var($_POST['tipo_usuario'] ?? '', FILTER_SANITIZE_STRING),
                    'cpf_cnpj' => preg_replace('/[^0-9]/', '', $_POST['cpf_cnpj'] ?? '')
                ];


                $errors = [];

                if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Email inválido.";
                }

                if (!validarForcaSenha($dados['senha'])) {
                    $errors[] = "A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.";
                }

                if ($dados['senha'] !== $dados['confirmar_senha']) {
                    $errors[] = "As senhas não coincidem.";
                }

                if (!validateCpfCnpj($dados['cpf_cnpj'])) {
                    $errors[] = "CPF ou CNPJ inválido.";
                }

                if (!preg_match('/^\d{5}-\d{3}$/', $dados['cep'])) {
                    $errors[] = "CEP inválido. Use o formato 00000-000.";
                }

                $celular_limpo = preg_replace('/[^0-9]/', '', $dados['celular']);
                if (strlen($celular_limpo) < 10 || strlen($celular_limpo) > 11) {
                    $errors[] = "Celular inválido.";
                }

                if (empty($errors)) {
                    $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

                    try {
                        $conn = getDBConnection();
                        $stmt = $conn->prepare("SELECT id, email_verificado FROM usuarios WHERE email = :email");
                        $stmt->bindParam(':email', $dados['email']);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($existingUser['email_verificado'] == 1) {
                                $errors[] = "Este email já está cadastrado e verificado.";
                            } else {
                                $errors[] = "Este email já foi cadastrado mas não foi verificado. Verifique sua caixa de email ou solicite um novo link.";
                            }
                        }

                        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE cpf_cnpj = :cpf_cnpj");
                        $stmt->bindParam(':cpf_cnpj', $dados['cpf_cnpj']);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            $errors[] = "Este CPF/CNPJ já está cadastrado.";
                        }

                        if (empty($errors)) {
                            $conn->beginTransaction();
                            $verificationToken = null;
                            $verificationExpires = null;

                            if ($emailVerificationRequired) {
                                $verificationToken = generateEmailVerificationToken();
                                $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                            }

                            $sql = "INSERT INTO usuarios (
                                nome, email, celular, senha, rua, bairro, cep, numero, 
                                municipio, estado, tipo_usuario, cpf_cnpj, data_cadastro,
                                email_verificado, verification_token, verification_expires
                            ) VALUES (
                                :nome, :email, :celular, :senha, :rua, :bairro, :cep, :numero,
                                :municipio, :estado, :tipo_usuario, :cpf_cnpj, NOW(),
                                :email_verificado, :verification_token, :verification_expires
                            )";

                            $stmt = $conn->prepare($sql);

                            $params = [
                                ':nome' => $dados['nome'],
                                ':email' => $dados['email'],
                                ':celular' => $dados['celular'],
                                ':senha' => $senha_hash,
                                ':rua' => $dados['rua'],
                                ':bairro' => $dados['bairro'],
                                ':cep' => $dados['cep'],
                                ':numero' => $dados['numero'],
                                ':municipio' => $dados['municipio'],
                                ':estado' => $dados['estado'],
                                ':tipo_usuario' => $dados['tipo_usuario'],
                                ':cpf_cnpj' => $dados['cpf_cnpj'],
                                ':email_verificado' => $emailVerificationRequired ? 0 : 1,
                                ':verification_token' => $verificationToken,
                                ':verification_expires' => $verificationExpires
                            ];

                            if ($stmt->execute($params)) {
                                $userId = $conn->lastInsertId();
                                $ip = $_SERVER['REMOTE_ADDR'];
                                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

                                $stmtLog = $conn->prepare("
                                    INSERT INTO logs_seguranca (usuario_id, acao, ip_address, user_agent)
                                    VALUES (?, 'cadastro', ?, ?)
                                ");
                                $stmtLog->execute([$userId, $ip, $userAgent]);

                                if ($emailVerificationRequired && $verificationToken) {
                                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                                    $verificationLink = $baseUrl . "/verify-email.php?token=" . $verificationToken;

                                    if (sendVerificationEmail($dados['email'], $dados['nome'], $verificationLink)) {
                                        $message = "🎉 Cadastro realizado com sucesso!<br><br>";
                                        $message .= "📧 <strong>Enviamos um email de verificação para:</strong><br>";
                                        $message .= "<span class='fw-bold'>" . htmlspecialchars($dados['email']) . "</span><br><br>";
                                        $message .= "📌 <strong>Importante:</strong> Verifique sua caixa de entrada <strong>(e a pasta de spam)</strong> para ativar sua conta.<br>";
                                        $message .= "🔗 O link de verificação é válido por <strong>24 horas</strong>.<br><br>";
                                        $message .= "👉 <a href='login.php' class='text-success fw-bold'>Fazer login após verificação</a>";
                                        $messageClass = "success";
                                    } else {
                                        $message = "⚠️ Cadastro realizado, mas houve um problema ao enviar o email de verificação.<br>";
                                        $message .= "Entre em contato com o suporte ou tente fazer login para solicitar um novo link.";
                                        $messageClass = "warning";
                                    }
                                } else {
                                    $message = "✅ Cadastro realizado com sucesso! Bem-vindo à CoopAgro!<br>";
                                    $message .= "Você será redirecionado para o login em 3 segundos...";
                                    $messageClass = "success";
                                    $redirectToLogin = true;
                                }

                                $conn->commit();
                                $_SESSION['cadastro_tentativas'] = 0;

                            } else {
                                $conn->rollBack();
                                $message = "❌ Erro ao cadastrar. Tente novamente.";
                                $messageClass = "danger";
                            }
                        }
                    } catch (PDOException $e) {
                        if (isset($conn) && $conn->inTransaction()) {
                            $conn->rollBack();
                        }

                        if ($e->getCode() == 23000) {
                            $message = "⚠️ Este email ou CPF/CNPJ já está cadastrado em nosso sistema.";
                        } else {
                            error_log("Erro no cadastro: " . $e->getMessage());
                            $message = "🚨 Erro no sistema. Tente novamente mais tarde.";
                        }
                        $messageClass = "danger";
                    }
                }

                if (!empty($errors)) {
                    $message = "⚠️ <strong>Corrija os seguintes erros:</strong><br>" . implode("<br>", $errors);
                    $messageClass = "warning";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
            --bege: #f5f5dc;
        }

        body {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            min-height: 90vh;
        }

        .register-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .form-section {
            padding: 40px;
        }

        .info-section {
            background: linear-gradient(135deg, var(--bege) 0%, #f8f9fa 100%);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 3px solid var(--verde-claro);
        }

        .info-section h2 {
            color: var(--verde-principal);
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .benefits-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(46, 125, 50, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.05rem;
        }

        .benefits-list li:last-child {
            border-bottom: none;
        }

        .benefit-icon {
            color: var(--verde-principal);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-card .card-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .form-card .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: var(--verde-principal);
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }

        .strength-medium {
            background-color: #ffc107;
            width: 50%;
        }

        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }

        .terms-card {
            border: 2px solid var(--verde-claro);
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }

        .btn-register:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }

        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .progress-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--verde-claro) 50%, transparent 100%);
            margin: 25px 0;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .register-container {
                margin: 10px;
                border-radius: 15px;
            }

            .register-header {
                padding: 30px 20px;
            }

            .register-header h1 {
                font-size: 2rem;
            }

            .form-section,
            .info-section {
                padding: 30px 20px;
            }

            .info-section {
                border-left: none;
                border-top: 3px solid var(--verde-claro);
            }
        }

        @media (max-width: 576px) {
            .register-header h1 {
                font-size: 1.8rem;
            }

            .form-section,
            .info-section {
                padding: 20px 15px;
            }
        }

        /* Botão de acessibilidade */
       
    </style>
</head>

<body>

    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>
    <?php include 'includes/_menu.php'; ?>
    <?php include_once 'includes/_acessibilidade.php'; ?>
     
    

    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-people-fill"></i> Cadastro - CoopAgro</h1>
            <p>Junte-se à nossa comunidade de produtores e comerciantes</p>
        </div>

        <div class="row g-0">
            <div class="col-lg-8">
                <div class="form-section">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageClass; ?> alert-custom alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($redirectToLogin): ?>
                        <script>
                            setTimeout(function () {
                                window.location.href = 'login.php';
                            }, 3000);
                        </script>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
                        id="cadastroForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="card form-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-person-badge"></i> Dados Pessoais
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nome" class="form-label">Nome Completo *</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required
                                            value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                            minlength="3" maxlength="255">
                                        <div class="form-text">Mínimo 3 caracteres</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="tipo_usuario" class="form-label">Tipo de Usuário *</label>
                                        <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                            <option value="">Selecione...</option>
                                            <option value="Produtor" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Produtor') ? 'selected' : ''; ?>>Produtor
                                            </option>
                                            <option value="Comerciante" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Comerciante') ? 'selected' : ''; ?>>Comerciante
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="cpf_cnpj" class="form-label">CPF ou CNPJ *</label>
                                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" required
                                            value="<?php echo isset($_POST['cpf_cnpj']) ? htmlspecialchars($_POST['cpf_cnpj']) : ''; ?>">
                                        <div class="form-text" id="cpf_cnpj_feedback"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required
                                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                        <div class="form-text">Use um email válido</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="celular" class="form-label">Celular *</label>
                                        <input type="tel" class="form-control" id="celular" name="celular" required
                                            value="<?php echo isset($_POST['celular']) ? htmlspecialchars($_POST['celular']) : ''; ?>">
                                        <div class="form-text">Formato: (11) 99999-9999</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Segurança -->
                        <div class="card form-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-shield-lock"></i> Segurança
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="senha" class="form-label">Senha *</label>
                                        <input type="password" class="form-control" id="senha" name="senha" required>
                                        <div class="password-strength" id="password-strength"></div>
                                        <small class="form-text text-muted">
                                            Use pelo menos 8 caracteres com letras maiúsculas, minúsculas, números e
                                            símbolos
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                        <input type="password" class="form-control" id="confirmar_senha"
                                            name="confirmar_senha" required>
                                        <div id="confirmar-senha-feedback" class="form-text"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card form-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-geo-alt"></i> Endereço
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cep" class="form-label">CEP *</label>
                                        <input type="text" class="form-control" id="cep" name="cep" required
                                            value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                                        <div class="form-text">Formato: 00000-000</div>
                                    </div>

                                    <div class="col-md-8">
                                        <label for="rua" class="form-label">Rua *</label>
                                        <input type="text" class="form-control" id="rua" name="rua" required
                                            value="<?php echo isset($_POST['rua']) ? htmlspecialchars($_POST['rua']) : ''; ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="numero" class="form-label">Número *</label>
                                        <input type="text" class="form-control" id="numero" name="numero" required
                                            value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
                                    </div>

                                    <div class="col-md-8">
                                        <label for="bairro" class="form-label">Bairro *</label>
                                        <input type="text" class="form-control" id="bairro" name="bairro" required
                                            value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
                                    </div>

                                    <div class="col-md-8">
                                        <label for="municipio" class="form-label">Município *</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio" required
                                            value="<?php echo isset($_POST['municipio']) ? htmlspecialchars($_POST['municipio']) : ''; ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="estado" class="form-label">Estado *</label>
                                        <input type="text" class="form-control" id="estado" name="estado" required
                                            value="<?php echo isset($_POST['estado']) ? htmlspecialchars($_POST['estado']) : ''; ?>"
                                            maxlength="2">
                                        <div class="form-text">Ex: SP, RJ, MG</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card form-card terms-card">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="aceitar_termos"
                                        name="aceitar_termos" required <?php echo (isset($_POST['aceitar_termos'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="aceitar_termos">
                                        Concordo com os <a href="politica.php" class="text-success fw-bold"
                                            target="_blank">Termos de Uso e Política de Privacidade</a> *
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register mt-4" id="submit-btn">
                            <i class="bi bi-person-plus"></i> Realizar Cadastro
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-section">
                    <h2><i class="bi bi-star-fill"></i> Por que se cadastrar na CoopAgro?</h2>
                    <ul class="benefits-list">
                        <li>
                            <i class="bi bi-people-fill benefit-icon"></i>
                            <span>Acesso a uma rede de produtores e comerciantes</span>
                        </li>
                        <li>
                            <i class="bi bi-briefcase-fill benefit-icon"></i>
                            <span>Oportunidades de negócio exclusivas</span>
                        </li>
                        <li>
                            <i class="bi bi-tools benefit-icon"></i>
                            <span>Suporte técnico especializado</span>
                        </li>
                        <li>
                            <i class="bi bi-currency-dollar benefit-icon"></i>
                            <span>Melhores preços para cooperados</span>
                        </li>
                        <li>
                            <i class="bi bi-calendar-event benefit-icon"></i>
                            <span>Eventos e capacitações gratuitas</span>
                        </li>
                        <li>
                            <i class="bi bi-shop benefit-icon"></i>
                            <span>Marketplace digital para seus produtos</span>
                        </li>
                    </ul>
                    <div class="section-divider"></div>
                    <p class="text-center mb-0">
                        <strong class="text-success">Junte-se a nós e faça parte dessa comunidade que cresce
                            junto!</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="footer bg-light py-4">
            <div class="container">
                <?php include 'includes/_footer.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('cadastroForm');
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            const tipoUsuario = document.getElementById('tipo_usuario');
            const cpfCnpj = document.getElementById('cpf_cnpj');
            const cpfCnpjFeedback = document.getElementById('cpf_cnpj_feedback');
            const submitBtn = document.getElementById('submit-btn');
            const passwordStrength = document.getElementById('password-strength');
            const confirmarSenhaFeedback = document.getElementById('confirmar-senha-feedback');
            function validarCpfCnpj(valor) {
                valor = valor.replace(/\D/g, '');

                if (valor.length === 11) {
                    return validarCPF(valor);
                } else if (valor.length === 14) {
                    return validarCNPJ(valor);
                }
                return false;
            }

            function validarCPF(cpf) {
                if (/(\d)\1{10}/.test(cpf)) return false;

                let soma = 0;
                for (let i = 0; i < 9; i++) {
                    soma += parseInt(cpf.charAt(i)) * (10 - i);
                }
                let resto = 11 - (soma % 11);
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.charAt(9))) return false;

                soma = 0;
                for (let i = 0; i < 10; i++) {
                    soma += parseInt(cpf.charAt(i)) * (11 - i);
                }
                resto = 11 - (soma % 11);
                if (resto === 10 || resto === 11) resto = 0;

                return resto === parseInt(cpf.charAt(10));
            }

            function validarCNPJ(cnpj) {
                if (/(\d)\1{13}/.test(cnpj)) return false;

                let tamanho = cnpj.length - 2;
                let numeros = cnpj.substring(0, tamanho);
                let digitos = cnpj.substring(tamanho);
                let soma = 0;
                let pos = tamanho - 7;

                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }

                let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(0)) return false;

                tamanho = tamanho + 1;
                numeros = cnpj.substring(0, tamanho);
                soma = 0;
                pos = tamanho - 7;

                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }

                resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                return resultado == digitos.charAt(1);
            }

            function validarForcaSenha(senha) {
                if (senha.length < 8) return false;
                if (!/[a-z]/.test(senha)) return false;
                if (!/[A-Z]/.test(senha)) return false;
                if (!/[0-9]/.test(senha)) return false;
                if (!/[^A-Za-z0-9]/.test(senha)) return false;
                return true;
            }

            senha.addEventListener('input', function () {
                const strength = validarForcaSenha(senha.value) ? 'strong' :
                    senha.value.length >= 6 ? 'medium' : 'weak';

                passwordStrength.className = 'password-strength strength-' + strength;

                if (senha.value.length === 0) {
                    passwordStrength.style.width = '0%';
                }
            });

            confirmarSenha.addEventListener('input', function () {
                if (confirmarSenha.value.length > 0) {
                    if (senha.value !== confirmarSenha.value) {
                        confirmarSenha.style.borderColor = '#dc3545';
                        confirmarSenhaFeedback.textContent = 'As senhas não coincidem';
                        confirmarSenhaFeedback.className = 'form-text text-danger';
                    } else {
                        confirmarSenha.style.borderColor = '#28a745';
                        confirmarSenhaFeedback.textContent = 'Senhas coincidem';
                        confirmarSenhaFeedback.className = 'form-text text-success';
                    }
                } else {
                    confirmarSenha.style.borderColor = '#e9ecef';
                    confirmarSenhaFeedback.textContent = '';
                }
            });

            cpfCnpj.addEventListener('blur', function () {
                const valor = cpfCnpj.value.replace(/\D/g, '');
                if (valor.length === 11 || valor.length === 14) {
                    const valido = validarCpfCnpj(valor);
                    if (valido) {
                        cpfCnpj.style.borderColor = '#28a745';
                        cpfCnpjFeedback.textContent = 'Documento válido';
                        cpfCnpjFeedback.className = 'form-text text-success';
                    } else {
                        cpfCnpj.style.borderColor = '#dc3545';
                        cpfCnpjFeedback.textContent = 'Documento inválido';
                        cpfCnpjFeedback.className = 'form-text text-danger';
                    }
                }
            });

            form.addEventListener('submit', function (e) {
                let isValid = true;
                let errorMessage = '';

                if (!validarForcaSenha(senha.value)) {
                    isValid = false;
                    errorMessage += 'A senha não atende aos requisitos de segurança!\n\nUse pelo menos 8 caracteres, incluindo:\n- Letras maiúsculas (A-Z)\n- Letras minúsculas (a-z)\n- Números (0-9)\n- Símbolos (!@#$...)\n\n';
                }

                if (senha.value !== confirmarSenha.value) {
                    isValid = false;
                    errorMessage += 'As senhas não coincidem!\n';
                }

                const cpfCnpjValor = cpfCnpj.value.replace(/\D/g, '');
                if (!validarCpfCnpj(cpfCnpjValor)) {
                    isValid = false;
                    errorMessage += 'CPF ou CNPJ inválido!\n';
                }

                const cepValor = document.getElementById('cep').value;
                if (!/^\d{5}-\d{3}$/.test(cepValor)) {
                    isValid = false;
                    errorMessage += 'CEP inválido! Use o formato 00000-000.\n';
                }

                if (!document.getElementById('aceitar_termos').checked) {
                    isValid = false;
                    errorMessage += 'Você deve aceitar os Termos de Uso e Política de Privacidade!\n';
                }

                if (!isValid) {
                    e.preventDefault();
                    alert('Por favor, corrija os seguintes erros:\n\n' + errorMessage);
                    return false;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="progress-spinner"></span> Cadastrando...';
            });

            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });

            cepInput.addEventListener('blur', function () {
                const cep = cepInput.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('rua').value = data.logradouro || '';
                                document.getElementById('bairro').value = data.bairro || '';
                                document.getElementById('municipio').value = data.localidade || '';
                                document.getElementById('estado').value = data.uf || '';
                            }
                        })
                        .catch(error => console.error('Erro ao buscar CEP:', error));
                }
            });

            const celularInput = document.getElementById('celular');
            celularInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = '(' + value;
                    if (value.length > 3) {
                        value = value.substring(0, 3) + ') ' + value.substring(3);
                    }
                    if (value.length > 10) {
                        value = value.substring(0, 10) + '-' + value.substring(10, 15);
                    }
                }
                e.target.value = value;
            });

            tipoUsuario.addEventListener('change', function () {
                cpfCnpj.placeholder = this.value === 'Produtor' ? '000.000.000-00' : '00.000.000/0000-00';
                cpfCnpj.value = '';
            });

            cpfCnpj.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                const tipo = tipoUsuario.value;

                if (tipo === 'Produtor') {
                    if (value.length > 3) {
                        value = value.substring(0, 3) + '.' + value.substring(3);
                    }
                    if (value.length > 7) {
                        value = value.substring(0, 7) + '.' + value.substring(7);
                    }
                    if (value.length > 11) {
                        value = value.substring(0, 11) + '-' + value.substring(11, 13);
                    }
                } else if (tipo === 'Comerciante') {
                    if (value.length > 2) {
                        value = value.substring(0, 2) + '.' + value.substring(2);
                    }
                    if (value.length > 6) {
                        value = value.substring(0, 6) + '.' + value.substring(6);
                    }
                    if (value.length > 10) {
                        value = value.substring(0, 10) + '/' + value.substring(10);
                    }
                    if (value.length > 15) {
                        value = value.substring(0, 15) + '-' + value.substring(15, 17);
                    }
                }

                e.target.value = value;
            });
        });
    </script>

    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>

</html>