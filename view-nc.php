<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();
$id_nc = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados da não conformidade
$query = "SELECT nc.*, a.titulo as titulo_auditoria, u1.nome as nome_responsavel, u2.nome as nome_criador, u3.nome as nome_escalonado
          FROM nao_conformidades nc 
          LEFT JOIN auditorias a ON nc.auditoria_id = a.id
          LEFT JOIN usuarios u1 ON nc.responsavel_id = u1.id 
          LEFT JOIN usuarios u2 ON nc.criado_por = u2.id 
          LEFT JOIN usuarios u3 ON nc.escalonado_para_id = u3.id
          WHERE nc.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_nc);
$stmt->execute();
$nc = $stmt->fetch();

if (!$nc) {
    header('Location: nao-conformidades.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Não Conformidade - QualiTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="nao-conformidades.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Visualizar Não Conformidade</h1>
                        <p class="text-sm text-muted-foreground">Auditoria: <?php echo htmlspecialchars($nc['titulo_auditoria']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-chart-5/10 text-chart-5">
                        Resolvida
                    </span>
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Informações da NC -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="flex justify-between items-start mb-6">
                <div class="flex-1">
                    <h2 class="text-xl font-medium text-foreground mb-3"><?php echo htmlspecialchars($nc['titulo']); ?></h2>
                    
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="px-2 py-1 text-sm font-medium rounded-full
                            <?php 
                            switch($nc['classificacao']) {
                                case 'critica': echo 'bg-destructive/10 text-destructive'; break;
                                case 'alta': echo 'bg-chart-3/10 text-chart-3'; break;
                                case 'media': echo 'bg-chart-4/10 text-chart-4'; break;
                                case 'baixa': echo 'bg-chart-1/10 text-chart-1'; break;
                            }
                            ?>">
                            Classificação: <?php echo ucfirst($nc['classificacao']); ?>
                        </span>
                        
                        <span class="px-2 py-1 text-sm font-medium rounded-full bg-chart-5/10 text-chart-5">
                            Status: Resolvida
                        </span>
                        
                        <?php if ($nc['nivel_escalonamento'] > 0): ?>
                        <span class="px-2 py-1 text-sm font-medium rounded-full bg-chart-3/10 text-chart-3">
                            Escalonado <?php echo $nc['nivel_escalonamento']; ?>x
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Informações -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="text-sm font-medium text-muted-foreground mb-2">Informações Gerais</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Criado por:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($nc['nome_criador']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Data de criação:</span>
                            <span class="text-foreground"><?php echo date('d/m/Y H:i', strtotime($nc['criado_em'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Responsável:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($nc['nome_responsavel']); ?></span>
                        </div>
                        <?php if ($nc['nome_escalonado']): ?>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Escalonado para:</span>
                            <span class="text-foreground"><?php echo htmlspecialchars($nc['nome_escalonado']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-muted-foreground mb-2">Prazos</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Data de vencimento:</span>
                            <span class="text-foreground"><?php echo date('d/m/Y', strtotime($nc['data_vencimento'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Data de resolução:</span>
                            <span class="text-chart-5 font-medium"><?php echo date('d/m/Y', strtotime($nc['data_resolucao'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Tempo para resolver:</span>
                            <span class="text-foreground">
                                <?php 
                                $dias_resolucao = (strtotime($nc['data_resolucao']) - strtotime($nc['criado_em'])) / (60 * 60 * 24);
                                echo round($dias_resolucao) . ' dias';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descrição da NC -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <h3 class="text-lg font-medium text-foreground mb-4">Descrição da Não Conformidade</h3>
            <div class="bg-muted rounded-lg p-4">
                <p class="text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao'])); ?></p>
            </div>
        </div>

        <!-- Resolução -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <h3 class="text-lg font-medium text-foreground mb-4">Resolução</h3>
            <div class="bg-chart-5/10 border border-chart-5/20 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-chart-5 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-chart-5 mb-2">Descrição da Resolução:</p>
                        <p class="text-sm text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao_resolucao'])); ?></p>
                        <p class="text-xs text-muted-foreground mt-3">
                            Resolvido em <?php echo date('d/m/Y H:i', strtotime($nc['data_resolucao'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações -->
        <div class="flex justify-between items-center">
            <a href="nao-conformidades.php" 
               class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                Voltar para Lista
            </a>
            
            <div class="space-x-4">
                <a href="view-audit.php?id=<?php echo $nc['auditoria_id']; ?>" 
                   class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                    Ver Auditoria
                </a>
                
                <?php if (podeGerenciar() || $nc['responsavel_id'] == $usuario['id'] || $nc['criado_por'] == $usuario['id']): ?>
                <button onclick="window.print()" 
                        class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    Imprimir
                </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <style>
        @media print {
            header, nav, .no-print { display: none !important; }
            body { background: white !important; }
            .bg-card { background: white !important; border: 1px solid #ccc !important; }
        }
    </style>
</body>
</html>
