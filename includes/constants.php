<?php
/**
 * QualiTrack - Sistema de Auditoria de Qualidade
 * Arquivo de constantes e configurações globais
 */

// Informações do Sistema
define('APP_NAME', 'QualiTrack');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema de Auditoria de Qualidade');

// Status de Auditoria
define('AUDITORIA_STATUS', [
    'planejado' => 'Planejado',
    'em_progresso' => 'Em Progresso', 
    'completo' => 'Completo',
    'cancelado' => 'Cancelado'
]);

// Status de Não Conformidades
define('NC_STATUS', [
    'aberto' => 'Aberto',
    'em_progresso' => 'Em Progresso',
    'resolvido' => 'Resolvido', 
    'escalonado' => 'Escalonado'
]);

// Classificações de NC
define('NC_CLASSIFICACOES', [
    'critica' => 'Crítica',
    'alta' => 'Alta',
    'media' => 'Média',
    'baixa' => 'Baixa'
]);

// Funções de Usuário
define('USER_ROLES', [
    'admin' => 'Administrador',
    'gerente' => 'Gerente',
    'auditor' => 'Auditor'
]);

// Configurações de Paginação
define('ITEMS_PER_PAGE', 15);

// Configurações de Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Mensagens do Sistema
define('MESSAGES', [
    'login_required' => 'Você precisa fazer login para acessar esta página.',
    'access_denied' => 'Você não tem permissão para acessar esta funcionalidade.',
    'item_not_found' => 'Item não encontrado.',
    'operation_success' => 'Operação realizada com sucesso!',
    'operation_error' => 'Erro ao realizar operação. Tente novamente.'
]);

// Configurações de Tempo
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIME_FORMAT', 'H:i');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função helper para traduzir status
function getStatusLabel($status, $type = 'auditoria') {
    switch($type) {
        case 'auditoria':
            return AUDITORIA_STATUS[$status] ?? ucfirst($status);
        case 'nc':
            return NC_STATUS[$status] ?? ucfirst($status);
        case 'classificacao':
            return NC_CLASSIFICACOES[$status] ?? ucfirst($status);
        case 'funcao':
            return USER_ROLES[$status] ?? ucfirst($status);
        default:
            return ucfirst($status);
    }
}

// Função para formatar data
function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

// Função para calcular dias entre datas
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

// Função para verificar se data está vencida
function isOverdue($date) {
    return strtotime($date) < strtotime('today');
}
?>
