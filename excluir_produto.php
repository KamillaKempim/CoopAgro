<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verifica se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID do produto inválido.';
    header("Location: telaadministrativa.php");
    exit();
}

$id = intval($_GET['id']);

// Verifica se o usuário tem permissão para excluir
$usuario_id = intval($_SESSION['user_id']);

try {
    $conn = getDBConnection();
    
    // Verifica se o produto existe e se pertence ao usuário (ou se é admin)
    $stmt = $conn->prepare("
        SELECT p.*, u.tipo_usuario, u.id as usuario_id 
        FROM produtos p 
        LEFT JOIN usuarios u ON p.criado_por = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        $_SESSION['error'] = 'Produto não encontrado.';
        header("Location: telaadministrativa.php");
        exit();
    }
    
    // Verificar permissões
    $permissao = false;
    
    // Administrador pode excluir qualquer produto
    if ($_SESSION['tipo_usuario'] === 'Administrador') {
        $permissao = true;
    }
    // Produtor só pode excluir seus próprios produtos
    elseif ($_SESSION['tipo_usuario'] === 'Produtor' && $produto['criado_por'] == $usuario_id) {
        $permissao = true;
    }
    
    if (!$permissao) {
        $_SESSION['error'] = 'Você não tem permissão para excluir este produto.';
        header("Location: telaadministrativa.php");
        exit();
    }
    
    // Verificar se existem pedidos ativos para este produto
    $stmtPedidos = $conn->prepare("
        SELECT COUNT(*) as total_pedidos 
        FROM pedidos 
        WHERE produto_id = ? AND status NOT IN ('entregue', 'cancelado')
    ");
    $stmtPedidos->execute([$id]);
    $pedidosAtivos = $stmtPedidos->fetch(PDO::FETCH_ASSOC);
    
    if ($pedidosAtivos['total_pedidos'] > 0) {
        $_SESSION['error'] = 'Não é possível excluir este produto pois existem pedidos ativos relacionados a ele.';
        header("Location: telaadministrativa.php");
        exit();
    }
    
    // Obter informações do produto para log
    $nomeProduto = $produto['nome'];
    $imagemUrl = $produto['imagem_url'];
    
    // Exclui o produto
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    
    // Remove a imagem do produto se existir
    if (!empty($imagemUrl)) {
        $uploadDir = 'uploads/produtos/';
        $imagemPath = $uploadDir . $imagemUrl;
        
        if (file_exists($imagemPath)) {
            unlink($imagemPath);
        }
    }
    
    // Log da ação
    
    
    $_SESSION['success'] = "Produto '{$nomeProduto}' excluído com sucesso!";
    header("Location: telaadministrativa.php");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Erro ao excluir produto: " . htmlspecialchars($e->getMessage());
    logSecurity($usuario_id, 'erro_exclusao_produto', "Erro ao excluir produto #{$id}: " . $e->getMessage());
    header("Location: telaadministrativa.php");
    exit();
}
?>