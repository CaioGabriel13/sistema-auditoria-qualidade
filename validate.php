<?php
/**
 * Validador do Sistema de Auditoria de Qualidade
 * Execute este arquivo para verificar se tudo está funcionando corretamente
 */

$erros = [];
$avisos = [];
$sucessos = [];

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Validação - Sistema de Auditoria de Qualidade</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; margin: 5px 0; }
        .error { color: #dc3545; margin: 5px 0; }
        .warning { color: #ffc107; margin: 5px 0; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        h1, h2 { color: #333; }
        .status { padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .ok { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        ul { padding-left: 20px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Validação do Sistema de Auditoria de Qualidade</h1>";

// 1. Verificar arquivos essenciais
echo "<div class='section'><h2>📁 Verificação de Arquivos</h2>";
$arquivos_essenciais = [
    'index.php' => 'Página principal',
    'login.php' => 'Página de login',
    'logout.php' => 'Sistema de logout',
    'config/database.php' => 'Configuração do banco',
    'includes/auth.php' => 'Sistema de autenticação',
    'assets/css/style.css' => 'Arquivo de estilos',
    'database/qualidade.sql' => 'Estrutura do banco'
];

foreach ($arquivos_essenciais as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "<div class='success'>✅ $descricao ($arquivo) - OK</div>";
        $sucessos[] = $arquivo;
    } else {
        echo "<div class='error'>❌ $descricao ($arquivo) - NÃO ENCONTRADO</div>";
        $erros[] = "Arquivo $arquivo não encontrado";
    }
}
echo "</div>";

// 2. Verificar configurações PHP
echo "<div class='section'><h2>🔧 Configurações PHP</h2>";

// Versão PHP
$versao_php = phpversion();
if (version_compare($versao_php, '7.4.0', '>=')) {
    echo "<div class='success'>✅ PHP $versao_php - OK</div>";
} else {
    echo "<div class='error'>❌ PHP $versao_php - Requer 7.4+</div>";
    $erros[] = "Versão PHP muito antiga";
}

// Extensões necessárias
$extensoes = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ Extensão $ext - OK</div>";
    } else {
        echo "<div class='error'>❌ Extensão $ext - NÃO CARREGADA</div>";
        $erros[] = "Extensão $ext não está carregada";
    }
}
echo "</div>";

// 3. Verificar conexão com banco
echo "<div class='section'><h2>🗄️ Conexão com Banco de Dados</h2>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $db = getConexao();
        
        if ($db) {
            echo "<div class='success'>✅ Conexão com MySQL - OK</div>";
            
            // Verificar se banco existe
            $stmt = $db->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch();
            if ($result['db_name'] === 'qualidade') {
                echo "<div class='success'>✅ Banco 'qualidade' selecionado - OK</div>";
                
                // Verificar tabelas
                $tabelas = ['usuarios', 'modelos_checklist', 'itens_checklist', 'auditorias', 'respostas_auditoria', 'nao_conformidades'];
                foreach ($tabelas as $tabela) {
                    try {
                        $stmt = $db->query("SELECT COUNT(*) FROM $tabela");
                        $count = $stmt->fetchColumn();
                        echo "<div class='success'>✅ Tabela '$tabela' - $count registros</div>";
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Tabela '$tabela' - NÃO ENCONTRADA</div>";
                        $erros[] = "Tabela $tabela não existe";
                    }
                }
                
            } else {
                echo "<div class='error'>❌ Banco 'qualidade' não encontrado</div>";
                $erros[] = "Banco de dados 'qualidade' não foi criado";
            }
        } else {
            echo "<div class='error'>❌ Não foi possível conectar ao MySQL</div>";
            $erros[] = "Falha na conexão com MySQL";
        }
    } else {
        echo "<div class='error'>❌ Arquivo de configuração não encontrado</div>";
        $erros[] = "config/database.php não existe";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
    $erros[] = "Erro de conexão: " . $e->getMessage();
}
echo "</div>";

// 4. Verificar permissões
echo "<div class='section'><h2>🔐 Permissões de Arquivos</h2>";
$diretorios = ['.', 'assets', 'config', 'includes', 'database'];
foreach ($diretorios as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "<div class='success'>✅ Diretório '$dir' - Leitura OK</div>";
        } else {
            echo "<div class='error'>❌ Diretório '$dir' - SEM PERMISSÃO DE LEITURA</div>";
            $erros[] = "Sem permissão de leitura em $dir";
        }
    }
}
echo "</div>";

// 5. Resumo final
echo "<div class='section'><h2>📊 Resumo da Validação</h2>";

$total_testes = count($sucessos) + count($erros) + count($avisos);
$taxa_sucesso = count($sucessos) / $total_testes * 100;

echo "<div style='font-size: 18px; margin: 20px 0;'>";
echo "<div>✅ Sucessos: " . count($sucessos) . "</div>";
echo "<div>❌ Erros: " . count($erros) . "</div>";
echo "<div>⚠️ Avisos: " . count($avisos) . "</div>";
echo "<div>📈 Taxa de Sucesso: " . number_format($taxa_sucesso, 1) . "%</div>";
echo "</div>";

if (empty($erros)) {
    echo "<div class='status ok'>🎉 SISTEMA PRONTO PARA USO!</div>";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Acessar Sistema</a></p>";
    echo "<div style='margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 5px;'>";
    echo "<h3>👥 Usuários de Teste:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@qualidade.com / password</li>";
    echo "<li><strong>Gerente:</strong> joao.silva@empresa.com / password</li>";
    echo "<li><strong>Auditor:</strong> maria.santos@empresa.com / password</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='status fail'>❌ PROBLEMAS ENCONTRADOS</div>";
    echo "<h3>Erros que precisam ser corrigidos:</h3>";
    echo "<ul>";
    foreach ($erros as $erro) {
        echo "<li class='error'>$erro</li>";
    }
    echo "</ul>";
    echo "<p><strong>Soluções:</strong></p>";
    echo "<ul>";
    echo "<li>Execute o <a href='install.php'>script de instalação</a></li>";
    echo "<li>Verifique se o XAMPP está rodando (Apache + MySQL)</li>";
    echo "<li>Confirme se todos os arquivos foram copiados corretamente</li>";
    echo "</ul>";
}

echo "</div>";

echo "</div></body></html>";
?>
