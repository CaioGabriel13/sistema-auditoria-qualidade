<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();

require_once 'config/database.php';
$db = getConexao();

// Filtros
$status_filtro = $_GET['status'] ?? '';
$auditor_filtro = $_GET['auditor'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($status_filtro) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $status_filtro;
}

if ($auditor_filtro) {
    $where_conditions[] = "a.auditor_id = :auditor_id";
    $params[':auditor_id'] = $auditor_filtro;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar auditorias
$query = "SELECT a.*, m.nome as nome_modelo, u1.nome as nome_auditor, u2.nome as nome_auditado 
          FROM auditorias a 
          LEFT JOIN modelos_checklist m ON a.modelo_id = m.id
          LEFT JOIN usuarios u1 ON a.auditor_id = u1.id 
          LEFT JOIN usuarios u2 ON a.auditado_id = u2.id 
          $where_clause
          ORDER BY a.criado_em DESC";

$stmt = $db->prepare($query);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$auditorias = $stmt->fetchAll();

// Buscar usuários para filtro
$query = "SELECT id, nome FROM usuarios ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Estatísticas
$query = "SELECT status, COUNT(*) as total FROM auditorias GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = [];
foreach ($stmt->fetchAll() as $row) {
    $stats[$row['status']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditorias - Sistema de Auditoria de Qualidade</title>
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
                <a href="auditorias.php" class="border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium">Auditorias</a>
                <a href="nao-conformidades.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Não Conformidades</a>
                <?php if (podeGerenciar()): ?>
                <a href="modelos.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Modelos</a>
                <a href="usuarios.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Usuários</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-1 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Planejadas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats['planejado'] ?? 0; ?></p>
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
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats['em_progresso'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-5 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Concluídas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats['completo'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-secondary rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Canceladas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats['cancelado'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros e Ações -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                    <form method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                        <div>
                            <select name="status" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="">Todos os Status</option>
                                <option value="planejado" <?php echo $status_filtro === 'planejado' ? 'selected' : ''; ?>>Planejado</option>
                                <option value="em_progresso" <?php echo $status_filtro === 'em_progresso' ? 'selected' : ''; ?>>Em Progresso</option>
                                <option value="completo" <?php echo $status_filtro === 'completo' ? 'selected' : ''; ?>>Concluído</option>
                                <option value="cancelado" <?php echo $status_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <div>
                            <select name="auditor" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="">Todos os Auditores</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $auditor_filtro == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                            Filtrar
                        </button>
                    </form>
                </div>
                
                <a href="create-audit.php" class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nova Auditoria
                </a>
            </div>
        </div>

        <!-- Lista de Auditorias -->
        <div class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Auditoria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Modelo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Auditor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Aderência</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($auditorias as $auditoria): ?>
                        <tr class="hover:bg-muted/50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></div>
                                    <?php if ($auditoria['nome_auditado']): ?>
                                        <div class="text-sm text-muted-foreground">Auditado: <?php echo htmlspecialchars($auditoria['nome_auditado']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground"><?php echo htmlspecialchars($auditoria['nome_modelo']); ?></td>
                            <td class="px-6 py-4 text-sm text-foreground"><?php echo htmlspecialchars($auditoria['nome_auditor']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($auditoria['status']) {
                                        case 'completo': echo 'bg-chart-5/10 text-chart-5'; break;
                                        case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                                        case 'planejado': echo 'bg-chart-1/10 text-chart-1'; break;
                                        case 'cancelado': echo 'bg-secondary/10 text-secondary'; break;
                                        default: echo 'bg-muted text-muted-foreground';
                                    }
                                    ?>">
                                    <?php 
                                    switch($auditoria['status']) {
                                        case 'completo': echo 'Concluída'; break;
                                        case 'em_progresso': echo 'Em Andamento'; break;
                                        case 'planejado': echo 'Planejada'; break;
                                        case 'cancelado': echo 'Cancelada'; break;
                                        default: echo ucfirst($auditoria['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <?php 
                                if ($auditoria['data_completa']) {
                                    echo date('d/m/Y', strtotime($auditoria['data_completa']));
                                } else {
                                    echo date('d/m/Y', strtotime($auditoria['data_planejada']));
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <?php if ($auditoria['percentual_adesao'] !== null): ?>
                                    <span class="font-medium"><?php echo number_format($auditoria['percentual_adesao'], 1); ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted-foreground">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <?php if ($auditoria['status'] === 'planejado' || $auditoria['status'] === 'em_progresso'): ?>
                                    <a href="execute-audit.php?id=<?php echo $auditoria['id']; ?>" 
                                       class="text-accent hover:text-accent/80">Executar</a>
                                <?php endif; ?>
                                
                                <?php if ($auditoria['status'] === 'completo'): ?>
                                    <a href="view-audit.php?id=<?php echo $auditoria['id']; ?>" 
                                       class="text-chart-1 hover:text-chart-1/80">Visualizar</a>
                                <?php endif; ?>
                                
                                <?php if (podeGerenciar() || $auditoria['auditor_id'] == $usuario['id']): ?>
                                    <a href="edit-audit.php?id=<?php echo $auditoria['id']; ?>" 
                                       class="text-secondary hover:text-secondary/80">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($auditorias)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                                <svg class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-lg font-medium mb-2">Nenhuma auditoria encontrada</p>
                                <p class="text-sm">Crie sua primeira auditoria para começar o processo de qualidade.</p>
                                <a href="create-audit.php" class="inline-flex items-center px-4 py-2 mt-4 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Nova Auditoria
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
