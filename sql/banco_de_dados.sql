-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS agricultura_familiar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE agricultura_familiar;

-- Tabela de produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    imagem_url VARCHAR(500),
    disponivel TINYINT(1) NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserção de dados de exemplo
INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, imagem_url) VALUES
('Tomate Orgânico', 'Tomate cultivado sem agrotóxicos, colhido na semana.', 5.90, 50, 'https://example.com/tomate.jpg'),
('Alface Crespa', 'Alface fresca colhida diariamente na horta familiar.', 3.50, 30, 'https://example.com/alface.jpg'),
('Cenoura', 'Cenouras ricas em vitaminas, cultivadas localmente.', 4.20, 40, 'https://example.com/cenoura.jpg'),
('Beterraba', 'Beterrabas doces e nutritivas, perfeitas para saladas.', 6.80, 25, 'https://example.com/beterraba.jpg'),
('Couve', 'Couve mineira fresca, ideal para preparo de refogados.', 2.90, 35, 'https://example.com/couve.jpg');

-- Criação de usuário específico para a aplicação (execute separadamente no MySQL)
-- CREATE USER 'usuario_seguro'@'localhost' IDENTIFIED BY 'senha_super_segura_123';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON agricultura_familiar.* TO 'usuario_seguro'@'localhost';
-- FLUSH PRIVILEGES;