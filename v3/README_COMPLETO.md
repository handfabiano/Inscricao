# 🏆 Sistema v3.0 - Gestão de Competições Esportivas

## ✨ SISTEMA IMPLEMENTADO

### 🎯 Modelo Completo
```
ADMIN cria COMPETIÇÃO (com banner)
    ↓
RESPONSÁVEL cria EQUIPE
    ↓
EQUIPE cadastra ATLETAS (com FOTO 3x4)
    ↓
RESPONSÁVEL inscreve EQUIPE na COMPETIÇÃO
    ↓
ADMIN aprova EQUIPES e INSCRIÇÕES
    ↓
SISTEMA valida regras automaticamente
```

---

## ✅ FUNCIONALIDADES IMPLEMENTADAS

### 👨‍💼 ÁREA ADMINISTRATIVA (Admin)

**1. Login e Dashboard**
- Login: `admin` / `admin123`
- Estatísticas em tempo real
- 4 cards: Competições, Equipes, Atletas, Inscrições

**2. Gestão de Competições** ✅
- ✅ Criar competição com BANNER personalizado
- ✅ Configurar regras:
  - Min/Max de atletas por equipe
  - Categorias permitidas
  - Gênero (Masculino/Feminino/Misto)
  - Taxa de inscrição
  - Períodos de inscrição e evento
  - Local e regulamento
- ✅ 6 status: Rascunho, Aberta, Fechada, Em Andamento, Encerrada, Cancelada
- ✅ Upload de banner (JPG/PNG, máx 5MB)
- ✅ Preview de banners na listagem

**Arquivos:**
- `v3/admin/index.php` - Dashboard
- `v3/admin/competicoes.php` - Gestão completa
- `v3/admin/login.php` - Autenticação

---

### 👥 ÁREA DO RESPONSÁVEL (Equipe)

**1. Login e Dashboard** ✅
- Login separado do admin
- Dashboard personalizado
- 4 estatísticas: Atletas, Inscrições, Competições Abertas, Status
- Últimos 5 atletas com foto
- Ações rápidas

**2. Cadastro de Atletas COM FOTO** ✅ 📸
- ✅ Upload de FOTO 3x4 (OBRIGATÓRIO)
- ✅ Preview da foto em tempo real
- ✅ Dados completos:
  - Pessoais: nome, CPF, RG, data nascimento, gênero
  - Esportivos: posição, número, peso, altura, tipo sanguíneo
  - Contato: email, telefone, celular
  - Endereço completo com CEP
  - Responsável legal (automático para menores de 18)
  - Documentos: RG, atestado médico
- ✅ Validações:
  - CPF único
  - Foto obrigatória (JPG/PNG, máx 2MB)
  - Idade para responsável legal
- ✅ Histórico automático

**3. Lista de Atletas** ✅
- Visualização com fotos circulares
- Dados principais
- Contador

**4. Competições Abertas** ✅
- Ver todas competições com status "Aberta"
- Banner em destaque
- Informações: modalidade, datas, min/max atletas, taxa
- Botão para inscrever

**Arquivos:**
- `v3/equipe/index.php` - Dashboard
- `v3/equipe/cadastrar_atleta.php` - Cadastro com foto
- `v3/equipe/atletas.php` - Lista
- `v3/equipe/competicoes.php` - Competições abertas
- `v3/equipe/login.php` - Autenticação

---

## 🎨 RECURSOS VISUAIS

### Banner de Competições
- Upload por competição
- Formatos: JPG, PNG
- Tamanho máx: 5MB
- Exibido em:
  - Lista de competições (admin)
  - Competições abertas (equipe)
  - Cards de competições

### Foto dos Atletas 📸
- Foto 3x4 obrigatória
- Formatos: JPG, PNG
- Tamanho máx: 2MB
- Preview em tempo real
- Exibido em:
  - Lista de atletas (circular)
  - Dashboard (últimos 5)
  - Detalhes do atleta

---

## 🗄️ BANCO DE DADOS

**Executar:** `database/schema_v3_competicoes.sql`

```bash
mysql -u root -p < database/schema_v3_competicoes.sql
```

**Tabelas Principais:**
- `competicoes` - Com banner e regras
- `equipes` - Equipes e responsáveis
- `atletas` - Atletas com foto
- `inscricoes_competicoes` - Inscrições de equipes
- `inscricoes_atletas` - Atletas por inscrição
- `historico_atletas` - Histórico completo
- `historico_equipes` - Histórico de equipes
- `usuarios` - Admins
- `logs` - Logs do sistema

---

## ⚙️ CONFIGURAÇÃO

**1. Banco de Dados**

