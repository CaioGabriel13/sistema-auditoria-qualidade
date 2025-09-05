<?php
// Arquivo de teste para verificar funcionamento das páginas criadas
require_once 'includes/auth.php';

$testes = [
    'create-modelo.php' => 'Criação de Modelo de Checklist',
    'create-usuario.php' => 'Criação de Usuário',
    'modelos.php' => 'Lista de Modelos',
    'usuarios.php' => 'Lista de Usuários'
];

echo "<h1>Teste de Funcionalidade</h1>";
echo "<ul>";

foreach ($testes as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "<li><a href='$arquivo' target='_blank'>✅ $descricao</a></li>";
    } else {
        echo "<li>❌ $descricao - Arquivo não encontrado</li>";
    }
}

echo "</ul>";
echo "<br><p><strong>Credenciais de teste:</strong></p>";
echo "<ul>";
echo "<li>Admin: admin@qualidade.com / password</li>";
echo "<li>Gerente: joao.silva@empresa.com / password</li>";
echo "<li>Auditor: maria.santos@empresa.com / password</li>";
echo "</ul>";
?>
