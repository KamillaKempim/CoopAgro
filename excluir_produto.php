<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verifica se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: telaadministrativa.php");
    exit();
}

$id = $_GET['id'];

try {
    $conn = getDBConnection();
    
    // Verifica se o produto existe
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        header("Location: telaadministrativa.php");
        exit();
    }
    
    // Exclui o produto
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: telaadministrativa.php?success=3");
    exit();
} catch(PDOException $e) {
    die("Erro ao excluir produto: " . $e->getMessage());
}
?>