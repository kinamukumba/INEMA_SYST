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
    $base_id = (int)($data['base_id'] ?? 0);
    
    if (!$base_id) {
        sendResponse(false, 'Nenhuma base selecionada.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE ocorrencias SET status = 'aprovada', base_atribuida_id = ? WHERE id = ?");
        $stmt->execute([$base_id, $id]);
        
        // Criar alerta para a base
        $stmt_alert = $pdo->prepare("INSERT INTO alertas (base_id, mensagem) VALUES (?, ?)");
        $stmt_alert->execute([$base_id, "NOVA OCORRÊNCIA ENCAMINHADA (#$id)"]);
        
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
if ($action === 'get_all_occurrences') {
    $stmt = $pdo->query("
        SELECT o.*, u.nome as utente_nome, u.telefone as utente_tel, c.nome_categoria, b.nome_base 
        FROM ocorrencias o 
        JOIN usuarios u ON o.utente_id = u.id 
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id 
        LEFT JOIN bases b ON o.base_atribuida_id = b.id
        ORDER BY o.data_abertura DESC
    ");
    $occurrences = $stmt->fetchAll();
    sendResponse(true, 'Todas as ocorrências carregadas', $occurrences);
}

if ($action === 'reject_occurrence') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("UPDATE ocorrencias SET status = 'rejeitada' WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(true, 'Ocorrência rejeitada com sucesso.');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao rejeitar: ' . $e->getMessage());
    }
}
if ($action === 'get_bases') {
    $stmt = $pdo->query("
        SELECT b.*, 
        (SELECT COUNT(*) FROM ocorrencias WHERE base_atribuida_id = b.id) as total_processadas,
        (SELECT COUNT(*) FROM viaturas WHERE base_id = b.id) as total_viaturas
        FROM bases b
        ORDER BY b.nome_base ASC
    ");
    $bases = $stmt->fetchAll();
    sendResponse(true, 'Bases carregadas', $bases);
}
if ($action === 'register_base') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nome = $data['nome_base'] ?? '';
    $email = $data['email_institucional'] ?? '';
    $municipio = $data['municipio'] ?? '';
    $endereco = $data['endereco'] ?? 'Não especificado';
    $capacidade = (int)($data['capacidade'] ?? 0);
    $tipo_base = $data['tipo_base'] ?? 'operacional';
    $password = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
    
    if (empty($nome) || empty($email)) {
        sendResponse(false, 'Nome e Email são obrigatórios.');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bases (nome_base, email_institucional, municipio, endereco, capacidade, tipo_base, senha) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $municipio, $endereco, $capacidade, $tipo_base, $password]);
        sendResponse(true, 'Base registada com sucesso!');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            sendResponse(false, 'Este email já está registado em outra base.');
        } else {
            sendResponse(false, 'Erro ao registar base: ' . $e->getMessage());
        }
    }
}
if ($action === 'update_base') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $nome = $data['nome_base'] ?? '';
    $email = $data['email_institucional'] ?? '';
    $municipio = $data['municipio'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $capacidade = (int)($data['capacidade'] ?? 0);
    $tipo_base = $data['tipo_base'] ?? 'operacional';

    try {
        $stmt = $pdo->prepare("UPDATE bases SET nome_base = ?, email_institucional = ?, municipio = ?, endereco = ?, capacidade = ?, tipo_base = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $municipio, $endereco, $capacidade, $tipo_base, $id]);
        sendResponse(true, 'Base atualizada com sucesso!');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao atualizar base: ' . $e->getMessage());
    }
}

if ($action === 'delete_base') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);

    try {
        // Verificar se existem ocorrências ou viaturas vinculadas
        $check = $pdo->prepare("SELECT COUNT(*) FROM ocorrencias WHERE base_atribuida_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            sendResponse(false, 'Não é possível eliminar a base pois existem ocorrências vinculadas a ela.');
        }

        $stmt = $pdo->prepare("DELETE FROM bases WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(true, 'Base eliminada com sucesso!');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao eliminar base: ' . $e->getMessage());
    }
}
if ($action === 'get_all_vehicles') {
    $stmt = $pdo->query("
        SELECT v.*, b.nome_base 
        FROM viaturas v 
        JOIN bases b ON v.base_id = b.id 
        ORDER BY b.nome_base ASC
    ");
    $vehicles = $stmt->fetchAll();
    sendResponse(true, 'Todas as viaturas carregadas', $vehicles);
}
?>
