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
        $stmt_att = $pdo->prepare("INSERT INTO atendimentos (ocorrencia_id, viatura_id, equipe_id, data_despacho) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
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
if ($action === 'get_my_fleet') {
    $stmt = $pdo->prepare("SELECT * FROM viaturas WHERE base_id = ? ORDER BY placa ASC");
    $stmt->execute([$base_id]);
    sendResponse(true, 'Frota carregada', $stmt->fetchAll());
}

if ($action === 'register_vehicle') {
    $data = json_decode(file_get_contents('php://input'), true);
    $placa = sanitize($data['placa'] ?? '');
    $modelo = sanitize($data['modelo'] ?? '');
    $tipo = sanitize($data['tipo_suporte'] ?? 'basico');

    try {
        $stmt = $pdo->prepare("INSERT INTO viaturas (base_id, placa, modelo, tipo_suporte, status_vtr) VALUES (?, ?, ?, ?, 'disponivel')");
        $stmt->execute([$base_id, $placa, $modelo, $tipo]);
        sendResponse(true, 'Viatura registada com sucesso!');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao registar: ' . $e->getMessage());
    }
}

if ($action === 'update_vehicle_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $status = sanitize($data['status'] ?? 'disponivel');

    $stmt = $pdo->prepare("UPDATE viaturas SET status_vtr = ? WHERE id = ? AND base_id = ?");
    $stmt->execute([$status, $id, $base_id]);
    sendResponse(true, 'Status da viatura atualizado.');
}

if ($action === 'delete_vehicle') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM viaturas WHERE id = ? AND base_id = ?");
    $stmt->execute([$id, $base_id]);
    sendResponse(true, 'Viatura removida da frota.');
}

if ($action === 'get_my_teams') {
    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE base_id = ?");
    $stmt->execute([$base_id]);
    sendResponse(true, 'Equipas carregadas', $stmt->fetchAll());
}

if ($action === 'register_team') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nome = sanitize($data['nome_equipe'] ?? '');
    $membros = sanitize($data['descricao_membros'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO equipes (base_id, nome_equipe, descricao_membros) VALUES (?, ?, ?)");
    $stmt->execute([$base_id, $nome, $membros]);
    sendResponse(true, 'Equipa registada com sucesso!');
}

if ($action === 'delete_team') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM equipes WHERE id = ? AND base_id = ?");
    $stmt->execute([$id, $base_id]);
    sendResponse(true, 'Equipa removida.');
}

if ($action === 'get_active_atendimentos') {
    $stmt = $pdo->prepare("
        SELECT a.*, o.localizacao_texto, o.num_vitimas, o.feridos_graves, o.observacoes, o.status as status_ocorrencia,
               c.nome_categoria, u.nome as utente_nome, u.telefone as utente_tel,
               v.placa as vtr_placa, v.modelo as vtr_modelo, e.nome_equipe
        FROM atendimentos a
        JOIN ocorrencias o ON a.ocorrencia_id = o.id
        JOIN categorias_ocorrencia c ON o.categoria_id = c.id
        JOIN usuarios u ON o.utente_id = u.id
        JOIN viaturas v ON a.viatura_id = v.id
        JOIN equipes e ON a.equipe_id = e.id
        WHERE o.base_atribuida_id = ?
        ORDER BY a.data_despacho DESC
    ");
    $stmt->execute([$base_id]);
    sendResponse(true, 'Atendimentos carregados', $stmt->fetchAll());
}

if ($action === 'update_atendimento_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $atendimento_id = (int)($data['atendimento_id'] ?? 0);
    $novo_estado = sanitize($data['estado'] ?? '');
    $relatorio = sanitize($data['relatorio_final'] ?? '');

    try {
        $pdo->beginTransaction();

        // Obter dados do atendimento para saber ocorrência e viatura
        $stmt_get = $pdo->prepare("SELECT ocorrencia_id, viatura_id FROM atendimentos WHERE id = ?");
        $stmt_get->execute([$atendimento_id]);
        $atendimento = $stmt_get->fetch();

        if (!$atendimento) {
            throw new Exception("Atendimento não encontrado.");
        }

        $ocorrencia_id = $atendimento['ocorrencia_id'];
        $viatura_id = $atendimento['viatura_id'];

        if ($novo_estado === 'concluido') {
            // Atualizar atendimento para concluído
            $stmt = $pdo->prepare("UPDATE atendimentos SET estado = 'concluido', data_conclusao = CURRENT_TIMESTAMP, relatorio_final = ? WHERE id = ?");
            $stmt->execute([$relatorio, $atendimento_id]);

            // Atualizar ocorrência
            $stmt_occ = $pdo->prepare("UPDATE ocorrencias SET status = 'concluida' WHERE id = ?");
            $stmt_occ->execute([$ocorrencia_id]);

            // Liberar viatura
            $stmt_vtr = $pdo->prepare("UPDATE viaturas SET status_vtr = 'disponivel' WHERE id = ?");
            $stmt_vtr->execute([$viatura_id]);
        } 
        else if ($novo_estado === 'no_local') {
            // Registrar hora de chegada no local
            $stmt = $pdo->prepare("UPDATE atendimentos SET estado = 'no_local', data_chegada = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$atendimento_id]);

            // Ocorrência passa a "em_curso"
            $stmt_occ = $pdo->prepare("UPDATE ocorrencias SET status = 'em_curso' WHERE id = ?");
            $stmt_occ->execute([$ocorrencia_id]);
        } 
        else if ($novo_estado === 'a_caminho') {
            $stmt = $pdo->prepare("UPDATE atendimentos SET estado = 'a_caminho' WHERE id = ?");
            $stmt->execute([$atendimento_id]);

            $stmt_occ = $pdo->prepare("UPDATE ocorrencias SET status = 'despachada' WHERE id = ?");
            $stmt_occ->execute([$ocorrencia_id]);
        } 
        else {
            $stmt = $pdo->prepare("UPDATE atendimentos SET estado = 'despachado' WHERE id = ?");
            $stmt->execute([$atendimento_id]);
        }

        $pdo->commit();
        sendResponse(true, 'Estado do atendimento atualizado com sucesso.');
    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse(false, 'Erro ao atualizar atendimento: ' . $e->getMessage());
    }
}

if ($action === 'add_atendimento_recurso') {
    $data = json_decode(file_get_contents('php://input'), true);
    $atendimento_id = (int)($data['atendimento_id'] ?? 0);
    $nome_recurso = sanitize($data['nome_recurso'] ?? '');
    $quantidade = (int)($data['quantidade'] ?? 1);

    if (empty($nome_recurso) || $quantidade <= 0) {
        sendResponse(false, 'Nome do recurso e quantidade são obrigatórios.');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO recursos_atendimento (atendimento_id, nome_recurso, quantidade) VALUES (?, ?, ?)");
        $stmt->execute([$atendimento_id, $nome_recurso, $quantidade]);
        sendResponse(true, 'Recurso adicionado com sucesso!');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao adicionar recurso: ' . $e->getMessage());
    }
}

if ($action === 'get_atendimento_recursos') {
    $atendimento_id = (int)($_GET['atendimento_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("SELECT id, nome_recurso, quantidade FROM recursos_atendimento WHERE atendimento_id = ? ORDER BY id ASC");
        $stmt->execute([$atendimento_id]);
        sendResponse(true, 'Recursos do atendimento carregados', $stmt->fetchAll());
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao carregar recursos: ' . $e->getMessage());
    }
}
?>
