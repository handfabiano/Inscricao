# 📋 Ordem Correta de Setup do Sistema

## ✅ Problema Resolvido

O erro **"Tabela 'administradores' não existe"** ocorria porque faltava uma migration base que criasse as tabelas fundamentais do sistema antes das migrations enterprise.

---

## 🔄 Ordem Correta das Migrations

As migrations agora seguem esta ordem:

### **0️⃣ Migration 000 - Schema Base** (NOVO!)
```
000_create_base_schema.sql
```
**Cria:**
- ✅ administradores (tabela de admins)
- ✅ modalidades (Futsal, Vôlei, Basquete, etc.)
- ✅ categorias (Sub-12, Sub-15, Adulto, etc.)
- ✅ competicoes (competições/torneios)
- ✅ equipes (times cadastrados)
- ✅ atletas (jogadores)
- ✅ inscricoes_competicoes (inscrições de equipes)
- ✅ inscricoes_atletas (atletas por inscrição)
- ✅ historico_equipes e historico_atletas
- ✅ convites_atletas

**Dados inseridos:**
- 5 modalidades padrão
- 5 categorias padrão

---

### **1️⃣ Migration 001 - Multi-Tenancy**
```
001_create_multi_tenancy_structure.sql
```
**Cria:**
- organizacoes (empresas/instituições)
- planos_assinatura (Basic, Professional, Enterprise)
- assinaturas (controle de planos)

---

### **2️⃣ Migration 002 - Adiciona organizacao_id**
```
002_add_organization_id_to_existing_tables.sql
```
**Modifica:**
- Adiciona coluna `organizacao_id` em todas as tabelas existentes
- Cria índices e foreign keys
- Implementa isolamento de dados por organização

---

### **3️⃣ Migration 003 - Pagamentos**
```
003_create_payment_system.sql
```
**Cria:**
- transacoes_pagamento
- configuracoes_pagamento (PIX, cartão, etc.)

---

### **4️⃣ Migration 004 - 2FA**
```
004_add_2fa_support.sql
```
**Adiciona:**
- Colunas para autenticação de dois fatores
- TOTP (Time-based One-Time Password)

---

### **5️⃣ Migration 005 - Campeonatos**
```
005_create_championship_system.sql
```
**Cria:**
- chaveamentos
- partidas
- grupos
- fases

---

### **6️⃣ Migration 006 - Estatísticas**
```
006_create_statistics_system.sql
```
**Cria:**
- estatisticas_partidas
- estatisticas_atletas
- eventos_partida

---

### **7️⃣ Migration 007 - Rankings**
```
007_create_ranking_system.sql
```
**Cria:**
- rankings_equipes
- Sistema ELO
- Histórico de rankings

---

### **8️⃣ Migration 008 - Analytics & BI**
```
008_create_analytics_system.sql
```
**Cria:**
- prediction_models (Machine Learning)
- match_predictions
- talent_scouts
- report_templates

---

### **9️⃣ Migration 009 - Integrações**
```
009_create_integrations_system.sql
```
**Cria:**
- integration_providers (CBF, COB, WhatsApp, etc.)
- webhooks
- notification_queue
- live_streams

---

## 🚀 Como Executar Setup Completo

### **Passo 1: Configurar Banco de Dados**
```bash
# Edite config/database.php com suas credenciais
```

### **Passo 2: Executar Migrations**
```bash
# Via navegador:
http://localhost/Inscricao/migrations/run_migrations.php

# Ou via linha de comando:
php migrations/run_migrations.php
```

### **Passo 3: Criar Administrador Inicial**
```bash
# Via navegador:
http://localhost/Inscricao/admin/criar_admin.php

# Credenciais padrão criadas:
# Email: admin@admin.com
# Senha: admin123
```

### **Passo 4: Fazer Login**
```bash
http://localhost/Inscricao/admin/login.php
```

---

## 📊 Resultado Final

Após executar todas as migrations, você terá:

- **55 tabelas** criadas
- **10 migrations** executadas (000 a 009)
- **Sistema completo** funcional com:
  - ✅ Multi-tenancy
  - ✅ Pagamentos
  - ✅ 2FA
  - ✅ Campeonatos
  - ✅ Estatísticas
  - ✅ Rankings ELO
  - ✅ Analytics & BI
  - ✅ Machine Learning
  - ✅ Integrações (WhatsApp, YouTube, CBF, COB)

---

## 🔍 Verificação

Para confirmar que tudo funcionou:

```sql
-- Ver todas as tabelas criadas
SHOW TABLES;

-- Ver migrations executadas
SELECT * FROM migrations ORDER BY executed_at;

-- Ver planos cadastrados
SELECT * FROM planos_assinatura;

-- Ver modalidades
SELECT * FROM modalidades;

-- Ver categorias
SELECT * FROM categorias;
```

---

## ⚠️ Troubleshooting

### Se der erro em alguma migration:

**1. Verificar qual migration falhou:**
```sql
SELECT * FROM migrations ORDER BY executed_at DESC;
```

**2. Dropar tabela de controle (se necessário):**
```sql
DROP TABLE migrations;
```

**3. Re-executar migrations:**
```bash
php migrations/run_migrations.php
```

### Se a migration 002 ainda falhar:

Certifique-se que a migration 000 foi executada primeiro:
```sql
SELECT * FROM administradores;  -- Deve retornar tabela vazia (não erro)
```

---

## 📝 Commits Relacionados

- `77d3b08` - feat: Adiciona migration 000 para criar schema base
- `a0518cb` - docs: Guia de correção do erro 403
- `d46f381` - feat: Script de criação de admin inicial

---

## ✅ Status

- ✅ Migration 000 criada com schema base completo
- ✅ run_migrations.php atualizado com ordem correta
- ✅ Sistema pronto para instalação limpa
- ✅ Documentação completa

**Sistema totalmente funcional! 🎉**
