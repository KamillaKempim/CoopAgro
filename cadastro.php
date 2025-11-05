<?php
session_start();
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
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            
            .form-section, .info-section {
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
            
            .form-section, .info-section {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-people-fill"></i> Cadastro - CoopAgro</h1>
            <p>Junte-se à nossa comunidade de produtores e comerciantes</p>
        </div>
        
        <div class="row g-0">
            <div class="col-lg-8">
                <div class="form-section">
                    <?php
                    require_once 'config/database.php';

                    function validarForcaSenha($senha) {
                        if (strlen($senha) < 8) return false;
                        if (!preg_match('/[a-z]/', $senha)) return false;
                        if (!preg_match('/[A-Z]/', $senha)) return false;
                        if (!preg_match('/[0-9]/', $senha)) return false;
                        if (!preg_match('/[^A-Za-z0-9]/', $senha)) return false;
                        return true;
                    }
                    
                    $message = '';
                    $messageClass = '';
                    $redirectToLogin = false;
                    
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        if (!isset($_SESSION['cadastro_tentativas'])) {
                            $_SESSION['cadastro_tentativas'] = 0;
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
                                $nome = sanitizeInput($_POST['nome']);
                                $email = sanitizeInput($_POST['email']);
                                $celular = sanitizeInput($_POST['celular']);
                                $senha = $_POST['senha'];
                                $confirmar_senha = $_POST['confirmar_senha'];
                                $rua = sanitizeInput($_POST['rua']);
                                $cep = sanitizeInput($_POST['cep']);
                                $numero = sanitizeInput($_POST['numero']);
                                $bairro = sanitizeInput($_POST['bairro']);
                                $municipio = sanitizeInput($_POST['municipio']);
                                $estado = sanitizeInput($_POST['estado']);
                                $tipo_usuario = sanitizeInput($_POST['tipo_usuario']);
                                $cpf_cnpj = sanitizeInput($_POST['cpf_cnpj']);
                                
                                if (!validarForcaSenha($senha)) {
                                    $message = "A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.";
                                    $messageClass = "warning";
                                } elseif ($senha !== $confirmar_senha) {
                                    $message = "As senhas não coincidem.";
                                    $messageClass = "warning";
                                } else {
                                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                                    
                                    try {
                                        $conn = getDBConnection();
                                        
                                        if (!$conn) {
                                            throw new Exception("Erro de conexão com o banco de dados.");
                                        }
                                        
                                        $sql = "INSERT INTO usuarios (nome, email, celular, senha, rua, bairro, cep, numero, municipio, estado, tipo_usuario, cpf_cnpj) 
                                                VALUES (:nome, :email, :celular, :senha, :rua, :bairro, :cep, :numero, :municipio, :estado, :tipo_usuario, :cpf_cnpj)";
                                        
                                        $stmt = $conn->prepare($sql);
                                        
                                        $stmt->bindParam(':nome', $nome);
                                        $stmt->bindParam(':email', $email);
                                        $stmt->bindParam(':celular', $celular);
                                        $stmt->bindParam(':senha', $senha_hash);
                                        $stmt->bindParam(':rua', $rua);
                                        $stmt->bindParam(':bairro', $bairro);
                                        $stmt->bindParam(':cep', $cep);
                                        $stmt->bindParam(':numero', $numero);
                                        $stmt->bindParam(':municipio', $municipio);
                                        $stmt->bindParam(':estado', $estado);
                                        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
                                        $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
                                        
                                        if ($stmt->execute()) {
                                            $message = "Cadastro realizado com sucesso! Bem-vindo à CoopAgro! Você será redirecionado para a página de login em 3 segundos.";
                                            $messageClass = "success";
                                            $redirectToLogin = true;
                                            $_SESSION['cadastro_tentativas'] = 0;
                                        } else {
                                            $message = "Erro ao cadastrar. Tente novamente.";
                                            $messageClass = "danger";
                                        }
                                    } catch(PDOException $e) {
                                        if ($e->getCode() == 23000) {
                                            $message = "Este email ou CPF/CNPJ já está cadastrado em nosso sistema.";
                                        } else {
                                            $message = "Erro no sistema: " . $e->getMessage();
                                        }
                                        $messageClass = "danger";
                                    } catch(Exception $e) {
                                        $message = "Erro: " . $e->getMessage();
                                        $messageClass = "danger";
                                    }
                                }
                            }
                        }
                    }
                    
                    if (!empty($message)) {
                        echo "<div class='alert alert-$messageClass alert-custom'>$message</div>";
                    }
                    
                    if ($redirectToLogin) {
                        echo '<script>
                            setTimeout(function() {
                                window.location.href = "login.php";
                            }, 3000);
                        </script>';
                    }
                    ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="cadastroForm">
                        <!-- Dados Pessoais -->
                        <div class="card form-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-person-badge"></i> Dados Pessoais
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="nome" class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required 
                                               value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                                        <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                            <option value="">Selecione...</option>
                                            <option value="Produtor" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Produtor') ? 'selected' : ''; ?>>Produtor</option>
                                            <option value="Comerciante" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Comerciante') ? 'selected' : ''; ?>>Comerciante</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="cpf_cnpj" class="form-label">CPF ou CNPJ</label>
                                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" required 
                                               value="<?php echo isset($_POST['cpf_cnpj']) ? htmlspecialchars($_POST['cpf_cnpj']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="celular" class="form-label">Celular</label>
                                        <input type="tel" class="form-control" id="celular" name="celular" required 
                                               value="<?php echo isset($_POST['celular']) ? htmlspecialchars($_POST['celular']) : ''; ?>">
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
                                        <label for="senha" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="senha" name="senha" required>
                                        <div class="password-strength" id="password-strength"></div>
                                        <small class="form-text text-muted">
                                            Use pelo menos 8 caracteres com letras maiúsculas, minúsculas, números e símbolos
                                        </small>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                        <div id="confirmar-senha-feedback" class="form-text"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Endereço -->
                        <div class="card form-card mb-4">
                            <div class="card-header">
                                <i class="bi bi-geo-alt"></i> Endereço
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cep" class="form-label">CEP</label>
                                        <input type="text" class="form-control" id="cep" name="cep" required 
                                               value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <label for="rua" class="form-label">Rua</label>
                                        <input type="text" class="form-control" id="rua" name="rua" required 
                                               value="<?php echo isset($_POST['rua']) ? htmlspecialchars($_POST['rua']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="numero" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="numero" name="numero" required 
                                               value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <label for="bairro" class="form-label">Bairro</label>
                                        <input type="text" class="form-control" id="bairro" name="bairro" required 
                                               value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <label for="municipio" class="form-label">Município</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio" required 
                                               value="<?php echo isset($_POST['municipio']) ? htmlspecialchars($_POST['municipio']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="estado" class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado" required 
                                               value="<?php echo isset($_POST['estado']) ? htmlspecialchars($_POST['estado']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Termos -->
                        <div class="card form-card terms-card">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="aceitar_termos" name="aceitar_termos" required 
                                           <?php echo (isset($_POST['aceitar_termos'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="aceitar_termos">
                                        Concordo com os <a href="politica.php" class="text-success fw-bold" target="_blank">Termos de Uso e Política de Privacidade</a>
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
                        <strong class="text-success">Junte-se a nós e faça parte dessa comunidade que cresce junto!</strong>
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
        // JavaScript permanece o mesmo do código anterior
        // (todas as funções de validação e formatação)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('cadastroForm');
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            const tipoUsuario = document.getElementById('tipo_usuario');
            const cpfCnpj = document.getElementById('cpf_cnpj');
            const submitBtn = document.getElementById('submit-btn');
            const passwordStrength = document.getElementById('password-strength');
            const confirmarSenhaFeedback = document.getElementById('confirmar-senha-feedback');
            
            function validarForcaSenha(senha) {
                if (senha.length < 8) return false;
                if (!/[a-z]/.test(senha)) return false;
                if (!/[A-Z]/.test(senha)) return false;
                if (!/[0-9]/.test(senha)) return false;
                if (!/[^A-Za-z0-9]/.test(senha)) return false;
                return true;
            }

            senha.addEventListener('input', function() {
                const strength = validarForcaSenha(senha.value) ? 'strong' : 
                                senha.value.length >= 6 ? 'medium' : 'weak';
                
                passwordStrength.className = 'password-strength strength-' + strength;
                
                if (senha.value.length === 0) {
                    passwordStrength.style.width = '0%';
                }
            });
            
            confirmarSenha.addEventListener('input', function() {
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
            
            form.addEventListener('submit', function(e) {
                if (senha.value !== confirmarSenha.value) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    senha.focus();
                    return;
                }
                
                if (!validarForcaSenha(senha.value)) {
                    e.preventDefault();
                    alert('A senha não atende aos requisitos de segurança!\n\nUse pelo menos 8 caracteres, incluindo:\n- Letras maiúsculas (A-Z)\n- Letras minúsculas (a-z)\n- Números (0-9)\n- Símbolos (!@#$...)');
                    senha.focus();
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="progress-spinner"></span> Cadastrando...';
            });
            
            // Funções de formatação (CEP, celular, CPF/CNPJ) permanecem as mesmas
            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });
            
            cepInput.addEventListener('blur', function() {
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
            celularInput.addEventListener('input', function(e) {
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
            
            tipoUsuario.addEventListener('change', function() {
                cpfCnpj.placeholder = this.value === 'Produtor' ? '000.000.000-00' : '00.000.000/0000-00';
                cpfCnpj.value = '';
            });
            
            cpfCnpj.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (tipoUsuario.value === 'Produtor') {
                    if (value.length > 3) {
                        value = value.substring(0, 3) + '.' + value.substring(3);
                    }
                    if (value.length > 7) {
                        value = value.substring(0, 7) + '.' + value.substring(7);
                    }
                    if (value.length > 11) {
                        value = value.substring(0, 11) + '-' + value.substring(11, 13);
                    }
                } else if (tipoUsuario.value === 'Comerciante') {
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
</body>
</html>