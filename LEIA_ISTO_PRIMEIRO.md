# 🚀 LEIA ISTO PRIMEIRO - Setup Rápido

## ⚠️ Erro "Acesso Negado ao information_schema"?

Se você recebeu o erro **"Acesso negado ao banco de dados information_schema"**, use a versão **BÁSICA** das migrations.

---

## 📋 Qual Migration Executar?

### ✅ **HOSTINGER ou Hospedagem Compartilhada**
```
Execute: /migrations/run_migrations_BASICO.php
```
**Por quê?** Hostinger não dá permissão para acessar `information_schema`, necessário para as migrations enterprise.

### ✅ **Servidor Próprio / VPS / Localhost com Permissões Completas**
```
Execute: /migrations/run_migrations.php
```
**Por quê?** Você tem controle total do servidor e pode executar todas as 10 migrations com funcionalidades enterprise.

---

## 🎯 Setup em 3 Passos

### **1️⃣ Configure o Banco de Dados**
Edite `config/database.php` com suas credenciais:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');  // Ex: u320952164_compet
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'seu_banco');    // Ex: u320952164_compet
```

### **2️⃣ Execute a Migration**

**HOSTINGER:**
```
https://seu-site.hostingersite.com/migrations/run_migrations_BASICO.php
```

**LOCALHOST:**
```
http://localhost/Inscricao/migrations/run_migrations_BASICO.php
```

### **3️⃣ Crie o Administrador**
```
https://seu-site.hostingersite.com/admin/criar_admin.php
```
Ou localmente:
```
http://localhost/Inscricao/admin/criar_admin.php
```

---

## 📊 O que cada versão instala?

### **run_migrations_BASICO.php** (Recomendado para Hostinger)
Instala **1 migration**:
- ✅ **000** - Schema base (11 tabelas fundamentais)
  - administradores
  - modalidades (5 esportes)
  - categorias (5 faixas etárias)
  - competicoes
  - equipes
  - atletas
  - inscricoes_competicoes
  - inscricoes_atletas
  - historico_equipes
  - historico_atletas
  - convites_atletas

**Sistema 100% funcional para:**
- Cadastro de equipes e atletas
- Criação de competições
- Inscrições em torneios
- Gestão de convites
- Área administrativa completa

---

### **run_migrations.php** (Para Servidores com Permissões Completas)
Instala **10 migrations**:
- ✅ **000** - Schema base (11 tabelas)
- ✅ **001** - Multi-tenancy (3 tabelas + planos)
- ✅ **002** - Isolamento por organização
- ✅ **003** - Sistema de pagamentos (2 tabelas)
- ✅ **004** - Autenticação 2FA
- ✅ **005** - Campeonatos avançados (10 tabelas)
- ✅ **006** - Estatísticas (6 tabelas)
- ✅ **007** - Rankings ELO (5 tabelas)
- ✅ **008** - Analytics & BI + ML (10 tabelas)
- ✅ **009** - Integrações (10 tabelas)

**Sistema enterprise completo com:**
- Tudo da versão básica +
- Multi-tenancy (várias organizações)
- Pagamentos (PIX, Cartão)
- 2FA (TOTP)
- Chaveamentos automáticos
- Machine Learning para predições
- Integrações (WhatsApp, YouTube, CBF, COB)
- Sistema de ranking ELO

**Total: 55 tabelas**

---

## ⚡ Solução de Problemas

### Erro: "Tabela administradores não existe"
✅ **Solução**: Execute `run_migrations_BASICO.php`

### Erro: "Acesso negado ao information_schema"
✅ **Solução**: Use `run_migrations_BASICO.php` em vez de `run_migrations.php`

### Erro: "Duplicate column name"
✅ **Solução**: Ignore. A migration é idempotente (pode ser executada várias vezes).

### Erro: "Column 'e.municipio' not found"
✅ **Solução**: Já corrigido! Execute `git pull` para atualizar.

### Erro 403 em configuracoes.php
✅ **Solução**: Consulte `CORRECAO_ERRO_403.md`

---

## 📚 Documentação Completa

- **SETUP_ORDEM_CORRETA.md** - Explicação detalhada de todas as migrations
- **CORRECAO_ERRO_403.md** - Como corrigir erro 403
- **CONFIGURAR_HOSTINGER.md** - Guia específico para Hostinger
- **DEPLOY_RAPIDO.md** - Setup de deploy automático

---

## ✅ Checklist Pós-Instalação

- [ ] Migration executada com sucesso
- [ ] Administrador criado (`admin@admin.com` / `admin123`)
- [ ] Login no sistema funcionando
- [ ] 5 modalidades e 5 categorias cadastradas
- [ ] Página pública carregando (`/publico/index.php`)

---

## 🆘 Precisa de Ajuda?

Se encontrar algum problema:

1. Verifique o arquivo de erro correspondente:
   - `CORRECAO_ERRO_403.md`
   - `SETUP_ORDEM_CORRETA.md`
   - `CONFIGURAR_HOSTINGER.md`

2. Execute o teste de diagnóstico:
   ```
   /test_error.php
   ```

3. Verifique as credenciais do banco:
   ```
   /test_connection.php
   ```

---

## 🎉 Tudo Funcionando?

Após o setup:
1. Acesse: `/admin/login.php`
2. Use: `admin@admin.com` / `admin123`
3. **IMPORTANTE**: Altere a senha após primeiro login!
4. Desabilite os scripts de diagnóstico:
   - `criar_admin.php`
   - `test_error.php`
   - `test_connection.php`

---

**Versão do Sistema**: 1.0.0
**Última Atualização**: 2025-11-10
**Total de Commits**: 12+
