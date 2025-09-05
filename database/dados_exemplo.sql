-- Script para inserir dados de exemplo para testar os gráficos
-- Execute este script após criar o banco de dados principal

USE qualidade;

-- Inserir algumas auditorias de exemplo com dados dos últimos meses
INSERT INTO auditorias (titulo, descricao, modelo_id, auditor_id, auditado_id, nome_artefato, versao_artefato, status, data_planejada, data_completa, percentual_adesao, criado_em) VALUES
('Auditoria Plano de Projeto - Sistema ERP', 'Auditoria de qualidade do plano de projeto do novo sistema ERP', 1, 3, 4, 'Plano_Projeto_ERP_v1.2.pdf', '1.2', 'completo', '2025-01-15', '2025-01-20', 87.5, '2025-01-15 10:00:00'),
('Auditoria Plano de Projeto - App Mobile', 'Auditoria do plano de projeto da aplicação mobile', 1, 3, 2, 'Plano_App_Mobile_v2.1.pdf', '2.1', 'completo', '2025-02-10', '2025-02-15', 92.3, '2025-02-10 14:30:00'),
('Auditoria Plano de Projeto - Portal Web', 'Auditoria do plano de projeto do portal web institucional', 1, 4, 2, 'Plano_Portal_Web_v1.0.pdf', '1.0', 'completo', '2025-03-05', '2025-03-10', 78.9, '2025-03-05 09:15:00'),
('Auditoria Plano de Projeto - API REST', 'Auditoria do plano de projeto da nova API REST', 1, 3, 4, 'Plano_API_REST_v1.5.pdf', '1.5', 'completo', '2025-04-12', '2025-04-18', 94.7, '2025-04-12 11:20:00'),
('Auditoria Plano de Projeto - Dashboard BI', 'Auditoria do plano de projeto do dashboard de Business Intelligence', 1, 4, 2, 'Plano_Dashboard_BI_v2.0.pdf', '2.0', 'completo', '2025-05-08', '2025-05-14', 85.2, '2025-05-08 16:45:00'),
('Auditoria Plano de Projeto - Integração', 'Auditoria do plano de projeto de integração de sistemas', 1, 3, 4, 'Plano_Integracao_v1.8.pdf', '1.8', 'completo', '2025-06-20', '2025-06-25', 91.6, '2025-06-20 13:10:00'),
('Auditoria Plano de Projeto - Migração DB', 'Auditoria do plano de projeto de migração de banco de dados', 1, 4, 2, 'Plano_Migracao_DB_v1.3.pdf', '1.3', 'completo', '2025-07-15', '2025-07-22', 88.4, '2025-07-15 08:30:00'),
('Auditoria Plano de Projeto - Security', 'Auditoria do plano de projeto de implementação de segurança', 1, 3, 4, 'Plano_Security_v2.2.pdf', '2.2', 'completo', '2025-08-10', '2025-08-16', 96.1, '2025-08-10 10:50:00'),
('Auditoria Plano de Projeto - DevOps', 'Auditoria do plano de projeto de implementação DevOps', 1, 4, 2, 'Plano_DevOps_v1.7.pdf', '1.7', 'em_progresso', '2025-09-01', NULL, NULL, '2025-09-01 14:20:00'),
('Auditoria Plano de Projeto - Cloud', 'Auditoria do plano de projeto de migração para cloud', 1, 3, 4, 'Plano_Cloud_v1.1.pdf', '1.1', 'planejado', '2025-09-15', NULL, NULL, '2025-09-15 12:00:00');

-- Inserir algumas não-conformidades de exemplo
INSERT INTO nao_conformidades (auditoria_id, item_id, titulo, descricao, classificacao, responsavel_id, status, data_vencimento, criado_por, criado_em) VALUES
(1, 2, 'Escopo mal definido', 'O escopo do projeto não está claramente definido no documento', 'media', 4, 'resolvido', '2025-02-01', 3, '2025-01-20 15:30:00'),
(2, 8, 'Marcos não especificados', 'Os marcos do projeto não estão adequadamente especificados', 'baixa', 2, 'resolvido', '2025-03-01', 3, '2025-02-15 11:45:00'),
(3, 11, 'Análise de riscos incompleta', 'A análise de riscos do projeto está incompleta', 'alta', 2, 'em_progresso', '2025-04-01', 4, '2025-03-10 16:20:00'),
(4, 5, 'Restrições não identificadas', 'As restrições do projeto não foram adequadamente identificadas', 'baixa', 4, 'resolvido', '2025-05-01', 3, '2025-04-18 09:10:00'),
(5, 14, 'Plano de comunicação vago', 'O plano de comunicação não está suficientemente detalhado', 'media', 2, 'aberto', '2025-06-15', 4, '2025-05-14 13:25:00'),
(6, 10, 'Orçamento não especificado', 'O orçamento do projeto não está claramente especificado', 'critica', 2, 'aberto', '2025-07-10', 3, '2025-06-25 14:40:00');

-- Atualizar algumas auditorias com data de resolução para NCs resolvidas
UPDATE nao_conformidades SET data_resolucao = '2025-01-28', descricao_resolucao = 'Escopo redefinido e aprovado pela equipe' WHERE id = 1;
UPDATE nao_conformidades SET data_resolucao = '2025-02-20', descricao_resolucao = 'Marcos revisados e documentados adequadamente' WHERE id = 2;
UPDATE nao_conformidades SET data_resolucao = '2025-04-25', descricao_resolucao = 'Restrições identificadas e documentadas' WHERE id = 4;
