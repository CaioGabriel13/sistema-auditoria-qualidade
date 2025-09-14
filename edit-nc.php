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

// Verificar permissões - só pode editar se for responsável, criador ou admin
if (!podeGerenciar() && $nc['responsavel_id'] != $usuario['id'] && $nc['criado_por'] != $usuario['id']) {
    header('Location: nao-conformidades.php');
    exit();
}

// Buscar usuários para responsável
$query = "SELECT id, nome, departamento FROM usuarios ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll();

$sucesso = '';
$erro = '';

if ($_POST) {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $classificacao = $_POST['classificacao'] ?? '';
    $responsavel_id = $_POST['responsavel_id'] ?? '';
    $data_vencimento = $_POST['data_vencimento'] ?? '';
    
    if ($titulo && $descricao && $classificacao && $responsavel_id && $data_vencimento) {
        try {
            $query = "UPDATE nao_conformidades SET 
                      titulo = :titulo, 
                      descricao = :descricao, 
                      classificacao = :classificacao, 
                      responsavel_id = :responsavel_id, 
                      data_vencimento = :data_vencimento
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':classificacao', $classificacao);
            $stmt->bindParam(':responsavel_id', $responsavel_id);
            $stmt->bindParam(':data_vencimento', $data_vencimento);
            $stmt->bindParam(':id', $id_nc);
            
            if ($stmt->execute()) {
                $sucesso = 'Não conformidade atualizada com sucesso!';
                
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
            $erro = 'Erro ao atualizar não conformidade: ' . $e->getMessage();
        }
    } else {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Não Conformidade - QualiTrack</title>
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
                        <h1 class="text-xl font-bold text-foreground">Editar Não Conformidade</h1>
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

        <div class="bg-card rounded-lg border border-border p-6">
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-medium text-foreground">Informações da Não Conformidade</h2>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        <?php 
                        switch($nc['status']) {
                            case 'resolvido': echo 'bg-chart-5/10 text-chart-5'; break;
                            case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                            case 'aberto': echo 'bg-destructive/10 text-destructive'; break;
                            case 'escalonado': echo 'bg-chart-3/10 text-chart-3'; break;
                            default: echo 'bg-muted text-muted-foreground';
                        }
                        ?>">
                        <?php 
                        switch($nc['status']) {
                            case 'resolvido': echo 'Resolvido'; break;
                            case 'em_progresso': echo 'Em Progresso'; break;
                            case 'aberto': echo 'Aberto'; break;
                            case 'escalonado': echo 'Escalonado'; break;
                            default: echo ucfirst($nc['status']);
                        }
                        ?>
                    </span>
                </div>
                
                <div class="mt-4 text-sm text-muted-foreground">
                    <p>Criado por: <?php echo htmlspecialchars($nc['nome_criador']); ?> em <?php echo date('d/m/Y H:i', strtotime($nc['criado_em'])); ?></p>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="titulo" class="block text-sm font-medium text-foreground mb-2">
                        Título *
                    </label>
                    <input type="text" id="titulo" name="titulo" required
                           value="<?php echo htmlspecialchars($nc['titulo']); ?>"
                           class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                           placeholder="Título da não conformidade">
                </div>

                <div>
                    <label for="descricao" class="block text-sm font-medium text-foreground mb-2">
                        Descrição *
                    </label>
                    <textarea id="descricao" name="descricao" rows="4" required
                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                              placeholder="Descrição detalhada da não conformidade"><?php echo htmlspecialchars($nc['descricao']); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="classificacao" class="block text-sm font-medium text-foreground mb-2">
                            Classificação *
                        </label>
                        <select id="classificacao" name="classificacao" required
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <option value="">Selecione a classificação</option>
                            <option value="baixa" <?php echo $nc['classificacao'] === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                            <option value="media" <?php echo $nc['classificacao'] === 'media' ? 'selected' : ''; ?>>Média</option>
                            <option value="alta" <?php echo $nc['classificacao'] === 'alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="critica" <?php echo $nc['classificacao'] === 'critica' ? 'selected' : ''; ?>>Crítica</option>
                        </select>
                    </div>

                    <div>
                        <label for="responsavel_id" class="block text-sm font-medium text-foreground mb-2">
                            Responsável *
                        </label>
                        <select id="responsavel_id" name="responsavel_id" required
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $nc['responsavel_id'] == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nome'] . ' - ' . $u['departamento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="data_vencimento" class="block text-sm font-medium text-foreground mb-2">
                        Data de Vencimento *
                    </label>
                    <input type="date" id="data_vencimento" name="data_vencimento" required
                           value="<?php echo $nc['data_vencimento']; ?>"
                           class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="nao-conformidades.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
