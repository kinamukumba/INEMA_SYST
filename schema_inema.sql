-- =========================================================================
-- ESQUEMA DA BASE DE DADOS RELACIONAL INEMA
-- SGBD Recomendado: MySQL ou PostgreSQL
-- =========================================================================

-- Iniciar transação (remova BEGIN/COMMIT se usar um cliente sem suporte ou puramente MySQL MyISAM)
-- BEGIN;

-- 1. Criação do Banco de Dados Geral (Opcional, de acordo com o servidor)
-- CREATE DATABASE IF NOT EXISTS sys_inema_db;
-- USE sys_inema_db;

-- =========================================================================
-- TABELA DE BASES (POLOS DE OPERAÇÃO)
-- =========================================================================
CREATE TABLE bases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_ponto VARCHAR(150) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    capacidade_operacional INT DEFAULT 0,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- TABELA DE USUÁRIOS (FUNCIONÁRIOS/SISTEMA)
-- =========================================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_id INT, -- A que base este operador/socorrista pertence
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    num_mecanografico VARCHAR(50) UNIQUE, -- Usuário 54321
    telefone VARCHAR(30) NOT NULL,
    cargo ENUM('ADMINISTRADOR', 'OPERADOR_CENTRAL', 'PARAMEDICO', 'MOTORISTA', 'MEDICO_CHEF') DEFAULT 'PARAMEDICO',
    pontos_acumulados INT DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE SET NULL
);

-- =========================================================================
-- TABELA DE VIATURAS (FROTA EM PRONTIDÃO)
-- =========================================================================
CREATE TABLE viaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_id INT NOT NULL,
    placa VARCHAR(20) NOT NULL UNIQUE,
    tipo_ambulancia ENUM('SUPORTE_BASICO', 'SUPORTE_AVANCADO', 'RESGATE', 'APOIO_LOGISTICO') NOT NULL,
    status_viatura ENUM('DISPONIVEL', 'EM_MISSAO', 'MANUTENCAO', 'INOP') DEFAULT 'DISPONIVEL',
    
    FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
);

-- =========================================================================
-- TABELA DE CATEGORIAS DE OCORRÊNCIA
-- =========================================================================
CREATE TABLE categorias_ocorrencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo_categoria VARCHAR(100) NOT NULL, -- Ex: 'Acidente de Viação', 'Inundações de Água'
    severidade_cor VARCHAR(20) DEFAULT '#c9c9c9', -- Ex: para front-end UI
    criticidade INT DEFAULT 1 -- 1 (Baixa), 2 (Media), 3 (Alta)
);

-- =========================================================================
-- TABELA PRINCIPAL DE OCORRÊNCIAS
-- =========================================================================
CREATE TABLE ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_reporter_id INT, -- Qual usuário atendeu a chamada ou logou no painel
    categoria_id INT,
    viatura_id INT, -- Viatura despachada
    
    localizacao_exata VARCHAR(255) NOT NULL, -- 'Avenida Deolinda Rodrigues'
    municipio VARCHAR(100),
    descricao TEXT,
    
    estado ENUM('PENDENTE', 'EM_RESPOSTA', 'NO_LOCAL', 'EM_TRÂNSITO_PARA_HOSPITAL', 'FINALIZADO', 'CANCELADO') DEFAULT 'PENDENTE',
    
    data_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_finalizacao DATETIME NULL,
    
    FOREIGN KEY (usuario_reporter_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias_ocorrencia(id) ON DELETE SET NULL,
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id) ON DELETE SET NULL
);

-- =========================================================================
-- TABELA DE INTERAÇÕES E ESTATÍSTICA PÚBLICA (Website Externo)
-- =========================================================================
CREATE TABLE interacoes_publicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    tipo_interacao ENUM('COMENTARIO', 'CURTIDA') NOT NULL,
    comentario TEXT NULL,
    data_interacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE
);

-- =========================================================================
-- DADOS MOCKUP DE TESTE INICIAIS (SEEDING)
-- =========================================================================
INSERT INTO bases (nome_ponto, endereco, municipio, capacidade_operacional) VALUES 
('Base INEMA Camaçari', 'Praça da mutamba Camaçari', 'Camaçari', 5),
('Base INEMA Viana', 'Via Expressa, Km 14', 'Viana', 3);

INSERT INTO categorias_ocorrencia (titulo_categoria, criticidade) VALUES 
('Acidente de Viação', 3),
('Afogamento', 3),
('Inundações de Água', 2),
('Intoxicação', 2),
('Insuficiência Cardíaca', 3);

INSERT INTO usuarios (base_id, nome_completo, email, senha_hash, num_mecanografico, telefone, cargo, pontos_acumulados) VALUES 
(1, 'Moisés Kialanda', 'moiseskialanda09@gmail.com', 'h4sh_s3gur0', '54321', '+(244) 931 917 997', 'ADMINISTRADOR', 432);

-- COMMIT;
