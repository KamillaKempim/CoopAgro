<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
checkAuth();

$usuario_id = $_SESSION['user_id'];
$errors = [];
$success = false;

try {
    $conn = getDBConnection();
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

        if (empty($nome))
            $errors[] = "Nome é obrigatório.";
        if (empty($email))
            $errors[] = "Email é obrigatório.";
        if (empty($celular))
            $errors[] = "Celular é obrigatório.";

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, celular = ?, rua = ?, cep = ?, numero = ?, bairro = ?, municipio = ?, estado = ? 
                WHERE id = ?
            ");

            if ($stmt->execute([$nome, $email, $celular, $rua, $cep, $numero, $bairro, $municipio, $estado, $usuario_id])) {
                $success = true;
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

} catch (PDOException $e) {
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

        /* Botão de acessibilidade */
        .painel-flutuante {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        #btnAbrir {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background-color: #0c7534;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }

        #btnAbrir:hover {
            background-color: #0b7d44;
        }


        .painel-acessibilidade {
            display: none;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 10px;
            background: #ffffff;
            color: rgb(11, 66, 5);
            padding: 12px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }


        .painel-acessibilidade button {
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.2s;
            background-color: #f0f0f0;
        }

        .painel-acessibilidade button:hover {
            background-color: #d4d4d4;
        }


        .modo-contraste,
        .modo-contraste * {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #fff !important;
        }

        .modo-contraste a {
            color: #FFD700 !important;
            text-decoration: underline;
        }

        .modo-contraste img {
            filter: brightness(0.8) !important;
        }

        .modo-contraste,
        .modo-contraste * {
            background-color: #000 !important;
            color: #fff !important;
            border-color: #fff !important;
            fill: #fff !important;
            stroke: #fff !important;
        }

        .modo-contraste a,
        .modo-contraste a * {
            color: #FFD700 !important;
            text-decoration: underline !important;
        }

        .modo-contraste * {
            background-image: none !important;
        }

        .modo-contraste img,
        .modo-contraste [style*="background-image"] {
            filter: grayscale(1) brightness(0.4) !important;
        }

        .modo-contraste .card,
        .modo-contraste .container,
        .modo-contraste section,
        .modo-contraste .row,
        .modo-contraste .col,
        .modo-contraste footer,
        .modo-contraste header,
        .modo-contraste nav {
            background-color: #000 !important;
            color: #fff !important;
        }

        .modo-contraste button,
        .modo-contraste .btn {
            background-color: #222 !important;
            color: #fff !important;
            border: 1px solid #fff !important;
        }

        .modo-contraste .bi,
        .modo-contraste i {
            color: #fff !important;
        }

        .modo-contraste .carousel-item,
        .modo-contraste .carousel-caption {
            background-color: #000 !important;
        }

        @media (max-width: 768px) {
            .container img {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .texto {
                width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .hero {
                height: auto;
            }

            .hero-img {
                width: 100%;
                height: auto;
                object-fit: contain;
            }
        }

        .modo-contraste img,
        .modo-contraste [style*="background-image"] {
            filter: grayscale(0.2) brightness(0.8) !important;
        }
    </style>
</head>

<body>
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <!-- Painel acessibilidade -->
    <div class="painel-flutuante">
        <button id="btnAbrir">⚙️</button>
        <div class="painel-acessibilidade" id="painelAcessibilidade">
            <h4>Painel de Acessibilidade</h4>
            <button onclick="contraste()"><i class="bi bi-brightness-high-fill"> </i>Alto contraste</button>
            <button onclick="fonteMais()"><i class="bi bi-type-bold"></i></button>
            <button onclick="fonteMenos()"><i class="bi bi-type"></i></button>
            <button onclick="resetar()"><i class="bi bi-arrow-counterclockwise"></i> Padrão</button>
        </div>
    </div>


    <script>
        let tamanho = localStorage.getItem("fonte") || 16;
        document.body.style.fontSize = tamanho + "px";

        if (localStorage.getItem("contraste") === "ativo") {
            document.body.classList.add("modo-contraste");
        }

        function contraste() {
            document.body.classList.toggle("modo-contraste");
            let ativo = document.body.classList.contains("modo-contraste");
            localStorage.setItem("contraste", ativo ? "ativo" : "inativo");
        }

        function fonteMais() {
            tamanho = parseInt(tamanho) + 2;
            document.body.style.fontSize = tamanho + "px";
            localStorage.setItem("fonte", tamanho);
        }

        function fonteMenos() {
            tamanho = parseInt(tamanho) - 2;
            document.body.style.fontSize = tamanho + "px";
            localStorage.setItem("fonte", tamanho);
        }

        function resetar() {
            document.body.classList.remove("modo-contraste");
            document.body.style.fontSize = "16px";
            localStorage.clear();
        }

        const btnAbrir = document.getElementById('btnAbrir');
        const painel = document.getElementById('painelAcessibilidade');

        btnAbrir.addEventListener('click', () => {
            painel.style.display = painel.style.display === 'flex' ? 'none' : 'flex';
        });
    </script>
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
                        <input type="text" class="form-control" name="nome"
                            value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email"
                            value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Celular</label>
                        <input type="tel" class="form-control" name="celular"
                            value="<?php echo htmlspecialchars($usuario['celular']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control" name="cep"
                            value="<?php echo htmlspecialchars($usuario['cep']); ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Rua</label>
                        <input type="text" class="form-control" name="rua"
                            value="<?php echo htmlspecialchars($usuario['rua']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="numero"
                            value="<?php echo htmlspecialchars($usuario['numero']); ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control" name="bairro"
                            value="<?php echo htmlspecialchars($usuario['bairro']); ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Município</label>
                        <input type="text" class="form-control" name="municipio"
                            value="<?php echo htmlspecialchars($usuario['municipio']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" name="estado"
                            value="<?php echo htmlspecialchars($usuario['estado']); ?>" required>
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