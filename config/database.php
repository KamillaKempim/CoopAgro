<?php
// Arquivo: config/database.php
// Versão compatível com o código existente

// Configurações de acesso ao banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'coopagro');
define('DB_USER', 'root');
define('DB_PASS', 'Seq098@$');
define('DB_CHARSET', 'utf8mb4');

// Função para conexão com o banco
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $conn = new PDO($dsn, DB_USER, DB_PASS);
            
            // Configurar opções do PDO
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Configurações adicionais
            $conn->exec("SET time_zone = '-03:00'");
            $conn->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            
        } catch(PDOException $e) {
            // Log seguro
            error_log("Erro de conexão PDO: " . $e->getMessage());
            
            // Em desenvolvimento, mostrar erro detalhado
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                die("Erro de conexão: " . $e->getMessage());
            } else {
                die("Erro ao conectar com o banco de dados. Tente novamente mais tarde.");
            }
        }
    }
    
    return $conn;
}

// Funções de sanitização
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// Validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validar CPF/CNPJ
function validateCpfCnpj($cpf_cnpj) {
    $cpf_cnpj = preg_replace('/[^0-9]/', '', $cpf_cnpj);
    
    if (strlen($cpf_cnpj) === 11) {
        return validateCPF($cpf_cnpj);
    } elseif (strlen($cpf_cnpj) === 14) {
        return validateCNPJ($cpf_cnpj);
    }
    return false;
}

function validateCPF($cpf) {
    // Elimina CPFs inválidos conhecidos
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Valida primeiro dígito verificador
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function validateCNPJ($cnpj) {
    // Elimina CNPJs inválidos conhecidos
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Valida primeiro dígito verificador
    $tamanho = $cnpj.length - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    if ($resultado != $digitos[0]) {
        return false;
    }
    
    // Valida segundo dígito verificador
    $tamanho = $tamanho + 1;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    return $resultado == $digitos[1];
}

// Gerar token seguro
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Previne contra injeção de arquivos
function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', $filename);
    $filename = substr($filename, 0, 255);
    return $filename;
}

// Função para log de erros
function logError($message, $exception = null) {
    $logMessage = date('[Y-m-d H:i:s]') . " " . $message;
    
    if ($exception instanceof Exception) {
        $logMessage .= " - " . $exception->getMessage();
        $logMessage .= " em " . $exception->getFile() . ":" . $exception->getLine();
    }
    
    // Log em arquivo
    $logFile = __DIR__ . '/../logs/errors.log';
    
    // Criar diretório de logs se não existir
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    error_log($logMessage . "\n", 3, $logFile);
}

// Definir ambiente (desenvolvimento ou produção)
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Altere para 'production' em produção
}
?>