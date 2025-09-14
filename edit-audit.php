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

// Verificar se a auditoria pode ser editada
if ($auditoria['status'] === 'completo') {
    header('Location: view-audit.php?id=' . $id_auditoria);
    exit();
}

// Verificar permissões
if (!podeGerenciar() && $auditoria['auditor_id'] != $usuario['id']) {
    header('Location: auditorias.php');
    exit();
}

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
            // Determinar novo status
            $acao_status = $_POST['acao_status'] ?? '';
            
            // Se o modelo foi alterado, limpar respostas existentes
            if ($modelo_id != $auditoria['modelo_id']) {
                $query = "DELETE FROM respostas_auditoria WHERE auditoria_id = :id_auditoria";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_auditoria', $id_auditoria);
                $stmt->execute();
                
                // Resetar status para planejado se estiver em progresso
                if ($auditoria['status'] === 'em_progresso') {
                    $status = 'planejado';
                } else {
                    $status = $auditoria['status'];
                }
            } else {
                $status = $auditoria['status'];
            }
            
            // Se está retomando auditoria cancelada
            if ($acao_status === 'retomar' && $auditoria['status'] === 'cancelado') {
                $status = 'planejado';
                $justificativa_cancelamento = null; // Limpar justificativa
            } else {
                $justificativa_cancelamento = $auditoria['justificativa_cancelamento']; // Manter existente
            }
            
            $query = "UPDATE auditorias SET 
                      titulo = :titulo, 
                      descricao = :descricao, 
                      modelo_id = :modelo_id, 
                      auditado_id = :auditado_id, 
                      nome_artefato = :nome_artefato, 
                      versao_artefato = :versao_artefato, 
                      data_planejada = :data_planejada,
                      status = :status,
                      justificativa_cancelamento = :justificativa_cancelamento
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':modelo_id', $modelo_id);
            $stmt->bindParam(':auditado_id', $auditado_id);
            $stmt->bindParam(':nome_artefato', $nome_artefato);
            $stmt->bindParam(':versao_artefato', $versao_artefato);
            $stmt->bindParam(':data_planejada', $data_planejada);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':justificativa_cancelamento', $justificativa_cancelamento);
            $stmt->bindParam(':id', $id_auditoria);
            
            if ($stmt->execute()) {
                if ($acao_status === 'retomar' && $auditoria['status'] === 'cancelado') {
                    $sucesso = 'Auditoria retomada com sucesso! Status alterado para "Planejada".';
                } else {
                    $sucesso = 'Auditoria atualizada com sucesso!';
                }
                
                // Atualizar dados da auditoria
                $stmt = $db->prepare("SELECT a.*, t.nome as nome_modelo, u1.nome as nome_auditor, u2.nome as nome_auditado 
                                      FROM auditorias a 
                                      JOIN modelos_checklist t ON a.modelo_id = t.id 
                                      LEFT JOIN usuarios u1 ON a.auditor_id = u1.id 
                                      LEFT JOIN usuarios u2 ON a.auditado_id = u2.id 
                                      WHERE a.id = :id");
                $stmt->bindParam(':id', $id_auditoria);
                $stmt->execute();
                $auditoria = $stmt->fetch();
            }
        } catch (Exception $e) {
            $erro = 'Erro ao atualizar auditoria: ' . $e->getMessage();
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
    <title>Editar Auditoria - <?php echo htmlspecialchars($auditoria['titulo']); ?></title>
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
                        <h1 class="text-xl font-bold text-foreground">Editar Auditoria</h1>
                        <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        <?php 
                        switch($auditoria['status']) {
                            case 'em_progresso': echo 'bg-chart-4/10 text-chart-4'; break;
                            case 'planejado': echo 'bg-chart-1/10 text-chart-1'; break;
                            case 'cancelado': echo 'bg-destructive/10 text-destructive'; break;
                            default: echo 'bg-muted text-muted-foreground';
                        }
                        ?>">
                        <?php 
                        switch($auditoria['status']) {
                            case 'em_progresso': echo 'Em Progresso'; break;
                            case 'planejado': echo 'Planejada'; break;
                            case 'cancelado': echo 'Cancelada'; break;
                            default: echo ucfirst($auditoria['status']);
                        }
                        ?>
                    </span>
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
                <div class="mt-2 space-x-4">
                    <a href="auditorias.php" class="text-sm underline">Voltar para lista de auditorias</a>
                    <a href="execute-audit.php?id=<?php echo $id_auditoria; ?>" class="text-sm underline">Continuar execução</a>
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
                <h2 class="text-lg font-medium text-foreground mb-2">Informações da Auditoria</h2>
                
                <?php if ($auditoria['status'] === 'cancelado'): ?>
                    <div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg text-sm mb-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <strong>Auditoria Cancelada</strong>
                                <?php if ($auditoria['justificativa_cancelamento']): ?>
                                    <p class="mt-2"><strong>Justificativa:</strong> <?php echo nl2br(htmlspecialchars($auditoria['justificativa_cancelamento'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick="retomar()" 
                                    class="ml-4 px-3 py-1 bg-accent text-accent-foreground hover:bg-accent/90 rounded text-sm transition-colors">
                                Retomar Auditoria
                            </button>
                        </div>
                    </div>
                <?php elseif ($auditoria['status'] === 'em_progresso'): ?>
                    <div class="bg-chart-4/10 border border-chart-4/20 text-chart-4 px-4 py-3 rounded-lg text-sm">
                        <strong>Atenção:</strong> Esta auditoria está em progresso. Alterar o modelo apagará todas as respostas já preenchidas.
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="space-y-6" id="formEdicao">
                <input type="hidden" name="acao_status" id="acao_status" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-foreground mb-2">
                            Título da Auditoria *
                        </label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?php echo htmlspecialchars($auditoria['titulo']); ?>"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Auditoria Plano de Projeto - Sistema XYZ">
                    </div>

                    <div>
                        <label for="modelo_id" class="block text-sm font-medium text-foreground mb-2">
                            Modelo de Checklist *
                        </label>
                        <select id="modelo_id" name="modelo_id" required
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                <?php echo $auditoria['status'] === 'em_progresso' ? 'onchange="confirmarAlteracaoModelo()"' : ''; ?>>
                            <option value="">Selecione um modelo</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?php echo $modelo['id']; ?>" <?php echo ($auditoria['modelo_id'] == $modelo['id']) ? 'selected' : ''; ?>>
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
                                 placeholder="Descreva o objetivo e escopo desta auditoria"><?php echo htmlspecialchars($auditoria['descricao']); ?></textarea>
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
                                <option value="<?php echo $u['id']; ?>" <?php echo ($auditoria['auditado_id'] == $u['id']) ? 'selected' : ''; ?>>
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
                               value="<?php echo $auditoria['data_planejada']; ?>"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome_artefato" class="block text-sm font-medium text-foreground mb-2">
                            Nome do Artefato
                        </label>
                        <input type="text" id="nome_artefato" name="nome_artefato"
                               value="<?php echo htmlspecialchars($auditoria['nome_artefato']); ?>"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Plano de Projeto Sistema XYZ">
                    </div>

                    <div>
                        <label for="versao_artefato" class="block text-sm font-medium text-foreground mb-2">
                            Versão do Artefato
                        </label>
                        <input type="text" id="versao_artefato" name="versao_artefato"
                               value="<?php echo htmlspecialchars($auditoria['versao_artefato']); ?>"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: v1.0, v2.1">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="auditorias.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <a href="execute-audit.php?id=<?php echo $id_auditoria; ?>" 
                       class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                        Executar Auditoria
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Atualizar Auditoria
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function retomar() {
            if (confirm('Tem certeza que deseja retomar esta auditoria? Ela voltará ao status "Planejada" e poderá ser executada novamente.')) {
                document.getElementById('acao_status').value = 'retomar';
                document.getElementById('formEdicao').submit();
            }
        }
        
        function confirmarAlteracaoModelo() {
            const select = document.getElementById('modelo_id');
            const valorOriginal = '<?php echo $auditoria['modelo_id']; ?>';
            
            if (select.value !== valorOriginal && select.value !== '') {
                if (!confirm('Alterar o modelo apagará todas as respostas já preenchidas. Deseja continuar?')) {
                    select.value = valorOriginal;
                }
            }
        }
    </script>
</body>
</html>
