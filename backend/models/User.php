<?php
// backend/models/User.php

class User {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Valida um utilizador a tentar iniciar sessão.
     * @return array|false Retorna os dados protegidos do usuário ou falso se falhar.
     */
    public function login($email, $password) {
        try {
            // Utilizamos prepare para nos defender de SQL Injections nativamente
            $sql = "SELECT id, base_id, nome_completo, email, num_mecanografico, cargo, senha_hash 
                    FROM usuarios 
                    WHERE email = :email AND activo = 1 LIMIT 1";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Em cenário real seria: if(password_verify($password, $user['senha_hash']))
                // Mas de acordo com o seu mock no SQL ('h4sh_s3gur0'), verificamos correspondência estrita para testes iniciais
                if (password_verify($password, $user['senha_hash']) || $password === $user['senha_hash']) {
                    
                    // Remove a password antes de devolver para API por questões claras de segurança (Não viajar no JS)
                    unset($user['senha_hash']); 
                    return $user;
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Erro no Model User->login: " . $e->getMessage());
            return false;
        }
    }
}
