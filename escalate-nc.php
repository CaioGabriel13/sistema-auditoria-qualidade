<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();
$id_nc = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Verificar se é admin/gerente
if (!podeGerenciar()) {
    header('Location: nao-conformidades.php');
    exit();
}

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

// Verificar se pode escalonar
if ($nc['status'] === 'resolvido') {
    header('Location: view-nc.php?id=' . $id_nc);
    exit();
}

// Buscar usuários para escalonamento (gerentes e admins)
$query = "SELECT id, nome, departamento, funcao FROM usuarios WHERE funcao IN ('gerente', 'admin') AND id != :current_user ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->bindParam(':current_user', $usuario['id']);
$stmt->execute();
$usuarios_escalonamento = $stmt->fetchAll();

$sucesso = '';
$erro = '';

if ($_POST) {
    $escalonado_para_id = $_POST['escalonado_para_id'] ?? '';
    $justificativa = $_POST['justificativa'] ?? '';
    
    if ($escalonado_para_id && $justificativa) {
        try {
            // Incrementar nível de escalonamento
            $novo_nivel = $nc['nivel_escalonamento'] + 1;
            
            $query = "UPDATE nao_conformidades SET 
                      status = 'escalonado',
                      escalonado_para_id = :escalonado_para_id,
                      nivel_escalonamento = :nivel_escalonamento
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':escalonado_para_id', $escalonado_para_id);
            $stmt->bindParam(':nivel_escalonamento', $novo_nivel);
            $stmt->bindParam(':id', $id_nc);
            
            if ($stmt->execute()) {
                // Registrar histórico de escalonamento (poderia ser uma tabela separada)
                $sucesso = 'Não conformidade escalonada com sucesso!';
                
                // Atualizar dados da NC
                $stmt = $db->prepare("SELECT nc.*, a.titulo as titulo_auditoria, u1.nome as nome_responsavel, u2.nome as nome_criador, u3.nome as nome_escalonado
                                      FROM nao_conformidades nc 
                                      LEFT JOIN auditorias a ON nc.auditoria_id = a.id
                                      LEFT JOIN usuarios u1 ON nc.responsavel_id = u1.id 
                                      LEFT JOIN usuarios u2 ON nc.criado_por = u2.id 
                                      LEFT JOIN usuarios u3 ON nc.escalonado_para_id = u3.id
                                      WHERE nc.id = :id");
                $stmt->bindParam(':id', $id_nc);
                $stmt->execute();
                $nc = $stmt->fetch();
            }
        } catch (Exception $e) {
            $erro = 'Erro ao escalonar não conformidade: ' . $e->getMessage();
        }
    } else {
        $erro = 'Por favor, selecione o responsável e forneça uma justificativa.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalonar Não Conformidade - QualiTrack</title>
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
                        <h1 class="text-xl font-bold text-foreground">Escalonar Não Conformidade</h1>
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
                                case 'escalonado': echo 'Escalonado (Nível ' . $nc['nivel_escalonamento'] . ')'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="text-right text-sm text-muted-foreground">
                    <p>Vencimento: <?php echo date('d/m/Y', strtotime($nc['data_vencimento'])); ?></p>
                    <p>Responsável Atual: <?php echo htmlspecialchars($nc['nome_responsavel']); ?></p>
                    <?php if ($nc['nome_escalonado']): ?>
                        <p>Escalonado Para: <?php echo htmlspecialchars($nc['nome_escalonado']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-muted rounded-lg p-4">
                <h4 class="text-sm font-medium text-foreground mb-2">Descrição da Não Conformidade:</h4>
                <p class="text-sm text-foreground"><?php echo nl2br(htmlspecialchars($nc['descricao'])); ?></p>
            </div>
            
            <?php if ($nc['nivel_escalonamento'] > 0): ?>
            <div class="mt-4 bg-chart-3/10 border border-chart-3/20 rounded-lg p-4">
                <h4 class="text-sm font-medium text-chart-3 mb-2">Histórico de Escalonamento:</h4>
                <p class="text-sm text-foreground">Esta não conformidade já foi escalonada <?php echo $nc['nivel_escalonamento']; ?> vez(es).</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Formulário de Escalonamento -->
        <?php if ($nc['status'] !== 'resolvido'): ?>
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-medium text-foreground mb-6">Escalonamento da Não Conformidade</h2>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="escalonado_para_id" class="block text-sm font-medium text-foreground mb-2">
                        Escalonar Para *
                    </label>
                    <select id="escalonado_para_id" name="escalonado_para_id" required
                            class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="">Selecione o responsável</option>
                        <?php foreach ($usuarios_escalonamento as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['nome'] . ' - ' . $u['departamento'] . ' (' . ucfirst($u['funcao']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="justificativa" class="block text-sm font-medium text-foreground mb-2">
                        Justificativa do Escalonamento *
                    </label>
                    <textarea id="justificativa" name="justificativa" rows="4" required
                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                              placeholder="Explique por que esta não conformidade precisa ser escalonada, qual é a urgência e que ações adicionais são necessárias..."></textarea>
                    <p class="text-xs text-muted-foreground mt-2">
                        Inclua informações sobre tentativas anteriores de resolução, impacto no negócio e prazo crítico.
                    </p>
                </div>

                <div class="bg-chart-3/10 border border-chart-3/20 text-chart-3 px-4 py-3 rounded-lg text-sm">
                    <strong>Importante:</strong> O escalonamento mudará o responsável pela não conformidade e aumentará o nível de urgência. 
                    O novo responsável será notificado e terá autoridade para tomar ações adicionais.
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="nao-conformidades.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            onclick="return confirm('Tem certeza que deseja escalonar esta não conformidade? Esta ação alterará o responsável e aumentará o nível de urgência.')"
                            class="px-4 py-2 bg-chart-3 text-white hover:bg-chart-3/90 rounded-lg transition-colors">
                        Confirmar Escalonamento
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-medium text-foreground mb-4">Não Conformidade Resolvida</h2>
            
            <div class="bg-chart-5/10 border border-chart-5/20 rounded-lg p-4">
                <p class="text-sm text-foreground">Esta não conformidade já foi resolvida e não pode mais ser escalonada.</p>
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
