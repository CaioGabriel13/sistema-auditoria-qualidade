// Configuração personalizada do Tailwind CSS para o Sistema de Auditoria
tailwind.config = {
  theme: {
    extend: {
      colors: {
        // Cores do tema de qualidade
        'background': '#f8fafc',
        'foreground': '#1f2937',
        'card': '#ffffff',
        'border': '#e5e7eb',
        'input': '#ffffff',
        'muted': '#f3f4f6',
        'muted-foreground': '#6b7280',
        'accent': '#8b5cf6',
        'accent-foreground': '#ffffff',
        'destructive': '#ef4444',
        'primary': '#1f2937',
        'primary-foreground': '#ffffff',
        'secondary': '#6b7280',
        'secondary-foreground': '#ffffff',
        'sidebar': '#f9fafb',
        'sidebar-foreground': '#6b7280',
        'sidebar-accent': '#8b5cf6',
        'sidebar-border': '#e5e7eb',
        'chart': {
          1: '#8b5cf6',
          2: '#06b6d4',
          3: '#f59e0b',
          4: '#10b981',
          5: '#84cc16'
        }
      },
      fontFamily: {
        'sans': ['Segoe UI', 'system-ui', 'sans-serif']
      }
    }
  }
};
