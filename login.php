<?php
require_once 'includes/auth.php';

if (estaLogado()) {
    header('Location: index.php');
    exit();
}

$erro = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (fazerLogin($email, $senha)) {
        header('Location: index.php');
        exit();
    } else {
        $erro = 'Email ou senha inválidos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Auditoria de Qualidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen bg-background flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 bg-accent rounded-lg flex items-center justify-center mb-4">
                <svg class="h-6 w-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-foreground">Sistema de Auditoria</h2>
            <p class="mt-2 text-muted-foreground">Faça login para acessar o sistema</p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <?php if ($erro): ?>
                <div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-foreground">Email</label>
                    <input id="email" name="email" type="email" required 
                           class="mt-1 block w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                           placeholder="seu@email.com">
                </div>
                
                <div>
                    <label for="senha" class="block text-sm font-medium text-foreground">Senha</label>
                    <input id="senha" name="senha" type="password" required 
                           class="mt-1 block w-full px-3 py-2 bg-input border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                           placeholder="Sua senha">
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-primary-foreground bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                Entrar
            </button>
            
            <div class="text-center text-sm text-muted-foreground">
                <p>Usuários de teste:</p>
                <p><strong>Admin:</strong> admin@qualidade.com / password</p>
                <p><strong>Auditor:</strong> maria.santos@empresa.com / password</p>
            </div>
        </form>
    </div>
</body>
</html>