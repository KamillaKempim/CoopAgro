<?php
// Arquivo: resend-verification.php
// Reenvio de email de verificação

session_start();
require_once 'config/database.php';
require_once 'config/email.php';

$message = '';
$messageClass = '';
$email = $_GET['email'] ?? '';

// Se veio do formulário POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Email inválido.";
        $messageClass = "danger";
    } else {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("
                SELECT id, nome, email_verificado, verification_token 
                FROM usuarios 
                WHERE email = :email
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user['email_verificado'] == 1) {
                    $message = "ℹ️ Este email já foi verificado.";
                    $messageClass = "info";
                } else {
                    // Gerar novo token
                    $newToken = generateVerificationToken();
                    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    $updateStmt = $conn->prepare("
                        UPDATE usuarios 
                        SET verification_token = :token,
                            verification_expires = :expires
                        WHERE id = :id
                    ");
                    $updateStmt->bindParam(':token', $newToken);
                    $updateStmt->bindParam(':expires', $expires);
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                    
                    // Enviar email
                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                    $verificationLink = $baseUrl . "/verify-email.php?token=" . $newToken;
                    
                    if (sendVerificationEmail($email, $user['nome'], $verificationLink)) {
                        $message = "✅ <strong>Novo link enviado!</strong><br><br>";
                        $message .= "📧 Enviamos um novo link de verificação para:<br>";
                        $message .= "<span class='fw-bold'>" . htmlspecialchars($email) . "</span><br><br>";
                        $message .= "🔗 O link é válido por <strong>24 horas</strong>.<br>";
                        $message .= "📌 Verifique sua caixa de entrada <strong>(e a pasta de spam)</strong>.";
                        $messageClass = "success";
                    } else {
                        $message = "❌ Erro ao enviar email. Tente novamente.";
                        $messageClass = "danger";
                    }
                }
            } else {
                $message = "❌ Email não encontrado em nosso sistema.";
                $messageClass = "warning";
            }
        } catch(PDOException $e) {
            error_log("Erro ao reenviar verificação: " . $e->getMessage());
            $message = "🚨 Erro no sistema. Tente novamente mais tarde.";
            $messageClass = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenviar Verificação - CoopAgro</title>
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
        
        .resend-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        .icon-container {
            font-size: 4rem;
            color: var(--verde-principal);
            text-align: center;
            margin-bottom: 20px;
        }
        
        h1 {
            color: var(--verde-principal);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }
        
        .btn-primary:disabled {
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
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        @media (max-width: 576px) {
            .resend-container {
                padding: 30px 20px;
                border-radius: 15px;
            }
            
            .icon-container {
                font-size: 3rem;
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
  
    <div class="resend-container">
        <div class="icon-container">
            <i class="bi bi-envelope-arrow-up"></i>
        </div>
        
        <h1>Reenviar Verificação</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageClass; ?> alert-custom alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            
            <?php if ($messageClass == 'success'): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 5000);
                </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <p class="text-muted mb-4">
            Digite o email que você usou no cadastro para receber um novo link de verificação.
        </p>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" 
                       required
                       placeholder="seu@email.com">
            </div>
            
            <button type="submit" class="btn btn-primary mb-3" id="submit-btn">
                <i class="bi bi-send me-2"></i> Enviar Novo Link
            </button>
        </form>
        
        <div class="back-link">
            <a href="login.php" class="text-success">
                <i class="bi bi-arrow-left me-1"></i> Voltar para o Login
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submit-btn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
                });
            }
        });
    </script>
</body>
</html>