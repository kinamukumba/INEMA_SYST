<?php
require_once 'core.php';

$action = $_GET['action'] ?? '';

// Verificar autenticação (Só Admins podem acessar estes endpoints)
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'admin') {
    sendResponse(false, 'Acesso restrito a administradores.');
}

if ($action === 'get_pending_occurrences') {
    $stmt = $pdo->query("
        SELECT o.*, u.nome as utente_nome, c.nome_categoria 
        FROM ocorrencias o 
        JOIN usuarios u ON o.utente_id = u.id 
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id 
        WHERE o.status = 'pendente' 
        ORDER BY o.data_abertura DESC
    ");
    $occurrences = $stmt->fetchAll();
    sendResponse(true, 'Ocorrências pendentes carregadas', $occurrences);
}

if ($action === 'approve_occurrence') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    // Lógica de Redirecionamento Automático:
    // Selecionamos a base mais próxima (por agora simplificado para a primeira base do sistema)
    $stmt_base = $pdo->query("SELECT id FROM bases LIMIT 1");
    $base = $stmt_base->fetch();
    
    if (!$base) {
        sendResponse(false, 'Nenhuma base cadastrada no sistema.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE ocorrencias SET status = 'aprovada', base_atribuida_id = ? WHERE id = ?");
        $stmt->execute([$base['id'], $id]);
        
        // Criar alerta para a base
        $stmt_alert = $pdo->prepare("INSERT INTO alertas (base_id, mensagem) VALUES (?, ?)");
        $stmt_alert->execute([$base['id'], "NOVA OCORRÊNCIA ENCAMINHADA (#$id)"]);
        
        sendResponse(true, 'Ocorrência aprovada e redirecionada para a base.');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao aprovar: ' . $e->getMessage());
    }
}

if ($action === 'get_stats') {
    $total_occ = $pdo->query("SELECT COUNT(*) FROM ocorrencias")->fetchColumn();
    $total_bases = $pdo->query("SELECT COUNT(*) FROM bases")->fetchColumn();
    $total_concluidas = $pdo->query("SELECT COUNT(*) FROM ocorrencias WHERE status = 'concluida'")->fetchColumn();
    
    sendResponse(true, 'Estatísticas carregadas', [
        'total_ocorrencias' => $total_occ,
        'total_bases' => $total_bases,
        'respostas' => $total_concluidas
    ]);
}
?>
