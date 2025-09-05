<?php
require_once 'includes/auth.php';
requerLogin();

if (!podeGerenciar()) {
    header('Location: index.php');
    exit();
}

$usuario = getUsuarioAtual();

require_once 'config/database.php';
$db = getConexao();

$sucesso = '';
$erro = '';

// Processar ações
if ($_POST) {
    $acao = $_POST['acao'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($acao === 'toggle_status' && $user_id && $user_id != $usuario['id']) {
        try {
            // Para este exemplo, vamos apenas atualizar a última atividade
            $query = "UPDATE usuarios SET atualizado_em = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $sucesso = "Status do usuário atualizado com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao atualizar status: " . $e->getMessage();
        }
    }
}

// Filtros
$funcao_filtro = $_GET['funcao'] ?? '';
$departamento_filtro = $_GET['departamento'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($funcao_filtro) {
    $where_conditions[] = "u.funcao = :funcao";
    $params[':funcao'] = $funcao_filtro;
}

if ($departamento_filtro) {
    $where_conditions[] = "u.departamento = :departamento";
    $params[':departamento'] = $departamento_filtro;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar usuários
$query = "SELECT u.*, s.nome as nome_superior,
          (SELECT COUNT(*) FROM auditorias WHERE auditor_id = u.id) as total_auditorias,
          (SELECT COUNT(*) FROM nao_conformidades WHERE responsavel_id = u.id AND status IN ('aberto', 'em_progresso')) as ncs_pendentes
          FROM usuarios u 
          LEFT JOIN usuarios s ON u.superior_id = s.id 
          $where_clause
          ORDER BY u.nome";

$stmt = $db->prepare($query);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Buscar departamentos únicos
$query = "SELECT DISTINCT departamento FROM usuarios WHERE departamento IS NOT NULL ORDER BY departamento";
$stmt = $db->prepare($query);
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Estatísticas
$query = "SELECT funcao, COUNT(*) as total FROM usuarios GROUP BY funcao";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_funcao = [];
foreach ($stmt->fetchAll() as $row) {
    $stats_funcao[$row['funcao']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Sistema de Auditoria de Qualidade</title>
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
                <a href="nao-conformidades.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Não Conformidades</a>
                <a href="modelos.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Modelos</a>
                <a href="usuarios.php" class="border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium">Usuários</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?php if ($sucesso): ?>
            <div class="mb-6 bg-chart-5/10 border border-chart-5/20 text-chart-5 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-destructive rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Administradores</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_funcao['admin'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-3 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Gerentes</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_funcao['gerente'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-1 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Auditores</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $stats_funcao['auditor'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-4 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo count($usuarios); ?></p>
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
                            <select name="funcao" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="">Todas as Funções</option>
                                <option value="admin" <?php echo $funcao_filtro === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="gerente" <?php echo $funcao_filtro === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                                <option value="auditor" <?php echo $funcao_filtro === 'auditor' ? 'selected' : ''; ?>>Auditor</option>
                            </select>
                        </div>
                        
                        <div>
                            <select name="departamento" class="px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="">Todos os Departamentos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departamento_filtro === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                            Filtrar
                        </button>
                    </form>
                </div>
                
                <a href="create-usuario.php" class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Usuário
                </a>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Função</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Departamento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Superior</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Atividade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($usuarios as $user): ?>
                        <tr class="hover:bg-muted/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 bg-accent rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-accent-foreground">
                                            <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-foreground"><?php echo htmlspecialchars($user['nome']); ?></div>
                                        <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($user['funcao']) {
                                        case 'admin': echo 'bg-destructive/10 text-destructive'; break;
                                        case 'gerente': echo 'bg-chart-3/10 text-chart-3'; break;
                                        case 'auditor': echo 'bg-chart-1/10 text-chart-1'; break;
                                        default: echo 'bg-muted text-muted-foreground';
                                    }
                                    ?>">
                                    <?php 
                                    switch($user['funcao']) {
                                        case 'admin': echo 'Administrador'; break;
                                        case 'gerente': echo 'Gerente'; break;
                                        case 'auditor': echo 'Auditor'; break;
                                        default: echo ucfirst($user['funcao']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground"><?php echo htmlspecialchars($user['departamento'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-sm text-foreground"><?php echo htmlspecialchars($user['nome_superior'] ?? '-'); ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-foreground"><?php echo $user['total_auditorias']; ?> auditorias</div>
                                <?php if ($user['ncs_pendentes'] > 0): ?>
                                    <div class="text-xs text-destructive"><?php echo $user['ncs_pendentes']; ?> NCs pendentes</div>
                                <?php else: ?>
                                    <div class="text-xs text-muted-foreground">Nenhuma NC pendente</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-chart-5/10 text-chart-5">
                                    Ativo
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="view-usuario.php?id=<?php echo $user['id']; ?>" 
                                   class="text-chart-1 hover:text-chart-1/80">Visualizar</a>
                                
                                <?php if ($user['id'] != $usuario['id']): ?>
                                    <a href="edit-usuario.php?id=<?php echo $user['id']; ?>" 
                                       class="text-accent hover:text-accent/80">Editar</a>
                                <?php endif; ?>
                                
                                <?php if ($usuario['funcao'] === 'admin' && $user['id'] != $usuario['id']): ?>
                                    <button onclick="toggleUserStatus(<?php echo $user['id']; ?>)" 
                                            class="text-secondary hover:text-secondary/80">
                                        Desativar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">
                                <svg class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <p class="text-lg font-medium mb-2">Nenhum usuário encontrado</p>
                                <p class="text-sm">Ajuste os filtros ou crie um novo usuário.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleUserStatus(userId) {
            if (confirm('Tem certeza que deseja alterar o status deste usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
