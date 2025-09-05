<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Auditoria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/css/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-background">
    <div class="max-w-4xl mx-auto py-6 px-4">
        <div class="bg-card rounded-lg border border-border p-6">
            <h1 class="text-xl font-bold text-foreground mb-4">🔧 Teste de Layout</h1>
            
            <div class="space-y-4">
                <div class="p-4 bg-muted rounded-lg">
                    <h2 class="font-medium text-foreground">Status do Sistema</h2>
                    <p class="text-sm text-muted-foreground">Este é um teste para verificar se o layout está funcionando corretamente.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-chart-1/10 rounded-lg">
                        <h3 class="font-medium text-chart-1">Teste 1</h3>
                        <p class="text-sm text-muted-foreground">Layout responsivo funcionando</p>
                    </div>
                    <div class="p-4 bg-chart-5/10 rounded-lg">
                        <h3 class="font-medium text-chart-5">Teste 2</h3>
                        <p class="text-sm text-muted-foreground">CSS carregando corretamente</p>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <h3 class="font-medium text-foreground">Teste de Barras de Progresso</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Teste 1</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-muted rounded-full h-2">
                                <div class="bg-accent h-2 rounded-full" style="width: 75%"></div>
                            </div>
                            <span class="text-sm font-medium text-foreground">75%</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Teste 2</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-muted rounded-full h-2">
                                <div class="bg-chart-5 h-2 rounded-full" style="width: 90%"></div>
                            </div>
                            <span class="text-sm font-medium text-foreground">90%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-border">
                <a href="index.php" class="inline-flex items-center px-4 py-2 bg-accent text-accent-foreground hover:bg-accent/90 rounded-lg transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Teste de layout carregado com sucesso');
            console.log('📊 Altura da página:', document.body.scrollHeight);
            console.log('📱 Altura da viewport:', window.innerHeight);
            
            // Verificar se há elementos problemáticos
            const problemElements = [];
            document.querySelectorAll('*').forEach(el => {
                if (el.offsetHeight > window.innerHeight * 3) {
                    problemElements.push({
                        element: el.tagName,
                        height: el.offsetHeight,
                        classes: el.className
                    });
                }
            });
            
            if (problemElements.length > 0) {
                console.warn('⚠️ Elementos com altura excessiva:', problemElements);
            } else {
                console.log('✅ Nenhum elemento problemático encontrado');
            }
        });
    </script>
</body>
</html>
