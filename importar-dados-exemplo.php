<?php
/**
 * QualiTrack - Sistema de Auditoria de Qualidade
 * Script para importar dados de exemplo no sistema
 */

require_once 'config/database.php';

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

try {
    $db = getConexao();
    echo "<h2>QualiTrack - Importação de Dados de Exemplo</h2>\n";
    echo "<p>Iniciando importação...</p>\n";

    // Verificar se já existem dados
    $query = "SELECT COUNT(*) as total FROM auditorias";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $auditorias_existentes = $stmt->fetch()['total'];

    $query = "SELECT COUNT(*) as total FROM nao_conformidades";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ncs_existentes = $stmt->fetch()['total'];

    if ($auditorias_existentes > 0 || $ncs_existentes > 0) {
        echo "<div style='color: orange; font-weight: bold;'>⚠️ Atenção: Já existem dados no sistema!</div>\n";
        echo "<p>Auditorias: $auditorias_existentes | Não Conformidades: $ncs_existentes</p>\n";
        echo "<p>Continuando com a importação (dados serão adicionados)...</p>\n";
    }

    // Buscar IDs de usuários existentes
    $query = "SELECT id, nome, funcao FROM usuarios ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    if (count($usuarios) < 4) {
        echo "<div style='color: red; font-weight: bold;'>❌ Erro: Sistema precisa de pelo menos 4 usuários!</div>\n";
        echo "<p>Execute primeiro o script de instalação ou crie usuários manualmente.</p>\n";
        exit;
    }

    $admin_id = $usuarios[0]['id'];
    $gerente_id = $usuarios[1]['id'];
    $auditor1_id = $usuarios[2]['id'];
    $auditor2_id = $usuarios[3]['id'];

    echo "<p>✓ Usuários encontrados: " . count($usuarios) . "</p>\n";

    // Buscar ID do modelo de checklist
    $query = "SELECT id FROM modelos_checklist ORDER BY id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $modelo = $stmt->fetch();

    if (!$modelo) {
        echo "<div style='color: red; font-weight: bold;'>❌ Erro: Nenhum modelo de checklist encontrado!</div>\n";
        echo "<p>Execute primeiro o script de instalação para criar o modelo padrão.</p>\n";
        exit;
    }

    $modelo_id = $modelo['id'];
    echo "<p>✓ Modelo de checklist encontrado (ID: $modelo_id)</p>\n";

    // 1. CRIAR AUDITORIAS DE EXEMPLO
    echo "<h3>1. Criando Auditorias de Exemplo</h3>\n";

    $auditorias_exemplo = [
        [
            'titulo' => 'Auditoria do Plano de Projeto - Sistema ERP',
            'descricao' => 'Auditoria de qualidade do documento de plano do projeto do novo sistema ERP corporativo.',
            'auditor_id' => $auditor1_id,
            'auditado_id' => $gerente_id,
            'nome_artefato' => 'Plano_Projeto_ERP_v2.1.docx',
            'versao_artefato' => '2.1',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-45 days')),
            'data_completa' => date('Y-m-d', strtotime('-38 days')),
            'percentual_adesao' => 85.50
        ],
        [
            'titulo' => 'Auditoria do Plano de Projeto - App Mobile',
            'descricao' => 'Verificação de conformidade do plano de desenvolvimento do aplicativo mobile.',
            'auditor_id' => $auditor2_id,
            'auditado_id' => $admin_id,
            'nome_artefato' => 'Plano_App_Mobile_v1.3.pdf',
            'versao_artefato' => '1.3',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-30 days')),
            'data_completa' => date('Y-m-d', strtotime('-22 days')),
            'percentual_adesao' => 92.75
        ],
        [
            'titulo' => 'Auditoria do Plano de Migração de Dados',
            'descricao' => 'Auditoria do plano de migração de dados do sistema legado para a nova plataforma.',
            'auditor_id' => $auditor1_id,
            'auditado_id' => $gerente_id,
            'nome_artefato' => 'Plano_Migracao_Dados_v1.0.docx',
            'versao_artefato' => '1.0',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-60 days')),
            'data_completa' => date('Y-m-d', strtotime('-55 days')),
            'percentual_adesao' => 78.25
        ],
        [
            'titulo' => 'Auditoria do Plano de Projeto - Portal Cliente',
            'descricao' => 'Revisão de qualidade do plano de desenvolvimento do portal do cliente.',
            'auditor_id' => $auditor2_id,
            'auditado_id' => $admin_id,
            'nome_artefato' => 'Plano_Portal_Cliente_v1.1.pdf',
            'versao_artefato' => '1.1',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-75 days')),
            'data_completa' => date('Y-m-d', strtotime('-68 days')),
            'percentual_adesao' => 88.90
        ],
        [
            'titulo' => 'Auditoria do Plano de Integração APIs',
            'descricao' => 'Auditoria de conformidade do plano de integração com APIs externas.',
            'auditor_id' => $auditor1_id,
            'auditado_id' => $gerente_id,
            'nome_artefato' => 'Plano_Integracao_APIs_v2.0.docx',
            'versao_artefato' => '2.0',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-90 days')),
            'data_completa' => date('Y-m-d', strtotime('-85 days')),
            'percentual_adesao' => 95.25
        ],
        [
            'titulo' => 'Auditoria do Plano de Testes Automatizados',
            'descricao' => 'Verificação do plano de implementação de testes automatizados.',
            'auditor_id' => $auditor2_id,
            'auditado_id' => $admin_id,
            'nome_artefato' => 'Plano_Testes_Auto_v1.4.pdf',
            'versao_artefato' => '1.4',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-105 days')),
            'data_completa' => date('Y-m-d', strtotime('-98 days')),
            'percentual_adesao' => 91.80
        ],
        [
            'titulo' => 'Auditoria do Plano de Segurança Cibernética',
            'descricao' => 'Auditoria do plano de implementação de medidas de segurança cibernética.',
            'auditor_id' => $auditor1_id,
            'auditado_id' => $gerente_id,
            'nome_artefato' => 'Plano_Seguranca_Cyber_v1.2.docx',
            'versao_artefato' => '1.2',
            'status' => 'em_progresso',
            'data_planejada' => date('Y-m-d', strtotime('-10 days')),
            'data_completa' => null,
            'percentual_adesao' => null
        ],
        [
            'titulo' => 'Auditoria do Plano de Backup e Recovery',
            'descricao' => 'Revisão do plano de backup e recuperação de desastres.',
            'auditor_id' => $auditor2_id,
            'auditado_id' => $admin_id,
            'nome_artefato' => 'Plano_Backup_Recovery_v1.0.pdf',
            'versao_artefato' => '1.0',
            'status' => 'planejado',
            'data_planejada' => date('Y-m-d', strtotime('+5 days')),
            'data_completa' => null,
            'percentual_adesao' => null
        ],
        [
            'titulo' => 'Auditoria do Plano de Projeto - Dashboard Analytics',
            'descricao' => 'Auditoria do plano de desenvolvimento do dashboard de analytics.',
            'auditor_id' => $auditor1_id,
            'auditado_id' => $gerente_id,
            'nome_artefato' => 'Plano_Dashboard_Analytics_v1.5.docx',
            'versao_artefato' => '1.5',
            'status' => 'completo',
            'data_planejada' => date('Y-m-d', strtotime('-120 days')),
            'data_completa' => date('Y-m-d', strtotime('-110 days')),
            'percentual_adesao' => 87.40
        ],
        [
            'titulo' => 'Auditoria do Plano de Capacitação DevOps',
            'descricao' => 'Verificação do plano de capacitação da equipe em práticas DevOps.',
            'auditor_id' => $auditor2_id,
            'auditado_id' => $admin_id,
            'nome_artefato' => 'Plano_Capacitacao_DevOps_v1.1.pdf',
            'versao_artefato' => '1.1',
            'status' => 'cancelado',
            'data_planejada' => date('Y-m-d', strtotime('-15 days')),
            'data_completa' => null,
            'percentual_adesao' => null
        ]
    ];

    $auditoria_ids = [];
    
    foreach ($auditorias_exemplo as $i => $auditoria) {
        $query = "INSERT INTO auditorias (titulo, descricao, modelo_id, auditor_id, auditado_id, nome_artefato, versao_artefato, status, data_planejada, data_completa, percentual_adesao, criado_em) 
                  VALUES (:titulo, :descricao, :modelo_id, :auditor_id, :auditado_id, :nome_artefato, :versao_artefato, :status, :data_planejada, :data_completa, :percentual_adesao, :criado_em)";
        
        $stmt = $db->prepare($query);
        
        $criado_em = date('Y-m-d H:i:s', strtotime($auditoria['data_planejada'] . ' -7 days'));
        
        $stmt->execute([
            ':titulo' => $auditoria['titulo'],
            ':descricao' => $auditoria['descricao'],
            ':modelo_id' => $modelo_id,
            ':auditor_id' => $auditoria['auditor_id'],
            ':auditado_id' => $auditoria['auditado_id'],
            ':nome_artefato' => $auditoria['nome_artefato'],
            ':versao_artefato' => $auditoria['versao_artefato'],
            ':status' => $auditoria['status'],
            ':data_planejada' => $auditoria['data_planejada'],
            ':data_completa' => $auditoria['data_completa'],
            ':percentual_adesao' => $auditoria['percentual_adesao'],
            ':criado_em' => $criado_em
        ]);
        
        $auditoria_ids[] = $db->lastInsertId();
        echo "<p>✓ Auditoria " . ($i + 1) . ": {$auditoria['titulo']}</p>\n";
    }

    // 2. CRIAR NÃO CONFORMIDADES DE EXEMPLO
    echo "<h3>2. Criando Não Conformidades de Exemplo</h3>\n";

    // Buscar alguns IDs de itens do checklist
    $query = "SELECT id FROM itens_checklist ORDER BY id LIMIT 15";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $itens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $ncs_exemplo = [
        [
            'auditoria_id' => $auditoria_ids[0], // ERP
            'item_id' => $itens[2],
            'titulo' => 'Objetivos não mensuráveis no plano ERP',
            'descricao' => 'Os objetivos do projeto ERP não estão especificados de forma mensurável. É necessário definir métricas claras como "reduzir tempo de processamento em 30%" ao invés de "melhorar eficiência".',
            'classificacao' => 'media',
            'responsavel_id' => $gerente_id,
            'status' => 'resolvido',
            'data_vencimento' => date('Y-m-d', strtotime('-30 days')),
            'data_resolucao' => date('Y-m-d', strtotime('-35 days')),
            'descricao_resolucao' => 'Objetivos foram reescritos com métricas quantificáveis. Definido: redução de 30% no tempo de processamento de pedidos, aumento de 25% na precisão do estoque, e diminuição de 50% nos relatórios manuais.',
            'criado_por' => $auditor1_id
        ],
        [
            'auditoria_id' => $auditoria_ids[1], // App Mobile
            'item_id' => $itens[5],
            'titulo' => 'Stakeholders não identificados adequadamente',
            'descricao' => 'A seção de stakeholders do plano do app mobile não inclui representantes da área de marketing e suporte ao cliente, que são cruciais para o sucesso do projeto.',
            'classificacao' => 'alta',
            'responsavel_id' => $admin_id,
            'status' => 'resolvido',
            'data_vencimento' => date('Y-m-d', strtotime('-15 days')),
            'data_resolucao' => date('Y-m-d', strtotime('-18 days')),
            'descricao_resolucao' => 'Stakeholders adicionados: Gerente de Marketing (João Santos), Coordenador de Suporte (Maria Silva). Definidos papéis e responsabilidades de cada um no projeto.',
            'criado_por' => $auditor2_id
        ],
        [
            'auditoria_id' => $auditoria_ids[2], // Migração de Dados
            'item_id' => $itens[10],
            'titulo' => 'Riscos de migração não analisados',
            'descricao' => 'O plano de migração de dados não inclui análise de riscos relacionados à perda de dados, inconsistências de formato, e tempo de indisponibilidade do sistema.',
            'classificacao' => 'critica',
            'responsavel_id' => $gerente_id,
            'status' => 'resolvido',
            'data_vencimento' => date('Y-m-d', strtotime('-45 days')),
            'data_resolucao' => date('Y-m-d', strtotime('-48 days')),
            'descricao_resolucao' => 'Adicionada seção completa de análise de riscos incluindo: backup completo antes da migração, testes em ambiente de homologação, plano de rollback, e janela de manutenção programada.',
            'criado_por' => $auditor1_id
        ],
        [
            'auditoria_id' => $auditoria_ids[3], // Portal Cliente
            'item_id' => $itens[7],
            'titulo' => 'Cronograma sem marcos definidos',
            'descricao' => 'O cronograma do projeto do portal do cliente apresenta atividades mas não possui marcos (milestones) claramente definidos para acompanhamento do progresso.',
            'classificacao' => 'media',
            'responsavel_id' => $admin_id,
            'status' => 'em_progresso',
            'data_vencimento' => date('Y-m-d', strtotime('+7 days')),
            'data_resolucao' => null,
            'descricao_resolucao' => null,
            'criado_por' => $auditor2_id
        ],
        [
            'auditoria_id' => $auditoria_ids[4], // Integração APIs
            'item_id' => $itens[11],
            'titulo' => 'Plano de mitigação de riscos incompleto',
            'descricao' => 'Embora os riscos tenham sido identificados, o plano de mitigação não apresenta ações concretas para os riscos de indisponibilidade das APIs externas.',
            'classificacao' => 'alta',
            'responsavel_id' => $gerente_id,
            'status' => 'escalonado',
            'data_vencimento' => date('Y-m-d', strtotime('-5 days')),
            'data_resolucao' => null,
            'descricao_resolucao' => null,
            'nivel_escalonamento' => 1,
            'escalonado_para_id' => $admin_id,
            'criado_por' => $auditor1_id
        ],
        [
            'auditoria_id' => $auditoria_ids[5], // Testes Automatizados
            'item_id' => $itens[8],
            'titulo' => 'Recursos humanos não especificados',
            'descricao' => 'O plano de testes automatizados não especifica quantos desenvolvedores serão necessários nem o perfil técnico exigido para a implementação.',
            'classificacao' => 'baixa',
            'responsavel_id' => $admin_id,
            'status' => 'aberto',
            'data_vencimento' => date('Y-m-d', strtotime('+3 days')),
            'data_resolucao' => null,
            'descricao_resolucao' => null,
            'criado_por' => $auditor2_id
        ]
    ];

    foreach ($ncs_exemplo as $i => $nc) {
        $query = "INSERT INTO nao_conformidades (auditoria_id, item_id, titulo, descricao, classificacao, responsavel_id, status, data_vencimento, data_resolucao, descricao_resolucao, nivel_escalonamento, escalonado_para_id, criado_por, criado_em) 
                  VALUES (:auditoria_id, :item_id, :titulo, :descricao, :classificacao, :responsavel_id, :status, :data_vencimento, :data_resolucao, :descricao_resolucao, :nivel_escalonamento, :escalonado_para_id, :criado_por, :criado_em)";
        
        $stmt = $db->prepare($query);
        
        $criado_em = date('Y-m-d H:i:s', strtotime($nc['data_vencimento'] . ' -3 days'));
        
        $stmt->execute([
            ':auditoria_id' => $nc['auditoria_id'],
            ':item_id' => $nc['item_id'],
            ':titulo' => $nc['titulo'],
            ':descricao' => $nc['descricao'],
            ':classificacao' => $nc['classificacao'],
            ':responsavel_id' => $nc['responsavel_id'],
            ':status' => $nc['status'],
            ':data_vencimento' => $nc['data_vencimento'],
            ':data_resolucao' => $nc['data_resolucao'],
            ':descricao_resolucao' => $nc['descricao_resolucao'],
            ':nivel_escalonamento' => $nc['nivel_escalonamento'] ?? 0,
            ':escalonado_para_id' => $nc['escalonado_para_id'] ?? null,
            ':criado_por' => $nc['criado_por'],
            ':criado_em' => $criado_em
        ]);
        
        echo "<p>✓ NC " . ($i + 1) . ": {$nc['titulo']} ({$nc['classificacao']})</p>\n";
    }

    // 3. ESTATÍSTICAS FINAIS
    echo "<h3>3. Resumo da Importação</h3>\n";
    
    // Contar totais
    $query = "SELECT COUNT(*) as total FROM auditorias";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_auditorias = $stmt->fetch()['total'];
    
    $query = "SELECT COUNT(*) as total FROM nao_conformidades";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_ncs = $stmt->fetch()['total'];
    
    $query = "SELECT status, COUNT(*) as total FROM auditorias GROUP BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $status_auditorias = $stmt->fetchAll();
    
    $query = "SELECT classificacao, COUNT(*) as total FROM nao_conformidades GROUP BY classificacao";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $class_ncs = $stmt->fetchAll();
    
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h4>📊 Estatísticas Atuais do Sistema</h4>\n";
    echo "<p><strong>Total de Auditorias:</strong> $total_auditorias</p>\n";
    echo "<p><strong>Total de Não Conformidades:</strong> $total_ncs</p>\n";
    
    echo "<p><strong>Status das Auditorias:</strong></p>\n";
    echo "<ul>\n";
    foreach ($status_auditorias as $status) {
        echo "<li>{$status['status']}: {$status['total']}</li>\n";
    }
    echo "</ul>\n";
    
    echo "<p><strong>Classificação das NCs:</strong></p>\n";
    echo "<ul>\n";
    foreach ($class_ncs as $class) {
        echo "<li>{$class['classificacao']}: {$class['total']}</li>\n";
    }
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h4>✅ Importação Concluída com Sucesso!</h4>\n";
    echo "<p><strong>Dados adicionados:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>🔍 10 Auditorias de exemplo (diferentes status e períodos)</li>\n";
    echo "<li>⚠️ 6 Não conformidades (diferentes classificações e status)</li>\n";
    echo "<li>📊 Dados distribuídos nos últimos 4 meses para gráficos realistas</li>\n";
    echo "<li>👥 Dados atribuídos aos usuários existentes no sistema</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div style='background: #cce5ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h4>🚀 Próximos Passos</h4>\n";
    echo "<p>1. <a href='index.php' style='color: #0066cc;'>Acesse o Dashboard</a> para ver as estatísticas atualizadas</p>\n";
    echo "<p>2. <a href='auditorias.php' style='color: #0066cc;'>Visualize as Auditorias</a> importadas</p>\n";
    echo "<p>3. <a href='nao-conformidades.php' style='color: #0066cc;'>Gerencie as Não Conformidades</a></p>\n";
    echo "<p>4. Explore o sistema com dados realistas!</p>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 8px;'>\n";
    echo "<h4>❌ Erro na Importação</h4>\n";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>\n";
    echo "<p>Verifique se o banco de dados está configurado corretamente.</p>\n";
    echo "</div>\n";
}
?>
