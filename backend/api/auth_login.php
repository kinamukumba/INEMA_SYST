<?php
// backend/api/auth_login.php
// Endpoint que será contactado pelo Javascript da página login.html

// Headers para API Rest (CORS e formatação)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../models/User.php';

// Captura do Payload enviado (normalmente vem num raw JSON do javascript fetch)
$data = json_decode(file_get_contents("php://input"));

// Se o frontend enviar por URL-Encoded, apanhamos do $_POST em vez disso
$email = $data->email ?? $_POST['email'] ?? null;
$password = $data->password ?? $_POST['password'] ?? null;

if (!empty($email) && !empty($password)) {
    // Ligações Base
    $db = Database::getConnection();
    $userModel = new User($db);

    $userData = $userModel->login($email, $password);

    if ($userData) {
        // Poderia gerar um Token JWT aqui nas etapas avançadas do seu TCC.
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login bem sucedido. Bem-vindo, " . $userData['nome_completo'] . ".",
            "user" => $userData,
            "redirect_url" => "painel.html" // Devolvermos instrução ao frontend para onde transitar
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Credenciais inválidas ou utilizador inativo/bloqueado."]);
    }
} else {
    http_response_code(400); // 400 Bad Request
    echo json_encode(["success" => false, "message" => "Dados incompletos. Informe por favor o Email e a Senha."]);
}
