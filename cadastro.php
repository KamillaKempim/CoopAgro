<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/cadastro.css" />
    <title>Cadastro - CoopAgro</title>
    
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cadastro - CoopAgro</h1>
            <p>Junte-se à nossa comunidade de produtores e comerciantes</p>
        </div>
        
        <div class="form-section">
            <?php
            // Inicia a sessão para controle de tentativas
           //session_start();
            
            // Inclui o arquivo de conexão
            require_once 'config/database.php';
            
            // Variáveis para mensagens
            $message = '';
            $messageClass = '';
            
            // Processa o formulário quando enviado
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Verifica limite de tentativas
                if (!isset($_SESSION['cadastro_tentativas'])) {
                    $_SESSION['cadastro_tentativas'] = 0;
                }
                
                if ($_SESSION['cadastro_tentativas'] > 5) {
                    $message = "Muitas tentativas de cadastro. Tente novamente mais tarde.";
                    $messageClass = "error";
                } else {
                    $_SESSION['cadastro_tentativas']++;
                    
                    // Verifica se o usuário concordou com os termos
                    if (!isset($_POST['aceitar_termos'])) {
                        $message = "Você deve aceitar os Termos de Uso e Política de Privacidade para se cadastrar.";
                        $messageClass = "error";
                    } else {
                        // Coleta e sanitiza os dados
                        $nome = sanitizeInput($_POST['nome']);
                        $email = sanitizeInput($_POST['email']);
                        $celular = sanitizeInput($_POST['celular']);
                        $senha = $_POST['senha'];
                        $confirmar_senha = $_POST['confirmar_senha'];
                        $rua = sanitizeInput($_POST['rua']);
                        $cep = sanitizeInput($_POST['cep']);
                        $numero = sanitizeInput($_POST['numero']);
                        $municipio = sanitizeInput($_POST['municipio']);
                        $tipo_usuario = sanitizeInput($_POST['tipo_usuario']);
                        $cpf_cnpj = sanitizeInput($_POST['cpf_cnpj']);
                        
                        // Validações
                        if (!validarEmail($email)) {
                            $message = "Email inválido.";
                            $messageClass = "error";
                        } elseif (!validarCPFCNPJ($cpf_cnpj, $tipo_usuario)) {
                            $message = $tipo_usuario === 'Produtor' ? "CPF inválido." : "CNPJ inválido.";
                            $messageClass = "error";
                        } elseif (!validarForcaSenha($senha)) {
                            $message = "A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.";
                            $messageClass = "error";
                        } elseif ($senha !== $confirmar_senha) {
                            $message = "As senhas não coincidem.";
                            $messageClass = "error";
                        } else {
                            // Hash da senha
                            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                            
                            try {
                                // Conecta ao banco
                                $conn = getDBConnection();
                                
                                if (!$conn) {
                                    throw new Exception("Erro de conexão com o banco de dados.");
                                }
                                
                                // Prepara a query SQL
                                $sql = "INSERT INTO usuarios (nome, email, celular, senha, rua, cep, numero, municipio, tipo_usuario, cpf_cnpj) 
                                        VALUES (:nome, :email, :celular, :senha, :rua, :cep, :numero, :municipio, :tipo_usuario, :cpf_cnpj)";
                                
                                $stmt = $conn->prepare($sql);
                                
                                // Bind dos parâmetros
                                $stmt->bindParam(':nome', $nome);
                                $stmt->bindParam(':email', $email);
                                $stmt->bindParam(':celular', $celular);
                                $stmt->bindParam(':senha', $senha_hash);
                                $stmt->bindParam(':rua', $rua);
                                $stmt->bindParam(':cep', $cep);
                                $stmt->bindParam(':numero', $numero);
                                $stmt->bindParam(':municipio', $municipio);
                                $stmt->bindParam(':tipo_usuario', $tipo_usuario);
                                $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
                                
                                // Executa a query
                                if ($stmt->execute()) {
                                    $message = "Cadastro realizado com sucesso! Bem-vindo à CoopAgro!";
                                    $messageClass = "success";
                                    // Reseta tentativas em caso de sucesso
                                    $_SESSION['cadastro_tentativas'] = 0;
                                } else {
                                    $message = "Erro ao cadastrar. Tente novamente.";
                                    $messageClass = "error";
                                }
                            } catch(PDOException $e) {
                                // Verifica se é erro de duplicação de email
                                if ($e->getCode() == 23000) {
                                    $message = "Este email já está cadastrado em nosso sistema.";
                                } else {
                                    $message = "Erro no sistema: " . $e->getMessage();
                                }
                                $messageClass = "error";
                            } catch(Exception $e) {
                                $message = "Erro: " . $e->getMessage();
                                $messageClass = "error";
                            }
                        }
                    }
                }
            }
            
            // Exibe mensagem se houver
            if (!empty($message)) {
                echo "<div class='message $messageClass'>$message</div>";
            }
            ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="cadastroForm">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="nome">Nome Completo:</label>
                        <input type="text" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_usuario">Tipo de Usuário:</label>
                        <select id="tipo_usuario" name="tipo_usuario" required>
                            <option value="">Selecione...</option>
                            <option value="Produtor" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Produtor') ? 'selected' : ''; ?>>Produtor</option>
                            <option value="Comerciante" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'Comerciante') ? 'selected' : ''; ?>>Comerciante</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf_cnpj">CPF ou CNPJ:</label>
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj" required value="<?php echo isset($_POST['cpf_cnpj']) ? htmlspecialchars($_POST['cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="celular">Celular:</label>
                        <input type="tel" id="celular" name="celular" required value="<?php echo isset($_POST['celular']) ? htmlspecialchars($_POST['celular']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                        <div id="senha-feedback" class="password-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha:</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                        <div id="confirmar-senha-feedback" class="password-feedback"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="rua">Rua:</label>
                        <input type="text" id="rua" name="rua" required value="<?php echo isset($_POST['rua']) ? htmlspecialchars($_POST['rua']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cep">CEP:</label>
                        <input type="text" id="cep" name="cep" required value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="numero">Número:</label>
                        <input type="text" id="numero" name="numero" required value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="municipio">Município:</label>
                        <input type="text" id="municipio" name="municipio" required value="<?php echo isset($_POST['municipio']) ? htmlspecialchars($_POST['municipio']) : ''; ?>">
                    </div>
                </div>
                
                <div class="terms-container">
                    <div class="checkbox-group">
                        <input type="checkbox" id="aceitar_termos" name="aceitar_termos" required <?php echo (isset($_POST['aceitar_termos'])) ? 'checked' : ''; ?>>
                        <label for="aceitar_termos">
                            Concordo com os <a href="politica.php" class="terms-link" target="_blank">Termos de Uso e Política de Privacidade</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit" id="submit-btn">Realizar Cadastro</button>
            </form>
        </div>
        
        <div class="info-section">
            <h2>Por que se cadastrar na CoopAgro?</h2>
            <ul>
                <li>Acesso a uma rede de produtores e comerciantes</li>
                <li>Oportunidades de negócio exclusivas</li>
                <li>Suporte técnico especializado</li>
                <li>Melhores preços para cooperados</li>
                <li>Eventos e capacitações gratuitas</li>
                <li>Marketplace digital para seus produtos</li>
            </ul>
            <p><strong>Junte-se a nós e faça parte dessa comunidade que cresce junto!</strong></p>
        </div>
        
        <div class="footer">
            <?php include 'includes/_footer.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('cadastroForm');
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            const tipoUsuario = document.getElementById('tipo_usuario');
            const cpfCnpj = document.getElementById('cpf_cnpj');
            const submitBtn = document.getElementById('submit-btn');
            const senhaFeedback = document.getElementById('senha-feedback');
            const confirmarSenhaFeedback = document.getElementById('confirmar-senha-feedback');
            
            // Função para validar força da senha
            function validarForcaSenha(senha) {
                const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                return regex.test(senha);
            }
            
            // Validação de força da senha em tempo real
            senha.addEventListener('input', function() {
                if (senha.value.length > 0) {
                    if (!validarForcaSenha(senha.value)) {
                        senha.style.borderColor = '#dc3545';
                        senhaFeedback.textContent = 'Senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos';
                        senhaFeedback.className = 'password-feedback password-weak';
                    } else {
                        senha.style.borderColor = '#28a745';
                        senhaFeedback.textContent = 'Senha forte';
                        senhaFeedback.className = 'password-feedback password-strong';
                    }
                } else {
                    senha.style.borderColor = '#ddd';
                    senhaFeedback.textContent = '';
                }
            });
            
            // Validação de confirmação de senha em tempo real
            confirmarSenha.addEventListener('input', function() {
                if (confirmarSenha.value.length > 0) {
                    if (senha.value !== confirmarSenha.value) {
                        confirmarSenha.style.borderColor = '#dc3545';
                        confirmarSenhaFeedback.textContent = 'As senhas não coincidem';
                        confirmarSenhaFeedback.className = 'password-feedback password-weak';
                    } else {
                        confirmarSenha.style.borderColor = '#28a745';
                        confirmarSenhaFeedback.textContent = 'Senhas coincidem';
                        confirmarSenhaFeedback.className = 'password-feedback password-strong';
                    }
                } else {
                    confirmarSenha.style.borderColor = '#ddd';
                    confirmarSenhaFeedback.textContent = '';
                }
            });
            
            // Validação de confirmação de senha no submit
            form.addEventListener('submit', function(e) {
                if (senha.value !== confirmarSenha.value) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    senha.focus();
                    return;
                }
                
                if (!validarForcaSenha(senha.value)) {
                    e.preventDefault();
                    alert('A senha não atende aos requisitos de segurança!');
                    senha.focus();
                    return;
                }
                
                // Mostrar loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Cadastrando... <span class="loading"></span>';
            });
            
            // Formatação do CEP
            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });
            
            // Busca automática de endereço pelo CEP
            cepInput.addEventListener('blur', function() {
                const cep = cepInput.value.replace(/\D/g, '');
                
                if (cep.length === 8) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('rua').value = data.logradouro || '';
                                document.getElementById('municipio').value = data.localidade || '';
                            }
                        })
                        .catch(error => console.error('Erro ao buscar CEP:', error));
                }
            });
            
            // Formatação do celular
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
            
            // Formatação dinâmica do CPF/CNPJ
            tipoUsuario.addEventListener('change', function() {
                cpfCnpj.placeholder = this.value === 'Produtor' ? '000.000.000-00' : '00.000.000/0000-00';
                cpfCnpj.value = '';
            });
            
            cpfCnpj.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (tipoUsuario.value === 'Produtor') {
                    // Formata CPF
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
                    // Formata CNPJ
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