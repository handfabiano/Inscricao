# 🔧 COMO CONFIGURAR O BANCO DE DADOS NO HOSTINGER

## Passo 1: Pegar as Credenciais no Painel Hostinger

1. Entre no **painel do Hostinger** (hpanel.hostinger.com)
2. Vá em **Databases** (Bancos de Dados) no menu lateral
3. Selecione seu banco de dados ou crie um novo
4. Anote estas informações:

```
Host: localhost (ou mysql.hostinger.com)
Database Name: u320952164_inscricao (exemplo)
Username: u320952164_admin (exemplo)
Password: sua_senha_aqui
```

**IMPORTANTE:** O número `u320952164` é o ID da sua conta Hostinger.

---

## Passo 2: Atualizar o arquivo config/database.php

Você tem 2 opções:

### OPÇÃO A: Editar pelo File Manager do Hostinger

1. No painel Hostinger, vá em **Files** > **File Manager**
2. Navegue até: `public_html/config/database.php`
3. Clique em **Edit**
4. Substitua as linhas 3-6 pelas suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u320952164_admin');  // SEU USUÁRIO AQUI
define('DB_PASS', 'SuaSenhaAqui');      // SUA SENHA AQUI
define('DB_NAME', 'u320952164_inscricao'); // SEU BANCO AQUI
```

5. Clique em **Save**

---

### OPÇÃO B: Editar via SSH (se tiver acesso)

```bash
cd ~/public_html/config
nano database.php
```

Altere as credenciais e salve (Ctrl+X, Y, Enter).

---

## Passo 3: Criar o Banco de Dados (se ainda não criou)

No painel Hostinger:

1. Vá em **Databases** > **MySQL Databases**
2. Clique em **Create New Database**
3. Nome sugerido: `inscricao` ou `inscricao_atletas`
4. Crie um usuário ou use o existente
5. Dê **todas as permissões** ao usuário neste banco
6. Anote as credenciais!

---

## Passo 4: Importar o Banco (se tiver backup)

Se você tem um arquivo `.sql` de backup:

1. No painel Hostinger, vá em **Databases**
2. Clique em **Enter phpMyAdmin**
3. Selecione seu banco de dados
4. Clique na aba **Import**
5. Escolha seu arquivo `.sql` e clique em **Go**

---

## Passo 5: Executar as Migrations

Depois de configurar o banco, execute as migrations:

**Via Navegador:**
```
https://mediumblue-rhinoceros-869852.hostingersite.com/migrations/run_migrations.php
```

**Via SSH:**
```bash
cd ~/public_html
php migrations/run_migrations.php
```

Isso criará todas as 55 tabelas necessárias!

---

## ✅ Verificar se Funcionou

Depois de tudo configurado, acesse novamente:
```
https://mediumblue-rhinoceros-869852.hostingersite.com/test_error.php
```

Deve mostrar:
```
✓ CONEXÃO COM BANCO OK!
Total de tabelas: 55
```

---

## 🔒 SEGURANÇA

Depois de tudo funcionando, **DELETE ESTES ARQUIVOS:**
```
phpinfo.php
test_error.php
```

São apenas para diagnóstico e não devem ficar em produção!

---

## 🆘 Problemas Comuns

### Erro: "Access Denied"
- Verifique usuário e senha
- Certifique-se que o usuário tem permissão no banco

### Erro: "Unknown database"
- Crie o banco de dados no painel
- Verifique se o nome está correto (case-sensitive!)

### Erro: "Can't connect to MySQL server"
- Verifique o host (pode ser `localhost` ou `mysql.hostinger.com`)
- Verifique se o MySQL está ativo no plano

### Timeout ao executar migrations
- Execute via SSH em vez do navegador
- Ou execute migration por migration manualmente

---

**Precisa de ajuda?** Me avise qual erro apareceu!
