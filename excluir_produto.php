<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID do produto inválido.';
    header("Location: telaadministrativa.php");
    exit();
}

$id = intval($_GET['id']);
$usuario_id = intval($_SESSION['user_id']);

try {
    $conn = getDBConnection();
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

    $permissao = false;

    if ($_SESSION['tipo_usuario'] === 'Administrador') {
        $permissao = true;
    } elseif ($_SESSION['tipo_usuario'] === 'Produtor' && $produto['criado_por'] == $usuario_id) {
        $permissao = true;
    }

    if (!$permissao) {
        $_SESSION['error'] = 'Você não tem permissão para excluir este produto.';
        header("Location: telaadministrativa.php");
        exit();
    }

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

    $nomeProduto = $produto['nome'];
    $imagemUrl = $produto['imagem_url'];
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    if (!empty($imagemUrl)) {
        $uploadDir = 'uploads/produtos/';
        $imagemPath = $uploadDir . $imagemUrl;

        if (file_exists($imagemPath)) {
            unlink($imagemPath);
        }
    }

    $_SESSION['success'] = "Produto '{$nomeProduto}' excluído com sucesso!";
    header("Location: telaadministrativa.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao excluir produto: " . htmlspecialchars($e->getMessage());
    logSecurity($usuario_id, 'erro_exclusao_produto', "Erro ao excluir produto #{$id}: " . $e->getMessage());
    header("Location: telaadministrativa.php");
    exit();
}
?>