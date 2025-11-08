# Configuração da Hostinger

Guia completo para configurar o projeto na hospedagem compartilhada da Hostinger.

## 1. Configurar Banco de Dados MySQL

### Passo 1: Criar Banco de Dados

1. Acesse **hpanel.hostinger.com**
2. Selecione sua hospedagem
3. Vá em **Bancos de Dados** → **MySQL Databases**
4. Clique em **Criar novo banco de dados**
5. Anote as credenciais:
   ```
   Nome do banco: u320952164_inscricao (exemplo)
   Usuário: u320952164_inscricao (exemplo)
   Senha: [sua senha]
   Host: localhost
   ```

### Passo 2: Importar Estrutura do Banco

1. No hPanel, vá em **Bancos de Dados** → **phpMyAdmin**
2. Selecione seu banco de dados
3. Clique em **Importar**
4. Faça upload do arquivo `migrations/schema.sql` (ou execute as migrations)

Ou execute via SSH (se disponível):
```bash
mysql -u u320952164_inscricao -p u320952164_inscricao < migrations/schema.sql
```

### Passo 3: Configurar Credenciais

1. Acesse via FTP ou Gerenciador de Arquivos
2. Navegue até `/public_html/config/`
3. Edite o arquivo `database.php`
4. Configure com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u320952164_inscricao');  // Seu usuário
define('DB_PASS', 'sua_senha_aqui');        // Sua senha
define('DB_NAME', 'u320952164_inscricao');  // Nome do banco
```

## 2. Configurar Estrutura de Pastas

### Estrutura Esperada na Hostinger:

```
/public_html/
├── admin/
├── api/
├── config/
│   ├── config.php
│   └── database.php  ← Configure este arquivo!
├── equipe/
├── includes/
├── migrations/
├── publico/
│   └── index.php  ← Página inicial
└── public/
    └── uploads/  ← Precisa ter permissão 755
```

### Configurar Permissões

Via FTP/Gerenciador de Arquivos, defina permissões:
```
/public_html/public/uploads/          → 755
/public_html/public/uploads/banners/  → 755
/public_html/public/uploads/fotos/    → 755
/public_html/public/uploads/documentos/ → 755
```

## 3. Configurar URL Base

Edite `/config/config.php`:

```php
define('BASE_URL', 'https://mediumblue-rhinoceros-869852.hostingersite.com');
```

Ou se tiver domínio próprio:
```php
define('BASE_URL', 'https://seudominio.com');
```

## 4. Executar Migrations

### Opção A: Via Navegador
Acesse: `https://seudominio.com/migrations/run_migrations.php`

### Opção B: Via SSH (se disponível)
```bash
cd /home/u320952164/domains/seudominio.com/public_html
php migrations/run_migrations.php
```

## 5. Configurar Arquivo .htaccess (Opcional)

Crie `/public_html/.htaccess` para melhorar segurança:

```apache
# Desabilitar listagem de diretórios
Options -Indexes

# Proteção de arquivos sensíveis
<FilesMatch "(\.env|config\.php|database\.php)$">
    Require all denied
</FilesMatch>

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300

# Redirecionamento HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 6. Criar Usuário Admin Inicial

Execute no phpMyAdmin:

```sql
-- Senha: admin123 (MUDE DEPOIS!)
INSERT INTO administradores (nome, email, senha, nivel_acesso, ativo, data_cadastro)
VALUES (
    'Administrador',
    'admin@seudominio.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Super Admin',
    1,
    NOW()
);
```

**⚠️ IMPORTANTE:** Mude a senha após primeiro login!

## 7. Testar Funcionamento

1. Acesse `https://seudominio.com/publico/index.php`
2. Deve aparecer a página inicial
3. Teste login admin: `https://seudominio.com/admin/login.php`
4. Teste login equipe: `https://seudominio.com/equipe/login.php`

## 8. Verificar Logs de Erro

Se algo não funcionar, verifique logs:

1. No hPanel, vá em **Arquivos** → **Gerenciador de Arquivos**
2. Procure por `error_log` em:
   - `/public_html/error_log`
   - `/public_html/publico/error_log`

## 9. Configuração de Email (Opcional)

Para envio de emails (notificações, recuperação de senha):

Edite `/config/config.php` e adicione:
```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@seudominio.com');
define('SMTP_PASS', 'sua_senha_email');
define('SMTP_FROM', 'noreply@seudominio.com');
define('SMTP_FROM_NAME', 'Sistema de Competições');
```

## 10. Backup Automático (Recomendado)

Configure backup automático no hPanel:
1. Vá em **Arquivos** → **Backups**
2. Configure backup semanal ou diário
3. Guarde backups do banco de dados também

## Solução de Problemas Comuns

### Erro: "Call to a member function on null"
- Verifique credenciais do banco em `config/database.php`
- Teste conexão via phpMyAdmin

### Erro: "Permission denied" em uploads
- Configure permissão 755 na pasta `/public/uploads/`
- Verifique proprietário dos arquivos

### Erro: "File not found" ou 404
- Verifique estrutura de pastas
- Confirme que arquivos foram enviados para `/public_html/`

### Página em branco
- Ative exibição de erros temporariamente:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Verifique `error_log`

## Segurança Adicional

1. **SSL/HTTPS**: Ative certificado SSL gratuito no hPanel
2. **Senhas fortes**: Use senhas complexas para banco e admin
3. **Atualizações**: Mantenha o PHP atualizado (verifique versão no hPanel)
4. **Backups**: Configure backups automáticos
5. **Firewall**: Use Cloudflare (gratuito) como camada extra

## Suporte

Se precisar de ajuda:
- Chat Hostinger: 24/7 disponível
- Base de conhecimento: support.hostinger.com
- GitHub Issues: Para problemas do código
