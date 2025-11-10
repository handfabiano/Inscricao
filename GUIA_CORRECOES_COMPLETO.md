# 🔧 Guia Completo de Correções do Sistema

**Data:** 2025-11-10
**Versão:** 1.0

---

## 📋 Problemas Identificados e Corrigidos

### ✅ 1. admin/relatorios.php - CORRIGIDO

**Problema:**
```
Erro: Column not found: 1054 Unknown column 'c.modalidade' in 'SELECT'
```

**Causa:**
- Consulta SQL tentava acessar `c.modalidade` diretamente
- Tabela `competicoes` não tem coluna `modalidade`, tem `modalidade_id`

**Solução Aplicada:**
```sql
-- ANTES (errado)
SELECT c.nome, c.modalidade, ...

-- DEPOIS (correto)
SELECT c.nome, m.nome as modalidade, ...
FROM competicoes c
LEFT JOIN modalidades m ON c.modalidade_id = m.id
```

**Status:** ✅ Corrigido e commitado

---

### ✅ 2. admin/equipes.php - CORRIGIDO

**Problema:**
```
Erro ao carregar lista de equipes
```

**Causa:**
- Consulta SQL usava `equipe_id` em atletas
- Coluna correta é `equipe_atual_id`

**Solução Aplicada:**
```sql
-- ANTES (errado)
SELECT COUNT(*) FROM atletas WHERE equipe_id = e.id

-- DEPOIS (correto)
SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id
```

**Status:** ✅ Corrigido e commitado

---

### ⚠️ 3. Estrutura da Tabela atletas - REQUER AÇÃO

**Problema:**
- Código usa colunas que não existem no schema original
- Faltam colunas para endereço detalhado e documentos

**Colunas Faltando:**
| Coluna | Descrição |
|--------|-----------|
| `nome_completo` | Nome completo do atleta (copia de `nome`) |
| `genero` | Gênero do atleta (copia de `sexo`) |
| `cep` | CEP do endereço |
| `numero` | Número do endereço |
| `complemento` | Complemento do endereço |
| `bairro` | Bairro do endereço |
| `foto_path` | Caminho da foto (copia de `foto`) |
| `documento_identidade_path` | Caminho do documento de identidade |
| `comprovante_residencia_path` | Caminho do comprovante de residência |
| `atestado_medico_path` | Caminho do atestado médico |
| `observacoes` | Observações adicionais do atleta |

**Solução Criada:**
- Migration: `migrations/002_fix_atletas_structure.sql`
- Interface Web: `admin/fix_atletas_structure.php`

**Como Executar:**
1. Acesse: `https://SEU-SITE.com/admin/fix_atletas_structure.php`
2. Clique em "▶️ Executar Migration Agora"
3. Aguarde conclusão
4. Verifique resultado

**Status:** ⚠️ REQUER AÇÃO DO USUÁRIO

---

### ✅ 4. Sistema de Convites - CORRIGIDO

**Problema:**
- Colunas `validade_ate` e `usado_em` vs `data_expiracao` e `data_aceite`

**Solução Aplicada:**
- Código tem compatibilidade retroativa
- Funciona com ambos os nomes de colunas
- Migration disponível em: `admin/fix_convites_columns.php`

**Status:** ✅ Funcionando (com ou sem migration)

---

## 🔍 Problemas Identificados - Aguardando Análise

### 5. admin/configuracoes.php

**Relatado:** "não está funcionando"

**Análise:**
- Arquivo existe e tem código completo
- Requer autenticação de admin
- Funções:
  - Criar/editar administradores
  - Alterar senhas
  - Configurar email (SMTP)
  - Limpar logs antigos

**Possível Causa:**
- Erro de autenticação
- Sessão expirada
- Permissões insuficientes

**Como Testar:**
1. Fazer login como admin
2. Acessar: `/admin/configuracoes.php`
3. Verificar console do navegador (F12) para erros JS
4. Verificar se há mensagens de erro na tela

**Status:** 📝 REQUER MAIS INFORMAÇÕES

---

### 6. CRUD de Atletas

**Relatado:** "não vi o CRUD dos atletas funcionando"

**Análise:**
- Arquivo `admin/atletas.php` existe
- Tem listagem com filtros
- Usa consultas que requerem colunas novas

**Possível Causa:**
- Colunas faltando na tabela atletas (ver item 3)
- Após executar migration, deve funcionar

**Como Testar:**
1. Executar migration de atletas (item 3)
2. Acessar: `/admin/atletas.php`
3. Verificar se lista aparece

**Status:** ⚠️ REQUER MIGRATION DE ATLETAS

---

### 7. Exibição de Fotos

**Relatado:** "a foto também não aparece"

**Análise:**
- Código salva fotos em: `/uploads/atletas/`
- Nomes de arquivos salvos na coluna `foto_path`

