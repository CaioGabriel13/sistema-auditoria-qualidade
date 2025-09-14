<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();
$id_auditoria = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados da auditoria
$query = "SELECT a.*, t.nome as nome_modelo, u1.nome as nome_auditor, u2.nome as nome_auditado 
          FROM auditorias a 
          JOIN modelos_checklist t ON a.modelo_id = t.id 
          LEFT JOIN usuarios u1 ON a.auditor_id = u1.id 
          LEFT JOIN usuarios u2 ON a.auditado_id = u2.id 
          WHERE a.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_auditoria);
$stmt->execute();
$auditoria = $stmt->fetch();

if (!$auditoria) {
    header('Location: auditorias.php');
    exit();
}

// Buscar itens do checklist
$query = "SELECT * FROM itens_checklist WHERE modelo_id = :modelo_id ORDER BY indice_ordem, id";
$stmt = $db->prepare($query);
$stmt->bindParam(':modelo_id', $auditoria['modelo_id']);
$stmt->execute();
$itens = $stmt->fetchAll();

// Buscar respostas da auditoria
$query = "SELECT * FROM respostas_auditoria WHERE auditoria_id = :id_auditoria";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_auditoria', $id_auditoria);
$stmt->execute();
$respostas = [];
foreach ($stmt->fetchAll() as $resposta) {
    $respostas[$resposta['item_id']] = $resposta;
}

// Buscar não conformidades relacionadas
$query = "SELECT nc.*, u.nome as nome_responsavel 
          FROM nao_conformidades nc 
          LEFT JOIN usuarios u ON nc.responsavel_id = u.id 
          WHERE nc.auditoria_id = :id_auditoria 
          ORDER BY nc.criado_em";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_auditoria', $id_auditoria);
$stmt->execute();
$nao_conformidades = $stmt->fetchAll();

// Calcular estatísticas
$total_itens = 0;
$itens_conformes = 0;
$itens_nao_conformes = 0;
$itens_na = 0;
$respostas_por_categoria = [];

