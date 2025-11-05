# Guia de Instalação - Sistema de Inscrição de Atletas

Este guia fornece instruções detalhadas para instalar e configurar o sistema.

## Índice
1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Instalação em Servidor Local](#instalação-em-servidor-local)
3. [Instalação em Servidor de Produção](#instalação-em-servidor-de-produção)
4. [Configuração Pós-Instalação](#configuração-pós-instalação)
5. [Solução de Problemas](#solução-de-problemas)

---

## Requisitos do Sistema

### Mínimos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache 2.4 ou Nginx 1.18
- 100 MB de espaço em disco
- SSL/HTTPS (recomendado para produção)

### Extensões PHP Necessárias
- pdo
- pdo_mysql
- mbstring
- json
- fileinfo
- gd (opcional, para manipulação de imagens)

### Verificar Extensões Instaladas
```bash
php -m
```

---

## Instalação em Servidor Local

### Opção 1: XAMPP (Windows/Mac/Linux)

1. **Baixe e instale o XAMPP**
   - Download: https://www.apachefriends.org/

2. **Inicie Apache e MySQL**
   - Abra o painel de controle do XAMPP
   - Inicie os serviços Apache e MySQL

3. **Copie os arquivos**
   ```bash
   cp -r Inscricao /xampp/htdocs/
   ```

4. **Crie o banco de dados**
   - Acesse http://localhost/phpmyadmin
   - Clique em "Novo"
   - Nome: `inscricao_atletas`
   - Codificação: `utf8mb4_general_ci`
   - Clique em "Criar"

5. **Importe o schema**
   - Selecione o banco `inscricao_atletas`
   - Vá para "Importar"
   - Escolha o arquivo `database/schema.sql`
   - Clique em "Executar"

6. **Configure o sistema**
   - Edite `config/database.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'inscricao_atletas');
   ```

   - Edite `config/config.php`
   ```php
   define('BASE_URL', 'http://localhost/Inscricao');
   ```

7. **Acesse o sistema**
   - Frontend: http://localhost/Inscricao
   - Admin: http://localhost/Inscricao/admin/login.php

### Opção 2: WAMP (Windows)

Similar ao XAMPP, mas com WAMP Server:
- Download: http://www.wampserver.com/
- Instale e siga os mesmos passos do XAMPP

### Opção 3: MAMP (Mac)

Similar ao XAMPP, mas com MAMP:
- Download: https://www.mamp.info/
- Instale e siga os mesmos passos do XAMPP

---

## Instalação em Servidor de Produção

### 1. Preparar o Servidor

#### Ubuntu/Debian
```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP e MySQL
sudo apt install apache2 php php-mysql php-mbstring php-json php-gd mysql-server -y

# Habilitar mod_rewrite
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

#### CentOS/RHEL
```bash
# Atualizar sistema
sudo yum update -y

# Instalar Apache, PHP e MySQL
sudo yum install httpd php php-mysql php-mbstring php-json php-gd mariadb-server -y

# Iniciar serviços
sudo systemctl start httpd
sudo systemctl start mariadb
sudo systemctl enable httpd
sudo systemctl enable mariadb
```

### 2. Configurar o Banco de Dados

```bash
# Acessar MySQL
sudo mysql -u root -p

# Criar banco de dados e usuário
CREATE DATABASE inscricao_atletas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'inscricao_user'@'localhost' IDENTIFIED BY 'senha_segura_aqui';
GRANT ALL PRIVILEGES ON inscricao_atletas.* TO 'inscricao_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar schema
mysql -u inscricao_user -p inscricao_atletas < /path/to/database/schema.sql
```

### 3. Upload dos Arquivos

```bash
# Via FTP/SFTP ou git
cd /var/www/html
sudo git clone [seu-repositorio] inscricao

# Ou via SCP
scp -r Inscricao usuario@servidor:/var/www/html/
```

### 4. Configurar Permissões

```bash
# Definir proprietário
sudo chown -R www-data:www-data /var/www/html/inscricao

# Configurar permissões
sudo chmod -R 755 /var/www/html/inscricao
sudo chmod -R 775 /var/www/html/inscricao/public/uploads
```

### 5. Configurar Apache

Crie um Virtual Host:

```bash
sudo nano /etc/apache2/sites-available/inscricao.conf
```

Adicione:

```apache
<VirtualHost *:80>
    ServerName seudominio.com.br
    ServerAlias www.seudominio.com.br

    DocumentRoot /var/www/html/inscricao

    <Directory /var/www/html/inscricao>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/inscricao_error.log
    CustomLog ${APACHE_LOG_DIR}/inscricao_access.log combined
</VirtualHost>
```

Habilite o site:

```bash
sudo a2ensite inscricao.conf
sudo systemctl reload apache2
```

### 6. Instalar SSL (Recomendado)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache -y

# Obter certificado SSL
sudo certbot --apache -d seudominio.com.br -d www.seudominio.com.br

# Renovação automática
sudo certbot renew --dry-run
```

### 7. Configurar Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 'Apache Full'
sudo ufw allow 22
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## Configuração Pós-Instalação

### 1. Alterar Senha do Admin

```bash
# Gerar hash da nova senha
php -r "echo password_hash('nova_senha_segura', PASSWORD_DEFAULT);"

# Atualizar no banco
mysql -u inscricao_user -p
USE inscricao_atletas;
UPDATE usuarios SET password = 'hash_gerado' WHERE username = 'admin';
EXIT;
```

### 2. Configurar Email (Opcional)

Edite `config/config.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@gmail.com');
define('SMTP_PASS', 'sua_senha_app');
define('SMTP_FROM', 'noreply@seudominio.com.br');
```

### 3. Ajustar Limites de Upload

Edite `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

Reinicie o Apache:
```bash
sudo systemctl restart apache2
```

### 4. Configurar Backups Automáticos

```bash
# Criar script de backup
sudo nano /usr/local/bin/backup-inscricao.sh
```

Adicione:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/inscricao"
mkdir -p $BACKUP_DIR

# Backup do banco
mysqldump -u inscricao_user -psenha inscricao_atletas | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup dos uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/html/inscricao/public/uploads

# Deletar backups com mais de 30 dias
find $BACKUP_DIR -type f -mtime +30 -delete
```

Tornar executável e adicionar ao cron:

```bash
sudo chmod +x /usr/local/bin/backup-inscricao.sh
sudo crontab -e

# Adicionar (backup diário às 3h da manhã)
0 3 * * * /usr/local/bin/backup-inscricao.sh
```

---

## Solução de Problemas

### Erro: "Permission denied" ao fazer upload

```bash
sudo chmod -R 775 /var/www/html/inscricao/public/uploads
sudo chown -R www-data:www-data /var/www/html/inscricao/public/uploads
```

### Erro: "Could not connect to database"

1. Verifique as credenciais em `config/database.php`
2. Teste a conexão:
   ```bash
   mysql -u inscricao_user -p
   ```

### Erro 500 - Internal Server Error

1. Verifique os logs:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. Verifique permissões:
   ```bash
   ls -la /var/www/html/inscricao
   ```

### .htaccess não funciona

```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Permitir AllowOverride
sudo nano /etc/apache2/apache2.conf

# Modificar:
<Directory /var/www/>
    AllowOverride All
</Directory>

# Reiniciar Apache
sudo systemctl restart apache2
```

### Upload de arquivos não funciona

Verifique `php.ini`:
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

---

## Verificação da Instalação

### Checklist Pós-Instalação

- [ ] Banco de dados criado e schema importado
- [ ] Arquivo `config/database.php` configurado
- [ ] Arquivo `config/config.php` configurado
- [ ] Pasta `public/uploads` com permissões corretas
- [ ] Senha do admin alterada
- [ ] SSL configurado (produção)
- [ ] Backups configurados
- [ ] Logs sendo gerados corretamente
- [ ] Email de notificação funcionando (se configurado)

### Testar Funcionalidades

1. Acesse a página inicial
2. Preencha o formulário de inscrição
3. Faça upload de documentos
4. Consulte a inscrição criada
5. Acesse o painel admin
6. Visualize, aprove ou rejeite a inscrição
7. Gere o comprovante em PDF

---

## Suporte

Se encontrar problemas:
1. Consulte a documentação: README.md
2. Verifique os logs do sistema
3. Abra uma issue no GitHub
4. Entre em contato: suporte@inscricoes.com

---

**Última atualização:** 2025
**Versão:** 1.0.0
