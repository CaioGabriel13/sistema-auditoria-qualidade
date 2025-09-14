<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();
$id_nc = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados da não conformidade
$query = "SELECT nc.*, a.titulo as titulo_auditoria, u1.nome as nome_responsavel, u2.nome as nome_criador
          FROM nao_conformidades nc 
          LEFT JOIN auditorias a ON nc.auditoria_id = a.id
          LEFT JOIN usuarios u1 ON nc.responsavel_id = u1.id 
          LEFT JOIN usuarios u2 ON nc.criado_por = u2.id 
          WHERE nc.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_nc);
$stmt->execute();
$nc = $stmt->fetch();

if (!$nc) {
    header('Location: nao-conformidades.php');
    exit();
}

// Verificar se pode resolver
if ($nc['status'] === 'resolvido') {
    header('Location: view-nc.php?id=' . $id_nc);
    exit();
}

// Verificar permissões - só pode resolver se for responsável ou admin
if (!podeGerenciar() && $nc['responsavel_id'] != $usuario['id']) {
    header('Location: nao-conformidades.php');
    exit();
}

$sucesso = '';
$erro = '';

if ($_POST) {
    $descricao_resolucao = $_POST['descricao_resolucao'] ?? '';
    
    if ($descricao_resolucao) {
        try {
            $query = "UPDATE nao_conformidades SET 
                      status = 'resolvido',
                      descricao_resolucao = :descricao_resolucao,
                      data_resolucao = CURDATE()
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':descricao_resolucao', $descricao_resolucao);
            $stmt->bindParam(':id', $id_nc);
            
            if ($stmt->execute()) {
                $sucesso = 'Não conformidade resolvida com sucesso!';
                
                // Atualizar dados da NC
                $stmt = $db->prepare("SELECT nc.*, a.titulo as titulo_auditoria, u1.nome as nome_responsavel, u2.nome as nome_criador
                                      FROM nao_conformidades nc 
                                      LEFT JOIN auditorias a ON nc.auditoria_id = a.id
                                      LEFT JOIN usuarios u1 ON nc.responsavel_id = u1.id 
                                      LEFT JOIN usuarios u2 ON nc.criado_por = u2.id 
                                      WHERE nc.id = :id");
                $stmt->bindParam(':id', $id_nc);
                $stmt->execute();
                $nc = $stmt->fetch();
            }
        } catch (Exception $e) {
            $erro = 'Erro ao resolver não conformidade: ' . $e->getMessage();
        }
    } else {
        $erro = 'Por favor, descreva como a não conformidade foi resolvida.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver Não Conformidade - QualiTrack</title>
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
                        <h1 class="text-xl font-bold text-foreground">Resolver Não Conformidade</h1>
                        <p class="text-sm text-muted-foreground">Auditoria: <?php echo htmlspecialchars($nc['titulo_auditoria']); ?></p>
                    </div>
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
                    <a href="nao-conformidades.php" class="text-sm underline">Voltar para lista de não conformidades</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <!-- Informações da NC -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-lg font-medium text-foreground mb-2"><?php echo htmlspecialchars($nc['titulo']); ?></h2>
                    <div class="flex items-center space-x-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
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
                        
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            <?php 
                            switch($nc['status']) {
                                case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                                case 'aberto': echo 'bg-destructive/10 text-destructive'; break;
                                case 'escalonado': echo 'bg-chart-3/10 text-chart-3'; break;
                            }
                            ?>">
                            <?php 
                            switch($nc['status']) {
                                case 'em_progresso': echo 'Em Progresso'; break;
                                case 'aberto': echo 'Aberto'; break;
                                case 'escalonado': echo 'Escalonado'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="text-right text-sm text-muted-foreground">
                    <p>Vencimento: <?php echo date('d/m/Y', strtotime($nc['data_vencimento'])); ?></p>
                    <p>Responsável: <?php echo htmlspecialchars($nc['nome_responsavel']); ?></p>
                </div>
            </div>
            
            <div class="bg-muted rounded-lg p-4">
                <h4 class="text-sm font-medium text-foreground mb-2">Descrição da Não Conformidade:</h4>
                <p class="text-sm text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao'])); ?></p>
            </div>
        </div>

        <!-- Formulário de Resolução -->
        <?php if ($nc['status'] !== 'resolvido'): ?>
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-medium text-foreground mb-6">Resolução da Não Conformidade</h2>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="descricao_resolucao" class="block text-sm font-medium text-foreground mb-2">
                        Descrição da Resolução *
                    </label>
                    <textarea id="descricao_resolucao" name="descricao_resolucao" rows="6" required
                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                              placeholder="Descreva detalhadamente como a não conformidade foi resolvida, quais ações foram tomadas e evidências da resolução..."></textarea>
                    <p class="text-xs text-muted-foreground mt-2">
                        Seja específico sobre as ações tomadas, cronograma de implementação e como foi verificada a eficácia da solução.
                    </p>
                </div>

                <div class="bg-chart-5/10 border border-chart-5/20 text-chart-5 px-4 py-3 rounded-lg text-sm">
                    <strong>Importante:</strong> Ao confirmar a resolução, a não conformidade será marcada como "Resolvida" e não poderá mais ser editada. 
                    Certifique-se de que todas as ações corretivas foram implementadas e verificadas.
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="nao-conformidades.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            onclick="return confirm('Tem certeza que deseja marcar esta não conformidade como resolvida? Esta ação não pode ser desfeita.')"
                            class="px-4 py-2 bg-chart-5 text-white hover:bg-chart-5/90 rounded-lg transition-colors">
                        Confirmar Resolução
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-medium text-foreground mb-4">Não Conformidade Resolvida</h2>
            
            <div class="bg-chart-5/10 border border-chart-5/20 rounded-lg p-4">
                <p class="text-sm font-medium text-chart-5 mb-2">Resolução:</p>
                <p class="text-sm text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao_resolucao'])); ?></p>
                <p class="text-xs text-muted-foreground mt-3">Resolvido em: <?php echo date('d/m/Y', strtotime($nc['data_resolucao'])); ?></p>
            </div>
            
            <div class="mt-6">
                <a href="nao-conformidades.php" 
                   class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                    Voltar para Lista
                </a>
            </div>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
