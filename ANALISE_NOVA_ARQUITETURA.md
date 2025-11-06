# Análise e Proposta de Reestruturação do Sistema

## 🔍 ANÁLISE DO SISTEMA ATUAL

### Modelo Atual (v1.0 e v2.0)
```
┌─────────────────┐
│    ATLETA       │
│  (Individual)   │
└────────┬────────┘
         │
         │ inscreve-se diretamente
         ↓
┌─────────────────┐
│   MODALIDADE    │
│   CATEGORIA     │
└─────────────────┘
```

**Características:**
- ✅ Inscrição individual de atletas
- ✅ Atleta se inscreve diretamente em modalidade/categoria
- ✅ Upload de documentos individuais
- ✅ Não há conceito de equipes
- ✅ Não há conceito de competições específicas
- ✅ Sistema genérico de inscrição

**Limitações para o novo modelo:**
- ❌ Não suporta equipes
- ❌ Não suporta competições com regras específicas
- ❌ Não mantém histórico estruturado
- ❌ Não suporta inscrição por responsável de equipe
- ❌ Não valida quantidade de atletas por equipe
- ❌ Não suporta banner de competição

---

## 🚀 NOVO SISTEMA PROPOSTO

### Modelo Novo (v3.0)
```
┌─────────────────┐
│   COMPETIÇÃO    │ ← tem banner, regras, período
│  (ex: Copa 2025)│
└────────┬────────┘
         │
         │ permite inscrição de
         ↓
┌─────────────────┐
│     EQUIPE      │ ← gerenciada por responsável
│  (ex: Time ABC) │
└────────┬────────┘
         │
         │ possui
         ↓
┌─────────────────┐
│     ATLETA      │ ← tem foto, histórico
│  (membro da     │
│    equipe)      │
└─────────────────┘
         │
         │ participa via
         ↓
┌─────────────────┐
│   INSCRIÇÃO     │ ← equipe inscreve atletas
│  (Competição X) │    em competição
└─────────────────┘
```

### Fluxo do Sistema Novo

**1. ADMINISTRADOR cria COMPETIÇÃO**
```
Competição: Copa Roraima 2025
├── Nome: Copa Roraima de Futsal 2025
├── Banner: upload de imagem
├── Período de Inscrições: 01/01/2025 - 31/01/2025
├── Período do Evento: 15/02/2025 - 20/02/2025
├── Modalidade: Futsal
├── Categorias permitidas: Sub-15, Sub-17, Adulto
├── Gênero: Masculino, Feminino, Misto
├── Regras específicas:
│   ├── Min. atletas por equipe: 8
│   ├── Max. atletas por equipe: 15
│   ├── Idade máxima: 35 anos
│   ├── Documentos obrigatórios
│   └── Taxa de inscrição: R$ 500,00
└── Status: Aberta/Fechada/Em andamento/Encerrada
```

**2. RESPONSÁVEL cria/gerencia EQUIPE**
```
Equipe: Tigres FC
├── Nome: Tigres Futebol Clube
├── Responsável: João Silva (CPF, email, telefone)
├── Cidade: Boa Vista - RR
├── Fundação: 2020
├── Cores: Amarelo e Preto
└── Histórico:
    └── Competições participadas: 5
```

**3. EQUIPE cadastra ATLETAS**
```
Atleta: Carlos Santos
├── Dados pessoais (nome, CPF, RG, data nascimento)
├── Foto 3x4: upload
├── Equipe atual: Tigres FC
├── Posição: Atacante
├── Documentos: RG, CPF, atestado médico
└── Histórico:
    ├── Equipes anteriores: [Leões FC (2018-2020)]
    └── Competições participadas: 12
```

**4. RESPONSÁVEL inscreve EQUIPE em COMPETIÇÃO**
```
Inscrição: Tigres FC → Copa Roraima 2025
├── Competição: Copa Roraima 2025
├── Equipe: Tigres FC
├── Categoria escolhida: Adulto
├── Gênero: Masculino
├── Atletas inscritos: 12 atletas
│   ├── Validação: 8 ≤ 12 ≤ 15 ✓
│   ├── Validação: todos masculinos ✓
│   ├── Validação: categoria correta ✓
│   └── Validação: documentos completos ✓
├── Status: Pendente/Aprovada/Rejeitada
└── Protocolo: COMP20250115XXXXX
```

---

## 🗄️ NOVA ESTRUTURA DE BANCO DE DADOS

### Tabelas Principais

**1. competicoes**
- id, nome, descricao, banner_path
- data_inicio_inscricoes, data_fim_inscricoes
- data_inicio_evento, data_fim_evento
- modalidade_id, categorias_permitidas (JSON)
- genero_permitido (M/F/Misto)
- min_atletas, max_atletas
- idade_minima, idade_maxima
- taxa_inscricao, regulamento
- status (Aberta/Fechada/Em andamento/Encerrada)
- created_at, updated_at

