# 📋 CRUD e Sistema de Fotos - Localizações

## ✅ O que JÁ ESTÁ IMPLEMENTADO:

---

## 🏆 CRUD DE COMPETIÇÕES (Eventos)

### Localização: `/admin/competicoes.php`

**Funcionalidades:**
- ✅ **CREATE** - Criar nova competição (linhas 13-79)
- ✅ **READ** - Listar competições (linhas 83-89)
- ✅ **UPDATE** - Editar competição (`/admin/editar_competicao.php`)
- ✅ **DELETE** - Excluir competição (`/admin/excluir_competicao.php`)
- ✅ **UPLOAD DE BANNER** - Banner do evento (linha 24-34)

**Upload de Imagem:**
```php
// Linha 26-34: Upload de banner para eventos
$bannerFileName = uploadFile(
    $_FILES['banner'],
    'banner',
    BANNER_PATH,  // /public/uploads/banners/
    ALLOWED_IMAGE_EXTENSIONS,  // jpg, jpeg, png
    MAX_FILE_SIZE  // 5MB
);
```

**Campos da Competição:**
- Nome, descrição, banner
- Datas (inscrições, evento)
- Modalidade, categorias, gênero
- Min/max atletas, taxa de inscrição
- Local, cidade, estado
- Regulamento, status

---

## 👥 CRUD DE ATLETAS

### Localização Admin: `/admin/atletas.php`
### Localização Equipe: `/equipe/cadastrar_atleta.php`

**Funcionalidades:**
- ✅ **CREATE** - Cadastrar atleta (`/equipe/cadastrar_atleta.php`)
- ✅ **READ** - Listar atletas (`/admin/atletas.php`, `/equipe/atletas.php`)
- ✅ **UPDATE** - Editar atleta (funcionalidade via admin)
- ✅ **DELETE** - Desativar atleta
- ✅ **UPLOAD DE FOTO 3x4** - Foto obrigatória do atleta (linha 33-45)

**Upload de Foto:**
```php
// Linha 38-45: Upload de foto 3x4 do atleta (OBRIGATÓRIA)
$fotoFileName = uploadFile(
    $_FILES['foto'],
    'foto',
    FOTO_PATH,  // /public/uploads/fotos/
    ALLOWED_IMAGE_EXTENSIONS,  // jpg, jpeg, png
    MAX_FOTO_SIZE  // 2MB
);
```

**Campos do Atleta:**
- Nome completo, CPF, RG
- Data de nascimento, gênero
- **FOTO 3x4** (obrigatória)
- Email, telefone, celular
- Endereço completo (CEP, rua, número, bairro, cidade, estado)
- Responsável legal (se menor de 18 anos)
- Peso, altura, tipo sanguíneo

**Validações:**
- ✅ CPF válido e único
- ✅ Foto obrigatória (max 2MB)
- ✅ Responsável legal para menores de 18 anos
- ✅ Formatos aceitos: JPG, JPEG, PNG

---

## 👨‍👩‍👧‍👦 CRUD DE EQUIPES

### Localização: `/admin/equipes.php`

**Funcionalidades:**
- ✅ **CREATE** - Criar equipe
- ✅ **READ** - Listar equipes
- ✅ **UPDATE** - Editar equipe
- ✅ **DELETE** - Excluir/desativar equipe
- ✅ Aprovar/rejeitar cadastros

---

## 📝 CRUD DE INSCRIÇÕES

### Localização Admin: `/admin/inscricoes.php`
### Localização Equipe: `/equipe/inscricoes.php`

**Funcionalidades:**
- ✅ **CREATE** - Criar inscrição em competição
- ✅ **READ** - Visualizar inscrições
- ✅ **UPDATE** - Editar inscrição, adicionar/remover atletas
- ✅ **DELETE** - Cancelar inscrição
- ✅ Gerenciar status (Pendente, Confirmada, Cancelada)
- ✅ Upload de documentos (comprovantes, autorizações)

---

## 📊 OUTROS CRUDs DISPONÍVEIS

### 1. Modalidades
- Localização: Sistema de configuração
- Criar modalidades esportivas (Futsal, Vôlei, etc.)

### 2. Categorias
- Localização: Sistema de configuração
- Criar categorias por idade (Sub-12, Sub-14, etc.)

### 3. Resultados
- Localização: `/admin/resultados.php`
- Registrar colocações e resultados de competições

### 4. Administradores
- Localização: `/admin/` (sistema de login)
- CRUD de usuários admin

---

## 📁 ESTRUTURA DE PASTAS DE UPLOAD

```
/public/uploads/
├── banners/         ✅ Criada - Banners de eventos/competições
├── fotos/           ✅ Criada - Fotos 3x4 dos atletas
└── documentos/      ✅ Criada - Documentos de inscrição
```

