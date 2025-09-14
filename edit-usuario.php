<?php
require_once 'includes/auth.php';
requerLogin();

if (!podeGerenciar()) {
    header('Location: index.php');
    exit();
}

$usuario_atual = getUsuarioAtual();
$id_usuario = $_GET['id'] ?? 0;

require_once 'config/database.php';
$db = getConexao();

$sucesso = '';
$erro = '';

// Buscar dados do usuário
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_usuario);
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: usuarios.php');
    exit();
}

// Não permitir editar a própria conta através desta página
if ($usuario['id'] == $usuario_atual['id']) {
    header('Location: usuarios.php');
    exit();
}

// Buscar possíveis superiores (usuários com função gerente ou admin, exceto o próprio usuário)
$query = "SELECT id, nome, funcao FROM usuarios WHERE funcao IN ('gerente', 'admin') AND id != :id ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_usuario);
$stmt->execute();
$superiores = $stmt->fetchAll();

// Processar formulário
if ($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $funcao = $_POST['funcao'] ?? '';
    $departamento = trim($_POST['departamento'] ?? '');
    $superior_id = $_POST['superior_id'] ?? null;
    $nova_senha = trim($_POST['nova_senha'] ?? '');
    
    // Validações
    if (empty($nome) || empty($email) || empty($funcao)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Por favor, forneça um email válido.";
    } elseif (!in_array($funcao, ['admin', 'gerente', 'auditor'])) {
        $erro = "Função inválida selecionada.";
    } else {
        try {
            // Verificar se email já existe (exceto para o próprio usuário)
            $query = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id_usuario);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $erro = "Este email já está em uso por outro usuário.";
            } else {
                // Preparar query de atualização
                if (!empty($nova_senha)) {
                    // Atualizar com nova senha
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $query = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, 
                             funcao = :funcao, departamento = :departamento, superior_id = :superior_id, 
                             atualizado_em = NOW() WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':senha', $senha_hash);
                } else {
                    // Atualizar sem alterar senha
                    $query = "UPDATE usuarios SET nome = :nome, email = :email, 
                             funcao = :funcao, departamento = :departamento, superior_id = :superior_id, 
                             atualizado_em = NOW() WHERE id = :id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':funcao', $funcao);
                $stmt->bindParam(':departamento', $departamento);
                $stmt->bindParam(':superior_id', $superior_id);
                $stmt->bindParam(':id', $id_usuario);
                
                if ($stmt->execute()) {
                    $sucesso = "Usuário atualizado com sucesso!";
                    
                    // Atualizar dados do usuário para exibir na página
                    $usuario['nome'] = $nome;
                    $usuario['email'] = $email;
                    $usuario['funcao'] = $funcao;
                    $usuario['departamento'] = $departamento;
                    $usuario['superior_id'] = $superior_id;
                } else {
                    $erro = "Erro ao atualizar usuário. Tente novamente.";
                }
            }
        } catch (Exception $e) {
            $erro = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - QualiTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <header class="bg-card border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="usuarios.php" class="h-8 w-8 bg-accent rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Editar Usuário</h1>
                        <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($usuario['nome']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-muted-foreground">Olá, <?php echo htmlspecialchars($usuario_atual['nome']); ?></span>
                    <a href="logout.php" class="text-sm text-destructive hover:text-destructive/80">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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

        <div class="bg-card rounded-lg border border-border p-6">
            <div class="flex items-center mb-6">
                <div class="h-12 w-12 bg-accent rounded-full flex items-center justify-center mr-4">
                    <span class="text-lg font-bold text-accent-foreground">
                        <?php echo strtoupper(substr($usuario['nome'], 0, 2)); ?>
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-medium text-foreground">Editar Informações</h2>
                    <p class="text-sm text-muted-foreground">Atualize os dados do usuário</p>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-foreground mb-2">
                            Nome Completo *
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               value="<?php echo htmlspecialchars($usuario['nome']); ?>"
                               required 
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-foreground mb-2">
                            Email *
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($usuario['email']); ?>"
                               required 
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="funcao" class="block text-sm font-medium text-foreground mb-2">
                            Função *
                        </label>
                        <select id="funcao" 
                                name="funcao" 
                                required 
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                            <option value="">Selecione uma função</option>
                            <option value="admin" <?php echo $usuario['funcao'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="gerente" <?php echo $usuario['funcao'] === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                            <option value="auditor" <?php echo $usuario['funcao'] === 'auditor' ? 'selected' : ''; ?>>Auditor</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="departamento" class="block text-sm font-medium text-foreground mb-2">
                            Departamento
                        </label>
                        <input type="text" 
                               id="departamento" 
                               name="departamento" 
                               value="<?php echo htmlspecialchars($usuario['departamento'] ?? ''); ?>"
                               placeholder="Ex: Qualidade, Produção, RH..."
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                    </div>
                </div>

                <div>
                    <label for="superior_id" class="block text-sm font-medium text-foreground mb-2">
                        Superior Hierárquico
                    </label>
                    <select id="superior_id" 
                            name="superior_id" 
                            class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                        <option value="">Nenhum superior</option>
                        <?php foreach ($superiores as $superior): ?>
                            <option value="<?php echo $superior['id']; ?>" 
                                    <?php echo $usuario['superior_id'] == $superior['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($superior['nome']); ?> 
                                (<?php echo ucfirst($superior['funcao']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="border-t border-border pt-6">
                    <h3 class="text-lg font-medium text-foreground mb-4">Alterar Senha</h3>
                    <p class="text-sm text-muted-foreground mb-4">Deixe em branco para manter a senha atual</p>
                    
                    <div>
                        <label for="nova_senha" class="block text-sm font-medium text-foreground mb-2">
                            Nova Senha
                        </label>
                        <input type="password" 
                               id="nova_senha" 
                               name="nova_senha" 
                               minlength="6"
                               placeholder="Mínimo 6 caracteres"
                               class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent text-foreground">
                    </div>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-border">
                    <div class="flex space-x-4">
                        <a href="usuarios.php" 
                           class="px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg transition-colors">
                            Cancelar
                        </a>
                        <a href="view-usuario.php?id=<?php echo $usuario['id']; ?>" 
                           class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                            Visualizar
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="px-6 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-6 bg-muted rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-chart-1 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-sm text-muted-foreground">
                    <p class="font-medium mb-1">Informações importantes:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Alterações na função podem afetar as permissões do usuário</li>
                        <li>O superior hierárquico deve ter função de gerente ou administrador</li>
                        <li>A senha atual será mantida se o campo "Nova Senha" estiver vazio</li>
                        <li>Usuário criado em: <?php echo date('d/m/Y H:i', strtotime($usuario['criado_em'])); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const funcao = document.getElementById('funcao').value;
            
            if (!nome || !email || !funcao) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const novaSenha = document.getElementById('nova_senha').value;
            if (novaSenha && novaSenha.length < 6) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 6 caracteres.');
                return;
            }
        });
    </script>
</body>
</html>