**2. equipes**
- id, nome, sigla, cidade, estado
- responsavel_nome, responsavel_cpf, responsavel_email
- responsavel_telefone, responsavel_cargo
- ano_fundacao, cores, escudo_path
- ativo, created_at, updated_at

**3. atletas**
- id, nome_completo, cpf, rg, data_nascimento
- genero, foto_path
- equipe_atual_id (FK → equipes)
- posicao, numero_camisa
- endereco completo, contato
- responsavel_legal (se menor)
- documento_identidade_path, atestado_medico_path
- ativo, created_at, updated_at

**4. inscricoes_competicoes**
- id, competicao_id (FK), equipe_id (FK)
- categoria_id, genero_equipe
- protocolo, status
- valor_pago, comprovante_pagamento_path
- observacoes, created_at, updated_at

**5. inscricoes_atletas**
- id, inscricao_competicao_id (FK)
- atleta_id (FK)
- numero_camisa, posicao
- aprovado (boolean)
- motivo_reprovacao

**6. historico_atletas**
- id, atleta_id, evento_tipo
- descricao, competicao_id
- equipe_id, data_evento

**7. historico_equipes**
- id, equipe_id, evento_tipo
- descricao, competicao_id
- data_evento

---

## 📊 COMPARAÇÃO: ATUAL vs. NOVO

| Característica | Sistema Atual | Sistema Novo |
|---------------|---------------|--------------|
| **Conceito** | Inscrição individual | Competições → Equipes → Atletas |
| **Gestão** | Admin apenas | Admin (competições) + Responsáveis (equipes) |
| **Inscrição** | Atleta individual | Equipe com múltiplos atletas |
| **Validações** | Básicas | Regras complexas por competição |
| **Histórico** | Não estruturado | Completo (atletas e equipes) |
| **Banner** | Não | Sim, por competição |
| **Fotos** | Sim (documentos) | Sim (foto perfil atleta) |
| **Equipes** | Não existe | Entidade principal |
| **Competições** | Genéricas | Específicas com regras |
| **Quantidade atletas** | N/A | Validada por competição |
| **Responsável** | Apenas para menor | Para toda a equipe |

---

## 🎯 FUNCIONALIDADES A IMPLEMENTAR

### ÁREA ADMINISTRATIVA (Admin)
1. ✅ **Gestão de Competições**
   - CRUD completo
   - Upload de banner
   - Definir regras (min/max atletas, categorias, etc)
   - Controle de status e períodos
   - Visualizar inscrições

2. ✅ **Gestão de Equipes**
   - Listar todas as equipes
   - Aprovar/Rejeitar equipes
   - Ver histórico da equipe

3. ✅ **Gestão de Inscrições**
   - Listar inscrições por competição
   - Validar documentos
   - Aprovar/Rejeitar inscrições
   - Gerar relatórios

4. ✅ **Relatórios**
   - Inscrições por competição
   - Atletas por equipe
   - Estatísticas gerais

### ÁREA DO RESPONSÁVEL (Equipe)
1. ✅ **Dashboard da Equipe**
   - Ver dados da equipe
   - Lista de atletas
   - Competições participadas

2. ✅ **Gestão de Atletas**
   - Cadastrar atletas
   - Upload de foto e documentos
   - Editar dados
   - Desativar atleta

3. ✅ **Inscrições em Competições**
   - Ver competições abertas
   - Selecionar atletas para inscrever
   - Validação em tempo real
   - Upload de comprovante de pagamento

4. ✅ **Histórico**
   - Ver histórico da equipe
   - Ver histórico de cada atleta

### ÁREA PÚBLICA
1. ✅ **Competições**
   - Listar competições abertas
   - Ver detalhes e banner
   - Ver regulamento

2. ✅ **Cadastro de Equipe**
   - Responsável se cadastra
   - Aguarda aprovação

---

## 🔧 TECNOLOGIAS (mantidas)
- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- Chart.js (gráficos)
- Font Awesome (ícones)

---

## 📝 PRÓXIMOS PASSOS

1. ✅ Criar novo schema de banco de dados
2. ✅ Implementar gestão de competições (admin)
3. ✅ Implementar gestão de equipes (admin + responsável)
4. ✅ Implementar cadastro de atletas
5. ✅ Implementar sistema de inscrições
6. ✅ Implementar validações de regras
7. ✅ Implementar histórico
8. ✅ Migrar dados existentes (se necessário)
9. ✅ Testes completos
10. ✅ Documentação atualizada

---

## ⚠️ IMPORTANTE

Este é um **sistema completamente novo** que substitui o modelo atual. Recomendo:

1. **Criar branch separada** para desenvolvimento
2. **Manter sistema atual** como backup
3. **Migrar dados gradualmente** se houver inscrições existentes
4. **Testar extensivamente** antes de produção

---

**Aprovado para implementação?**
- [ ] Sim, implementar novo sistema
- [ ] Não, ajustar proposta
- [ ] Combinar com sistema atual

---

**Versão deste documento:** 1.0
**Data:** 2025-01-05
**Autor:** Sistema de Análise