**Configuração (em `/config/config.php`):**
```php
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('BANNER_PATH', UPLOAD_PATH . 'banners/');
define('FOTO_PATH', UPLOAD_PATH . 'fotos/');
define('DOCUMENTO_PATH', UPLOAD_PATH . 'documentos/');

// Tamanhos máximos
define('MAX_FILE_SIZE', 5 * 1024 * 1024);  // 5MB
define('MAX_FOTO_SIZE', 2 * 1024 * 1024);  // 2MB para fotos

// Extensões permitidas
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_DOC_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
```

---

## 🔧 FUNÇÃO DE UPLOAD GENÉRICA

### Localização: `/config/config.php` (linhas 181-223)

```php
function uploadFile($file, $fieldName, $targetDir, $allowedExtensions, $maxSize) {
    // Validações automáticas:
    // ✅ Verifica se arquivo foi enviado
    // ✅ Valida extensão
    // ✅ Valida tamanho
    // ✅ Cria diretório se não existir
    // ✅ Gera nome único (evita conflitos)
    // ✅ Move arquivo para pasta
    // ❌ Lança exceção se houver erro
}
```

---

## 🎨 PREVIEW DE IMAGENS

### No formulário de cadastro de atleta (`/equipe/cadastrar_atleta.php`):

```html
<!-- Linha 163: Input de foto com preview -->
<input type="file" name="foto" accept="image/*" required onchange="previewFoto(event)">

<!-- Preview da foto -->
<div id="previewFoto">
    <img id="imgPreview" alt="Preview" style="max-width: 150px;">
</div>
```

**JavaScript para preview:**
```javascript
function previewFoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}
```

---

## 📸 ONDE AS FOTOS SÃO EXIBIDAS

### 1. Lista de Atletas
- **Admin**: `/admin/atletas.php` (linha 95-100)
  ```html
  <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
       class="foto-circular" alt="Foto">
  ```

### 2. Detalhes do Atleta
- Modal com informações completas
- Foto em tamanho maior

### 3. Fichas de Inscrição
- Foto do atleta na lista de inscritos

### 4. Banners de Competições
- Página pública (`/publico/index.php`)
- Cards de eventos

---

## 🔐 SEGURANÇA DE UPLOAD

**Validações implementadas:**
- ✅ Extensão de arquivo permitida
- ✅ Tamanho máximo (2MB fotos, 5MB banners)
- ✅ Nome de arquivo único (evita sobrescrever)
- ✅ Validação de tipo MIME
- ✅ Pasta com permissões corretas (755)

**Proteções:**
- ❌ Não aceita arquivos executáveis
- ❌ Não aceita extensões perigosas
- ✅ Arquivos armazenados fora do diretório web principal
- ✅ Nomes gerados com uniqid() + timestamp

---

## 🚀 COMO USAR

### Cadastrar Atleta com Foto:

1. Acesse: `/equipe/cadastrar_atleta.php`
2. Preencha dados pessoais
3. **Selecione foto 3x4** (obrigatória)
4. Preview aparece automaticamente
5. Submeta o formulário
6. Foto é salva em `/public/uploads/fotos/`

### Criar Competição com Banner:

1. Acesse: `/admin/competicoes.php`
2. Clique em "Nova Competição"
3. Preencha dados do evento
4. **Upload de banner** (opcional)
5. Submeta o formulário
6. Banner é salvo em `/public/uploads/banners/`

---

## ✅ RESUMO

| Funcionalidade | Status | Localização |
|----------------|--------|-------------|
| CRUD Atletas | ✅ Completo | `/admin/atletas.php`, `/equipe/cadastrar_atleta.php` |
| CRUD Competições | ✅ Completo | `/admin/competicoes.php` |
| CRUD Equipes | ✅ Completo | `/admin/equipes.php` |
| CRUD Inscrições | ✅ Completo | `/admin/inscricoes.php`, `/equipe/inscricoes.php` |
| Upload Foto Atleta | ✅ Funcional | Obrigatória no cadastro |
| Upload Banner Evento | ✅ Funcional | Opcional na competição |
| Upload Documentos | ✅ Funcional | Em inscrições |
| Preview de Imagem | ✅ Funcional | JavaScript no formulário |
| Validação de Arquivos | ✅ Completo | Extensão, tamanho, tipo |
| Pastas de Upload | ✅ Criadas | `/public/uploads/*` |

---

## 🔧 PRÓXIMAS MELHORIAS SUGERIDAS

- [ ] Redimensionamento automático de imagens
- [ ] Compressão de fotos antes do upload
- [ ] Galeria de fotos do evento
- [ ] Upload múltiplo de arquivos
- [ ] Crop de imagem no cliente
- [ ] Integração com CDN para fotos
