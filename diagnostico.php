<?php
echo "<h2>Teste de Diagnóstico do Sistema</h2>";

// Teste 1: Verificar se arquivos existem
echo "<h3>1. Verificação de Arquivos:</h3>";
$arquivos = [
    'config/database.php',
    'includes/auth.php',
    'index.php',
    'login.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - OK<br>";
    } else {
        echo "❌ $arquivo - NÃO ENCONTRADO<br>";
    }
}

// Teste 2: Verificar função de conexão
echo "<h3>2. Teste de Conexão com Banco:</h3>";
try {
    require_once 'config/database.php';
    $db = getConexao();
    if ($db) {
        echo "✅ Conexão com MySQL - OK<br>";
        
        // Testar se banco existe
        $stmt = $db->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        echo "✅ Banco atual: " . $result['db_name'] . "<br>";
        
        // Testar se tabela usuarios existe
        $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela 'usuarios' - OK<br>";
            
            // Contar usuários
            $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            echo "✅ Total de usuários: " . $result['total'] . "<br>";
        } else {
            echo "❌ Tabela 'usuarios' - NÃO ENCONTRADA<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "<br>";
}

// Teste 3: Verificar sessão
echo "<h3>3. Teste de Sessão:</h3>";
session_start();
echo "✅ Sessão iniciada - ID: " . session_id() . "<br>";

if (isset($_SESSION['id_usuario'])) {
    echo "✅ Usuário logado - ID: " . $_SESSION['id_usuario'] . "<br>";
} else {
    echo "⚠️ Nenhum usuário logado<br>";
}

// Teste 4: Verificar auth.php
echo "<h3>4. Teste de Autenticação:</h3>";
try {
    require_once 'includes/auth.php';
    echo "✅ Arquivo auth.php carregado<br>";
    
    if (function_exists('estaLogado')) {
        echo "✅ Função estaLogado() - OK<br>";
    } else {
        echo "❌ Função estaLogado() - NÃO ENCONTRADA<br>";
    }
    
    if (function_exists('getUsuarioAtual')) {
        echo "✅ Função getUsuarioAtual() - OK<br>";
    } else {
        echo "❌ Função getUsuarioAtual() - NÃO ENCONTRADA<br>";
    }
    
    if (function_exists('autenticarUsuario')) {
        echo "✅ Função autenticarUsuario() - OK<br>";
    } else {
        echo "❌ Função autenticarUsuario() - NÃO ENCONTRADA<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar auth.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Links de Teste:</h3>";
echo "<a href='login.php'>🔐 Página de Login</a><br>";
echo "<a href='index.php'>🏠 Dashboard</a><br>";
echo "<a href='validate.php'>🔧 Validação Completa</a><br>";
?>
