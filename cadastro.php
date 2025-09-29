<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - CoopAgro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 90vh;
        }
        
        .header {
            background: #2E7D32;
            color: white;
            padding: 30px;
            text-align: center;
            grid-column: 1 / -1;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .form-section {
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .info-section {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .info-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .info-section ul {
            list-style: none;
            margin: 20px 0;
        }
        
        .info-section li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-section li:before {
            content: "✓ ";
            font-weight: bold;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .full-width {
            flex: 1 0 100%;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input, select {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        
        .terms-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        
        .terms-link {
            color: #2E7D32;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .terms-link:hover {
            color: #1B5E20;
            text-decoration: underline;
        }
        
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 18px 30px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background: #2E7D32;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 14px;
            grid-column: 1 / -1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: normal;
        }
        
        /* Responsividade */
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                max-width: 800px;
            }
            
            .info-section {
                order: -1;
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .form-section {
                padding: 30px 20px;
            }
            
            .info-section {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .header p {
                font-size: 16px;
            }
            
            .form-group {
                min-width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .form-section, .info-section {
                padding: 20px 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
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
            // Inclui o arquivo de conexão
            require_once 'config/database.php';
            
            // Variáveis para mensagens
            $message = '';
            $messageClass = '';
            
            // Processa o formulário quando enviado
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Verifica se o usuário concordou com os termos
                if (!isset($_POST['aceitar_termos'])) {
                    $message = "Você deve aceitar os Termos de Uso e Política de Privacidade para se cadastrar.";
                    $messageClass = "error";
                } else {
                    // Coleta e sanitiza os dados
                    $nome = sanitizeInput($_POST['nome']);
                    $email = sanitizeInput($_POST['email']);
                    $celular = sanitizeInput($_POST['celular']);
                    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                    $rua = sanitizeInput($_POST['rua']);
                    $cep = sanitizeInput($_POST['cep']);
                    $numero = sanitizeInput($_POST['numero']);
                    $municipio = sanitizeInput($_POST['municipio']);
                    $tipo_usuario = sanitizeInput($_POST['tipo_usuario']);
                    $cpf_cnpj = sanitizeInput($_POST['cpf_cnpj']);
                    
                    try {
                        // Conecta ao banco
                        $conn = getDBConnection();
                        
                        // Prepara a query SQL
                        $sql = "INSERT INTO usuarios (nome, email, celular, senha, rua, cep, numero, municipio, tipo_usuario, cpf_cnpj) 
                                VALUES (:nome, :email, :celular, :senha, :rua, :cep, :numero, :municipio, :tipo_usuario, :cpf_cnpj)";
                        
                        $stmt = $conn->prepare($sql);
                        
                        // Bind dos parâmetros
                        $stmt->bindParam(':nome', $nome);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':celular', $celular);
                        $stmt->bindParam(':senha', $senha);
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
                    }
                }
            }
            
            // Exibe mensagem se houver
            if (!empty($message)) {
                echo "<div class='message $messageClass'>$message</div>";
            }
            ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="nome">Nome Completo:</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_usuario">Tipo de Usuário:</label>
                        <select id="tipo_usuario" name="tipo_usuario" required>
                            <option value="">Selecione...</option>
                            <option value="Produtor">Produtor</option>
                            <option value="Comerciante">Comerciante</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf_cnpj">CPF ou CNPJ:</label>
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="celular">Celular:</label>
                        <input type="tel" id="celular" name="celular" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha:</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="rua">Rua:</label>
                        <input type="text" id="rua" name="rua" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cep">CEP:</label>
                        <input type="text" id="cep" name="cep" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero">Número:</label>
                        <input type="text" id="numero" name="numero" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="municipio">Município:</label>
                        <input type="text" id="municipio" name="municipio" required>
                    </div>
                </div>
                
                <div class="terms-container">
                    <div class="checkbox-group">
                        <input type="checkbox" id="aceitar_termos" name="aceitar_termos" required>
                        <label for="aceitar_termos">
                            Concordo com os <a href="politica.php" class="terms-link" target="_blank">Termos de Uso e Política de Privacidade</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit">Realizar Cadastro</button>
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
            const form = document.querySelector('form');
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            const tipoUsuario = document.getElementById('tipo_usuario');
            const cpfCnpj = document.getElementById('cpf_cnpj');
            
            // Validação de confirmação de senha
            form.addEventListener('submit', function(e) {
                if (senha.value !== confirmarSenha.value) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    senha.focus();
                }
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