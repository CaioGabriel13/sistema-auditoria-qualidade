# QualiTrack - Sistema de Auditoria de Qualidade

**QualiTrack** é um sistema web moderno desenvolvido em PHP para gerenciamento completo de auditorias de qualidade, com foco em checklists de conformidade e acompanhamento de não-conformidades.

## 🚀 Instalação e Configuração

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior  
- Servidor web (Apache/Nginx) ou XAMPP
- Git

### 1. Clone o Repositório
```bash
git clone https://github.com/CaioGabriel13/sistema-auditoria-qualidade.git
cd sistema-auditoria-qualidade
```

### 2. Configuração do Banco de Dados
```bash
# Copie o arquivo de exemplo
cp config/database.example.php config/database.php

# Edite o arquivo config/database.php com suas credenciais
```

### 3. Criação do Banco de Dados
```bash
# Execute o script SQL no MySQL
mysql -u root -p < database/qualidade.sql

# (Opcional) Importar dados de exemplo
mysql -u root -p qualidade < database/dados_exemplo.sql
```

### 4. Configuração do Servidor Web

#### XAMPP
1. Coloque os arquivos em `htdocs/qualidade/`
2. Acesse: `http://localhost/qualidade/`

#### Apache/Nginx
1. Configure o DocumentRoot para a pasta do projeto
2. Certifique-se de que o PHP está habilitado

### 5. Primeiro Acesso
- **URL**: `http://localhost/qualidade/`
- **Usuário**: admin@qualidade.com
- **Senha**: password

---

## ✨ Características

- **Dashboard Interativo**: Visualização de métricas e estatísticas
- **Gestão de Auditorias**: Criação e execução de auditorias baseadas em checklists
- **Controle de Usuários**: Sistema de autenticação com diferentes níveis de acesso
- **Não-Conformidades**: Registro e acompanhamento de NCs com sistema de escalonamento
- **Relatórios**: Geração de relatórios de qualidade e aderência
- **Responsivo**: Interface adaptável para desktop e mobile

## 📋 Pré-requisitos

- XAMPP (Apache + MySQL + PHP 7.4+)
- Navegador web moderno
- 50MB de espaço em disco

## 🔧 Instalação no XAMPP

### Passo 1: Preparar o XAMPP
1. Instale e inicie o XAMPP
2. Ative os serviços **Apache** e **MySQL**
3. Acesse o painel de controle do XAMPP

### Passo 2: Instalar o Sistema
1. Extraia todos os arquivos para: `C:\xampp\htdocs\qualidade\`
2. Abra o navegador e acesse: `http://localhost/qualidade/install.php`
3. Execute o script de instalação
4. Aguarde a confirmação de sucesso

### Passo 3: Acessar o Sistema
1. Acesse: `http://localhost/qualidade/`
2. Use uma das contas de teste para login

## 👥 Usuários de Teste

| Tipo | Email | Senha | Permissões |
|------|-------|-------|------------|
| **Admin** | admin@qualidade.com | password | Acesso total |
| **Gerente** | joao.silva@empresa.com | password | Gestão + Auditoria |
| **Auditor** | maria.santos@empresa.com | password | Execução de auditorias |
| **Auditor** | pedro.costa@empresa.com | password | Execução de auditorias |

## 📁 Estrutura do Projeto

```
qualidade/
├── 📄 index.php              # Dashboard principal
├── 📄 login.php              # Página de login
├── 📄 logout.php             # Logout do sistema
├── 📄 install.php            # Script de instalação
├── 📄 create-audit.php       # Criação de auditorias
├── 📄 execute-audit.php      # Execução de auditorias
├── 📄 .htaccess              # Configurações Apache
├── 📁 assets/
│   └── 📁 css/
│       └── style.css         # Estilos CSS
├── 📁 config/
│   └── database.php          # Configuração do banco
├── 📁 includes/
│   └── auth.php              # Sistema de autenticação
└── 📁 database/
    └── qualidade.sql         # Estrutura do banco
```

## 🔍 Funcionalidades Principais

## 📊 Visualização de Dados com Dados Reais

