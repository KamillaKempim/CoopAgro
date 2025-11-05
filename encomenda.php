<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

// Verifica se o ID do produto foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produtos.php");
    exit();
}

$produto_id = $_GET['id'];
$errors = [];
$success = false;

try {
    $conn = getDBConnection();
    
    // Busca o produto
    $stmtProduto = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND disponivel = 1");
    $stmtProduto->execute([$produto_id]);
    $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        header("Location: produtos.php");
        exit();
    }
    
    // Busca os dados do usuário logado
    $usuario_id = $_SESSION['user_id'];
    $stmtUsuario = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtUsuario->execute([$usuario_id]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
    
    // Processa o pedido
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            die('Token CSRF inválido.');
        }
        
        $quantidade = intval($_POST['quantidade']);
        $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
        
        // Validações
        if ($quantidade <= 0) {
            $errors[] = "Quantidade deve ser maior que zero.";
        }
        
        if ($quantidade > $produto['quantidade_estoque']) {
            $errors[] = "Quantidade solicitada indisponível em estoque. Disponível: " . $produto['quantidade_estoque'];
        }
        
        if (empty($errors)) {
            // Prepara o endereço de entrega
            $endereco_entrega = implode(", ", [
                $usuario['rua'],
                $usuario['numero'],
                $usuario['bairro'] ?? '',
                $usuario['municipio'],
                $usuario['estado'] ?? '',
                "CEP: " . $usuario['cep']
            ]);
            
            // Calcula o total
            $preco_unitario = $produto['preco'];
            $total = $preco_unitario * $quantidade;
            
            // Inicia transação
            $conn->beginTransaction();
            
            try {
                // Insere o pedido
                $stmtPedido = $conn->prepare("
                    INSERT INTO pedidos 
                    (usuario_id, produto_id, quantidade, preco_unitario, total, endereco_entrega, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtPedido->execute([
                    $usuario_id,
                    $produto_id,
                    $quantidade,
                    $preco_unitario,
                    $total,
                    $endereco_entrega,
                    $observacoes
                ]);
                
                // Atualiza o estoque do produto
                $stmtEstoque = $conn->prepare("
                    UPDATE produtos 
                    SET quantidade_estoque = quantidade_estoque - ? 
                    WHERE id = ? AND quantidade_estoque >= ?
                ");
                $stmtEstoque->execute([$quantidade, $produto_id, $quantidade]);
                
                // Verifica se a atualização foi bem sucedida
                if ($stmtEstoque->rowCount() === 0) {
                    throw new Exception("Erro ao atualizar estoque. Produto pode ter sido vendido.");
                }
                
                // Confirma a transação
                $conn->commit();
                $success = true;
                $pedido_id = $conn->lastInsertId();
                
            } catch (Exception $e) {
                // Rollback em caso de erro
                $conn->rollBack();
                $errors[] = "Erro ao processar pedido: " . $e->getMessage();
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
    <title>Encomendar Produto - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --verde-claro: #a5d6a7;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--verde-principal) 0%, var(--verde-secundario) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .product-card {
            border: 2px solid var(--verde-claro);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        
        .address-card {
            border-left: 4px solid var(--verde-principal);
            background-color: #f8f9fa;
        }
        
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-success:hover {
            background-color: var(--verde-secundario);
            border-color: var(--verde-secundario);
        }
        
        .price-highlight {
            color: var(--verde-principal);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .stock-info {
            font-size: 0.9rem;
        }
        
        .success-card {
            border: 2px solid var(--verde-secundario);
            background: linear-gradient(135deg, #e8f5e8 0%, #f8fff8 100%);
        }
    </style>
</head>
<body>
    <?php include 'includes/_menu.php'; ?>

    <div class="container mt-4">
        <?php if ($success): ?>
            <!-- Tela de Sucesso -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card success-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle-fill text-success display-1 mb-3"></i>
                            <h2 class="text-success mb-3">Pedido Realizado com Sucesso!</h2>
                            <p class="lead mb-4">Seu pedido foi registrado e está sendo processado.</p>
                            
                            <div class="row text-start mb-4">
                                <div class="col-md-6">
                                    <p><strong>Número do Pedido:</strong> #<?php echo $pedido_id; ?></p>
                                    <p><strong>Produto:</strong> <?php echo htmlspecialchars($produto['nome']); ?></p>
                                    <p><strong>Quantidade:</strong> <?php echo $quantidade; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total:</strong> R$ <?php echo number_format($total, 2, ',', '.'); ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-warning">Pendente</span></p>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-center">
                                <a href="produtos.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-cart-plus"></i> Continuar Comprando
                                </a>
                                <a href="meus_pedidos.php" class="btn btn-outline-success btn-lg">
                                    <i class="bi bi-list-check"></i> Ver Meus Pedidos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulário de Encomenda -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="bi bi-truck"></i> Finalizar Encomenda</h4>
                        </div>
                        <div class="card-body">
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
                                
                                <h5 class="mb-3">1. Dados do Produto</h5>
                                <div class="card product-card mb-4">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <?php 
                                            $imagemSrc = !empty($produto['imagem_url']) ? 
                                                'uploads/produtos/' . htmlspecialchars($produto['imagem_url']) : 
                                                'https://via.placeholder.com/300x200/CCCCCC/969696?text=Sem+Imagem';
                                            ?>
                                            <img src="<?php echo $imagemSrc; ?>" 
                                                 class="img-fluid product-image w-100" 
                                                 alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                                 onerror="this.src='https://via.placeholder.com/300x200/CCCCCC/969696?text=Imagem+Não+Encontrada'">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                                <p class="card-text">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($produto['tipo']); ?></span>
                                                </p>
                                                <p class="card-text price-highlight">
                                                    R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                                </p>
                                                <p class="card-text stock-info text-muted">
                                                    <i class="bi bi-box-seam"></i> 
                                                    <?php echo $produto['quantidade_estoque']; ?> unidades disponíveis
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3">2. Quantidade</h5>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="quantidade" class="form-label">Quantidade Desejada</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="quantidade" 
                                               name="quantidade" 
                                               value="1" 
                                               min="1" 
                                               max="<?php echo $produto['quantidade_estoque']; ?>"
                                               required>
                                        <div class="form-text">
                                            Máximo: <?php echo $produto['quantidade_estoque']; ?> unidades
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="observacoes" class="form-label">Observações (Opcional)</label>
                                        <textarea class="form-control" 
                                                  id="observacoes" 
                                                  name="observacoes" 
                                                  rows="2" 
                                                  placeholder="Alguma observação sobre o pedido..."></textarea>
                                    </div>
                                </div>

                                <h5 class="mb-3">3. Endereço de Entrega</h5>
                                <div class="card address-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="card-title mb-0">Endereço Cadastrado</h6>
                                            <a href="editar_perfil.php" class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-pencil"></i> Atualizar Cadastro
                                            </a>
                                        </div>
                                        <p class="card-text mb-1">
                                            <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                        </p>
                                        <p class="card-text mb-1">
                                            <?php echo htmlspecialchars($usuario['rua']); ?>, 
                                            <?php echo htmlspecialchars($usuario['numero']); ?>
                                            <?php echo !empty($usuario['bairro']) ? ' - ' . htmlspecialchars($usuario['bairro']) : ''; ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <?php echo htmlspecialchars($usuario['municipio']); ?>
                                            <?php echo !empty($usuario['estado']) ? ' - ' . htmlspecialchars($usuario['estado']) : ''; ?>
                                        </p>
                                        <p class="card-text mb-0">
                                            CEP: <?php echo htmlspecialchars($usuario['cep']); ?>
                                        </p>
                                        <p class="card-text mb-0">
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($usuario['celular']); ?>
                                        </p>
                                    </div>
                                </div>

                                <h5 class="mb-3">4. Resumo do Pedido</h5>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <p class="mb-1">Subtotal:</p>
                                                <p class="mb-1">Quantidade:</p>
                                                <p class="mb-0"><strong>Total:</strong></p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="mb-1" id="subtotal">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                                                <p class="mb-1" id="quantidade-display">1 unidade</p>
                                                <p class="mb-0"><strong id="total">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-lg"></i> Confirmar Encomenda
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informações Importantes</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-truck"></i> Entrega</h6>
                                <p class="small mb-2">• Entregas realizadas de segunda a sábado</p>
                                <p class="small mb-2">• Prazo: 2-5 dias úteis</p>
                                <p class="small mb-0">• Horário: 8h às 18h</p>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-credit-card"></i> Pagamento</h6>
                                <p class="small mb-2">• Pagamento na entrega</p>
                                <p class="small mb-2">• Aceitamos dinheiro e PIX</p>
                                <p class="small mb-0">• Sem taxa de entrega</p>
                            </div>
                            
                            <div class="alert alert-success">
                                <h6><i class="bi bi-shield-check"></i> Segurança</h6>
                                <p class="small mb-0">Seus dados estão protegidos pela LGPD</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantidadeInput = document.getElementById('quantidade');
            const subtotalElement = document.getElementById('subtotal');
            const totalElement = document.getElementById('total');
            const quantidadeDisplay = document.getElementById('quantidade-display');
            const precoUnitario = <?php echo $produto['preco']; ?>;
            
            function atualizarTotal() {
                const quantidade = parseInt(quantidadeInput.value) || 0;
                const subtotal = precoUnitario * quantidade;
                const total = subtotal;
                
                subtotalElement.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
                totalElement.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
                quantidadeDisplay.textContent = quantidade + ' unidade' + (quantidade !== 1 ? 's' : '');
            }
            
            quantidadeInput.addEventListener('input', atualizarTotal);
            quantidadeInput.addEventListener('change', atualizarTotal);
            
            // Atualiza inicialmente
            atualizarTotal();
        });
    </script>
</body>
</html>