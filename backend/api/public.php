<?php
require_once 'core.php';

$action = $_GET['action'] ?? '';

if ($action === 'get_bases') {
    try {
        $stmt = $pdo->query(
            "SELECT id, nome_base, municipio, endereco, capacidade FROM bases ORDER BY nome_base ASC"
        );
        $bases = $stmt->fetchAll();
        sendResponse(true, 'Bases carregadas', $bases);
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao carregar bases: ' . $e->getMessage());
    }
}

sendResponse(false, 'Ação inválida.');
