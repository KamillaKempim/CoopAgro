<?php
// Configurações de acesso ao banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'agricultura_familiar');
define('DB_USER', 'root');
define('DB_PASS', 'Seq098@$');

// Função para conexão com o banco
function getDBConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("set names utf8");
        return $conn;
    } catch(PDOException $e) {
        error_log("Erro de conexão: " . $e->getMessage());
        die("Erro ao conectar com o banco de dados. Tente novamente mais tarde.");
    }
}

// Previne contra SQL Injection
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>