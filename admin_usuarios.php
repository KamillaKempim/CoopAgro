<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['tipo_usuario'] !== 'Administrador') {
        header("Location: perfil.php");
        exit();
    }
} catch (PDOException $e) {
    $error_permissao = "Erro ao verificar permissões: " . $e->getMessage();
}

$mensagem = '';
$tipo_mensagem = '';

if (isset($_POST['excluir_usuario'])) {
    try {
        $conn = getDBConnection();
        $usuario_excluir_id = $_POST['usuario_id'];

        if ($usuario_excluir_id == $usuario_id) {
            $mensagem = "Você não pode excluir sua própria conta!";
            $tipo_mensagem = "danger";
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_excluir_id]);

            $mensagem = "Usuário excluído com sucesso!";
            $tipo_mensagem = "success";
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir usuário: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

if (isset($_POST['editar_usuario'])) {
    try {
        $conn = getDBConnection();
        $usuario_editar_id = $_POST['usuario_id'];
        $novo_tipo = $_POST['tipo_usuario'];
        $novo_nome = $_POST['nome'];
        $novo_email = $_POST['email'];
        $novo_celular = $_POST['celular'];

        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET nome = ?, email = ?, celular = ?, tipo_usuario = ? 
            WHERE id = ?
        ");
        $stmt->execute([$novo_nome, $novo_email, $novo_celular, $novo_tipo, $usuario_editar_id]);

        $mensagem = "Usuário atualizado com sucesso!";
        $tipo_mensagem = "success";
    } catch (PDOException $e) {
        $mensagem = "Erro ao atualizar usuário: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT 
            id, nome, email, celular, tipo_usuario, cpf_cnpj, 
            data_cadastro, rua, numero, bairro, cep, municipio, estado
        FROM usuarios 
        ORDER BY data_cadastro DESC
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(*) as total_usuarios,
            COUNT(CASE WHEN tipo_usuario = 'Administrador' THEN 1 END) as total_admins,
            COUNT(CASE WHEN tipo_usuario = 'Produtor' THEN 1 END) as total_produtores,
            COUNT(CASE WHEN tipo_usuario = 'Comerciante' THEN 1 END) as total_comerciantes
        FROM usuarios
    ");
    $stmtStats->execute();
    $estatisticas = $stmtStats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erro ao carregar usuários: " . $e->getMessage();
    $usuarios = [];
    $estatisticas = [
        'total_usuarios' => 0,
        'total_admins' => 0,
        'total_produtores' => 0,
        'total_comerciantes' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - CoopAgro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --verde-principal: #2e7d32;
            --verde-secundario: #4caf50;
            --roxo-admin: #6a1b9a;
            --laranja-produtor: #ff6b35;
            --azul-comerciante: #2196F3;
        }

        .card-relatorio {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card-relatorio:hover {
            transform: translateY(-5px);
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--roxo-admin) 0%, #9c4dcc 100%);
            color: white;
            border: none;
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, #9c4dcc 0%, var(--roxo-admin) 100%);
            color: white;
        }

        .badge-admin {
            background: linear-gradient(135deg, var(--roxo-admin) 0%, #9c4dcc 100%);
            color: white;
        }

        .badge-produtor {
            background: linear-gradient(135deg, var(--laranja-produtor) 0%, #ff8e53 100%);
            color: white;
        }

        .badge-comerciante {
            background: linear-gradient(135deg, var(--azul-comerciante) 0%, #21CBF3 100%);
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(106, 27, 154, 0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--roxo-admin) 0%, #9c4dcc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .modal-header-admin {
            background: linear-gradient(135deg, var(--roxo-admin) 0%, #9c4dcc 100%);
            color: white;
        }

        .action-buttons .btn {
            margin: 2px;
        }

        /* Botão de acessibilidade */
       
    </style>
</head>

<body>

    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <?php include_once 'includes/_acessibilidade.php'; ?>
    

    <?php include 'includes/_menu.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-people"></i> Gerenciar Usuários
                    </h1>
                    <div>
                        <a href="admin_relatorios.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <a href="perfil.php" class="btn btn-outline-primary ms-2">
                            <i class="bi bi-person"></i> Meu Perfil
                        </a>
                    </div>
                </div>

                <?php if (isset($error_permissao)): ?>
                    <div class="alert alert-warning"><?php echo $error_permissao; ?></div>
                <?php endif; ?>

                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-people display-6 text-primary mb-3"></i>
                                <h3 class="text-primary"><?php echo $estatisticas['total_usuarios']; ?></h3>
                                <p class="text-muted mb-0">Total de Usuários</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-shield-check display-6 text-purple mb-3"></i>
                                <h3 class="text-purple"><?php echo $estatisticas['total_admins']; ?></h3>
                                <p class="text-muted mb-0">Administradores</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-tree display-6 text-warning mb-3"></i>
                                <h3 class="text-warning"><?php echo $estatisticas['total_produtores']; ?></h3>
                                <p class="text-muted mb-0">Produtores</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card card-relatorio text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-shop display-6 text-info mb-3"></i>
                                <h3 class="text-info"><?php echo $estatisticas['total_comerciantes']; ?></h3>
                                <p class="text-muted mb-0">Comerciantes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check"></i> Lista de Usuários Cadastrados
                        </h5>
                        <span class="badge bg-primary"><?php echo count($usuarios); ?> usuários</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usuarios)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people display-1 text-muted"></i>
                                <h4 class="text-muted mt-3">Nenhum usuário cadastrado</h4>
                                <p class="text-muted">Não há usuários no sistema.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Usuário</th>
                                            <th>Contato</th>
                                            <th>Tipo</th>
                                            <th>Documento</th>
                                            <th>Data Cadastro</th>
                                            <th>Endereço</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3">
                                                            <?php echo strtoupper(substr(explode(' ', $user['nome'])[0], 0, 1) . substr(explode(' ', $user['nome'])[1] ?? '', 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['nome']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                                                        <br>
                                                        <small
                                                            class="text-muted"><?php echo htmlspecialchars($user['celular']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge 
                                                        <?php
                                                        if ($user['tipo_usuario'] === 'Administrador')
                                                            echo 'badge-admin';
                                                        elseif ($user['tipo_usuario'] === 'Produtor')
                                                            echo 'badge-produtor';
                                                        else
                                                            echo 'badge-comerciante';
                                                        ?>">
                                                        <?php echo htmlspecialchars($user['tipo_usuario']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($user['cpf_cnpj']); ?></code>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($user['data_cadastro'])); ?>
                                                    <br>
                                                    <small
                                                        class="text-muted"><?php echo date('H:i', strtotime($user['data_cadastro'])); ?></small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo htmlspecialchars($user['rua']); ?>,
                                                        <?php echo htmlspecialchars($user['numero']); ?>
                                                        <?php if (!empty($user['bairro'])): ?>
                                                            <br><?php echo htmlspecialchars($user['bairro']); ?>
                                                        <?php endif; ?>
                                                        <br><?php echo htmlspecialchars($user['municipio']); ?> -
                                                        <?php echo htmlspecialchars($user['estado']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">

                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-user-nome="<?php echo htmlspecialchars($user['nome']); ?>"
                                                            data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                            data-user-celular="<?php echo htmlspecialchars($user['celular']); ?>"
                                                            data-user-tipo="<?php echo htmlspecialchars($user['tipo_usuario']); ?>">
                                                            <i class="bi bi-pencil"></i> Editar
                                                        </button>

                                                        <?php if ($user['id'] != $usuario_id): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="modal" data-bs-target="#modalExcluirUsuario"
                                                                data-user-id="<?php echo $user['id']; ?>"
                                                                data-user-nome="<?php echo htmlspecialchars($user['nome']); ?>">
                                                                <i class="bi bi-trash"></i> Excluir
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Você</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExcluirUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-admin">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o usuário <strong id="nomeUsuarioExcluir"></strong>?</p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita! Todos os dados do usuário serão
                        permanentemente removidos.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="usuario_id" id="usuarioIdExcluir">
                        <button type="submit" name="excluir_usuario" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Sim, Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-admin">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil"></i> Editar Usuário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="usuario_id" id="usuarioIdEditar">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="celular" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="celular" name="celular" required>
                        </div>

                        <div class="mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                <option value="Comerciante">Comerciante</option>
                                <option value="Produtor">Produtor</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i>
                                Usuários administradores terão acesso completo ao sistema.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_usuario" class="btn btn-admin">
                            <i class="bi bi-check"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const modalExcluir = document.getElementById('modalExcluirUsuario');
        modalExcluir.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-nome');

            document.getElementById('nomeUsuarioExcluir').textContent = userName;
            document.getElementById('usuarioIdExcluir').value = userId;
        });

        const modalEditar = document.getElementById('modalEditarUsuario');
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-nome');
            const userEmail = button.getAttribute('data-user-email');
            const userCelular = button.getAttribute('data-user-celular');
            const userTipo = button.getAttribute('data-user-tipo');

            document.getElementById('usuarioIdEditar').value = userId;
            document.getElementById('nome').value = userName;
            document.getElementById('email').value = userEmail;
            document.getElementById('celular').value = userCelular;
            document.getElementById('tipo_usuario').value = userTipo;
        });

        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>

</body>

</html>