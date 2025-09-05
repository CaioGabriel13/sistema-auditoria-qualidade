-- Criação do banco de dados para Sistema de Auditoria de Qualidade
CREATE DATABASE IF NOT EXISTS qualidade;

USE qualidade;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    funcao ENUM('auditor', 'gerente', 'admin') DEFAULT 'auditor',
    departamento VARCHAR(100),
    superior_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (superior_id) REFERENCES usuarios(id)
);

-- Tabela de modelos de checklist
CREATE TABLE modelos_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo_artefato VARCHAR(100) NOT NULL,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de itens do checklist
CREATE TABLE itens_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo_id INT NOT NULL,
    questao TEXT NOT NULL,
    categoria VARCHAR(100),
    peso DECIMAL(3,2) DEFAULT 1.00,
    indice_ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modelo_id) REFERENCES modelos_checklist(id) ON DELETE CASCADE
);

-- Tabela de auditorias
CREATE TABLE auditorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    modelo_id INT NOT NULL,
    auditor_id INT NOT NULL,
    auditado_id INT,
    nome_artefato VARCHAR(255),
    versao_artefato VARCHAR(50),
    status ENUM('planejado', 'em_progresso', 'completo', 'cancelado') DEFAULT 'planejado',
    data_planejada DATE,
    data_completa DATE,
    percentual_adesao DECIMAL(5,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modelo_id) REFERENCES modelos_checklist(id),
    FOREIGN KEY (auditor_id) REFERENCES usuarios(id),
    FOREIGN KEY (auditado_id) REFERENCES usuarios(id)
);

-- Tabela de respostas da auditoria
CREATE TABLE respostas_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auditoria_id INT NOT NULL,
    item_id INT NOT NULL,
    resposta ENUM('sim', 'nao', 'na') NOT NULL,
    comentarios TEXT,
    arquivo_evidencia VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens_checklist(id)
);

-- Tabela de não-conformidades
CREATE TABLE nao_conformidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auditoria_id INT NOT NULL,
    item_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    classificacao ENUM('baixa', 'media', 'alta', 'critica') NOT NULL,
    responsavel_id INT NOT NULL,
    status ENUM('aberto', 'em_progresso', 'resolvido', 'escalonado') DEFAULT 'aberto',
    data_vencimento DATE NOT NULL,
    data_resolucao DATE,
    descricao_resolucao TEXT,
    nivel_escalonamento INT DEFAULT 0,
    escalonado_para_id INT,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_id) REFERENCES auditorias(id),
    FOREIGN KEY (item_id) REFERENCES itens_checklist(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id),
    FOREIGN KEY (escalonado_para_id) REFERENCES usuarios(id),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de histórico de escalonamento
CREATE TABLE historico_escalonamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nc_id INT NOT NULL,
    do_usuario_id INT NOT NULL,
    para_usuario_id INT NOT NULL,
    nivel_escalonamento INT NOT NULL,
    motivo TEXT,
    nova_data_vencimento DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nc_id) REFERENCES nao_conformidades(id),
    FOREIGN KEY (do_usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (para_usuario_id) REFERENCES usuarios(id)
);

-- Tabela de comunicações/notificações
CREATE TABLE comunicacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nc_id INT NOT NULL,
    tipo ENUM('email', 'notificacao', 'lembrete') NOT NULL,
    destinatario_id INT NOT NULL,
    assunto VARCHAR(255),
    mensagem TEXT NOT NULL,
    enviado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lido_em TIMESTAMP NULL,
    FOREIGN KEY (nc_id) REFERENCES nao_conformidades(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
);

-- Inserir dados iniciais (senha padrão: 'password' para todos)
INSERT INTO usuarios (nome, email, senha, funcao, departamento) VALUES
('Admin Sistema', 'admin@qualidade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Qualidade'),
('João Silva', 'joao.silva@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 'TI'),
('Maria Santos', 'maria.santos@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'auditor', 'Qualidade'),
('Pedro Costa', 'pedro.costa@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'auditor', 'Desenvolvimento');

-- Atualizar superior_id
UPDATE usuarios SET superior_id = 1 WHERE id = 2;
UPDATE usuarios SET superior_id = 2 WHERE id IN (3, 4);

-- Modelo de checklist para Plano de Projeto
INSERT INTO modelos_checklist (nome, descricao, tipo_artefato, criado_por) VALUES
('Checklist - Plano de Projeto', 'Checklist para auditoria de qualidade de Planos de Projeto', 'Plano de Projeto', 1);

-- Itens do checklist baseados no processo de qualidade
INSERT INTO itens_checklist (modelo_id, questao, categoria, peso, indice_ordem) VALUES
(1, 'O documento possui identificação clara (nome, versão, data)?', 'Identificação', 1.00, 1),
(1, 'O escopo do projeto está claramente definido?', 'Escopo', 1.00, 2),
(1, 'Os objetivos do projeto estão especificados de forma mensurável?', 'Objetivos', 1.00, 3),
(1, 'As premissas do projeto estão documentadas?', 'Premissas', 1.00, 4),
(1, 'As restrições do projeto estão identificadas?', 'Restrições', 1.00, 5),
(1, 'Os stakeholders estão identificados e seus papéis definidos?', 'Stakeholders', 1.00, 6),
(1, 'O cronograma do projeto está presente e detalhado?', 'Cronograma', 1.00, 7),
(1, 'Os marcos (milestones) estão claramente definidos?', 'Marcos', 1.00, 8),
(1, 'Os recursos necessários estão identificados?', 'Recursos', 1.00, 9),
(1, 'O orçamento do projeto está especificado?', 'Orçamento', 1.00, 10),
(1, 'Os riscos do projeto foram identificados e analisados?', 'Riscos', 1.00, 11),
(1, 'Existe plano de mitigação para os riscos identificados?', 'Riscos', 1.00, 12),
(1, 'Os critérios de qualidade estão definidos?', 'Qualidade', 1.00, 13),
(1, 'O plano de comunicação está especificado?', 'Comunicação', 1.00, 14),
(1, 'Os entregáveis do projeto estão listados e descritos?', 'Entregáveis', 1.00, 15);