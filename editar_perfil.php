<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];
$errors = [];
$success = false;

try {
    $conn = getDBConnection();
    
    // Busca dados do usuário
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header("Location: login.php");
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            die('Token CSRF inválido.');
        }
        
        $nome = sanitizeInput($_POST['nome']);
        $email = sanitizeInput($_POST['email']);
        $celular = sanitizeInput($_POST['celular']);
        $rua = sanitizeInput($_POST['rua']);
        $cep = sanitizeInput($_POST['cep']);
        $numero = sanitizeInput($_POST['numero']);
        $bairro = sanitizeInput($_POST['bairro']);
        $municipio = sanitizeInput($_POST['municipio']);
        $estado = sanitizeInput($_POST['estado']);
        
        // Validações
        if (empty($nome)) $errors[] = "Nome é obrigatório.";
        if (empty($email)) $errors[] = "Email é obrigatório.";
        if (empty($celular)) $errors[] = "Celular é obrigatório.";
        
        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, celular = ?, rua = ?, cep = ?, numero = ?, bairro = ?, municipio = ?, estado = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$nome, $email, $celular, $rua, $cep, $numero, $bairro, $municipio, $estado, $usuario_id])) {
                $success = true;
                // Atualiza os dados locais
                $usuario = array_merge($usuario, [
                    'nome' => $nome,
                    'email' => $email,
                    'celular' => $celular,
                    'rua' => $rua,
                    'cep' => $cep,
                    'numero' => $numero,
                    'bairro' => $bairro,
                    'municipio' => $municipio,
                    'estado' => $estado
                ]);
            } else {
                $errors[] = "Erro ao atualizar perfil.";
            }
        }
    }
    
} catch(PDOException $e) {
    $errors[] = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
    </style>
</head>
<body>
    <!-- VLibras -->
  <div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper></div>
  </div>
    <?php include 'includes/_menu.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="bi bi-person-gear"></i> Editar Perfil</h1>
            <p class="mb-0">Atualize suas informações pessoais</p>
        </div>
        
        <div class="p-4">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Perfil atualizado com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Celular</label>
                        <input type="tel" class="form-control" name="celular" value="<?php echo htmlspecialchars($usuario['celular']); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control" name="cep" value="<?php echo htmlspecialchars($usuario['cep']); ?>" required>
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Rua</label>
                        <input type="text" class="form-control" name="rua" value="<?php echo htmlspecialchars($usuario['rua']); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="numero" value="<?php echo htmlspecialchars($usuario['numero']); ?>" required>
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control" name="bairro" value="<?php echo htmlspecialchars($usuario['bairro']); ?>" required>
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Município</label>
                        <input type="text" class="form-control" name="municipio" value="<?php echo htmlspecialchars($usuario['municipio']); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" name="estado" value="<?php echo htmlspecialchars($usuario['estado']); ?>" required>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-lg"></i> Salvar Alterações
                    </button>
                    <a href="telaadministrativa.php" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
  <script>
    new window.VLibras.Widget('https://vlibras.gov.br/app');
  </script>
</body>
</html>