<?php
// Configurações da Base de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'inema_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações do Sistema
define('SITE_NAME', 'INEMA - Sistema de Emergências');
define('BASE_URL', 'http://localhost/tcc/');

// Iniciar Sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
