<?php
// Arquivo: config/email.php
// Sistema completo de envio de emails para CoopAgro

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Requer instalação do PHPMailer via Composer

/**
 * Envia email de recuperação de senha
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhiewertton1@gmail.com';
        $mail->Password = 'ncav ncou yhdq hyzk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Remetente
        $mail->setFrom('jhiewertton1@gmail.com', 'CoopAgro');
        $mail->addAddress($toEmail, $toName);
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - CoopAgro';
        
        $emailBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2e7d32; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { 
                    display: inline-block; 
                    background: #4caf50; 
                    color: white; 
                    padding: 12px 30px; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
                .footer { 
                    margin-top: 20px; 
                    padding-top: 20px; 
                    border-top: 1px solid #ddd; 
                    font-size: 12px; 
                    color: #666; 
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>CoopAgro</h1>
                </div>
                <div class="content">
                    <h2>Redefinição de Senha</h2>
                    <p>Olá ' . htmlspecialchars($toName) . ',</p>
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style="text-align: center;">
                        <a href="' . $resetLink . '" class="button">Redefinir Senha</a>
                    </p>
                    <p>Ou copie e cole este link no seu navegador:</p>
                    <p><code>' . $resetLink . '</code></p>
                    <p><strong>Este link é válido por 1 hora.</strong></p>
                    <p>Se você não solicitou esta redefinição, ignore este email.</p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' CoopAgro. Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $emailBody;
        $mail->AltBody = "Redefinição de Senha - CoopAgro\n\n" .
                        "Olá " . $toName . ",\n\n" .
                        "Recebemos uma solicitação para redefinir a senha da sua conta.\n\n" .
                        "Clique no link para redefinir sua senha:\n" .
                        $resetLink . "\n\n" .
                        "Este link é válido por 1 hora.\n\n" .
                        "Se não solicitou, ignore este email.\n\n" .
                        "© " . date('Y') . " CoopAgro";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email de recuperação: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envia email de verificação de cadastro
 */
