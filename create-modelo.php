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

if ($_POST) {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo_artefato = $_POST['tipo_artefato'] ?? '';
    $itens = $_POST['itens'] ?? [];
    
    if ($nome && $tipo_artefato && !empty($itens)) {
        try {
            $db->beginTransaction();
            
            // Criar modelo
            $query = "INSERT INTO modelos_checklist (nome, descricao, tipo_artefato, criado_por) 
                      VALUES (:nome, :descricao, :tipo_artefato, :criado_por)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':tipo_artefato', $tipo_artefato);
            $stmt->bindParam(':criado_por', $usuario['id']);
            $stmt->execute();
            
            $modelo_id = $db->lastInsertId();
            
            // Inserir itens
            foreach ($itens as $index => $item) {
                if (!empty(trim($item['questao']))) {
                    $questao = trim($item['questao']);
                    $categoria = $item['categoria'] ?? '';
                    $peso = $item['peso'] ?? 1.0;
                    $ordem = $index + 1;
                    
                    $query = "INSERT INTO itens_checklist (modelo_id, questao, categoria, peso, indice_ordem) 
                              VALUES (:modelo_id, :questao, :categoria, :peso, :indice_ordem)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':modelo_id', $modelo_id);
                    $stmt->bindParam(':questao', $questao);
                    $stmt->bindParam(':categoria', $categoria);
                    $stmt->bindParam(':peso', $peso);
                    $stmt->bindParam(':indice_ordem', $ordem);
                    $stmt->execute();
                }
            }
            
            $db->commit();
            $sucesso = "Modelo criado com sucesso!";
            
            // Redirecionar após 2 segundos
            header("refresh:2;url=modelos.php");
            
        } catch (Exception $e) {
            $db->rollBack();
            $erro = "Erro ao criar modelo: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha todos os campos obrigatórios e adicione pelo menos um item.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Modelo - Sistema de Auditoria de Qualidade</title>
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
                    <h1 class="text-xl font-bold text-foreground">Novo Modelo de Checklist</h1>
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
                <div class="mt-2 text-sm">Redirecionando para a lista de modelos...</div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <!-- Informações Básicas -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-medium text-foreground mb-4">Informações Básicas</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-foreground mb-2">
                            Nome do Modelo *
                        </label>
                        <input type="text" id="nome" name="nome" required
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Checklist - Plano de Projeto">
                    </div>

                    <div>
                        <label for="tipo_artefato" class="block text-sm font-medium text-foreground mb-2">
                            Tipo de Artefato *
                        </label>
                        <input type="text" id="tipo_artefato" name="tipo_artefato" required
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                               placeholder="Ex: Plano de Projeto, Código Fonte, Documentação">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="descricao" class="block text-sm font-medium text-foreground mb-2">
                        Descrição
                    </label>
                    <textarea id="descricao" name="descricao" rows="3"
                              class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                              placeholder="Descreva o propósito e escopo deste modelo"></textarea>
                </div>
            </div>

            <!-- Itens do Checklist -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-foreground">Itens do Checklist</h2>
                    <button type="button" onclick="adicionarItem()" 
                            class="px-3 py-1 bg-accent text-accent-foreground hover:bg-accent/90 rounded text-sm transition-colors">
                        Adicionar Item
                    </button>
                </div>
                
                <div id="itens-container" class="space-y-4">
                    <!-- Item inicial -->
                    <div class="item-checklist border border-border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-sm font-medium text-muted-foreground">Item 1</span>
                            <button type="button" onclick="removerItem(this)" class="text-destructive hover:text-destructive/80 text-sm">Remover</button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-foreground mb-1">Questão *</label>
                                <textarea name="itens[0][questao]" rows="2" required
                                         class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm"
                                         placeholder="Ex: O documento possui identificação clara (nome, versão, data)?"></textarea>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1">Categoria</label>
                                    <input type="text" name="itens[0][categoria]"
                                           class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm"
                                           placeholder="Ex: Identificação">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1">Peso</label>
                                    <select name="itens[0][peso]" class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm">
                                        <option value="1.0">Normal (1.0)</option>
                                        <option value="1.5">Importante (1.5)</option>
                                        <option value="2.0">Crítico (2.0)</option>
                                        <option value="0.5">Opcional (0.5)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="flex justify-end space-x-4">
                <a href="modelos.php" 
                   class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    Criar Modelo
                </button>
            </div>
        </form>
    </main>

    <script>
        let itemCount = 1;

        function adicionarItem() {
            const container = document.getElementById('itens-container');
            const novoItem = document.createElement('div');
            novoItem.className = 'item-checklist border border-border rounded-lg p-4';
            novoItem.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <span class="text-sm font-medium text-muted-foreground">Item ${itemCount + 1}</span>
                    <button type="button" onclick="removerItem(this)" class="text-destructive hover:text-destructive/80 text-sm">Remover</button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-foreground mb-1">Questão *</label>
                        <textarea name="itens[${itemCount}][questao]" rows="2" required
                                 class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm"
                                 placeholder="Digite a questão do checklist"></textarea>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Categoria</label>
                            <input type="text" name="itens[${itemCount}][categoria]"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm"
                                   placeholder="Ex: Qualidade">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Peso</label>
                            <select name="itens[${itemCount}][peso]" class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm">
                                <option value="1.0">Normal (1.0)</option>
                                <option value="1.5">Importante (1.5)</option>
                                <option value="2.0">Crítico (2.0)</option>
                                <option value="0.5">Opcional (0.5)</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(novoItem);
            itemCount++;
            atualizarNumeracao();
        }

        function removerItem(botao) {
            const item = botao.closest('.item-checklist');
            const container = document.getElementById('itens-container');
            
            if (container.children.length > 1) {
                item.remove();
                atualizarNumeracao();
            } else {
                alert('Deve haver pelo menos um item no checklist.');
            }
        }

        function atualizarNumeracao() {
            const itens = document.querySelectorAll('.item-checklist');
            itens.forEach((item, index) => {
                const numero = item.querySelector('.text-muted-foreground');
                numero.textContent = `Item ${index + 1}`;
            });
        }
    </script>
</body>
</html>
