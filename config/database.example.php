<?php
// Arquivo de exemplo para configuração do banco de dados
// Copie este arquivo para database.php e configure suas credenciais

// Função para obter conexão com o banco
function getConexao() {
    // Configure suas credenciais aqui
    $host = "localhost";
    $db_name = "qualidade";
    $username = "root";
    $password = "";
    
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
        die("Erro de conexão: " . $e->getMessage());
    }
}
?>
