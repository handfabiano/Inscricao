# 📋 Sistema de Inscrições em Competições

## 🎯 Visão Geral

O sistema permite que equipes se inscrevam em competições abertas, selecionando atletas específicos para cada inscrição. Todo o processo é rastreado com histórico completo e geração de protocolo único.

---

## 🔄 Fluxo Completo de Inscrição

### 1️⃣ **Admin Cria Competição**
- Acessa: `admin/competicoes.php`
- Define:
  - Nome, descrição, banner
  - Período de inscrições
  - Modalidade
  - **Categorias permitidas** (array JSON)
  - **Gênero permitido** (Masculino/Feminino/Ambos)
  - **Quantidade de atletas** (min e max por equipe)

### 2️⃣ **Equipe Visualiza Competições Abertas**
- Acessa: `equipe/inscricoes.php`
- Vê apenas competições com:
  - Status: "Aberta"
  - Data atual entre `data_inicio_inscricoes` e `data_fim_inscricoes`
- Cada card mostra:
  - Banner da competição
  - Requisitos (categorias, gênero, quantidade atletas)
  - Status de inscrição (se já inscrito, desabilita botão)

### 3️⃣ **Equipe Seleciona Atletas**
- Clica em "Inscrever Equipe"
- Modal abre com:
  - Lista de **todos os atletas ativos** da equipe
  - Checkboxes para seleção
  - Filtro automático por gênero (se requerido)
  - Validação em tempo real da quantidade
- Informações exibidas de cada atleta:
  - Foto circular
  - Nome completo
  - Idade (calculada automaticamente)
  - Gênero

### 4️⃣ **Sistema Processa Inscrição**
Arquivo: `equipe/processar_inscricao.php`

**Validações realizadas:**
```
✅ Competição existe e está aberta
✅ Período de inscrições válido
✅ Equipe não está inscrita (evita duplicatas)
✅ Quantidade de atletas entre min e max
✅ Todos os atletas pertencem à equipe
✅ Todos os atletas estão ativos
✅ Gênero dos atletas atende requisito (se houver)
```

**Processo de registro:**
1. Inicia transação no banco
2. Gera protocolo único (`INSC{uniqid}`)
3. Insere em `inscricoes_competicoes`:
   - competicao_id
   - equipe_id
   - protocolo
   - status: "Pendente"
   - data_inscricao: NOW()
4. Insere cada atleta em `inscricoes_atletas`:
   - inscricao_competicao_id
   - atleta_id
   - data_inscricao: NOW()
5. Registra histórico da equipe (`historico_equipes`)
6. Registra histórico de cada atleta (`historico_atletas`)
7. Commit da transação
8. Redireciona para histórico com mensagem de sucesso

**Tratamento de erros:**
- Se qualquer validação falhar → Rollback + mensagem de erro
- Se erro no banco → Rollback + mensagem de erro

### 5️⃣ **Equipe Visualiza Histórico**
- Acessa: `equipe/minhas_inscricoes.php`
- Vê todas as inscrições (todos os status):
  - ⚠️ **Pendente** (aguardando confirmação)
  - ✅ **Confirmada** (aprovada pelo admin)
  - ❌ **Cancelada** (rejeitada ou cancelada)
- Estatísticas no topo:
  - Total de inscrições
  - Quantidade por status
- Cada card mostra:
  - Banner da competição
  - Protocolo único
  - Data da inscrição
  - Quantidade de atletas
  - Botão "Ver Detalhes"

### 6️⃣ **Detalhes da Inscrição**
- Clica em "Ver Detalhes"
- Modal carrega via AJAX: `equipe/get_detalhes_inscricao.php`
- Exibe:
  - Informações da competição
  - Status e protocolo
  - **Lista completa de atletas inscritos** com:
    - Foto circular
    - Nome
    - Idade
    - Gênero

---

## 📊 Estrutura do Banco de Dados

