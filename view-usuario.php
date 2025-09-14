<?php
require_once 'includes/auth.php';
requerLogin();

if (!podeGerenciar()) {
    header('Location: index.php');
    exit();
}

$usuario_atual = getUsuarioAtual();
$id_usuario = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados do usuário
$query = "SELECT u.*, s.nome as nome_superior,
          (SELECT COUNT(*) FROM auditorias WHERE auditor_id = u.id) as total_auditorias_criadas,
          (SELECT COUNT(*) FROM auditorias WHERE auditor_id = u.id AND status = 'completo') as auditorias_concluidas,
          (SELECT COUNT(*) FROM nao_conformidades WHERE responsavel_id = u.id) as total_ncs_responsavel,
          (SELECT COUNT(*) FROM nao_conformidades WHERE responsavel_id = u.id AND status = 'resolvido') as ncs_resolvidas,
          (SELECT COUNT(*) FROM nao_conformidades WHERE criado_por = u.id) as ncs_criadas
          FROM usuarios u 
          LEFT JOIN usuarios s ON u.superior_id = s.id 
          WHERE u.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_usuario);
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: usuarios.php');
    exit();
}

// Buscar últimas auditorias do usuário
$query = "SELECT a.*, m.nome as nome_modelo 
          FROM auditorias a 
          LEFT JOIN modelos_checklist m ON a.modelo_id = m.id 
          WHERE a.auditor_id = :id 
          ORDER BY a.criado_em DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_usuario);
$stmt->execute();
$ultimas_auditorias = $stmt->fetchAll();

// Buscar não conformidades relacionadas
$query = "SELECT nc.*, a.titulo as titulo_auditoria 
          FROM nao_conformidades nc 
          LEFT JOIN auditorias a ON nc.auditoria_id = a.id 
          WHERE nc.responsavel_id = :id1 OR nc.criado_por = :id2
          ORDER BY nc.criado_em DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':id1', $id_usuario);
