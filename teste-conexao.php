<?php
echo "<h2>Teste de Conexão com Banco de Dados</h2>";

// Teste direto de conexão PDO
echo "<h3>1. Teste Direto PDO:</h3>";
try {
    $host = 'localhost';
    $db_name = 'qualidade';
    $username = 'root';
    $password = '';
    
    echo "Host: {$host}<br>";
    echo "Database: {$db_name}<br>";
    echo "Username: {$username}<br>";
    echo "Password: " . (empty($password) ? '(vazio)' : '(definida)') . "<br><br>";
    
    $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
    echo "DSN: {$dsn}<br><br>";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ <strong>Conexão PDO direta - SUCESSO!</strong><br>";
    
    // Testar se consegue executar uma query
    $stmt = $pdo->query("SELECT 'Teste OK' as resultado");
    $result = $stmt->fetch();
    echo "✅ Query teste: " . $result['resultado'] . "<br>";
    
} catch(PDOException $e) {
    echo "❌ <strong>Erro na conexão direta:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Teste usando a função do sistema
echo "<h3>2. Teste Função getConexao():</h3>";
try {
    require_once 'config/database.php';
    $db = getConexao();
    
    if ($db) {
        echo "✅ <strong>Função getConexao() - SUCESSO!</strong><br>";
        
        // Testar uma query
        $stmt = $db->query("SELECT DATABASE() as db_atual");
        $result = $stmt->fetch();
        echo "✅ Banco atual: " . $result['db_atual'] . "<br>";
        
        // Verificar se a tabela usuarios existe
        $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela 'usuarios' encontrada<br>";
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            echo "✅ Total de usuários: " . $result['total'] . "<br>";
        } else {
            echo "⚠️ Tabela 'usuarios' não encontrada - Execute o script SQL<br>";
        }
    }
    
} catch(Exception $e) {
    echo "❌ <strong>Erro na função getConexao():</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Verificações adicionais
echo "<h3>3. Verificações do XAMPP:</h3>";

// Verificar se MySQL está rodando
$connection = @mysqli_connect('localhost', 'root', '');
if ($connection) {
    echo "✅ MySQL está rodando<br>";
    mysqli_close($connection);
} else {
    echo "❌ MySQL não está rodando ou inacessível<br>";
}

// Informações sobre extensões PHP
echo "<h3>4. Extensões PHP:</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO está disponível<br>";
} else {
    echo "❌ PDO não está disponível<br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL está disponível<br>";
} else {
    echo "❌ PDO MySQL não está disponível<br>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Sistema</a></p>";
?>
