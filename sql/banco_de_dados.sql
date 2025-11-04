CREATE DATABASE IF NOT EXISTS coopagro
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE coopagro;

-- Tabela de produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    imagem_url VARCHAR(255),
    disponivel TINYINT(1) NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Cria a tabela de usuários

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    celular VARCHAR(20) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    rua VARCHAR(255) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    tipo_usuario ENUM('Produtor', 'Comerciante') NOT NULL,
    cpf_cnpj VARCHAR(18) NOT NULL UNIQUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);