foreach ($itens as $item) {
    if (isset($respostas[$item['id']])) {
        $resposta = $respostas[$item['id']]['resposta'];
        $categoria = $item['categoria'] ?: 'Geral';
        
        if (!isset($respostas_por_categoria[$categoria])) {
            $respostas_por_categoria[$categoria] = ['sim' => 0, 'nao' => 0, 'na' => 0];
        }
        
        $respostas_por_categoria[$categoria][$resposta]++;
        
        if ($resposta !== 'na') {
            $total_itens++;
            if ($resposta === 'sim') {
                $itens_conformes++;
            } else {
                $itens_nao_conformes++;
            }
        } else {
            $itens_na++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Auditoria - <?php echo htmlspecialchars($auditoria['titulo']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="auditorias.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></h1>
                        <p class="text-sm text-muted-foreground">Modelo: <?php echo htmlspecialchars($auditoria['nome_modelo']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-chart-5/10 text-chart-5">
                        Auditoria Concluída
                    </span>
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Resumo da Auditoria -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <div class="bg-card rounded-lg border border-border p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">Informações da Auditoria</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Auditor</p>
                            <p class="text-foreground"><?php echo htmlspecialchars($auditoria['nome_auditor']); ?></p>
                        </div>
                        <?php if ($auditoria['nome_auditado']): ?>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Auditado</p>
                            <p class="text-foreground"><?php echo htmlspecialchars($auditoria['nome_auditado']); ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Data Planejada</p>
                            <p class="text-foreground"><?php echo date('d/m/Y', strtotime($auditoria['data_planejada'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Data de Conclusão</p>
                            <p class="text-foreground"><?php echo $auditoria['data_completa'] ? date('d/m/Y', strtotime($auditoria['data_completa'])) : '-'; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($auditoria['descricao']): ?>
                    <div class="mt-4 pt-4 border-t border-border">
                        <p class="text-sm font-medium text-muted-foreground mb-2">Descrição</p>
                        <p class="text-foreground"><?php echo nl2br(htmlspecialchars($auditoria['descricao'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($auditoria['nome_artefato']): ?>
                    <div class="mt-4 pt-4 border-t border-border">
                        <p class="text-sm font-medium text-muted-foreground mb-2">Artefato Auditado</p>
                        <p class="text-foreground">
                            <?php echo htmlspecialchars($auditoria['nome_artefato']); ?>
                            <?php if ($auditoria['versao_artefato']): ?>
                                <span class="text-muted-foreground">(<?php echo htmlspecialchars($auditoria['versao_artefato']); ?>)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="space-y-6">
                <!-- Resultado Geral -->
                <div class="bg-card rounded-lg border border-border p-6">
                    <h3 class="text-lg font-medium text-foreground mb-4">Resultado Geral</h3>
                    
                    <div class="text-center mb-4">
                        <div class="text-3xl font-bold text-chart-5"><?php echo number_format($auditoria['percentual_adesao'], 1); ?>%</div>
                        <p class="text-sm text-muted-foreground">Aderência</p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">Conformes</span>
                            <span class="text-sm font-medium text-chart-5"><?php echo $itens_conformes; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">Não Conformes</span>
                            <span class="text-sm font-medium text-destructive"><?php echo $itens_nao_conformes; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">N/A</span>
                            <span class="text-sm font-medium text-secondary"><?php echo $itens_na; ?></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-border">
                            <span class="text-sm font-medium text-foreground">Total</span>
                            <span class="text-sm font-medium text-foreground"><?php echo count($itens); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Não Conformidades -->
                <?php if (!empty($nao_conformidades)): ?>
                <div class="bg-card rounded-lg border border-border p-6">
                    <h3 class="text-lg font-medium text-foreground mb-4">Não Conformidades</h3>
                    <div class="text-center mb-4">
                        <div class="text-2xl font-bold text-destructive"><?php echo count($nao_conformidades); ?></div>
                        <p class="text-sm text-muted-foreground">Identificadas</p>
                    </div>
                    <a href="nao-conformidades.php?auditoria=<?php echo $auditoria['id']; ?>" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-lg transition-colors">
                        Ver Detalhes
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resultados por Categoria -->
        <?php if (!empty($respostas_por_categoria)): ?>
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <h2 class="text-lg font-medium text-foreground mb-4">Resultados por Categoria</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($respostas_por_categoria as $categoria => $stats): ?>
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="font-medium text-foreground mb-3"><?php echo htmlspecialchars($categoria); ?></h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">Conformes</span>
                            <span class="text-sm font-medium text-chart-5"><?php echo $stats['sim']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">Não Conformes</span>
                            <span class="text-sm font-medium text-destructive"><?php echo $stats['nao']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-foreground">N/A</span>
                            <span class="text-sm font-medium text-secondary"><?php echo $stats['na']; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detalhes do Checklist -->
        <div class="bg-card rounded-lg border border-border">
            <div class="p-6 border-b border-border">
                <h2 class="text-lg font-medium text-foreground">Detalhes do Checklist</h2>
            </div>
            
            <div class="p-6 border-b border-border">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-muted-foreground">
                        Auditoria concluída em <?php echo date('d/m/Y', strtotime($auditoria['data_completa'])); ?>
                    </p>
                    <div class="space-x-2">
                        <a href="auditorias.php" 
                           class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                            Voltar à Lista
                        </a>
                        <?php if (!empty($nao_conformidades)): ?>
                        <a href="nao-conformidades.php?auditoria=<?php echo $auditoria['id']; ?>" 
                           class="px-4 py-2 bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-lg transition-colors">
                            Ver Não Conformidades
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="divide-y divide-border">
                <?php foreach ($itens as $index => $item): ?>
                <?php $resposta = $respostas[$item['id']] ?? null; ?>
                <div class="p-6 <?php echo $resposta && $resposta['resposta'] === 'nao' ? 'bg-destructive/5 border-l-4 border-l-destructive' : ''; ?>">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                            <?php 
                            if ($resposta) {
                                switch($resposta['resposta']) {
                                    case 'sim': echo 'bg-chart-5/10 text-chart-5'; break;
                                    case 'nao': echo 'bg-destructive/10 text-destructive'; break;
                                    case 'na': echo 'bg-secondary/10 text-secondary'; break;
                                }
                            } else {
                                echo 'bg-muted text-muted-foreground';
                            }
                            ?>">
                            <?php 
                            if ($resposta) {
                                switch($resposta['resposta']) {
                                    case 'sim': echo '✓'; break;
                                    case 'nao': echo '✗'; break;
                                    case 'na': echo '-'; break;
                                }
                            } else {
                                echo $index + 1;
                            }
                            ?>
                        </div>
                        
                        <div class="flex-1">
                            <div class="mb-3">
                                <p class="text-foreground font-medium mb-2"><?php echo htmlspecialchars($item['questao']); ?></p>
                                <div class="flex items-center space-x-4">
                                    <?php if ($item['categoria']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent">
                                            <?php echo htmlspecialchars($item['categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-muted-foreground">Peso: <?php echo $item['peso']; ?></span>
                                </div>
                            </div>
                            
                            <?php if ($resposta): ?>
                            <div class="bg-muted rounded-lg p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-medium text-foreground">Resposta:</span>
                                    <span class="text-sm font-medium 
                                        <?php 
                                        switch($resposta['resposta']) {
                                            case 'sim': echo 'text-chart-5'; break;
                                            case 'nao': echo 'text-destructive'; break;
                                            case 'na': echo 'text-secondary'; break;
                                        }
                                        ?>">
                                        <?php 
                                        switch($resposta['resposta']) {
                                            case 'sim': echo 'Conforme'; break;
                                            case 'nao': echo 'Não Conforme'; break;
                                            case 'na': echo 'Não Aplicável'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if ($resposta['comentarios']): ?>
                                <div class="mt-2">
                                    <span class="text-sm font-medium text-muted-foreground">Comentários:</span>
                                    <p class="text-sm text-foreground mt-1"><?php echo nl2br(htmlspecialchars($resposta['comentarios'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>
