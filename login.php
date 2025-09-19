<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Conecta ao banco de dados
        $conn = getDBConnection();
        
        // Prepara a consulta para buscar o usuário
        $sql = "SELECT id, nome, email, senha FROM usuarios WHERE email = :username OR nome = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Verifica se encontrou o usuário
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifica a senha
            if (password_verify($password, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                
                header("Location: telaadministrativa.php");
                exit();
            } else {
                $error = "Senha incorreta!";
            }
        } else {
            $error = "Usuário não encontrado!";
        }
    } catch(PDOException $e) {
        $error = "Erro no sistema. Tente novamente mais tarde.";
        error_log("Erro de login: " . $e->getMessage());
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
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
  <header>
    <?php require_once "includes/_menu.php"; ?>
  </header>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">CoopAgro</h2>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuário ou Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Entrar</button>
                        </form>
                        <p class="text-center mt-3 mb-0">
                            <a href="cadastro.php">Não tem uma conta? Cadastre-se</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>