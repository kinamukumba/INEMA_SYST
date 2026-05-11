<?php
require_once 'core.php';

$action = $_GET['action'] ?? '';

// Verificar autenticação
if (!isset($_SESSION['user'])) {
    sendResponse(false, 'Sessão expirada. Faça login novamente.');
}

$user_id = $_SESSION['user']['id'];

if ($action === 'submit_sos') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $categoria_id = (int)($data['categoria_id'] ?? 0);
    $localizacao = sanitize($data['localizacao'] ?? '');
    $num_vitimas = (int)($data['num_vitimas'] ?? 1);
    $feridos_graves = ($data['feridos_graves'] === 'sim') ? 1 : 0;
    $observacoes = sanitize($data['observacoes'] ?? '');
    
    // Coordenadas simuladas para a demo
    $lat = -8.8354;
    $lng = 13.2389;

    try {
        $stmt = $pdo->prepare("INSERT INTO ocorrencias (utente_id, categoria_id, localizacao_texto, latitude, longitude, num_vitimas, feridos_graves, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')");
        $stmt->execute([$user_id, $categoria_id, $localizacao, $lat, $lng, $num_vitimas, $feridos_graves, $observacoes]);
        
        sendResponse(true, 'Socorro solicitado com sucesso! Redirecionando para acompanhamento.', ['id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao processar solicitação: ' . $e->getMessage());
    }
}

if ($action === 'get_active_occurrence') {
    $stmt = $pdo->prepare("
        SELECT o.*, c.nome_categoria 
        FROM ocorrencias o 
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id 
        WHERE o.utente_id = ? AND o.status NOT IN ('concluida', 'cancelada', 'rejeitada') 
        ORDER BY o.data_abertura DESC LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $occ = $stmt->fetch();
    
    if ($occ) {
        sendResponse(true, 'Ocorrência encontrada', $occ);
    } else {
        sendResponse(false, 'Nenhuma ocorrência ativa');
    }
}

if ($action === 'get_categories') {
    $stmt = $pdo->query("SELECT id, nome_categoria FROM categorias_ocorrencia");
    $categories = $stmt->fetchAll();
    sendResponse(true, 'Categorias carregadas', $categories);
}
?>
