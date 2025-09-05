<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();

// Buscar estatísticas do dashboard
require_once 'config/database.php';
$db = getConexao();

// Total de auditorias
$query = "SELECT COUNT(*) as total FROM auditorias";
$stmt = $db->prepare($query);
$stmt->execute();
$totalAuditorias = $stmt->fetch()['total'];

// Auditorias pendentes
$query = "SELECT COUNT(*) as total FROM auditorias WHERE status IN ('planejado', 'em_progresso')";
$stmt = $db->prepare($query);
$stmt->execute();
$auditoriasPendentes = $stmt->fetch()['total'];

// NCs abertas
$query = "SELECT COUNT(*) as total FROM nao_conformidades WHERE status IN ('aberto', 'em_progresso')";
$stmt = $db->prepare($query);
$stmt->execute();
$NCsAbertas = $stmt->fetch()['total'];

// NCs críticas
$query = "SELECT COUNT(*) as total FROM nao_conformidades WHERE classificacao = 'critica' AND status IN ('aberto', 'em_progresso')";
$stmt = $db->prepare($query);
$stmt->execute();
$NCsCriticas = $stmt->fetch()['total'];

// Aderência média
$query = "SELECT AVG(percentual_adesao) as media_aderencia FROM auditorias WHERE percentual_adesao IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$mediaAderencia = $stmt->fetch()['media_aderencia'] ?? 0;

// Auditorias recentes
$query = "SELECT a.*, u1.nome as nome_auditor, u2.nome as nome_auditado 
          FROM auditorias a 
          LEFT JOIN usuarios u1 ON a.auditor_id = u1.id 
          LEFT JOIN usuarios u2 ON a.auditado_id = u2.id 
          ORDER BY a.criado_em DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$auditoriasRecentes = $stmt->fetchAll();

// Aderência por mês (últimos 6 meses)
$aderenciaPorMes = [];
$meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $mesNome = $meses[date('n', strtotime("-$i months")) - 1];
    
    $query = "SELECT AVG(percentual_adesao) as media_aderencia 
              FROM auditorias 
              WHERE DATE_FORMAT(data_completa, '%Y-%m') = :mes 
              AND percentual_adesao IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mes', $mes);
    $stmt->execute();
    $resultado = $stmt->fetch();
    
    $aderenciaPorMes[] = [
        'mes' => $mesNome,
        'aderencia' => $resultado['media_aderencia'] ? round($resultado['media_aderencia'], 1) : 0
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Auditoria de Qualidade</title>
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
                <a href="index.php" class="border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium">Dashboard</a>
                <a href="auditorias.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Auditorias</a>
                <a href="nao-conformidades.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Não Conformidades</a>
                <?php if (podeGerenciar()): ?>
                <a href="modelos.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Modelos</a>
                <a href="usuarios.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Usuários</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-chart-1 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Total de Auditorias</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $totalAuditorias; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-chart-4 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Auditorias Pendentes</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $auditoriasPendentes; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-chart-3 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">NCs Abertas</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo $NCsAbertas; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-chart-5 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Aderência Média</p>
                        <p class="text-2xl font-bold text-foreground"><?php echo number_format($mediaAderencia, 1); ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-card rounded-lg p-6 border border-border">
                <h3 class="text-lg font-medium text-foreground mb-4">Aderência por Mês (Últimos 6 Meses)</h3>
                <div class="space-y-3">
                    <?php foreach ($aderenciaPorMes as $dados): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground"><?php echo $dados['mes']; ?></span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-muted rounded-full h-2">
                                <div class="bg-accent h-2 rounded-full" style="width: <?php echo $dados['aderencia']; ?>%"></div>
                            </div>
                            <span class="text-sm font-medium text-foreground"><?php echo $dados['aderencia']; ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($aderenciaPorMes) || array_sum(array_column($aderenciaPorMes, 'aderencia')) == 0): ?>
                    <div class="text-center py-4">
                        <p class="text-muted-foreground text-sm">Nenhuma auditoria concluída nos últimos 6 meses</p>
                        <p class="text-muted-foreground text-xs mt-1">Complete algumas auditorias para visualizar os dados</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-card rounded-lg p-6 border border-border">
                <h3 class="text-lg font-medium text-foreground mb-4">Auditorias Recentes</h3>
                <div class="space-y-4">
                    <?php foreach ($auditoriasRecentes as $auditoria): ?>
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div>
                            <p class="font-medium text-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></p>
                            <p class="text-sm text-muted-foreground">
                                Auditor: <?php echo htmlspecialchars($auditoria['nome_auditor']); ?>
                                <?php if ($auditoria['nome_auditado']): ?>
                                | Auditado: <?php echo htmlspecialchars($auditoria['nome_auditado']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            <?php 
                            switch($auditoria['status']) {
                                case 'completo': echo 'bg-chart-5 text-white'; break;
                                case 'em_progresso': echo 'bg-chart-4 text-white'; break;
                                case 'planejado': echo 'bg-chart-1 text-white'; break;
                                default: echo 'bg-secondary text-secondary-foreground';
                            }
                            ?>">
                            <?php 
                            switch($auditoria['status']) {
                                case 'completo': echo 'Concluída'; break;
                                case 'em_progresso': echo 'Em Andamento'; break;
                                case 'planejado': echo 'Planejada'; break;
                                default: echo ucfirst($auditoria['status']);
                            }
                            ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4">
                    <a href="auditorias.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-accent-foreground bg-accent hover:bg-accent/90 transition-colors">
                        Ver Todas as Auditorias
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-lg font-medium text-foreground mb-4">Ações Rápidas</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="create-audit.php" class="flex items-center p-4 bg-card border border-border rounded-lg hover:bg-muted transition-colors">
                    <div class="h-10 w-10 bg-accent rounded-lg flex items-center justify-center mr-4">
                        <svg class="h-6 w-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-foreground">Nova Auditoria</p>
                        <p class="text-sm text-muted-foreground">Criar uma nova auditoria</p>
                    </div>
                </a>

                <a href="nao-conformidades.php" class="flex items-center p-4 bg-card border border-border rounded-lg hover:bg-muted transition-colors">
                    <div class="h-10 w-10 bg-chart-3 rounded-lg flex items-center justify-center mr-4">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-foreground">Gerenciar NCs</p>
                        <p class="text-sm text-muted-foreground">Acompanhar não conformidades</p>
                    </div>
                </a>

                <a href="relatorios.php" class="flex items-center p-4 bg-card border border-border rounded-lg hover:bg-muted transition-colors">
                    <div class="h-10 w-10 bg-chart-1 rounded-lg flex items-center justify-center mr-4">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-foreground">Relatórios</p>
                        <p class="text-sm text-muted-foreground">Visualizar relatórios de qualidade</p>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <script>
        // Script para prevenir problemas de scroll infinito
        document.addEventListener('DOMContentLoaded', function() {
            // Prevenir scrolling automático problemático
            let scrollCount = 0;
            let lastScrollTop = 0;
            
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Se o scroll está mudando muito rapidamente, parar
                if (Math.abs(scrollTop - lastScrollTop) > 1000) {
                    window.scrollTo(0, lastScrollTop);
                    return false;
                }
                
                lastScrollTop = scrollTop;
            }, { passive: false });
            
            // Garantir que a página comece no topo
            window.scrollTo(0, 0);
            
            // Debug: Log se há elementos com altura muito grande
            const elements = document.querySelectorAll('*');
            elements.forEach(el => {
                if (el.offsetHeight > window.innerHeight * 5) {
                    console.warn('Elemento com altura excessiva encontrado:', el);
                }
            });
        });
    </script>
</body>
</html>