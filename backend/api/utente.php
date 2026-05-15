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
        SELECT o.*, c.nome_categoria, b.nome_base, b.municipio as base_municipio,
               v.placa as vtr_placa, v.modelo as vtr_modelo
        FROM ocorrencias o 
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id 
        LEFT JOIN bases b ON o.base_atribuida_id = b.id
        LEFT JOIN atendimentos a ON a.ocorrencia_id = o.id
        LEFT JOIN viaturas v ON a.viatura_id = v.id
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
if ($action === 'get_profile_stats') {
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM ocorrencias WHERE utente_id = ?");
    $stmt1->execute([$user_id]);
    $total_occ = $stmt1->fetchColumn();

    $stmt2 = $pdo->prepare("
        SELECT o.id, c.nome_categoria, o.data_abertura, o.localizacao_texto, o.status
        FROM ocorrencias o
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id
        WHERE o.utente_id = ?
        ORDER BY o.data_abertura DESC LIMIT 10
    ");
    $stmt2->execute([$user_id]);
    $history = $stmt2->fetchAll();

    $points = $total_occ * 50 + 100;

    sendResponse(true, 'Estatísticas carregadas', [
        'total_ocorrencias' => $total_occ,
        'pontos' => $points,
        'historico' => $history
    ]);
}
?>
