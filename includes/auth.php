<?php
// Arquivo: includes/auth.php
// Correções: Sessão segura e consistente

// Iniciar sessão apenas se não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookie de sessão seguro
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Verifica se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id'], $_SESSION['user_ip'], $_SESSION['user_agent']) 
           && $_SESSION['user_ip'] === $_SERVER['REMOTE_ADDR']
           && $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
}

// Verifica permissões
function checkAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit();
    }
}

// Verifica permissão de administrador
function isAdmin() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Administrador';
}

// Verifica permissão de produtor
function isProducer() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'Produtor';
}

// Proteção contra CSRF
function generateCSRFToken($formName = 'default') {
    if (empty($_SESSION['csrf_tokens'][$formName])) {
        $_SESSION['csrf_tokens'][$formName] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_tokens'][$formName];
}

function verifyCSRFToken($token, $formName = 'default') {
    if (!isset($_SESSION['csrf_tokens'][$formName])) {
        return false;
    }
    
    $isValid = hash_equals($_SESSION['csrf_tokens'][$formName], $token);
    
    // Token de uso único
    unset($_SESSION['csrf_tokens'][$formName]);
    
    return $isValid;
}

// Função para obter informações do usuário logado
function getUserInfo() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'nome' => $_SESSION['username'],
            'email' => $_SESSION['user_email'],
            'tipo_usuario' => $_SESSION['tipo_usuario'] ?? null
        ];
    }
    return null;
}

// Inicializar sessão de usuário
function initUserSession($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['nome'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['tipo_usuario'] = $userData['tipo_usuario'];
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['login_time'] = time();
    
    // Regenerar ID de sessão após login
    session_regenerate_id(true);
}

// Logout seguro
function secureLogout() {
    // Limpa todas as variáveis de sessão
    $_SESSION = [];
    
    // Destrói o cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destrói a sessão
    session_destroy();
}
?>