<?php
require_once 'core.php';

$action = $_GET['action'] ?? '';

// Verificar autenticação (Só Bases podem acessar estes endpoints)
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'base') {
    sendResponse(false, 'Acesso restrito a bases operacionais.');
}

$base_id = $_SESSION['user']['id'];

if ($action === 'get_new_occurrences') {
    // Buscar ocorrências aprovadas pelo admin e destinadas a esta base, que ainda não foram despachadas
    $stmt = $pdo->prepare("
        SELECT o.*, u.nome as utente_nome, c.nome_categoria 
        FROM ocorrencias o 
        JOIN usuarios u ON o.utente_id = u.id 
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id 
        WHERE o.base_atribuida_id = ? AND o.status = 'aprovada'
        ORDER BY o.data_abertura DESC
    ");
    $stmt->execute([$base_id]);
    $occurrences = $stmt->fetchAll();
    sendResponse(true, 'Novas ocorrências carregadas', $occurrences);
}

if ($action === 'get_fleet_and_teams') {
    // Buscar viaturas e equipes disponíveis nesta base
    $viaturas = $pdo->prepare("SELECT id, placa, modelo, tipo_suporte FROM viaturas WHERE base_id = ? AND status_vtr = 'disponivel'");
    $viaturas->execute([$base_id]);
    
    $equipes = $pdo->prepare("SELECT id, nome_equipe FROM equipes WHERE base_id = ?");
    $equipes->execute([$base_id]);
    
    sendResponse(true, 'Recursos carregados', [
        'viaturas' => $viaturas->fetchAll(),
        'equipes' => $equipes->fetchAll()
    ]);
}

if ($action === 'dispatch_ambulance') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ocorrencia_id = (int)($data['ocorrencia_id'] ?? 0);
    $viatura_id = (int)($data['viatura_id'] ?? 0);
    $equipe_id = (int)($data['equipe_id'] ?? 0);

    try {
        $pdo->beginTransaction();

        // 1. Criar registro de atendimento
        $stmt_att = $pdo->prepare("INSERT INTO atendimentos (ocorrencia_id, viatura_id, equipe_id) VALUES (?, ?, ?)");
        $stmt_att->execute([$ocorrencia_id, $viatura_id, $equipe_id]);

        // 2. Atualizar status da ocorrência
        $stmt_occ = $pdo->prepare("UPDATE ocorrencias SET status = 'despachada' WHERE id = ?");
        $stmt_occ->execute([$ocorrencia_id]);

        // 3. Atualizar status da viatura
        $stmt_vtr = $pdo->prepare("UPDATE viaturas SET status_vtr = 'em_missao' WHERE id = ?");
        $stmt_vtr->execute([$viatura_id]);

        $pdo->commit();
        sendResponse(true, 'Viatura despachada com sucesso! O utente já pode rastrear.');
    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse(false, 'Erro ao despachar: ' . $e->getMessage());
    }
}
?>
