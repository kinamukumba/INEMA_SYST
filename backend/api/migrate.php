<?php
require_once 'core.php';

// Apenas permitir execução via CLI ou se explicitamente acessado localmente
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("Acesso não autorizado.");
}

try {
    echo "Iniciando migração do banco de dados...\n";

    // 1. Adicionar tipo_base à tabela bases se não existir
    $stmt = $pdo->query("SHOW COLUMNS FROM bases LIKE 'tipo_base'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE bases ADD COLUMN tipo_base ENUM('operacional', 'hospitalar') DEFAULT 'operacional'");
        echo "- Coluna 'tipo_base' adicionada à tabela 'bases'.\n";
    } else {
        echo "- Coluna 'tipo_base' já existe na tabela 'bases'.\n";
    }

    // 2. Adicionar estado e data_chegada à tabela atendimentos se não existirem
    $stmt = $pdo->query("SHOW COLUMNS FROM atendimentos LIKE 'estado'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE atendimentos ADD COLUMN estado ENUM('despachado', 'a_caminho', 'no_local', 'concluido') DEFAULT 'despachado'");
        echo "- Coluna 'estado' adicionada à tabela 'atendimentos'.\n";
    } else {
        echo "- Coluna 'estado' já existe na tabela 'atendimentos'.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM atendimentos LIKE 'data_chegada'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE atendimentos ADD COLUMN data_chegada TIMESTAMP NULL DEFAULT NULL");
        echo "- Coluna 'data_chegada' adicionada à tabela 'atendimentos'.\n";
    } else {
        echo "- Coluna 'data_chegada' já existe na tabela 'atendimentos'.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM atendimentos LIKE 'data_despacho'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE atendimentos ADD COLUMN data_despacho TIMESTAMP NULL DEFAULT NULL");
        echo "- Coluna 'data_despacho' adicionada à tabela 'atendimentos'.\n";
    } else {
        echo "- Coluna 'data_despacho' já existe na tabela 'atendimentos'.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM atendimentos LIKE 'data_conclusao'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE atendimentos ADD COLUMN data_conclusao TIMESTAMP NULL DEFAULT NULL");
        echo "- Coluna 'data_conclusao' adicionada à tabela 'atendimentos'.\n";
    } else {
        echo "- Coluna 'data_conclusao' já existe na tabela 'atendimentos'.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM atendimentos LIKE 'relatorio_final'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE atendimentos ADD COLUMN relatorio_final TEXT NULL DEFAULT NULL");
        echo "- Coluna 'relatorio_final' adicionada à tabela 'atendimentos'.\n";
    } else {
        echo "- Coluna 'relatorio_final' já existe na tabela 'atendimentos'.\n";
    }


    // 3. Criar tabela de recursos_atendimento
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recursos_atendimento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            atendimento_id INT NOT NULL,
            nome_recurso VARCHAR(100) NOT NULL,
            quantidade INT NOT NULL,
            FOREIGN KEY (atendimento_id) REFERENCES atendimentos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "- Tabela 'recursos_atendimento' criada ou já existente.\n";

    // 4. Limpar e Reinserir Categorias de Ocorrência (Necessita limpar chaves estrangeiras primeiro)
    echo "Limpando e redefinindo categorias de ocorrência...\n";
    
    // Desativar restrições de chave estrangeira temporariamente para permitir limpar as tabelas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE atendimentos;");
    $pdo->exec("TRUNCATE TABLE ocorrencias;");
    $pdo->exec("TRUNCATE TABLE categorias_ocorrencia;");
    
    // Inserir as novas categorias exatamente conforme o solicitado
    $categorias = [
        ['Acidente', 'alta'],
        ['Queda grave', 'media'],
        ['Parto inesperado', 'alta'],
        ['Afogamento', 'critica'],
        ['Incêndio', 'alta'],
        ['Desastre natural', 'critica'],
        ['Picada de animal venenoso', 'alta'],
        ['Paragem cardíaca', 'critica']
    ];

    $stmtInsert = $pdo->prepare("INSERT INTO categorias_ocorrencia (nome_categoria, prioridade) VALUES (?, ?)");
    foreach ($categorias as $cat) {
        $stmtInsert->execute($cat);
        echo "  - Categoria inserida: {$cat[0]} ({$cat[1]})\n";
    }

    // Reativar restrições
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO DURANTE A MIGRAÇÃO: " . $e->getMessage() . "\n";
}
?>
