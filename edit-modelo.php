<?php
require_once 'includes/auth.php';
requerLogin();

if (!podeGerenciar()) {
    header('Location: index.php');
    exit();
}

$usuario = getUsuarioAtual();
$id_modelo = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

// Buscar dados do modelo
$query = "SELECT * FROM modelos_checklist WHERE id = :id";
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

$sucesso = '';
$erro = '';

if ($_POST) {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo_artefato = $_POST['tipo_artefato'] ?? '';
    $itens_checklist = $_POST['itens'] ?? [];
    
    if ($nome && $tipo_artefato) {
        try {
            $db->beginTransaction();
            
            // Atualizar modelo
            $query = "UPDATE modelos_checklist SET nome = :nome, descricao = :descricao, tipo_artefato = :tipo_artefato, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':tipo_artefato', $tipo_artefato);
            $stmt->bindParam(':id', $id_modelo);
            $stmt->execute();
            
            // Remover itens existentes
            $query = "DELETE FROM itens_checklist WHERE modelo_id = :modelo_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':modelo_id', $id_modelo);
            $stmt->execute();
            
            // Inserir novos itens
            if (!empty($itens_checklist)) {
                $query = "INSERT INTO itens_checklist (modelo_id, questao, categoria, peso, indice_ordem) VALUES (:modelo_id, :questao, :categoria, :peso, :indice_ordem)";
                $stmt = $db->prepare($query);
                
                foreach ($itens_checklist as $indice => $item) {
                    if (!empty($item['questao'])) {
                        $questao = trim($item['questao']);
                        $categoria = $item['categoria'] ?? '';
                        $peso = floatval($item['peso'] ?? 1.00);
                        $indice_ordem = $indice + 1;
                        
                        $stmt->bindParam(':modelo_id', $id_modelo);
                        $stmt->bindParam(':questao', $questao);
                        $stmt->bindParam(':categoria', $categoria);
                        $stmt->bindParam(':peso', $peso);
                        $stmt->bindParam(':indice_ordem', $indice_ordem);
                        $stmt->execute();
                    }
                }
            }
            
            $db->commit();
            $sucesso = "Modelo atualizado com sucesso!";
            
            // Atualizar dados para exibição
            $stmt = $db->prepare("SELECT * FROM modelos_checklist WHERE id = :id");
            $stmt->bindParam(':id', $id_modelo);
            $stmt->execute();
            $modelo = $stmt->fetch();
            
            $stmt = $db->prepare("SELECT * FROM itens_checklist WHERE modelo_id = :modelo_id ORDER BY indice_ordem, id");
            $stmt->bindParam(':modelo_id', $id_modelo);
            $stmt->execute();
            $itens = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $db->rollback();
            $erro = "Erro ao atualizar modelo: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Modelo - QualiTrack</title>
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
                    <h1 class="text-xl font-bold text-foreground">Editar Modelo de Checklist</h1>
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
                    <a href="modelos.php" class="text-sm underline">Voltar para a lista de modelos</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <div class="bg-card rounded-lg border border-border p-6">
            <form method="POST" class="space-y-6">
                <!-- Informações Básicas -->
                <div>
                    <h2 class="text-lg font-medium text-foreground mb-4">Informações do Modelo</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-foreground mb-2">
                                Nome do Modelo *
                            </label>
                            <input type="text" id="nome" name="nome" required
                                   value="<?php echo htmlspecialchars($modelo['nome']); ?>"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Ex: Checklist - Plano de Projeto">
                        </div>

                        <div>
                            <label for="tipo_artefato" class="block text-sm font-medium text-foreground mb-2">
                                Tipo de Artefato *
                            </label>
                            <input type="text" id="tipo_artefato" name="tipo_artefato" required
                                   value="<?php echo htmlspecialchars($modelo['tipo_artefato']); ?>"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Ex: Plano de Projeto, Código Fonte">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="descricao" class="block text-sm font-medium text-foreground mb-2">
                            Descrição
                        </label>
                        <textarea id="descricao" name="descricao" rows="3"
                                  class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                  placeholder="Descrição detalhada do modelo de checklist"><?php echo htmlspecialchars($modelo['descricao']); ?></textarea>
                    </div>
                </div>

                <!-- Itens do Checklist -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium text-foreground">Itens do Checklist</h2>
                        <button type="button" onclick="adicionarItem()" 
                                class="px-3 py-1 bg-accent text-accent-foreground hover:bg-accent/90 rounded text-sm transition-colors">
                            Adicionar Item
                        </button>
                    </div>
                    
                    <div id="itens-container" class="space-y-4">
                        <?php foreach ($itens as $index => $item): ?>
                        <div class="item-checklist bg-muted rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-sm font-medium text-foreground">Item <?php echo $index + 1; ?></span>
                                <button type="button" onclick="removerItem(this)" 
                                        class="text-destructive hover:text-destructive/80 text-sm">
                                    Remover
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-foreground mb-1">Questão *</label>
                                    <textarea name="itens[<?php echo $index; ?>][questao]" rows="2" required
                                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                              placeholder="Ex: O documento possui identificação clara?"><?php echo htmlspecialchars($item['questao']); ?></textarea>
                                </div>
                                
                                <div>
                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-foreground mb-1">Categoria</label>
                                        <input type="text" name="itens[<?php echo $index; ?>][categoria]"
                                               value="<?php echo htmlspecialchars($item['categoria']); ?>"
                                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                               placeholder="Ex: Identificação">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1">Peso</label>
                                        <input type="number" name="itens[<?php echo $index; ?>][peso]" step="0.01" min="0" max="10"
                                               value="<?php echo $item['peso']; ?>"
                                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                               placeholder="1.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($itens)): ?>
                        <div class="item-checklist bg-muted rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-sm font-medium text-foreground">Item 1</span>
                                <button type="button" onclick="removerItem(this)" 
                                        class="text-destructive hover:text-destructive/80 text-sm">
                                    Remover
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-foreground mb-1">Questão *</label>
                                    <textarea name="itens[0][questao]" rows="2" required
                                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                              placeholder="Ex: O documento possui identificação clara?"></textarea>
                                </div>
                                
                                <div>
                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-foreground mb-1">Categoria</label>
                                        <input type="text" name="itens[0][categoria]"
                                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                               placeholder="Ex: Identificação">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1">Peso</label>
                                        <input type="number" name="itens[0][peso]" step="0.01" min="0" max="10" value="1.00"
                                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                               placeholder="1.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="modelos.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Atualizar Modelo
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        let contadorItens = <?php echo count($itens) > 0 ? count($itens) : 1; ?>;
        
        function adicionarItem() {
            const container = document.getElementById('itens-container');
            const novoItem = document.createElement('div');
            novoItem.className = 'item-checklist bg-muted rounded-lg p-4';
            
            novoItem.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <span class="text-sm font-medium text-foreground">Item ${contadorItens + 1}</span>
                    <button type="button" onclick="removerItem(this)" 
                            class="text-destructive hover:text-destructive/80 text-sm">
                        Remover
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1">Questão *</label>
                        <textarea name="itens[${contadorItens}][questao]" rows="2" required
                                  class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                  placeholder="Ex: O documento possui identificação clara?"></textarea>
                    </div>
                    
                    <div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-foreground mb-1">Categoria</label>
                            <input type="text" name="itens[${contadorItens}][categoria]"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Ex: Identificação">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Peso</label>
                            <input type="number" name="itens[${contadorItens}][peso]" step="0.01" min="0" max="10" value="1.00"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="1.00">
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(novoItem);
            contadorItens++;
            atualizarNumeracao();
        }
        
        function removerItem(botao) {
            const container = document.getElementById('itens-container');
            if (container.children.length > 1) {
                botao.closest('.item-checklist').remove();
                atualizarNumeracao();
            } else {
                alert('Deve haver pelo menos um item no checklist.');
            }
        }
        
        function atualizarNumeracao() {
            const itens = document.querySelectorAll('.item-checklist');
            itens.forEach((item, index) => {
                const span = item.querySelector('span');
                span.textContent = `Item ${index + 1}`;
            });
        }
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const questoes = document.querySelectorAll('textarea[name*="[questao]"]');
            let temQuestaoVazia = false;
            
            questoes.forEach(questao => {
                if (!questao.value.trim()) {
                    temQuestaoVazia = true;
                }
            });
            
            if (temQuestaoVazia) {
                e.preventDefault();
                alert('Por favor, preencha todas as questões ou remova os itens vazios.');
            }
        });
    </script>
</body>
</html>
