<?php
require_once 'includes/auth.php';
requerLogin();

$usuario = getUsuarioAtual();

require_once 'config/database.php';
$db = getConexao();

// Verificar se há um modelo pré-selecionado
$modelo_pre_selecionado = $_GET['modelo_id'] ?? null;

// Buscar modelos de checklist disponíveis
$query = "SELECT * FROM modelos_checklist ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$modelos = $stmt->fetchAll();

// Buscar usuários para auditoria
$query = "SELECT id, nome, email, departamento FROM usuarios WHERE id != :id_usuario_atual ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_usuario_atual', $usuario['id']);
$stmt->execute();
$usuarios_auditados = $stmt->fetchAll();

$sucesso = '';
$erro = '';

if ($_POST) {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $modelo_id = $_POST['modelo_id'] ?? '';
    $auditado_id = $_POST['auditado_id'] ?? null;
    $nome_artefato = $_POST['nome_artefato'] ?? '';
    $versao_artefato = $_POST['versao_artefato'] ?? '';
    $data_planejada = $_POST['data_planejada'] ?? '';
    
    if ($titulo && $modelo_id && $data_planejada) {
        try {
            $query = "INSERT INTO auditorias (titulo, descricao, modelo_id, auditor_id, auditado_id, nome_artefato, versao_artefato, data_planejada, status) 
                      VALUES (:titulo, :descricao, :modelo_id, :auditor_id, :auditado_id, :nome_artefato, :versao_artefato, :data_planejada, 'planejado')";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':modelo_id', $modelo_id);
            $stmt->bindParam(':auditor_id', $usuario['id']);
            $stmt->bindParam(':auditado_id', $auditado_id);
            $stmt->bindParam(':nome_artefato', $nome_artefato);
            $stmt->bindParam(':versao_artefato', $versao_artefato);
            $stmt->bindParam(':data_planejada', $data_planejada);
            
            if ($stmt->execute()) {
                $id_auditoria = $db->lastInsertId();
                $sucesso = 'Auditoria criada com sucesso!';
                
                // Redirecionar para a página de execução da auditoria
                header("Location: execute-audit.php?id=$id_auditoria");
                exit();
            }
        } catch (Exception $e) {
            $erro = 'Erro ao criar auditoria: ' . $e->getMessage();
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
    <title>Nova Auditoria - QualiTrack</title>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-foreground">Nova Auditoria</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-card rounded-lg border border-border p-6">
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

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-foreground mb-2">
                            Título da Auditoria *
                        </label>
                        <input type="text" id="titulo" name="titulo" required
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Auditoria Plano de Projeto - Sistema XYZ">
                    </div>

                    <div>
                        <label for="modelo_id" class="block text-sm font-medium text-foreground mb-2">
                            Modelo de Checklist *
                        </label>
                        <select id="modelo_id" name="modelo_id" required
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <option value="">Selecione um modelo</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?php echo $modelo['id']; ?>" <?php echo ($modelo_pre_selecionado == $modelo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($modelo['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descricao" class="block text-sm font-medium text-foreground mb-2">
                        Descrição
                    </label>
                    <textarea id="descricao" name="descricao" rows="3"
                                 class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                 placeholder="Descreva o objetivo e escopo desta auditoria"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="auditado_id" class="block text-sm font-medium text-foreground mb-2">
                            Pessoa/Equipe Auditada
                        </label>
                        <select id="auditado_id" name="auditado_id"
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <option value="">Selecione (opcional)</option>
                            <?php foreach ($usuarios_auditados as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['nome'] . ' - ' . $u['departamento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="data_planejada" class="block text-sm font-medium text-foreground mb-2">
                            Data Planejada *
                        </label>
                        <input type="date" id="data_planejada" name="data_planejada" required
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome_artefato" class="block text-sm font-medium text-foreground mb-2">
                            Nome do Artefato
                        </label>
                        <input type="text" id="nome_artefato" name="nome_artefato"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Plano de Projeto Sistema XYZ">
                    </div>

                    <div>
                        <label for="versao_artefato" class="block text-sm font-medium text-foreground mb-2">
                            Versão do Artefato
                        </label>
                        <input type="text" id="versao_artefato" name="versao_artefato"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: v1.0, v2.1">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="index.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Criar Auditoria
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>