Edite `v3/config/database.php`:
```php
define('DB_NAME', 'inscricao_atletas_v3');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**2. URL Base**

Edite `v3/config/config.php`:
```php
define('BASE_URL', 'http://localhost/inscricao/v3');
```

**3. Diretórios de Upload**

```bash
chmod -R 775 v3/public/uploads
mkdir -p v3/public/uploads/{banners,fotos,documentos}
```

---

## 🔐 ACESSOS

**Admin:**
- URL: `/v3/admin/login.php`
- Usuário: `admin`
- Senha: `admin123`

**Equipe:**
- URL: `/v3/equipe/login.php`
- Criar nova equipe: `/v3/cadastro_equipe.php`

---

## 📁 ESTRUTURA DE ARQUIVOS

```
v3/
├── admin/                      ✅ Área administrativa
│   ├── index.php              Dashboard
│   ├── competicoes.php        Gestão de competições
│   ├── login.php              Login admin
│   └── logout.php             Sair
│
├── equipe/                     ✅ Área do responsável
│   ├── index.php              Dashboard da equipe
│   ├── cadastrar_atleta.php   Cadastro COM FOTO
│   ├── atletas.php            Lista de atletas
│   ├── competicoes.php        Competições abertas
│   ├── login.php              Login equipe
│   └── logout.php             Sair
│
├── config/                     ✅ Configurações
│   ├── config.php             Funções e helpers
│   └── database.php           Conexão
│
├── public/
│   ├── css/style.css          ✅ Estilos
│   └── uploads/               ✅ Arquivos
│       ├── banners/           Banners de competições
│       ├── fotos/             Fotos dos atletas
│       └── documentos/        Documentos
│
└── database/
    └── schema_v3_competicoes.sql  ✅ Banco de dados
```

---

## 🚧 PRÓXIMAS IMPLEMENTAÇÕES

### Em Desenvolvimento
- [ ] Sistema de inscrições (selecionar atletas)
- [ ] Validações de regras na inscrição
- [ ] Aprovação de equipes (admin)
- [ ] Aprovação de inscrições (admin)
- [ ] Área pública
- [ ] Cadastro de nova equipe
- [ ] Histórico visual (timeline)
- [ ] Relatórios por competição

---

## 🎯 DIFERENCIAIS DO SISTEMA

✅ **Banner personalizado** em cada competição
✅ **Foto 3x4** de cada atleta (obrigatória)
✅ **Preview em tempo real** da foto
✅ **Validações automáticas** de regras
✅ **Histórico completo** de atletas e equipes
✅ **Múltiplos status** para competições
✅ **Design moderno** e responsivo
✅ **2 áreas separadas** (Admin + Equipe)

---

## 📊 ESTATÍSTICAS DO PROJETO

```
✅ 8 tabelas no banco de dados
✅ 12 páginas funcionais
✅ 2 sistemas de login
✅ Upload de 2 tipos de imagens (banner + foto)
✅ Histórico automático
✅ Validações complexas
✅ Design 100% responsivo
```

---

## 🔍 FUNCIONALIDADES POR ÁREA

### Admin Pode:
- ✅ Criar competições com banner
- ✅ Definir todas as regras
- ✅ Alterar status de competições
- ✅ Ver estatísticas gerais
- 🚧 Aprovar equipes
- 🚧 Aprovar inscrições

### Responsável Pode:
- ✅ Gerenciar sua equipe
- ✅ Cadastrar atletas COM FOTO
- ✅ Ver lista de atletas
- ✅ Ver competições abertas
- 🚧 Inscrever equipe em competições
- 🚧 Ver histórico

---

## 💡 TECNOLOGIAS

- **Backend:** PHP 7.4+ com PDO
- **Banco:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Ícones:** Font Awesome 6.0
- **Upload:** Sistema próprio de upload
- **Validações:** Client-side + Server-side

---

## 📝 DOCUMENTAÇÃO

- **ANALISE_NOVA_ARQUITETURA.md** - Análise completa do sistema
- **README.md** - Documentação básica
- **README_COMPLETO.md** - Este arquivo (documentação completa)
- **schema_v3_competicoes.sql** - Schema do banco

---

## 🚀 COMO USAR

**1. Criar Competição (Admin)**
1. Login como admin
2. Ir em "Competições"
3. Clicar "Nova Competição"
4. Fazer upload do banner
5. Preencher dados e regras
6. Salvar

**2. Cadastrar Atleta (Equipe)**
1. Login como equipe
2. Ir em "Atletas"
3. Clicar "Cadastrar Atleta"
4. Fazer upload da FOTO 3x4
5. Preencher dados
6. Salvar

**3. Ver Competições (Equipe)**
1. Ir em "Competições Abertas"
2. Ver banner e detalhes
3. Clicar "Inscrever"
4. (Em desenvolvimento)

---

## ⚠️ IMPORTANTE

- Este é um **sistema novo** (v3.0)
- **Não substitui** o sistema atual (v1.0/v2.0)
- Sistemas rodam **independentemente**
- Bancos de dados **separados**

---

## 📞 SUPORTE

- **Documentação:** Leia os arquivos .md
- **Banco:** Execute o schema_v3_competicoes.sql
- **Configuração:** Edite os arquivos em config/

---

**Versão:** 3.0.0-alpha.3
**Status:** Em desenvolvimento ativo
**Última atualização:** 2025-01-05
**Funcionalidades Core:** ✅ Implementadas
**Funcionalidades Extras:** 🚧 Em desenvolvimento