### Tabela: `inscricoes_competicoes`
```sql
id                   INT PRIMARY KEY AUTO_INCREMENT
competicao_id        INT (FK → competicoes)
equipe_id            INT (FK → equipes)
protocolo            VARCHAR(50) UNIQUE
status               ENUM('Pendente', 'Confirmada', 'Cancelada')
data_inscricao       DATETIME
observacoes          TEXT
```

### Tabela: `inscricoes_atletas`
```sql
id                       INT PRIMARY KEY AUTO_INCREMENT
inscricao_competicao_id  INT (FK → inscricoes_competicoes)
atleta_id                INT (FK → atletas)
data_inscricao           DATETIME
```

### Tabela: `historico_equipes`
```sql
id              INT PRIMARY KEY AUTO_INCREMENT
equipe_id       INT (FK → equipes)
evento_tipo     VARCHAR(100)  -- 'Inscrição'
descricao       TEXT
dados_adicionais JSON         -- Dados completos da inscrição
data_evento     DATETIME
```

### Tabela: `historico_atletas`
```sql
id              INT PRIMARY KEY AUTO_INCREMENT
atleta_id       INT (FK → atletas)
evento_tipo     VARCHAR(100)  -- 'Inscrição em Competição'
descricao       TEXT
dados_novos     JSON
data_evento     DATETIME
```

---

## 🔐 Segurança Implementada

### Autenticação
- ✅ Todas as páginas verificam `requireEquipeLogin()`
- ✅ Sessão validada antes de qualquer operação
- ✅ Equipe só acessa seus próprios dados

### Validação de Ownership
```php
// Verifica se inscrição pertence à equipe logada
WHERE inscricao_competicao_id = ? AND equipe_id = ?
```

### Validação de Atletas
```php
// Verifica se atletas pertencem à equipe
WHERE id IN (...) AND equipe_atual_id = ? AND ativo = 1
```

### Prevenção de Duplicatas
```php
// Impede inscrição duplicada
WHERE competicao_id = ? AND equipe_id = ?
AND status IN ('Pendente', 'Confirmada')
```

### Transações Seguras
```php
try {
    $pdo->beginTransaction();
    // ... operações ...
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // ... tratamento de erro ...
}
```

---

## 🎨 Interface e UX

### Design Pattern
- Cards visuais com banners
- Badges coloridos para status
- Modal interativo para seleção
- Fotos circulares dos atletas
- Validação em tempo real
- Mensagens flash de sucesso/erro
- Responsivo (mobile-first)

### Feedback ao Usuário
- ✅ Botão desabilitado se já inscrito
- ⚠️ Alerta de requisitos não atendidos
- 📊 Contador de atletas selecionados
- 🔄 Loading spinner enquanto carrega
- ✔️ Mensagem de sucesso com protocolo

---

## 📱 Páginas do Sistema

### 1. `equipe/inscricoes.php`
**Objetivo:** Listar competições abertas e permitir inscrição

**Features:**
- Lista apenas competições com inscrições abertas
- Filtra automaticamente por data
- Mostra requisitos claramente
- Impede inscrição duplicada
- Modal de seleção de atletas
- Validação de quantidade

**Tecnologias:**
- PHP + PDO
- Bootstrap 5
- JavaScript (AJAX)
- Font Awesome icons

### 2. `equipe/processar_inscricao.php`
**Objetivo:** Processar e validar inscrição

**Validações:**
- Competição aberta
- Período válido
- Sem duplicatas
- Quantidade de atletas
- Ownership de atletas
- Gênero (se requerido)

**Operações:**
- Gera protocolo único
- Insere em 2 tabelas
- Registra histórico (equipe + atletas)
- Transação segura

### 3. `equipe/minhas_inscricoes.php`
**Objetivo:** Histórico completo de inscrições

**Features:**
- Lista todas as inscrições
- Estatísticas por status
- Cards visuais com banners
- Modal de detalhes
- Protocolo em destaque
- Filtro por status (visual)

### 4. `equipe/get_detalhes_inscricao.php`
**Objetivo:** API JSON para detalhes

