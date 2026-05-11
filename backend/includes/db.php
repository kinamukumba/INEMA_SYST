<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// Função para sanitizar inputs
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para verificar se está logado
function checkAuth($role = null) {
    if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['base_id'])) {
        header("Location: " . BASE_URL . "login.html");
        exit();
    }
    
    if ($role && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] !== $role) {
        header("Location: " . BASE_URL . "index.html");
        exit();
    }
}
?>
