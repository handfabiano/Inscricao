# Sistema v3.0 - Gestão de Competições Esportivas

## 🎯 Novo Modelo

Este é um sistema **completamente novo** que substitui o modelo de inscrições individuais por um modelo de **Competições → Equipes → Atletas**.

## 🔄 Fluxo do Sistema

```
1. ADMIN cria COMPETIÇÃO (com banner e regras)
   ↓
2. RESPONSÁVEL cria/gerencia EQUIPE
   ↓
3. EQUIPE cadastra ATLETAS (com foto)
   ↓
4. RESPONSÁVEL inscreve EQUIPE na COMPETIÇÃO
   ↓
5. SISTEMA valida regras automaticamente
```

## ✨ Funcionalidades Principais

### Para Administradores
- ✅ Criar competições com banner personalizado
- ✅ Definir regras (min/max atletas, categorias, etc)
- ✅ Aprovar equipes e inscrições
- ✅ Relatórios completos

### Para Responsáveis de Equipe
- ✅ Gerenciar equipe (escudo, cores, etc)
- ✅ Cadastrar atletas com foto
- ✅ Inscrever equipe em competições
- ✅ Ver histórico completo

### Para o Público
- ✅ Ver competições abertas com banner
- ✅ Cadastrar nova equipe
- ✅ Consultar inscrições

## 🗄️ Banco de Dados

Execute o script: `database/schema_v3_competicoes.sql`

```bash
mysql -u root -p < database/schema_v3_competicoes.sql
```

## ⚙️ Configuração

1. Edite `config/database.php`:
```php
define('DB_NAME', 'inscricao_atletas_v3');
```

2. Edite `config/config.php`:
```php
define('BASE_URL', 'http://localhost/inscricao/v3');
```

3. Crie os diretórios de upload:
```bash
chmod -R 775 public/uploads
```

## 🔐 Acesso Inicial

**Admin:**
- Usuário: `admin`
- Senha: `admin123`

## 📊 Estrutura

```
v3/
├── admin/          # Área administrativa
│   ├── index.php           # Dashboard
│   ├── competicoes.php     # Gestão de competições
│   └── login.php           # Login admin
├── equipe/         # Área do responsável (em desenvolvimento)
├── public/         # Arquivos públicos
│   ├── css/
│   ├── js/
│   └── uploads/
│       ├── banners/        # Banners de competições
│       ├── fotos/          # Fotos de atletas
│       └── documentos/     # Documentos
├── config/         # Configurações
└── database/       # Scripts SQL
```

## 🎨 Recursos Visuais

- **Banner**: Cada competição tem seu banner personalizado
- **Foto**: Cada atleta tem foto 3x4
- **Escudo**: Cada equipe pode ter escudo

## 📋 Validações Automáticas

- Quantidade mínima/máxima de atletas
- Categoria por idade
- Gênero da equipe
- Documentos obrigatórios
- Período de inscrições
- Vagas limitadas

## 📚 Histórico

O sistema mantém histórico completo de:
- Todas ações de atletas
- Todas ações de equipes
- Mudanças de equipe
- Participações em competições

## 🚀 Próximas Implementações

- [ ] Área completa do responsável
- [ ] Cadastro de atletas com foto
- [ ] Sistema de inscrições
- [ ] Validações de regras
- [ ] Histórico visual
- [ ] Relatórios avançados
- [ ] Área pública

## 📞 Suporte

- Documentação: `ANALISE_NOVA_ARQUITETURA.md`
- Schema: `database/schema_v3_competicoes.sql`

---

**Versão:** 3.0.0-alpha
**Status:** Em desenvolvimento ativo
**Data:** 2025-01-05