$stmt->bindParam(':id2', $id_usuario);
$stmt->execute();
$nao_conformidades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Usuário - QualiTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="usuarios.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Visualizar Usuário</h1>
                        <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($usuario['nome']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario_atual['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Informações do Usuário -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center">
                    <div class="h-16 w-16 bg-accent rounded-full flex items-center justify-center mr-4">
                        <span class="text-2xl font-bold text-accent-foreground">
                            <?php echo strtoupper(substr($usuario['nome'], 0, 2)); ?>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-foreground"><?php echo htmlspecialchars($usuario['nome']); ?></h2>
                        <p class="text-muted-foreground"><?php echo htmlspecialchars($usuario['email']); ?></p>
                        <div class="flex items-center mt-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full mr-2
                                <?php 
                                switch($usuario['funcao']) {
                                    case 'admin': echo 'bg-destructive/10 text-destructive'; break;
                                    case 'gerente': echo 'bg-chart-3/10 text-chart-3'; break;
                                    case 'auditor': echo 'bg-chart-1/10 text-chart-1'; break;
                                    default: echo 'bg-muted text-muted-foreground';
                                }
                                ?>">
                                <?php 
                                switch($usuario['funcao']) {
                                    case 'admin': echo 'Administrador'; break;
                                    case 'gerente': echo 'Gerente'; break;
                                    case 'auditor': echo 'Auditor'; break;
                                    default: echo ucfirst($usuario['funcao']);
                                }
                                ?>
                            </span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-chart-5/10 text-chart-5">
                                Ativo
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if ($usuario['id'] != $usuario_atual['id']): ?>
                <a href="edit-usuario.php?id=<?php echo $usuario['id']; ?>" 
                   class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    Editar Usuário
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Grid de Informações -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-muted-foreground mb-3">Informações Pessoais</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nome:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($usuario['nome']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Email:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($usuario['email']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Departamento:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($usuario['departamento'] ?? 'Não informado'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Superior:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($usuario['nome_superior'] ?? 'Nenhum'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-muted-foreground mb-3">Datas</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Cadastrado em:</span>
                            <span class="text-foreground"><?php echo date('d/m/Y H:i', strtotime($usuario['criado_em'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Última atualização:</span>
                            <span class="text-foreground"><?php echo date('d/m/Y H:i', strtotime($usuario['atualizado_em'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-1 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Auditorias</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $usuario['total_auditorias_criadas']; ?></p>
                        <p class="text-xs text-muted-foreground"><?php echo $usuario['auditorias_concluidas']; ?> concluídas</p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-3 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">NCs Responsável</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $usuario['total_ncs_responsavel']; ?></p>
                        <p class="text-xs text-muted-foreground"><?php echo $usuario['ncs_resolvidas']; ?> resolvidas</p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-chart-4 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">NCs Criadas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $usuario['ncs_criadas']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas Auditorias -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <h3 class="text-lg font-medium text-foreground mb-4">Últimas Auditorias</h3>
            <?php if (!empty($ultimas_auditorias)): ?>
                <div class="space-y-3">
                    <?php foreach ($ultimas_auditorias as $auditoria): ?>
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h4 class="text-sm font-medium text-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></h4>
                                <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($auditoria['status']) {
                                        case 'planejado': echo 'bg-muted text-muted-foreground'; break;
                                        case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                                        case 'completo': echo 'bg-chart-5/10 text-chart-5'; break;
                                        case 'cancelado': echo 'bg-destructive/10 text-destructive'; break;
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $auditoria['status'])); ?>
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Modelo: <?php echo htmlspecialchars($auditoria['nome_modelo']); ?> • 
                                <?php echo date('d/m/Y', strtotime($auditoria['criado_em'])); ?>
                            </p>
                        </div>
                        <a href="view-audit.php?id=<?php echo $auditoria['id']; ?>" 
                           class="text-accent hover:text-accent/80 text-sm">
                            Ver
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted-foreground text-center py-4">Nenhuma auditoria encontrada.</p>
            <?php endif; ?>
        </div>

        <!-- Não Conformidades -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h3 class="text-lg font-medium text-foreground mb-4">Não Conformidades Relacionadas</h3>
            <?php if (!empty($nao_conformidades)): ?>
                <div class="space-y-3">
                    <?php foreach ($nao_conformidades as $nc): ?>
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h4 class="text-sm font-medium text-foreground"><?php echo htmlspecialchars($nc['titulo']); ?></h4>
                                <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($nc['status']) {
                                        case 'aberto': echo 'bg-destructive/10 text-destructive'; break;
                                        case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                                        case 'resolvido': echo 'bg-chart-5/10 text-chart-5'; break;
                                        case 'escalonado': echo 'bg-chart-3/10 text-chart-3'; break;
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $nc['status'])); ?>
                                </span>
                                <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($nc['classificacao']) {
                                        case 'critica': echo 'bg-destructive/10 text-destructive'; break;
                                        case 'alta': echo 'bg-chart-3/10 text-chart-3'; break;
                                        case 'media': echo 'bg-chart-4/10 text-chart-4'; break;
                                        case 'baixa': echo 'bg-chart-1/10 text-chart-1'; break;
                                    }
                                    ?>">
                                    <?php echo ucfirst($nc['classificacao']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Auditoria: <?php echo htmlspecialchars($nc['titulo_auditoria']); ?> • 
                                <?php echo date('d/m/Y', strtotime($nc['criado_em'])); ?>
                            </p>
                        </div>
                        <a href="view-nc.php?id=<?php echo $nc['id']; ?>" 
                           class="text-accent hover:text-accent/80 text-sm">
                            Ver
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted-foreground text-center py-4">Nenhuma não conformidade encontrada.</p>
            <?php endif; ?>
        </div>

        <!-- Ações -->
        <div class="flex justify-between items-center mt-6">
            <a href="usuarios.php" 
               class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                Voltar para Lista
            </a>
            
            <?php if ($usuario['id'] != $usuario_atual['id']): ?>
            <a href="edit-usuario.php?id=<?php echo $usuario['id']; ?>" 
               class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                Editar Usuário
            </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
