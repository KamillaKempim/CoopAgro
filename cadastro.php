<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Agricultura Familiar</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: #2E7D32;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-group {
            flex: 1 0 calc(50% - 20px);
            margin: 0 10px 20px;
        }
        
        .full-width {
            flex: 1 0 calc(100% - 20px);
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
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 16px 30px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2E7D32;
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
        }
        
        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 calc(100% - 20px);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cadastro - CoopAgro</h1>
            <p>Preencha os dados abaixo para se cadastrar em nosso sistema</p>
        </div>
        
        <div class="form-container">
            <?php
            // Inclui o arquivo de conexão
            require_once 'config/database.php';
            //include 'database.php';
            
            // Variáveis para mensagens
            $message = '';
            $messageClass = '';
            
            // Processa o formulário quando enviado
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Coleta e sanitiza os dados
                $nome = sanitizeInput($_POST['nome']);
                $email = sanitizeInput($_POST['email']);
                $celular = sanitizeInput($_POST['celular']);
                $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Criptografa a senha
                $rua = sanitizeInput($_POST['rua']);
                $cep = sanitizeInput($_POST['cep']);
                $numero = sanitizeInput($_POST['numero']);
                $municipio = sanitizeInput($_POST['municipio']);
                
                try {
                    // Conecta ao banco
                    $conn = getDBConnection();
                    
                    // Prepara a query SQL
                    $sql = "INSERT INTO usuarios (nome, email, celular, senha, rua, cep, numero, municipio) 
                            VALUES (:nome, :email, :celular, :senha, :rua, :cep, :numero, :municipio)";
                    
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
                    
                    // Executa a query
                    if ($stmt->execute()) {
                        $message = "Cadastro realizado com sucesso!";
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
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="celular">Celular:</label>
                        <input type="tel" id="celular" name="celular" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha:</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="rua">Rua:</label>
                        <input type="text" id="rua" name="rua" required>
                    </div>
                    
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
                
                <button type="submit">Realizar Cadastro</button>
            </form>
        </div>
        
        <div class="footer">
            <p>Agricultura Familiar &copy; 2023 - Todos os direitos reservados</p>
        </div>
    </div>

    <script>
        // Validação de confirmação de senha
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            
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
        });
    </script>
</body>
</html>