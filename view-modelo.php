<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();
$id_modelo = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados do modelo
$query = "SELECT m.*, u.nome as criado_por_nome 
          FROM modelos_checklist m 
          LEFT JOIN usuarios u ON m.criado_por = u.id 
          WHERE m.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_modelo);
$stmt->execute();
$modelo = $stmt->fetch();

if (!$modelo) {
    header('Location: modelos.php');
    exit();
}

// Buscar itens do checklist
$query = "SELECT * FROM itens_checklist WHERE modelo_id = :modelo_id ORDER BY indice_ordem, id";
$stmt = $db->prepare($query);
$stmt->bindParam(':modelo_id', $id_modelo);
$stmt->execute();
$itens = $stmt->fetchAll();

// Agrupar itens por categoria
$itens_por_categoria = [];
foreach ($itens as $item) {
    $categoria = $item['categoria'] ?: 'Geral';
    if (!isset($itens_por_categoria[$categoria])) {
        $itens_por_categoria[$categoria] = [];
    }
    $itens_por_categoria[$categoria][] = $item;
}

// Estatísticas do modelo
$query = "SELECT COUNT(*) as total_auditorias FROM auditorias WHERE modelo_id = :modelo_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':modelo_id', $id_modelo);
$stmt->execute();
$total_auditorias = $stmt->fetch()['total_auditorias'];

$query = "SELECT AVG(percentual_adesao) as media_aderencia 
          FROM auditorias 
          WHERE modelo_id = :modelo_id AND percentual_adesao IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->bindParam(':modelo_id', $id_modelo);
$stmt->execute();
$media_aderencia = $stmt->fetch()['media_aderencia'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($modelo['nome']); ?> - QualiTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="modelos.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-foreground"><?php echo htmlspecialchars($modelo['nome']); ?></h1>
                        <p class="text-sm text-muted-foreground">Visualização do Modelo de Checklist</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Informações do Modelo -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <h2 class="text-lg font-medium text-foreground mb-4">Informações do Modelo</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Nome:</label>
                            <p class="text-foreground"><?php echo htmlspecialchars($modelo['nome']); ?></p>
                        </div>
                        
                        <?php if ($modelo['descricao']): ?>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Descrição:</label>
                            <p class="text-foreground"><?php echo htmlspecialchars($modelo['descricao']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Tipo de Artefato:</label>
                            <p class="text-foreground"><?php echo htmlspecialchars($modelo['tipo_artefato']); ?></p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Criado por:</label>
                                <p class="text-foreground"><?php echo htmlspecialchars($modelo['criado_por_nome']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Data de Criação:</label>
                                <p class="text-foreground"><?php echo date('d/m/Y H:i', strtotime($modelo['criado_em'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div>
                    <h3 class="text-lg font-medium text-foreground mb-4">Estatísticas</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-muted rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Total de Itens</p>
                                    <p class="text-2xl font-bold text-foreground"><?php echo count($itens); ?></p>
                                </div>
                                <div class="h-8 w-8 bg-chart-1 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-muted rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Auditorias Realizadas</p>
                                    <p class="text-2xl font-bold text-foreground"><?php echo $total_auditorias; ?></p>
                                </div>
                                <div class="h-8 w-8 bg-chart-4 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-muted rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Aderência Média</p>
                                    <p class="text-2xl font-bold text-foreground"><?php echo number_format($media_aderencia, 1); ?>%</p>
                                </div>
                                <div class="h-8 w-8 bg-chart-5 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (podeGerenciar()): ?>
                    <div class="mt-6">
                        <a href="create-audit.php?modelo_id=<?php echo $modelo['id']; ?>" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nova Auditoria
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Checklist por Categoria -->
        <div class="space-y-6">
            <?php if (empty($itens)): ?>
                <div class="bg-card rounded-lg border border-border p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-foreground mb-2">Nenhum item encontrado</h3>
                    <p class="text-muted-foreground">Este modelo ainda não possui itens de checklist.</p>
                </div>
            <?php else: ?>
                <?php foreach ($itens_por_categoria as $categoria => $itens_categoria): ?>
                <div class="bg-card rounded-lg border border-border">
                    <div class="px-6 py-4 border-b border-border">
                        <h3 class="text-lg font-medium text-foreground"><?php echo htmlspecialchars($categoria); ?></h3>
                        <p class="text-sm text-muted-foreground"><?php echo count($itens_categoria); ?> <?php echo count($itens_categoria) === 1 ? 'item' : 'itens'; ?></p>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($itens_categoria as $index => $item): ?>
                            <div class="flex items-start space-x-4 p-4 bg-muted rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-accent rounded-full flex items-center justify-center text-accent-foreground font-medium text-sm">
                                        <?php echo $index + 1; ?>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground mb-1">
                                        <?php echo htmlspecialchars($item['questao']); ?>
                                    </p>
                                    
                                    <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                                        <?php if ($item['peso'] != 1.00): ?>
                                        <span>Peso: <?php echo number_format($item['peso'], 2); ?></span>
                                        <?php endif; ?>
                                        
                                        <span>Item #<?php echo $item['id']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ações -->
        <div class="mt-8 flex justify-between items-center">
            <a href="modelos.php" 
               class="inline-flex items-center px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar aos Modelos
            </a>
            
            <div class="flex space-x-2">
                <?php if (podeGerenciar()): ?>
                <a href="create-audit.php?modelo_id=<?php echo $modelo['id']; ?>" 
                   class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Criar Auditoria
                </a>
                <?php endif; ?>
                
                <button onclick="window.print()" 
                        class="inline-flex items-center px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H3a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>
    </main>

    <style>
        @media print {
            body {
                font-size: 12px;
            }
            
            header, 
            .no-print {
                display: none !important;
            }
            
            .bg-card,
            .bg-muted {
                background: white !important;
                border: 1px solid #ccc !important;
            }
        }
    </style>
</body>
</html>