function sendVerificationEmail($toEmail, $toName, $verificationLink) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhiewertton1@gmail.com';
        $mail->Password = 'ncav ncou yhdq hyzk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Remetente
        $mail->setFrom('jhiewertton1@gmail.com', 'CoopAgro');
        $mail->addAddress($toEmail, $toName);
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = 'Verificação de Email - CoopAgro';
        
        $emailBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0;
                }
                .content { 
                    padding: 40px; 
                    background: #f9f9f9; 
                    border: 1px solid #e0e0e0;
                    border-top: none;
                }
                .button { 
                    display: inline-block; 
                    background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); 
                    color: white; 
                    padding: 14px 35px; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    margin: 25px 0; 
                    font-weight: bold;
                    font-size: 16px;
                }
                .button:hover {
                    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
                }
                .footer { 
                    margin-top: 30px; 
                    padding-top: 20px; 
                    border-top: 1px solid #ddd; 
                    font-size: 12px; 
                    color: #666;
                    text-align: center;
                }
                .warning-box {
                    background: #fff3cd;
                    border: 1px solid #ffc107;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #856404;
                }
                .link-box {
                    background: #f8f9fa;
                    border: 1px dashed #6c757d;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 15px 0;
                    word-break: break-all;
                    font-family: monospace;
                    font-size: 13px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0; font-size: 28px;">🌟 CoopAgro</h1>
                    <p style="margin: 10px 0 0; opacity: 0.9;">Verificação de Email</p>
                </div>
                <div class="content">
                    <h2 style="color: #2e7d32; margin-top: 0;">Olá, ' . htmlspecialchars($toName) . '! 👋</h2>
                    
                    <p>Seja muito bem-vindo(a) à <strong>CoopAgro</strong>!</p>
                    
                    <p>Estamos muito felizes em tê-lo(a) conosco. Para ativar sua conta e começar a usufruir de todos os benefícios, precisamos confirmar que este email realmente pertence a você.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $verificationLink . '" class="button" style="color: white; text-decoration: none;">
                            ✅ VERIFICAR MEU EMAIL
                        </a>
                    </div>
                    
                    <p>Ou copie e cole o link abaixo em seu navegador:</p>
                    
                    <div class="link-box">
                        ' . $verificationLink . '
                    </div>
                    
                    <div class="warning-box">
                        <strong>⚠️ Importante:</strong> Este link é válido por <strong>24 horas</strong>. 
                        Após este período, será necessário solicitar um novo link de verificação.
                    </div>
                    
                    <p>Se você não se cadastrou na CoopAgro, por favor ignore este email ou entre em contato conosco.</p>
                    
                    <p>Atenciosamente,<br>
                    <strong>Equipe CoopAgro</strong></p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' CoopAgro - Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                    <p><small>Endereço: Sistema CoopAgro | Contato: jhiewertton1@gmail.com</small></p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $emailBody;
        $mail->AltBody = "VERIFICAÇÃO DE EMAIL - COOPAGRO\n\n" .
                        "Olá " . $toName . ",\n\n" .
                        "Bem-vindo(a) à CoopAgro!\n\n" .
                        "Para ativar sua conta, verifique seu email clicando no link abaixo:\n\n" .
                        $verificationLink . "\n\n" .
                        "Este link é válido por 24 horas.\n\n" .
                        "Se você não se cadastrou na CoopAgro, ignore este email.\n\n" .
                        "Atenciosamente,\nEquipe CoopAgro\n\n" .
                        "© " . date('Y') . " CoopAgro";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email de verificação: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Gera token de verificação seguro
 */
function generateVerificationToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Envia email de boas-vindas após verificação
 */
function sendWelcomeEmail($toEmail, $toName) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhiewertton1@gmail.com';
        $mail->Password = 'ncav ncou yhdq hyzk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Remetente
        $mail->setFrom('jhiewertton1@gmail.com', 'CoopAgro');
        $mail->addAddress($toEmail, $toName);
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = '🎉 Bem-vindo(a) à CoopAgro!';
        
        $emailBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); 
                    color: white; 
                    padding: 40px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0;
                }
                .content { 
                    padding: 40px; 
                    background: #f9f9f9; 
                    border: 1px solid #e0e0e0;
                    border-top: none;
                }
                .benefit { 
                    background: white; 
                    border: 1px solid #e0e0e0; 
                    border-radius: 8px; 
                    padding: 15px; 
                    margin: 10px 0; 
                    display: flex; 
                    align-items: center;
                }
                .benefit-icon { 
                    color: #2e7d32; 
                    font-size: 20px; 
                    margin-right: 15px; 
                    min-width: 30px;
                }
                .cta-button { 
                    display: inline-block; 
                    background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); 
                    color: white; 
                    padding: 14px 35px; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    margin: 25px 0; 
                    font-weight: bold;
                    font-size: 16px;
                }
                .footer { 
                    margin-top: 30px; 
                    padding-top: 20px; 
                    border-top: 1px solid #ddd; 
                    font-size: 12px; 
                    color: #666;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0; font-size: 32px;">🎉 CoopAgro</h1>
                    <p style="margin: 10px 0 0; opacity: 0.9; font-size: 18px;">Sua conta foi ativada com sucesso!</p>
                </div>
                <div class="content">
                    <h2 style="color: #2e7d32; margin-top: 0;">Parabéns, ' . htmlspecialchars($toName) . '! 🎊</h2>
                    
                    <p>Sua conta na <strong>CoopAgro</strong> foi ativada com sucesso e agora você tem acesso completo à nossa plataforma!</p>
                    
                    <p><strong>Próximos passos:</strong></p>
                    
                    <div class="benefit">
                        <div class="benefit-icon">👤</div>
                        <div>
                            <strong>Complete seu perfil</strong><br>
                            Adicione mais informações para melhorar sua experiência
                        </div>
                    </div>
                    
                    <div class="benefit">
                        <div class="benefit-icon">🔍</div>
                        <div>
                            <strong>Explore produtos</strong><br>
                            Veja os produtos disponíveis na nossa comunidade
                        </div>
                    </div>
                    
                    <div class="benefit">
                        <div class="benefit-icon">📱</div>
                        <div>
                            <strong>Configure preferências</strong><br>
                            Personalize sua experiência na plataforma
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="https://seusite.com/perfil.php" class="cta-button" style="color: white; text-decoration: none;">
                            🚀 ACESSAR MINHA CONTA
                        </a>
                    </div>
                    
                    <p>Estamos aqui para ajudar! Se tiver alguma dúvida, não hesite em entrar em contato conosco.</p>
                    
                    <p>Atenciosamente,<br>
                    <strong>Equipe CoopAgro</strong></p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' CoopAgro - Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $emailBody;
        $mail->AltBody = "BEM-VINDO(A) À COOPAGRO!\n\n" .
                        "Parabéns, " . $toName . "!\n\n" .
                        "Sua conta na CoopAgro foi ativada com sucesso!\n\n" .
                        "Agora você pode:\n" .
                        "1. Acessar sua conta: https://seusite.com/login.php\n" .
                        "2. Completar seu perfil\n" .
                        "3. Explorar produtos da comunidade\n" .
                        "4. Configurar suas preferências\n\n" .
                        "Estamos aqui para ajudar!\n\n" .
                        "Atenciosamente,\nEquipe CoopAgro\n\n" .
                        "© " . date('Y') . " CoopAgro";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email de boas-vindas: " . $mail->ErrorInfo);
        return false;
    }
}
?>