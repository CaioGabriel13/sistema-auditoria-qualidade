<?php
require_once 'includes/auth.php';
requerLogin();

if (!podeGerenciar()) {
    header('Location: index.php');
    exit();
}

$usuario = getUsuarioAtual();
$sucesso = '';
$erro = '';

if ($_POST && isset($_POST['importar_dados'])) {
    require_once 'config/database.php';
    $database = new Conexao();
    $db = $database->getConexao();
    
    try {
        // Ler o arquivo SQL
        $sql_file = 'database/dados_exemplo.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            
            // Remover comentários e dividir em comandos
            $sql_content = preg_replace('/--.*$/m', '', $sql_content);
            $commands = array_filter(array_map('trim', explode(';', $sql_content)));
            
            $db->beginTransaction();
            
            foreach ($commands as $command) {
                if (!empty($command) && !preg_match('/^(USE|CREATE|DROP)/i', $command)) {
                    $stmt = $db->prepare($command);
                    $stmt->execute();
                }
            }
            
            $db->commit();
            $sucesso = "Dados de exemplo importados com sucesso! Agora você pode visualizar gráficos com dados reais.";
        } else {
            $erro = "Arquivo de dados de exemplo não encontrado.";
        }
    } catch (Exception $e) {
        $db->rollback();
        $erro = "Erro ao importar dados: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Dados de Exemplo - Sistema de Auditoria de Qualidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="index.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-foreground">Importar Dados de Exemplo</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?php if ($sucesso): ?>
            <div class="mb-6 bg-chart-5/10 border border-chart-5/20 text-chart-5 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($sucesso); ?>
                <div class="mt-2">
                    <a href="index.php" class="text-sm underline">Ver Dashboard com dados reais</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-medium text-foreground mb-4">Importar Dados de Exemplo</h2>
            
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-2">O que será importado:</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• 10 auditorias de exemplo dos últimos 8 meses</li>
                    <li>• Percentuais de aderência reais para cada auditoria</li>
                    <li>• 6 não-conformidades de exemplo</li>
                    <li>• Dados distribuídos ao longo do tempo para visualização nos gráficos</li>
                </ul>
            </div>

            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">⚠️ Importante:</h3>
                <p class="text-sm text-yellow-700">
                    Esta ação irá adicionar dados de exemplo ao banco. 
                    Execute apenas uma vez para evitar dados duplicados.
                </p>
            </div>

            <form method="POST" class="space-y-6">
                <div class="flex justify-end space-x-4">
                    <a href="index.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" name="importar_dados" value="1"
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors"
                            onclick="return confirm('Tem certeza que deseja importar os dados de exemplo?')">
                        Importar Dados de Exemplo
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-8 bg-muted rounded-lg p-6">
            <h3 class="text-lg font-medium text-foreground mb-4">Após a Importação</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-card border border-border rounded-lg p-4">
                    <h4 class="font-medium text-foreground mb-2">Dashboard</h4>
                    <p class="text-sm text-muted-foreground">Visualize gráficos de aderência por mês com dados reais</p>
                </div>
                <div class="bg-card border border-border rounded-lg p-4">
                    <h4 class="font-medium text-foreground mb-2">Auditorias</h4>
                    <p class="text-sm text-muted-foreground">Explore auditorias completas com percentuais de aderência</p>
                </div>
                <div class="bg-card border border-border rounded-lg p-4">
                    <h4 class="font-medium text-foreground mb-2">Não Conformidades</h4>
                    <p class="text-sm text-muted-foreground">Veja exemplos de NCs com diferentes classificações</p>
                </div>
                <div class="bg-card border border-border rounded-lg p-4">
                    <h4 class="font-medium text-foreground mb-2">Estatísticas</h4>
                    <p class="text-sm text-muted-foreground">Números reais nos cards do dashboard</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
