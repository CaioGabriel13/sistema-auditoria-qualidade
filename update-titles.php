<?php
// Script para atualizar títulos do sistema para QualiTrack

$arquivos = [
    'auditorias.php',
    'nao-conformidades.php', 
    'modelos.php',
    'usuarios.php',
    'login.php',
    'create-audit.php',
    'create-modelo.php',
    'create-usuario.php',
    'edit-audit.php',
    'edit-modelo.php',
    'edit-usuario.php',
    'view-audit.php',
    'view-modelo.php',
    'view-usuario.php',
    'view-nc.php',
    'edit-nc.php',
    'resolve-nc.php',
    'escalate-nc.php',
    'execute-audit.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        $conteudo = file_get_contents($arquivo);
        
        // Atualizar título da página
        $conteudo = str_replace(
            'Sistema de Auditoria de Qualidade',
            'QualiTrack',
            $conteudo
        );
        
        // Atualizar títulos específicos
        $conteudo = str_replace(
            '<title>Auditorias - Sistema de Auditoria de Qualidade</title>',
            '<title>Auditorias - QualiTrack</title>',
            $conteudo
        );
        
        $conteudo = str_replace(
            '<title>Não Conformidades - Sistema de Auditoria de Qualidade</title>',
            '<title>Não Conformidades - QualiTrack</title>',
            $conteudo
        );
        
        $conteudo = str_replace(
            '<title>Modelos de Checklist - Sistema de Auditoria de Qualidade</title>',
            '<title>Modelos de Checklist - QualiTrack</title>',
            $conteudo
        );
        
        $conteudo = str_replace(
            '<title>Usuários - Sistema de Auditoria de Qualidade</title>',
            '<title>Usuários - QualiTrack</title>',
            $conteudo
        );
        
        $conteudo = str_replace(
            '<title>Login - Sistema de Auditoria de Qualidade</title>',
            '<title>Login - QualiTrack</title>',
            $conteudo
        );
        
        // Atualizar outros títulos
        $patterns = [
            '/<title>[^<]*Sistema de Auditoria de Qualidade[^<]*<\/title>/' => function($matches) {
                return str_replace('Sistema de Auditoria de Qualidade', 'QualiTrack', $matches[0]);
            }
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $conteudo = preg_replace_callback($pattern, $replacement, $conteudo);
        }
        
        file_put_contents($arquivo, $conteudo);
        echo "✓ Atualizado: $arquivo\n";
    } else {
        echo "✗ Não encontrado: $arquivo\n";
    }
}

echo "\n🎯 Atualização concluída! Todos os títulos foram alterados para QualiTrack.\n";
?>
