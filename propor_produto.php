<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];

// Verifica se o usuário é produtor
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['tipo_usuario'] !== 'Produtor') {
        header("Location: perfil.php");
        exit();
    }
} catch(PDOException $e) {
    die("Erro ao verificar tipo de usuário: " . $e->getMessage());
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF inválido.');
    }
    
    $nome = sanitizeInput($_POST['nome']);
    $descricao = sanitizeInput($_POST['descricao']);
    $tipo = sanitizeInput($_POST['tipo']);
    $quantidade_disponivel = intval($_POST['quantidade_disponivel']);
    $preco_sugerido = floatval($_POST['preco_sugerido']);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    // Validações
    if (empty($nome)) $errors[] = "Nome do produto é obrigatório.";
    if (empty($descricao)) $errors[] = "Descrição é obrigatória.";
    if (empty($tipo)) $errors[] = "Tipo do produto é obrigatório.";
    if ($quantidade_disponivel <= 0) $errors[] = "Quantidade deve ser maior que zero.";
    if ($preco_sugerido <= 0) $errors[] = "Preço sugerido deve ser maior que zero.";
    
    // Processar upload da imagem (similar ao cadastro de produtos anterior)
    $imagemNome = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Código de upload similar ao anterior...
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("
                INSERT INTO produtos_propostos 
                (produtor_id, nome, descricao, tipo, quantidade_disponivel, imagem_url, preco_sugerido, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $nome,
                $descricao,
                $tipo,
                $quantidade_disponivel,
                $imagemNome,
                $preco_sugerido,
                $observacoes
            ]);
            
            $success = true;
            
        } catch(PDOException $e) {
            $errors[] = "Erro ao propor produto: " . $e->getMessage();
        }
    }
}
?>

