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

// Processar exclusão
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Verificar se há auditorias usando este modelo
        $query = "SELECT COUNT(*) FROM auditorias WHERE modelo_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $erro = "Não é possível excluir este modelo pois existem $count auditoria(s) que o utilizam.";
        } else {
            $query = "DELETE FROM modelos_checklist WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $sucesso = "Modelo excluído com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao excluir modelo: " . $e->getMessage();
    }
}

// Buscar modelos
$query = "SELECT m.*, u.nome as nome_criador, 
          (SELECT COUNT(*) FROM itens_checklist WHERE modelo_id = m.id) as total_itens,
          (SELECT COUNT(*) FROM auditorias WHERE modelo_id = m.id) as total_auditorias
          FROM modelos_checklist m 
          LEFT JOIN usuarios u ON m.criado_por = u.id 
          ORDER BY m.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$modelos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modelos de Checklist - QualiTrack</title>
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
                    <h1 class="text-xl font-bold text-foreground">QualiTrack</h1>
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
                <a href="modelos.php" class="border-b-2 border-sidebar-accent text-sidebar-accent py-4 px-1 text-sm font-medium">Modelos</a>
                <a href="usuarios.php" class="border-b-2 border-transparent text-sidebar-foreground hover:text-sidebar-accent hover:border-sidebar-accent py-4 px-1 text-sm font-medium">Usuários</a>
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

        <!-- Cabeçalho -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-medium text-foreground">Modelos de Checklist</h2>
                    <p class="text-sm text-muted-foreground mt-1">Gerencie os modelos de checklist para diferentes tipos de auditoria</p>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <a href="create-modelo.php" class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Modelo
                    </a>
                </div>
            </div>
        </div>

        <!-- Lista de Modelos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($modelos as $modelo): ?>
                <div class="bg-card rounded-lg border border-border p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-foreground mb-2"><?php echo htmlspecialchars($modelo['nome']); ?></h3>
                            <p class="text-sm text-muted-foreground mb-3"><?php echo htmlspecialchars($modelo['descricao']); ?></p>
                            
                            <div class="flex items-center text-sm text-muted-foreground mb-2">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a.997.997 0 01-1.414 0l-7-7A1.997 1.997 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <?php echo htmlspecialchars($modelo['tipo_artefato']); ?>
                            </div>
                            
                            <div class="flex items-center text-sm text-muted-foreground">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php echo htmlspecialchars($modelo['nome_criador']); ?>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <button onclick="toggleDropdown('dropdown-<?php echo $modelo['id']; ?>')" class="inline-flex items-center px-3 py-1 text-xs bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground rounded-md transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                Ações
                            </button>
                            
                            <div id="dropdown-<?php echo $modelo['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-card border border-border rounded-lg shadow-lg z-10">
                                <div class="py-1">
                                    <a href="view-modelo.php?id=<?php echo $modelo['id']; ?>" class="flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted">
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Visualizar
                                    </a>
                                    <a href="edit-modelo.php?id=<?php echo $modelo['id']; ?>" class="flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted">
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <a href="duplicate-modelo.php?id=<?php echo $modelo['id']; ?>" class="flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted">
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        Duplicar
                                    </a>
                                    <?php if ($modelo['total_auditorias'] == 0): ?>
                                        <div class="border-t border-border my-1"></div>
                                        <a href="?delete=<?php echo $modelo['id']; ?>" onclick="return confirm('⚠️ Tem certeza que deseja excluir este modelo?\n\nEsta ação não pode ser desfeita!')" class="flex items-center px-4 py-2 text-sm text-destructive hover:bg-destructive/10">
                                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Excluir
                                        </a>
                                    <?php else: ?>
                                        <div class="border-t border-border my-1"></div>
                                        <div class="flex items-center px-4 py-2 text-sm text-muted-foreground cursor-not-allowed">
                                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Não pode excluir
                                            <span class="ml-auto text-xs"><?php echo $modelo['total_auditorias']; ?> auditorias</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-border pt-4">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-foreground"><?php echo $modelo['total_itens']; ?></div>
                                <div class="text-xs text-muted-foreground">Itens</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-foreground"><?php echo $modelo['total_auditorias']; ?></div>
                                <div class="text-xs text-muted-foreground">Auditorias</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="flex justify-between items-center text-xs text-muted-foreground">
                            <span>Criado em:</span>
                            <span><?php echo date('d/m/Y', strtotime($modelo['criado_em'])); ?></span>
                        </div>
                        <?php if ($modelo['atualizado_em'] !== $modelo['criado_em']): ?>
                            <div class="flex justify-between items-center text-xs text-muted-foreground mt-1">
                                <span>Atualizado em:</span>
                                <span><?php echo date('d/m/Y', strtotime($modelo['atualizado_em'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4 flex space-x-2">
                        <a href="view-modelo.php?id=<?php echo $modelo['id']; ?>" 
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded transition-colors">
                            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Visualizar
                        </a>
                        <a href="edit-modelo.php?id=<?php echo $modelo['id']; ?>" 
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs bg-accent text-accent-foreground hover:bg-accent/90 rounded transition-colors">
                            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($modelos)): ?>
                <div class="col-span-full bg-card rounded-lg border border-border p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p class="text-lg font-medium text-foreground mb-2">Nenhum modelo encontrado</p>
                    <p class="text-sm text-muted-foreground mb-4">Crie seu primeiro modelo de checklist para começar.</p>
                    <a href="create-modelo.php" class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Criar Primeiro Modelo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const button = dropdown.previousElementSibling;
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            const allButtons = document.querySelectorAll('button[onclick^="toggleDropdown"]');
            
            // Fechar todos os outros dropdowns e resetar botões
            allDropdowns.forEach((d, index) => {
                if (d.id !== id) {
                    d.classList.add('hidden');
                    allButtons[index]?.classList.remove('bg-accent', 'text-accent-foreground');
                    allButtons[index]?.classList.add('bg-muted', 'text-muted-foreground');
                }
            });
            
            // Toggle do dropdown atual
            const isHidden = dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden');
            
            // Atualizar visual do botão
            if (isHidden) {
                button.classList.remove('bg-muted', 'text-muted-foreground');
                button.classList.add('bg-accent', 'text-accent-foreground');
            } else {
                button.classList.remove('bg-accent', 'text-accent-foreground');
                button.classList.add('bg-muted', 'text-muted-foreground');
            }
        }

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function(event) {
            if (!event.target.closest('button[onclick^="toggleDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
                const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
                const allButtons = document.querySelectorAll('button[onclick^="toggleDropdown"]');
                
                allDropdowns.forEach(d => d.classList.add('hidden'));
                allButtons.forEach(b => {
                    b.classList.remove('bg-accent', 'text-accent-foreground');
                    b.classList.add('bg-muted', 'text-muted-foreground');
                });
            }
        });

        // Melhor feedback visual para o botão de exclusão
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Adicionar um pequeno delay para mostrar o feedback visual
                    this.classList.add('bg-destructive', 'text-white');
                    setTimeout(() => {
                        this.classList.remove('bg-destructive', 'text-white');
                    }, 200);
                });
            });
        });
    </script>
</body>
</html>
