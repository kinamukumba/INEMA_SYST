-- Base de Dados INEMA
CREATE DATABASE IF NOT EXISTS inema_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inema_db;

-- Tabela de Usuários (Utentes e Admins)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('utente', 'admin') DEFAULT 'utente',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Bases Operacionais
CREATE TABLE IF NOT EXISTS bases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_base VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    endereco TEXT NOT NULL,
    capacidade INT NOT NULL,
    email_institucional VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categorias de Ocorrência
CREATE TABLE IF NOT EXISTS categorias_ocorrencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(100) NOT NULL,
    prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media'
);

-- Ocorrências
CREATE TABLE IF NOT EXISTS ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    categoria_id INT NOT NULL,
    localizacao_texto TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    num_vitimas INT DEFAULT 1,
    feridos_graves BOOLEAN DEFAULT FALSE,
    observacoes TEXT,
    status ENUM('pendente', 'aprovada', 'rejeitada', 'despachada', 'em_curso', 'concluida', 'cancelada') DEFAULT 'pendente',
    base_atribuida_id INT NULL,
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES usuarios(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias_ocorrencia(id),
    FOREIGN KEY (base_atribuida_id) REFERENCES bases(id)
);

-- Viaturas
CREATE TABLE IF NOT EXISTS viaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_id INT NOT NULL,
    placa VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(50) NOT NULL,
    tipo_suporte ENUM('basico', 'medio', 'avancado') DEFAULT 'basico',
    status EN_ATENDIMENTO BOOLEAN DEFAULT FALSE,
    status_vtr ENUM('disponivel', 'em_missao', 'manutencao') DEFAULT 'disponivel',
    FOREIGN KEY (base_id) REFERENCES bases(id)
);

-- Equipes
CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_id INT NOT NULL,
    nome_equipe VARCHAR(50) NOT NULL,
    descricao_membros TEXT,
    FOREIGN KEY (base_id) REFERENCES bases(id)
);

-- Atendimentos (Vínculo final entre Ocorrência, Viatura e Equipe)
CREATE TABLE IF NOT EXISTS atendimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    viatura_id INT NOT NULL,
    equipe_id INT NOT NULL,
    data_despacho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao TIMESTAMP NULL,
    relatorio_final TEXT,
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id),
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id),
    FOREIGN KEY (equipe_id) REFERENCES equipes(id)
);

-- Alertas/Mensagens
CREATE TABLE IF NOT EXISTS alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    base_id INT,
    mensagem TEXT NOT NULL,
    lido BOOLEAN DEFAULT FALSE,
    data_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir Dados Iniciais (Admin e Categorias)
INSERT INTO usuarios (nome, email, telefone, senha, tipo_usuario) 
VALUES ('Administrador Central', 'admin@inema.ao', '900000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO categorias_ocorrencia (nome_categoria, prioridade) VALUES 
('Acidente de Transito', 'alta'),
('Queda Grave', 'media'),
('Mal-estar / Doença Súbita', 'alta'),
('Afogamento', 'critica'),
('Agressão Física', 'media'),
('Dores no Peito / Coração', 'critica');
