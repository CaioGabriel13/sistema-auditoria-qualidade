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

// Buscar itens do checklist
$query = "SELECT * FROM itens_checklist WHERE modelo_id = :modelo_id ORDER BY indice_ordem, id";
$stmt = $db->prepare($query);
$stmt->bindParam(':modelo_id', $auditoria['modelo_id']);
$stmt->execute();
$itens = $stmt->fetchAll();

// Buscar respostas existentes
$query = "SELECT * FROM respostas_auditoria WHERE auditoria_id = :id_auditoria";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_auditoria', $id_auditoria);
$stmt->execute();
$respostas = [];
foreach ($stmt->fetchAll() as $resposta) {
    $respostas[$resposta['item_id']] = $resposta;
}

$sucesso = '';
$erro = '';

if ($_POST) {
    try {
        $db->beginTransaction();
        
        // Atualizar status da auditoria
        $query = "UPDATE auditorias SET status = 'em_progresso' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_auditoria);
        $stmt->execute();
        
        // Processar respostas
        $total_itens = 0;
        $itens_conformes = 0;
        $nao_conformidades = [];
        
        foreach ($itens as $item) {
            $resposta = $_POST['resposta_' . $item['id']] ?? '';
            $comentarios = $_POST['comentarios_' . $item['id']] ?? '';
            
            if ($resposta) {
                // Deletar resposta existente
                $query = "DELETE FROM respostas_auditoria WHERE auditoria_id = :id_auditoria AND item_id = :item_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_auditoria', $id_auditoria);
                $stmt->bindParam(':item_id', $item['id']);
                $stmt->execute();
                
                // Inserir nova resposta
                $query = "INSERT INTO respostas_auditoria (auditoria_id, item_id, resposta, comentarios) 
                          VALUES (:id_auditoria, :item_id, :resposta, :comentarios)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_auditoria', $id_auditoria);
                $stmt->bindParam(':item_id', $item['id']);
                $stmt->bindParam(':resposta', $resposta);
                $stmt->bindParam(':comentarios', $comentarios);
                $stmt->execute();
                
                // Calcular aderência (não contar N/A)
                if ($resposta !== 'na') {
                    $total_itens++;
                    if ($resposta === 'sim') {
                        $itens_conformes++;
                    } else {
                        // Criar não conformidade
                        $titulo_nc = "NC - " . substr($item['questao'], 0, 50) . "...";
                        $descricao_nc = $item['questao'];
                        if ($comentarios) {
                            $descricao_nc .= "\n\nComentários: " . $comentarios;
                        }
                        
                        $classificacao = 'media'; // Padrão
                        $data_vencimento = date('Y-m-d', strtotime('+7 days')); // 7 dias padrão
                        
                        $responsavel_id = $auditoria['auditado_id'] ?? $usuario['id'];
                        
                        $query = "INSERT INTO nao_conformidades (auditoria_id, item_id, titulo, descricao, classificacao, responsavel_id, data_vencimento, criado_por) 
                                  VALUES (:id_auditoria, :item_id, :titulo, :descricao, :classificacao, :responsavel_id, :data_vencimento, :criado_por)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id_auditoria', $id_auditoria);
                        $stmt->bindParam(':item_id', $item['id']);
                        $stmt->bindParam(':titulo', $titulo_nc);
                        $stmt->bindParam(':descricao', $descricao_nc);
                        $stmt->bindParam(':classificacao', $classificacao);
                        $stmt->bindParam(':responsavel_id', $responsavel_id);
                        $stmt->bindParam(':data_vencimento', $data_vencimento);
                        $stmt->bindParam(':criado_por', $usuario['id']);
                        $stmt->execute();
                        
                        $nao_conformidades[] = $db->lastInsertId();
                    }
                }
            }
        }
        
        // Calcular % de aderência
        $percentual_aderencia = $total_itens > 0 ? ($itens_conformes / $total_itens) * 100 : 0;
        
        // Atualizar auditoria com resultado
        $query = "UPDATE auditorias SET status = 'completo', data_completa = CURDATE(), percentual_adesao = :aderencia 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':aderencia', $percentual_aderencia);
        $stmt->bindParam(':id', $id_auditoria);
        $stmt->execute();
        
        $db->commit();
        
        $sucesso = "Auditoria concluída com sucesso! Aderência: " . number_format($percentual_aderencia, 1) . "%";
        
        // Enviar comunicações das NCs
        if (!empty($nao_conformidades)) {
            // Aqui seria implementado o envio de emails
            $sucesso .= " " . count($nao_conformidades) . " não conformidade(s) identificada(s).";
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $erro = 'Erro ao salvar auditoria: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Auditoria - <?php echo htmlspecialchars($auditoria['titulo']); ?></title>
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
                        <h1 class="text-xl font-bold text-foreground"><?php echo htmlspecialchars($auditoria['titulo']); ?></h1>
                        <p class="text-sm text-muted-foreground">Modelo: <?php echo htmlspecialchars($auditoria['nome_modelo']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?php if ($sucesso): ?>
            <div class="mb-6 bg-chart-5/10 border border-chart-5/20 text-chart-5 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($sucesso); ?>
                <div class="mt-2">
                    <a href="auditorias.php" class="text-sm underline">Voltar para lista de auditorias</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Auditor</p>
                    <p class="text-foreground"><?php echo htmlspecialchars($auditoria['nome_auditor']); ?></p>
                </div>
                <?php if ($auditoria['nome_auditado']): ?>
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Auditado</p>
                    <p class="text-foreground"><?php echo htmlspecialchars($auditoria['nome_auditado']); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Data Planejada</p>
                    <p class="text-foreground"><?php echo date('d/m/Y', strtotime($auditoria['data_planejada'])); ?></p>
                </div>
            </div>
            
            <?php if ($auditoria['nome_artefato']): ?>
            <div class="mt-4 pt-4 border-t border-border">
                <p class="text-sm font-medium text-muted-foreground">Artefato Auditado</p>
                <p class="text-foreground">
                    <?php echo htmlspecialchars($auditoria['nome_artefato']); ?>
                    <?php if ($auditoria['versao_artefato']): ?>
                        (<?php echo htmlspecialchars($auditoria['versao_artefato']); ?>)
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="space-y-6">
            <div class="bg-card rounded-lg border border-border">
                <div class="p-6 border-b border-border">
                    <h2 class="text-lg font-medium text-foreground">Checklist de Auditoria</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Responda cada item do checklist. Use "Sim" para conformidade, "Não" para não conformidade, e "N/A" para não aplicável.
                    </p>
                </div>
                
                <div class="divide-y divide-border">
                    <?php foreach ($itens as $index => $item): ?>
                    <div class="p-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-muted-foreground"><?php echo $index + 1; ?></span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="mb-4">
                                    <p class="text-foreground font-medium mb-2"><?php echo htmlspecialchars($item['questao']); ?></p>
                                    <?php if ($item['categoria']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent">
                                            <?php echo htmlspecialchars($item['categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" name="resposta_<?php echo $item['id']; ?>" value="sim" 
                                                   class="h-4 w-4 text-chart-5 focus:ring-chart-5 border-border"
                                                   <?php echo (isset($respostas[$item['id']]) && $respostas[$item['id']]['resposta'] === 'sim') ? 'checked' : ''; ?>>
                                            <span class="ml-2 text-sm text-foreground">Sim (Conforme)</span>
                                        </label>
                                        
                                        <label class="flex items-center">
                                            <input type="radio" name="resposta_<?php echo $item['id']; ?>" value="nao" 
                                                   class="h-4 w-4 text-destructive focus:ring-destructive border-border"
                                                   <?php echo (isset($respostas[$item['id']]) && $respostas[$item['id']]['resposta'] === 'nao') ? 'checked' : ''; ?>>
                                            <span class="ml-2 text-sm text-foreground">Não (Não Conforme)</span>
                                        </label>
                                        
                                        <label class="flex items-center">
                                            <input type="radio" name="resposta_<?php echo $item['id']; ?>" value="na" 
                                                   class="h-4 w-4 text-secondary focus:ring-secondary border-border"
                                                   <?php echo (isset($respostas[$item['id']]) && $respostas[$item['id']]['resposta'] === 'na') ? 'checked' : ''; ?>>
                                            <span class="ml-2 text-sm text-foreground">N/A (Não Aplicável)</span>
                                        </label>
                                    </div>
                                    
                                    <div>
                                        <label for="comentarios_<?php echo $item['id']; ?>" class="block text-sm font-medium text-muted-foreground mb-1">
                                            Comentários/Evidências
                                        </label>
                                        <textarea id="comentarios_<?php echo $item['id']; ?>" name="comentarios_<?php echo $item['id']; ?>" rows="2"
                                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-sm"
                                                   placeholder="Adicione comentários, evidências ou justificativas..."><?php echo isset($respostas[$item['id']]) ? htmlspecialchars($respostas[$item['id']]['comentarios']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="p-6 bg-muted border-t border-border">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-muted-foreground">
                            Total de itens: <?php echo count($itens); ?>
                        </div>
                        
                        <div class="flex space-x-4">
                            <a href="auditorias.php" 
                               class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors font-medium">
                                Finalizar Auditoria
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
        // Funcionalidade de auto-salvar (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, textarea');
            
            // Destacar itens não conformes
            inputs.forEach(input => {
                if (input.type === 'radio' && input.value === 'nao') {
                    input.addEventListener('change', function() {
                        if (this.checked) {
                            this.closest('.p-6').classList.add('bg-destructive/5', 'border-l-4', 'border-l-destructive');
                        }
                    });
                }
                
                if (input.type === 'radio' && (input.value === 'sim' || input.value === 'na')) {
                    input.addEventListener('change', function() {
                        if (this.checked) {
                            this.closest('.p-6').classList.remove('bg-destructive/5', 'border-l-4', 'border-l-destructive');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>