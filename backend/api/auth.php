<?php
require_once 'core.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $senha = $data['password'] ?? '';
    $tipo = $data['login_type'] ?? 'utente';

    if ($tipo === 'base') {
        $stmt = $pdo->prepare("SELECT * FROM bases WHERE email_institucional = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'] ?? $user['nome_base'],
            'tipo' => $tipo === 'base' ? 'base' : $user['tipo_usuario']
        ];
        sendResponse(true, 'Login realizado com sucesso', ['redirect' => $_SESSION['user']['tipo']]);
    } else {
        sendResponse(false, 'Credenciais inválidas');
    }
}

if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nome = $data['nome'] ?? '';
    $email = $data['email'] ?? '';
    $telefone = $data['telefone'] ?? '';
    $senha = password_hash($data['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, tipo_usuario) VALUES (?, ?, ?, ?, 'utente')");
        $stmt->execute([$nome, $email, $telefone, $senha]);
        sendResponse(true, 'Usuário cadastrado com sucesso');
    } catch (PDOException $e) {
        sendResponse(false, 'Erro ao cadastrar: Email já existe');
    }
}

if ($action === 'get_user_data') {
    if (!isset($_SESSION['user'])) {
        sendResponse(false, 'Não autenticado');
    }
    sendResponse(true, 'Dados carregados', $_SESSION['user']);
}

if ($action === 'logout') {
    session_destroy();
    sendResponse(true, 'Sessão encerrada');
}
?>