**Retorna:**
```json
{
  "inscricao": {
    "protocolo": "INSC...",
    "status": "Pendente",
    "data_inscricao": "25/01/2025 14:30"
  },
  "competicao": {
    "nome": "Campeonato Municipal",
    "modalidade": "Futsal"
  },
  "atletas": [
    {
      "nome": "João Silva",
      "idade": 15,
      "genero": "Masculino",
      "foto_path": "https://..."
    }
  ]
}
```

---

## 🧪 Como Testar

### Passo 1: Criar Competição
```
1. Login como admin (admin/admin123)
2. Acesse admin/competicoes.php
3. Clique em "Nova Competição"
4. Preencha:
   - Nome: "Campeonato Teste"
   - Modalidade: Futsal
   - Categorias: ["Sub-11", "Sub-13"]
   - Gênero: Ambos
   - Min atletas: 5
   - Max atletas: 10
   - Período inscrições: HOJE até +7 dias
5. Salve
```

### Passo 2: Fazer Login como Equipe
```
1. Logout do admin
2. Login como equipe (equipe1/equipe123)
3. Cadastre pelo menos 5 atletas
```

### Passo 3: Inscrever na Competição
```
1. Acesse equipe/inscricoes.php
2. Veja o card "Campeonato Teste"
3. Clique em "Inscrever Equipe"
4. Selecione 5 a 10 atletas
5. Clique em "Confirmar Inscrição"
6. Veja mensagem: "Inscrição realizada! Protocolo: INSCxxx"
```

### Passo 4: Ver Histórico
```
1. Acesse equipe/minhas_inscricoes.php
2. Veja o card da inscrição
3. Status: Pendente (amarelo)
4. Clique em "Ver Detalhes"
5. Modal mostra todos os atletas inscritos
```

### Passo 5: Verificar Histórico
```sql
-- Ver histórico da equipe
SELECT * FROM historico_equipes WHERE equipe_id = 1;

-- Ver histórico dos atletas
SELECT * FROM historico_atletas WHERE atleta_id IN (1,2,3,4,5);

-- Ver dados da inscrição
SELECT * FROM inscricoes_competicoes WHERE protocolo = 'INSCxxx';

-- Ver atletas da inscrição
SELECT * FROM inscricoes_atletas WHERE inscricao_competicao_id = 1;
```

---

## 🚀 Próximos Passos (Sugestões)

### Admin: Gerenciar Inscrições
- [ ] admin/inscricoes.php (listar todas as inscrições)
- [ ] Aprovar/Rejeitar inscrições (mudar status)
- [ ] Ver atletas de cada inscrição
- [ ] Exportar lista de inscritos

### Equipe: Funcionalidades Extras
- [ ] Editar inscrição (adicionar/remover atletas)
- [ ] Cancelar inscrição (status = Cancelada)
- [ ] Imprimir comprovante de inscrição
- [ ] Notificação quando status mudar

### Sistema de Convites
- [ ] Admin pode convidar organizadores
- [ ] Organizadores podem criar competições
- [ ] Organizadores podem convidar responsáveis de equipes
- [ ] Responsáveis criam equipes via convite

---

## ✅ Status Atual

### Implementado ✅
- ✅ Listar competições abertas
- ✅ Inscrever equipe com seleção de atletas
- ✅ Validações completas
- ✅ Geração de protocolo único
- ✅ Histórico automático (equipe + atletas)
- ✅ Ver histórico de inscrições
- ✅ Detalhes de cada inscrição
- ✅ Estatísticas por status
- ✅ Interface responsiva
- ✅ Segurança e ownership

### Pendente ⏳
- ⏳ Admin gerenciar inscrições
- ⏳ Sistema de convites
- ⏳ Editar/cancelar inscrição
- ⏳ Exportar inscritos

---

## 📞 Contato

Para dúvidas sobre o sistema de inscrições, consulte:
- **FUNCOES_AUXILIARES.md** - Referência de todas as funções
- **verificar_sistema.php** - Verificador de integridade
- Este arquivo - Fluxo completo de inscrições