**Possível Causa:**
1. Coluna `foto_path` não existe (requer migration)
2. Diretório `/uploads/atletas/` não existe
3. Permissões incorretas no diretório
4. Caminho relativo/absoluto incorreto no HTML

**Como Corrigir:**
```bash
# Criar diretório se não existir
mkdir -p /uploads/atletas

# Dar permissões corretas
chmod 755 /uploads/atletas
chown www-data:www-data /uploads/atletas
```

**Verificar no Código:**
```php
// Caminho deve ser assim:
<img src="/uploads/atletas/<?php echo $atleta['foto_path']; ?>">

// OU assim:
<img src="../uploads/atletas/<?php echo $atleta['foto_path']; ?>">
```

**Status:** ⚠️ REQUER MIGRATION + VERIFICAÇÃO

---

### 8. Sistema de Confrontos e Resultados

**Relatado:** "não vi ainda o sistema de confrontos e resultados"

**Análise:**
- NÃO existe no código atual
- Tabela `matches` existe no schema mas sem páginas de gestão

**O Que Falta:**
- [ ] Página para criar confrontos/jogos
- [ ] Página para registrar resultados
- [ ] Página para visualizar tabela de classificação
- [ ] Página para visualizar histórico de jogos
- [ ] Sistema de chaveamento (eliminatórias)

**Status:** ❌ NÃO IMPLEMENTADO

---

## 🚀 Plano de Ação Recomendado

### Prioridade ALTA (Fazer Agora)

1. **Executar Migration da Tabela Atletas**
   - URL: `/admin/fix_atletas_structure.php`
   - Tempo: 2-3 minutos
   - Impacto: Resolve CRUD de atletas e fotos

2. **Executar Migration de Convites (Opcional)**
   - URL: `/admin/fix_convites_columns.php`
   - Tempo: 1-2 minutos
   - Impacto: Padroniza nomes de colunas (já funciona sem)

3. **Verificar Diretório de Uploads**
   ```bash
   mkdir -p uploads/atletas
   chmod 755 uploads/atletas
   ```

### Prioridade MÉDIA (Fazer Esta Semana)

4. **Testar admin/configuracoes.php**
   - Fazer login como admin
   - Acessar página
   - Reportar erro específico se houver

5. **Testar CRUD de Atletas**
   - Após migration (item 1)
   - Acessar `/admin/atletas.php`
   - Tentar criar/editar atleta

### Prioridade BAIXA (Fazer Quando Possível)

6. **Sistema de Confrontos e Resultados**
   - Requer desenvolvimento completo
   - Estimativa: 2-3 dias de trabalho
   - Funcionalidades complexas

---

## 📁 Arquivos Criados/Modificados

### Arquivos Novos:
```
migrations/001_fix_convites_atletas_columns.sql
migrations/002_fix_atletas_structure.sql
admin/fix_convites_columns.php
admin/fix_atletas_structure.php
admin/validar_sistema.php
CORRECAO_CONVITES.md
GUIA_CORRECOES_COMPLETO.md (este arquivo)
```

### Arquivos Modificados:
```
admin/relatorios.php         ✅ Corrigido
admin/equipes.php            ✅ Corrigido
publico/cadastro_atleta.php  ✅ Compatibilidade
publico/processar_cadastro_atleta.php ✅ Compatibilidade
equipe/convites_atletas.php  ✅ Compatibilidade
```

---

## 🔗 Links Úteis

### Validação e Diagnóstico:
- Validar Sistema: `/admin/validar_sistema.php`

### Migrations:
- Fix Convites: `/admin/fix_convites_columns.php`
- Fix Atletas: `/admin/fix_atletas_structure.php`

### Admin:
- Dashboard: `/admin/index.php`
- Configurações: `/admin/configuracoes.php`
- Relatórios: `/admin/relatorios.php` ✅
- Equipes: `/admin/equipes.php` ✅
- Atletas: `/admin/atletas.php` ⚠️ (requer migration)

---

## 📞 Suporte

Se encontrar erros:
1. Tire print da tela
2. Copie mensagem de erro completa
3. Informe qual página e o que estava fazendo
4. Verifique console do navegador (F12)

---

## ✅ Checklist Rápido

- [ ] Executei migration de atletas
- [ ] Executei migration de convites (opcional)
- [ ] Verifiquei diretório de uploads
- [ ] Testei admin/configuracoes.php
- [ ] Testei admin/relatorios.php
- [ ] Testei admin/equipes.php
- [ ] Testei admin/atletas.php
- [ ] Testei upload de fotos
- [ ] Sistema de confrontos (aguardando desenvolvimento)

---

**Última Atualização:** 2025-11-10
**Commits:** 710fc97, da6460e
