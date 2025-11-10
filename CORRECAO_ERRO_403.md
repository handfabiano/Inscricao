# 🔧 Correção do Erro 403 - admin/configuracoes.php

## ✅ Problema Resolvido

O erro **403 Forbidden** na página `admin/configuracoes.php` foi causado por:

1. **URL hardcoded** - BASE_URL estava fixado para Hostinger
2. **Redirecionamentos absolutos** - Funções de login usavam URLs absolutas
3. **Falta de admin cadastrado** - Sistema redirecionava para login sem admin existente

---

## 📋 O que foi corrigido:

### 1️⃣ **config/config.php** - Detecção automática de ambiente

**Antes:**
```php
define('BASE_URL', 'https://mediumblue-rhinoceros-869852.hostingersite.com');
```

**Depois:**
```php
// Detectar ambiente e definir URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

define('BASE_URL', $protocol . '://' . $host . $scriptDir);
```

✅ **Resultado**: Sistema funciona tanto localmente quanto em produção

---

### 2️⃣ **Funções de Redirecionamento** - Caminhos relativos

**Antes:**
```php
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}
```

**Depois:**
```php
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        if (strpos($currentPath, '/admin/') !== false) {
            header('Location: login.php');  // Relativo
        } else {
            header('Location: /admin/login.php');  // Absoluto do root
        }
        exit;
    }
}
```

✅ **Resultado**: Redirecionamentos funcionam corretamente em qualquer contexto

---

### 3️⃣ **Script de Criação de Admin** - admin/criar_admin.php

Novo arquivo criado para facilitar primeiro acesso:

```bash
http://localhost/Inscricao/admin/criar_admin.php
```

**Funcionalidades:**
- ✅ Verifica se tabela de administradores existe
- ✅ Lista administradores cadastrados
- ✅ Cria admin padrão se não existir nenhum
- ✅ Inclui instruções de segurança

**Credenciais padrão criadas:**
- 📧 **Email**: admin@admin.com
- 🔑 **Senha**: admin123

⚠️ **Importante**: Altere a senha após o primeiro login!

---

## 🚀 Como usar agora:

### **Opção 1: Criar Admin Inicial (se não tiver nenhum)**

1. Acesse: `http://localhost/Inscricao/admin/criar_admin.php`
2. Clique em "Criar Administrador Padrão"
3. Use as credenciais:
   - Email: **admin@admin.com**
   - Senha: **admin123**
4. Faça login em: `http://localhost/Inscricao/admin/login.php`
5. **Desabilite o script** após uso (edite `criar_admin.php` e mude `$SCRIPT_HABILITADO = false`)

### **Opção 2: Fazer Login (se já tiver admin)**

1. Acesse: `http://localhost/Inscricao/admin/login.php`
2. Faça login com suas credenciais
3. Acesse: `http://localhost/Inscricao/admin/configuracoes.php`

---

## 🔍 Verificação:

Para confirmar que está tudo funcionando:

1. ✅ `config/config.php` - BASE_URL é detectado automaticamente
2. ✅ `admin/login.php` - Página de login carrega sem erro
3. ✅ `admin/criar_admin.php` - Cria admin se necessário
4. ✅ `admin/configuracoes.php` - Carrega após login bem-sucedido

---

## 📞 Troubleshooting:

### Se ainda der erro 403:

**Verifique permissões dos arquivos:**
```bash
chmod 644 admin/configuracoes.php
chmod 644 config/config.php
```

**Verifique se o banco está configurado:**
```bash
# Acesse:
http://localhost/Inscricao/test_error.php
```

**Limpe o cache do navegador:**
- Chrome: Ctrl + Shift + Del
- Firefox: Ctrl + Shift + Del

**Verifique sessão PHP:**
```bash
# Em config/config.php a sessão é iniciada automaticamente
```

---

## 🎯 Commits relacionados:

- `9c65c34` - fix: Corrige erro 403 em configuracoes.php
- `d46f381` - feat: Adiciona script de criação de administrador inicial

---

## ✅ Status Final:

- ✅ Erro 403 corrigido
- ✅ Sistema funciona local e produção
- ✅ Redirecionamentos funcionando
- ✅ Script de criação de admin disponível
- ✅ Documentação completa

**Sistema totalmente operacional! 🎉**
