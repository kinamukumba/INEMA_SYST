<?php
// backend/config/database.php

require_once 'config.php';

class Database {
    private static $connection = null;

    /**
     * Retorna a instância nativa do PDO conectada à base de dados.
     * Utiliza o padrão Singleton para evitar múltiplas conexões na mesma requisição.
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                
                $options = [
                    // Garante que o PDO lance uma Exception sempre que ocorrer um erro SQL
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Retorna os dados como Arrays Associativos por excelência ($linha['nome'])
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Desabilita emulações de prepares estritos para proteção efetiva e real contra SQL INJECTION
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                // Em caso de erro estrutural, sendo uma API, emitiremos um JSON em vez de rebentar o PHP puro.
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro crítico de conexão com a Base de Dados.',
                    'error_dev' => $e->getMessage() // Pode remover em produção
                ]);
                exit;
            }
        }
        
        return self::$connection;
    }
}
