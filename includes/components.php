<?php
/**
 * QualiTrack - Sistema de Auditoria de Qualidade
 * Arquivo de componentes comuns para HTML
 */

require_once 'constants.php';

function renderHead($title = null) {
    if ($title === null) $title = APP_NAME;
    if (strpos($title, APP_NAME) === false) $title .= ' - ' . APP_NAME;
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">';
}

function renderHeader($usuario, $currentPage = '') {
    echo '<header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-foreground">' . APP_NAME . '</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, ' . htmlspecialchars($usuario['nome']) . '</span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>';
}

function renderNavigation($currentPage = '') {
    $pages = [
        'dashboard' => ['url' => 'index.php', 'label' => 'Dashboard'],
        'auditorias' => ['url' => 'auditorias.php', 'label' => 'Auditorias'],
        'nao-conformidades' => ['url' => 'nao-conformidades.php', 'label' => 'Não Conformidades'],
        'modelos' => ['url' => 'modelos.php', 'label' => 'Modelos'],
        'usuarios' => ['url' => 'usuarios.php', 'label' => 'Usuários']
    ];
    
    echo '<nav class="bg-sidebar border-b border-sidebar-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">';
    
    foreach ($pages as $page => $info) {
        $isActive = $currentPage === $page;
        $classes = $isActive 
            ? 'border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium'
            : 'border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium';
        
        echo '<a href="' . $info['url'] . '" class="' . $classes . '">' . $info['label'] . '</a>';
    }
    
    echo '    </div>
        </div>
    </nav>';
}

function renderAlerts($sucesso = '', $erro = '') {
    if ($sucesso) {
        echo '<div class="mb-6 bg-chart-5/10 border border-chart-5/20 text-chart-5 px-4 py-3 rounded-lg">
                ' . htmlspecialchars($sucesso) . '
              </div>';
    }
    
    if ($erro) {
        echo '<div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                ' . htmlspecialchars($erro) . '
              </div>';
    }
}

function renderBackButton($url, $label = 'Voltar') {
    echo '<a href="' . $url . '" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
            <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </a>';
}

function renderStatusBadge($status, $type = 'auditoria') {
    $classes = '';
    $text = getStatusLabel($status, $type);
    
    if ($type === 'auditoria') {
        switch($status) {
            case 'planejado': $classes = 'bg-muted text-muted-foreground'; break;
            case 'em_progresso': $classes = 'bg-chart-4/10 text-chart-4'; break;
            case 'completo': $classes = 'bg-chart-5/10 text-chart-5'; break;
            case 'cancelado': $classes = 'bg-destructive/10 text-destructive'; break;
        }
    } elseif ($type === 'nc') {
        switch($status) {
            case 'aberto': $classes = 'bg-destructive/10 text-destructive'; break;
            case 'em_progresso': $classes = 'bg-chart-4/10 text-chart-4'; break;
            case 'resolvido': $classes = 'bg-chart-5/10 text-chart-5'; break;
            case 'escalonado': $classes = 'bg-chart-3/10 text-chart-3'; break;
        }
    } elseif ($type === 'funcao') {
        switch($status) {
            case 'admin': $classes = 'bg-destructive/10 text-destructive'; break;
            case 'gerente': $classes = 'bg-chart-3/10 text-chart-3'; break;
            case 'auditor': $classes = 'bg-chart-1/10 text-chart-1'; break;
            default: $classes = 'bg-muted text-muted-foreground';
        }
    } elseif ($type === 'classificacao') {
        switch($status) {
            case 'critica': $classes = 'bg-destructive/10 text-destructive'; break;
            case 'alta': $classes = 'bg-chart-3/10 text-chart-3'; break;
            case 'media': $classes = 'bg-chart-4/10 text-chart-4'; break;
            case 'baixa': $classes = 'bg-chart-1/10 text-chart-1'; break;
        }
    }
    
    echo '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $classes . '">' . htmlspecialchars($text) . '</span>';
}

function formatDateTime($date, $format = DATETIME_FORMAT) {
    return formatDate($date, $format);
}
?>
