<?php
session_start();

function estaLogado() {
    return isset($_SESSION['id_usuario']);
}

function requerLogin() {
    if (!estaLogado()) {
        header('Location: login.php');
        exit();
    }
}

function getUsuarioAtual() {
    if (!estaLogado()) {
        return null;
    }
    
    require_once 'config/database.php';
    $database = new Conexao();
    $db = $database->getConexao();
    
    $query = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['id_usuario']);
    $stmt->execute();
    
    return $stmt->fetch();
}

function temFuncao($funcao) {
    $usuario = getUsuarioAtual();
    return $usuario && $usuario['funcao'] === $funcao;
}

function podeGerenciar() {
    $usuario = getUsuarioAtual();
    return $usuario && in_array($usuario['funcao'], ['gerente', 'admin']);
}

function fazerLogin($email, $senha) {
    require_once 'config/database.php';
    $database = new Conexao();
    $db = $database->getConexao();
    
    $query = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['funcao_usuario'] = $usuario['funcao'];
        return true;
    }
    
    return false;
}

function fazerLogout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>