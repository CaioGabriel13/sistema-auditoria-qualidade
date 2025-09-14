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

// Buscar usuários para lista de superiores
$query = "SELECT id, nome, funcao, departamento FROM usuarios WHERE funcao IN ('gerente', 'admin') ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$superiores = $stmt->fetchAll();

if ($_POST) {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $funcao = $_POST['funcao'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $superior_id = $_POST['superior_id'] ?? null;
    
    if ($nome && $email && $senha && $funcao) {
        if ($senha !== $confirmar_senha) {
            $erro = "As senhas não coincidem.";
        } else {
            try {
                // Verificar se email já existe
                $query = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $erro = "Este email já está em uso.";
                } else {
                    // Criar usuário
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    $query = "INSERT INTO usuarios (nome, email, senha, funcao, departamento, superior_id) 
                              VALUES (:nome, :email, :senha, :funcao, :departamento, :superior_id)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nome', $nome);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':senha', $senha_hash);
                    $stmt->bindParam(':funcao', $funcao);
                    $stmt->bindParam(':departamento', $departamento);
                    $stmt->bindParam(':superior_id', $superior_id);
                    
                    if ($stmt->execute()) {
                        $sucesso = "Usuário criado com sucesso!";
                        
                        // Redirecionar após 2 segundos
                        header("refresh:2;url=usuarios.php");
                    }
                }
            } catch (Exception $e) {
                $erro = "Erro ao criar usuário: " . $e->getMessage();
            }
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
    <title>Novo Usuário - QualiTrack</title>
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
                    <h1 class="text-xl font-bold text-foreground">Novo Usuário</h1>
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
                <div class="mt-2 text-sm">Redirecionando para a lista de usuários...</div>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <div class="bg-card rounded-lg border border-border p-6">
            <form method="POST" class="space-y-6">
                <!-- Informações Pessoais -->
                <div>
                    <h2 class="text-lg font-medium text-foreground mb-4">Informações Pessoais</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-foreground mb-2">
                                Nome Completo *
                            </label>
                            <input type="text" id="nome" name="nome" required
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Digite o nome completo">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-foreground mb-2">
                                Email *
                            </label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="usuario@empresa.com">
                        </div>
                    </div>
                </div>

                <!-- Credenciais -->
                <div>
                    <h2 class="text-lg font-medium text-foreground mb-4">Credenciais de Acesso</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="senha" class="block text-sm font-medium text-foreground mb-2">
                                Senha *
                            </label>
                            <input type="password" id="senha" name="senha" required minlength="6"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Mínimo 6 caracteres">
                        </div>

                        <div>
                            <label for="confirmar_senha" class="block text-sm font-medium text-foreground mb-2">
                                Confirmar Senha *
                            </label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Digite a senha novamente">
                        </div>
                    </div>
                </div>

                <!-- Informações Organizacionais -->
                <div>
                    <h2 class="text-lg font-medium text-foreground mb-4">Informações Organizacionais</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="funcao" class="block text-sm font-medium text-foreground mb-2">
                                Função *
                            </label>
                            <select id="funcao" name="funcao" required
                                    class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                                <option value="">Selecione uma função</option>
                                <option value="auditor">Auditor</option>
                                <option value="gerente">Gerente</option>
                                <?php if ($usuario['funcao'] === 'admin'): ?>
                                    <option value="admin">Administrador</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label for="departamento" class="block text-sm font-medium text-foreground mb-2">
                                Departamento
                            </label>
                            <input type="text" id="departamento" name="departamento"
                                   class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   placeholder="Ex: Qualidade, TI, Desenvolvimento">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="superior_id" class="block text-sm font-medium text-foreground mb-2">
                            Superior Hierárquico
                        </label>
                        <select id="superior_id" name="superior_id"
                                class="w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <option value="">Selecione (opcional)</option>
                            <?php foreach ($superiores as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>">
                                    <?php echo htmlspecialchars($sup['nome'] . ' - ' . ucfirst($sup['funcao']) . ' (' . $sup['departamento'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Informações de Permissões -->
                <div class="bg-muted rounded-lg p-4">
                    <h3 class="text-sm font-medium text-foreground mb-2">Permissões por Função</h3>
                    <div class="text-xs text-muted-foreground space-y-1">
                        <div><strong>Auditor:</strong> Execução de auditorias e visualização de NCs</div>
                        <div><strong>Gerente:</strong> Todas as permissões do Auditor + gestão de modelos e usuários</div>
                        <div><strong>Administrador:</strong> Acesso total ao sistema</div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="usuarios.php" 
                       class="px-4 py-2 border border-border text-foreground bg-background hover:bg-muted rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                        Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Validação de senha em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const senha = document.getElementById('senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            
            function validarSenhas() {
                if (senha.value && confirmarSenha.value) {
                    if (senha.value === confirmarSenha.value) {
                        confirmarSenha.classList.remove('border-destructive');
                        confirmarSenha.classList.add('border-chart-5');
                    } else {
                        confirmarSenha.classList.remove('border-chart-5');
                        confirmarSenha.classList.add('border-destructive');
                    }
                }
            }
            
            senha.addEventListener('input', validarSenhas);
            confirmarSenha.addEventListener('input', validarSenhas);
            
            // Validação do formulário
            document.querySelector('form').addEventListener('submit', function(e) {
                if (senha.value !== confirmarSenha.value) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    confirmarSenha.focus();
                }
            });
        });
    </script>
</body>
</html>
