<?php
/**
 * Script de instalação/configuração do Sistema de Auditoria de Qualidade
 * Execute este arquivo uma vez para configurar o banco de dados
 */

// Configurações do banco de dados
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'qualidade';

try {
    // Conectar ao MySQL sem especificar banco
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado ao MySQL com sucesso!<br>";
    
    // Criar banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Banco de dados '$database' criado/verificado com sucesso!<br>";
    
    // Conectar ao banco específico
    $pdo->exec("USE `$database`");
    
    // Ler e executar o arquivo SQL
    $sqlFile = __DIR__ . '/database/qualidade.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Remover comentários e linhas vazias
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Dividir em comandos individuais
        $commands = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($commands as $command) {
            if (!empty($command)) {
                try {
                    $pdo->exec($command);
                } catch (PDOException $e) {
                    // Ignorar erros de tabela já existente
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
                    }
                }
            }
        }
        
        echo "✅ Estrutura do banco de dados criada com sucesso!<br>";
        echo "✅ Dados iniciais inseridos com sucesso!<br>";
    } else {
        echo "❌ Arquivo SQL não encontrado: $sqlFile<br>";
    }
    
    // Verificar se há usuários no banco
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        echo "<br><h3>✅ Sistema instalado com sucesso!</h3>";
        echo "<p><strong>Usuários de teste disponíveis:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Admin:</strong> admin@qualidade.com / password</li>";
        echo "<li><strong>Gerente:</strong> joao.silva@empresa.com / password</li>";
        echo "<li><strong>Auditor 1:</strong> maria.santos@empresa.com / password</li>";
        echo "<li><strong>Auditor 2:</strong> pedro.costa@empresa.com / password</li>";
        echo "</ul>";
        echo "<p><a href='index.php' style='background: #8b5cf6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Acessar Sistema</a></p>";
    } else {
        echo "❌ Erro: Nenhum usuário foi criado no banco de dados.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "<br>";
    echo "<br><strong>Instruções:</strong><br>";
    echo "1. Certifique-se que o XAMPP está rodando<br>";
    echo "2. Verifique se o MySQL está ativo<br>";
    echo "3. Confirme as credenciais do banco no arquivo config/database.php<br>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Auditoria de Qualidade</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        ul { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Sistema de Auditoria de Qualidade - Instalação</h1>
        <p>Este script configura automaticamente o banco de dados para o sistema.</p>
        <hr>
    </div>
</body>
</html>
