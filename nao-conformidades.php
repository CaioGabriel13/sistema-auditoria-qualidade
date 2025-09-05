<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();

require_once 'config/database.php';
$database = new Conexao();
$db = $database->getConexao();

// Filtros
$status_filtro = $_GET['status'] ?? '';
$classificacao_filtro = $_GET['classificacao'] ?? '';
$responsavel_filtro = $_GET['responsavel'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($status_filtro) {
    $where_conditions[] = "nc.status = :status";
    $params[':status'] = $status_filtro;
}

if ($classificacao_filtro) {
    $where_conditions[] = "nc.classificacao = :classificacao";
    $params[':classificacao'] = $classificacao_filtro;
}

if ($responsavel_filtro) {
    $where_conditions[] = "nc.responsavel_id = :responsavel_id";
    $params[':responsavel_id'] = $responsavel_filtro;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar não conformidades
$query = "SELECT nc.*, a.titulo as titulo_auditoria, u1.nome as nome_responsavel, u2.nome as nome_criador
          FROM nao_conformidades nc 
          LEFT JOIN auditorias a ON nc.auditoria_id = a.id
          LEFT JOIN usuarios u1 ON nc.responsavel_id = u1.id 
          LEFT JOIN usuarios u2 ON nc.criado_por = u2.id 
          $where_clause
          ORDER BY 
            CASE nc.classificacao 
                WHEN 'critica' THEN 1 
                WHEN 'alta' THEN 2 
                WHEN 'media' THEN 3 
                WHEN 'baixa' THEN 4 
            END,
            nc.data_vencimento ASC";

$stmt = $db->prepare($query);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$nao_conformidades = $stmt->fetchAll();

// Buscar usuários para filtro
$query = "SELECT id, nome FROM usuarios ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Estatísticas
$query = "SELECT status, COUNT(*) as total FROM nao_conformidades GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_status = [];
foreach ($stmt->fetchAll() as $row) {
    $stats_status[$row['status']] = $row['total'];
}

$query = "SELECT classificacao, COUNT(*) as total FROM nao_conformidades WHERE status IN ('aberto', 'em_progresso') GROUP BY classificacao";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_classificacao = [];
foreach ($stmt->fetchAll() as $row) {
    $stats_classificacao[$row['classificacao']] = $row['total'];
}

// NCs vencidas
$query = "SELECT COUNT(*) as total FROM nao_conformidades WHERE status IN ('aberto', 'em_progresso') AND data_vencimento < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$ncs_vencidas = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Não Conformidades - Sistema de Auditoria de Qualidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-foreground">Sistema de Auditoria de Qualidade</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-sidebar border-b border-sidebar-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="index.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Dashboard</a>
                <a href="auditorias.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Auditorias</a>
                <a href="nao-conformidades.php" class="border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium">Não Conformidades</a>
                <?php if (podeGerenciar()): ?>
                <a href="modelos.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Modelos</a>
                <a href="usuarios.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Usuários</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-destructive rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Abertas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_status['aberto'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-4 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Em Progresso</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_status['em_progresso'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-3 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Críticas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_classificacao['critica'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-destructive rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Vencidas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $ncs_vencidas; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <form method="GET" class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                    <div>
                        <select name="status" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="">Todos os Status</option>
                            <option value="aberto" <?php echo $status_filtro === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                            <option value="em_progresso" <?php echo $status_filtro === 'em_progresso' ? 'selected' : ''; ?>>Em Progresso</option>
                            <option value="resolvido" <?php echo $status_filtro === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                            <option value="escalonado" <?php echo $status_filtro === 'escalonado' ? 'selected' : ''; ?>>Escalonado</option>
                        </select>
                    </div>
                    
                    <div>
                        <select name="classificacao" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="">Todas as Classificações</option>
                            <option value="critica" <?php echo $classificacao_filtro === 'critica' ? 'selected' : ''; ?>>Crítica</option>
                            <option value="alta" <?php echo $classificacao_filtro === 'alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="media" <?php echo $classificacao_filtro === 'media' ? 'selected' : ''; ?>>Média</option>
                            <option value="baixa" <?php echo $classificacao_filtro === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                        </select>
                    </div>
                    
                    <div>
                        <select name="responsavel" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="">Todos os Responsáveis</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $responsavel_filtro == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Não Conformidades -->
        <div class="space-y-4">
            <?php foreach ($nao_conformidades as $nc): ?>
                <?php 
                $vencida = strtotime($nc['data_vencimento']) < time() && $nc['status'] !== 'resolvido';
                $urgente = strtotime($nc['data_vencimento']) <= strtotime('+3 days') && $nc['status'] !== 'resolvido';
                ?>
                <div class="bg-card rounded-lg border border-border p-6 <?php echo $vencida ? 'border-l-4 border-l-destructive' : ($urgente ? 'border-l-4 border-l-chart-3' : ''); ?>">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-foreground mb-2"><?php echo htmlspecialchars($nc['titulo']); ?></h3>
                                    <p class="text-sm text-muted-foreground mb-3"><?php echo nl2br(htmlspecialchars($nc['descricao'])); ?></p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 text-muted-foreground mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            <span class="text-muted-foreground"><?php echo htmlspecialchars($nc['titulo_auditoria']); ?></span>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 text-muted-foreground mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="text-muted-foreground"><?php echo htmlspecialchars($nc['nome_responsavel']); ?></span>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 text-muted-foreground mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="<?php echo $vencida ? 'text-destructive font-medium' : ($urgente ? 'text-chart-3 font-medium' : 'text-muted-foreground'); ?>">
                                                Vencimento: <?php echo date('d/m/Y', strtotime($nc['data_vencimento'])); ?>
                                                <?php if ($vencida): ?>
                                                    (VENCIDA)
                                                <?php elseif ($urgente): ?>
                                                    (URGENTE)
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-end space-y-2 ml-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                        switch($nc['classificacao']) {
                                            case 'critica': echo 'bg-destructive/10 text-destructive'; break;
                                            case 'alta': echo 'bg-chart-3/10 text-chart-3'; break;
                                            case 'media': echo 'bg-chart-4/10 text-chart-4'; break;
                                            case 'baixa': echo 'bg-chart-1/10 text-chart-1'; break;
                                            default: echo 'bg-muted text-muted-foreground';
                                        }
                                        ?>">
                                        <?php echo ucfirst($nc['classificacao']); ?>
                                    </span>
                                    
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                        switch($nc['status']) {
                                            case 'resolvido': echo 'bg-chart-5/10 text-chart-5'; break;
                                            case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                                            case 'aberto': echo 'bg-destructive/10 text-destructive'; break;
                                            case 'escalonado': echo 'bg-chart-3/10 text-chart-3'; break;
                                            default: echo 'bg-muted text-muted-foreground';
                                        }
                                        ?>">
                                        <?php 
                                        switch($nc['status']) {
                                            case 'resolvido': echo 'Resolvido'; break;
                                            case 'em_progresso': echo 'Em Progresso'; break;
                                            case 'aberto': echo 'Aberto'; break;
                                            case 'escalonado': echo 'Escalonado'; break;
                                            default: echo ucfirst($nc['status']);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($nc['descricao_resolucao']): ?>
                                <div class="mt-4 p-3 bg-chart-5/10 rounded-lg border border-chart-5/20">
                                    <p class="text-sm font-medium text-chart-5 mb-1">Resolução:</p>
                                    <p class="text-sm text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao_resolucao'])); ?></p>
                                    <?php if ($nc['data_resolucao']): ?>
                                        <p class="text-xs text-muted-foreground mt-2">Resolvido em: <?php echo date('d/m/Y', strtotime($nc['data_resolucao'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex flex-row lg:flex-col space-x-2 lg:space-x-0 lg:space-y-2 mt-4 lg:mt-0">
                            <?php if ($nc['status'] !== 'resolvido'): ?>
                                <a href="edit-nc.php?id=<?php echo $nc['id']; ?>" 
                                   class="px-3 py-1 text-xs bg-accent text-accent-foreground hover:bg-accent/90 rounded transition-colors">
                                    Editar
                                </a>
                                
                                <?php if ($nc['responsavel_id'] == $usuario['id'] || podeGerenciar()): ?>
                                    <a href="resolve-nc.php?id=<?php echo $nc['id']; ?>" 
                                       class="px-3 py-1 text-xs bg-chart-5 text-white hover:bg-chart-5/90 rounded transition-colors">
                                        Resolver
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (podeGerenciar()): ?>
                                    <a href="escalate-nc.php?id=<?php echo $nc['id']; ?>" 
                                       class="px-3 py-1 text-xs bg-chart-3 text-white hover:bg-chart-3/90 rounded transition-colors">
                                        Escalonar
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="view-nc.php?id=<?php echo $nc['id']; ?>" 
                                   class="px-3 py-1 text-xs bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded transition-colors">
                                    Visualizar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($nao_conformidades)): ?>
                <div class="bg-card rounded-lg border border-border p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium text-foreground mb-2">Nenhuma não conformidade encontrada</p>
                    <p class="text-sm text-muted-foreground">Isso é uma boa notícia! Continue mantendo a qualidade em alta.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