O sistema agora utiliza **dados reais** do banco de dados para todas as visualizações:

### Dashboard Atualizado
- **Gráficos de aderência por mês**: Calculados a partir de auditorias concluídas
- **Barras de progresso dinâmicas**: Baseadas em percentuais reais de aderência
- **Estatísticas em tempo real**: Contadores atualizados automaticamente
- **Dados dos últimos 6 meses**: Visualização histórica baseada em dados reais

### Dados de Exemplo
Para testar o sistema com dados realistas:
1. Acesse: `importar-dados-exemplo.php`
2. Importe dados de exemplo (10 auditorias + 6 não-conformidades)
3. Visualize gráficos com dados reais no dashboard

### Dashboard
- Métricas de auditorias (total, pendentes, concluídas)
- Estatísticas de não-conformidades
- Gráficos de aderência
- Ações rápidas

### Gestão de Auditorias
- Criação baseada em modelos de checklist
- Atribuição de auditores e auditados
- Controle de status e progresso
- Registro de evidências

### Sistema de Qualidade
- Checklists customizáveis por tipo de artefato
- Cálculo automático de percentual de aderência
- Identificação automática de não-conformidades
- Sistema de classificação de severidade

### Controle de Acesso
- **Administrador**: Acesso total ao sistema
- **Gerente**: Gestão de usuários e relatórios
- **Auditor**: Execução de auditorias

## 🛠️ Configuração Avançada

### Banco de Dados
O arquivo `config/database.php` contém as configurações de conexão:
```php
private $host = 'localhost';
private $db_name = 'qualidade';
private $username = 'root';
private $password = '';
```

### Personalização
- Modifique `assets/css/style.css` para personalizar a aparência
- Ajuste `database/qualidade.sql` para incluir dados específicos da empresa
- Configure `.htaccess` para melhorar a segurança

## 🔒 Segurança

### Recursos Implementados
- ✅ Senhas hasheadas com bcrypt
- ✅ Proteção contra SQL Injection (PDO)
- ✅ Sanitização de dados de entrada
- ✅ Controle de sessão PHP
- ✅ Proteção de arquivos sensíveis (.htaccess)

### Recomendações Adicionais
- Altere as senhas padrão em produção
- Configure HTTPS para ambientes de produção
- Implemente backup automático do banco de dados
- Configure logs de auditoria para ações críticas

## 📊 Banco de Dados

### Tabelas Principais
- `usuarios` - Gestão de usuários e permissões
- `modelos_checklist` - Templates de auditoria
- `itens_checklist` - Questões dos checklists
- `auditorias` - Registros de auditorias
- `respostas_auditoria` - Respostas dos checklists
- `nao_conformidades` - Registro de NCs
- `historico_escalonamento` - Rastreamento de escalonamentos

## 🐛 Solução de Problemas

### Erro de Conexão com Banco
1. Verifique se o MySQL está rodando no XAMPP
2. Confirme as credenciais em `config/database.php`
3. Execute novamente `install.php`

### Página em Branco
1. Ative a exibição de erros PHP no `php.ini`
2. Verifique os logs do Apache em `xampp/apache/logs/`
3. Confirme as permissões dos arquivos

### Problemas de CSS
1. Verifique se o arquivo `assets/css/style.css` existe
2. Confirme se o Apache está servindo arquivos estáticos
3. Limpe o cache do navegador

## 📈 Próximos Passos

### Funcionalidades Planejadas
- [ ] Módulo de relatórios avançados
- [ ] Sistema de notificações por email
- [ ] API REST para integração
- [ ] Dashboard de métricas em tempo real
- [ ] Módulo de treinamento e capacitação

### Melhorias de Performance
- [ ] Cache de consultas frequentes
- [ ] Otimização de queries do banco
- [ ] Compressão de assets
- [ ] CDN para recursos estáticos

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique a documentação acima
2. Consulte os logs de erro
3. Execute o `install.php` novamente se necessário

---

**Desenvolvido para facilitar o processo de auditoria de qualidade em organizações de qualquer porte.**
