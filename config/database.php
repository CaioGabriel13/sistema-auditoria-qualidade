<?php
// Configurações do banco de dados
$host = 'localhost';
$db_name = 'qualidade';
$username = 'root';
$password = '';

// Função para obter conexão com o banco
function getConexao() {
    // Definir as configurações diretamente na função
    $host = 'localhost';
    $db_name = 'qualidade';
    $username = 'root';
    $password = '';
    
    try {
        $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        die("Erro de conexão: " . $e->getMessage() . "<br>DSN: mysql:host={$host};dbname={$db_name}<br>User: {$username}");
    }
}
?>