# 📚 FUNÇÕES AUXILIARES DO SISTEMA v3.0

## 🔐 FUNÇÕES DE AUTENTICAÇÃO

### `isAdminLoggedIn()`
- **Retorna:** `true` se admin está logado, `false` caso contrário
- **Uso:** `if (isAdminLoggedIn()) { ... }`

### `isEquipeLoggedIn()`
- **Retorna:** `true` se equipe está logada, `false` caso contrário
- **Uso:** `if (isEquipeLoggedIn()) { ... }`

### `requireAdminLogin()`
- **Ação:** Redireciona para login se admin não estiver logado
- **Uso:** `requireAdminLogin();` (no início de páginas admin)

### `requireEquipeLogin()`
- **Ação:** Redireciona para login se equipe não estiver logada
- **Uso:** `requireEquipeLogin();` (no início de páginas equipe)

---

## 🧹 FUNÇÕES DE VALIDAÇÃO E FORMATAÇÃO

### `sanitize($string)`
- **Parâmetro:** String a ser sanitizada
- **Retorna:** String limpa e segura
- **Uso:** `$nome = sanitize($_POST['nome']);`

### `validarCPF($cpf)`
- **Parâmetro:** CPF (com ou sem formatação)
- **Retorna:** `true` se válido, `false` se inválido
- **Uso:** `if (validarCPF($cpf)) { ... }`

### `calcularIdade($dataNascimento)`
- **Parâmetro:** Data no formato Y-m-d
- **Retorna:** Idade em anos (int)
- **Uso:** `$idade = calcularIdade('2000-01-01');`

### `formatarCPF($cpf)`
- **Parâmetro:** CPF (apenas números)
- **Retorna:** CPF formatado (000.000.000-00)
- **Uso:** `echo formatarCPF('12345678901');`

### `formatarTelefone($telefone)`
- **Parâmetro:** Telefone (apenas números)
- **Retorna:** Telefone formatado (95) 99988-7766
- **Uso:** `echo formatarTelefone('95999887766');`

### `formatarData($data)`
- **Parâmetro:** Data no formato Y-m-d
- **Retorna:** Data formatada (dd/mm/aaaa)
- **Uso:** `echo formatarData('2025-01-15');`

### `formatarDataHora($dataHora)`
- **Parâmetro:** Timestamp
- **Retorna:** Data e hora formatadas (dd/mm/aaaa hh:mm)
- **Uso:** `echo formatarDataHora('2025-01-15 14:30:00');`

---

## 📤 FUNÇÕES DE UPLOAD

### `uploadFile($file, $fieldName, $targetDir, $allowedExtensions, $maxSize)`
- **Parâmetros:**
  - `$file`: Array $_FILES['nome_campo']
  - `$fieldName`: Nome do campo (para mensagens de erro)
  - `$targetDir`: Diretório destino (use constantes: BANNER_PATH, FOTO_PATH, etc)
  - `$allowedExtensions`: Array de extensões permitidas
  - `$maxSize`: Tamanho máximo em bytes
- **Retorna:** Nome do arquivo salvo
- **Exceção:** Lança Exception em caso de erro
- **Exemplo:**
```php
$foto = uploadFile(
    $_FILES['foto'],
    'foto',
    FOTO_PATH,
    ALLOWED_IMAGE_EXTENSIONS,
    MAX_FOTO_SIZE
);
```

---

## 📝 FUNÇÕES DE HISTÓRICO

### `registrarHistoricoAtleta($pdo, $atletaId, $eventoTipo, $descricao, $dados = [])`
- **Parâmetros:**
  - `$pdo`: Conexão PDO
  - `$atletaId`: ID do atleta
  - `$eventoTipo`: Tipo do evento (Cadastro, Mudanca_Equipe, etc)
  - `$descricao`: Descrição do evento
  - `$dados`: Array opcional com dados adicionais
- **Uso:**
```php
registrarHistoricoAtleta(
    $pdo,
    $atletaId,
    'Cadastro',
    'Atleta cadastrado na equipe X'
);
```

### `registrarHistoricoEquipe($pdo, $equipeId, $eventoTipo, $descricao, $dados = [])`
- **Parâmetros:**
  - `$pdo`: Conexão PDO
  - `$equipeId`: ID da equipe
  - `$eventoTipo`: Tipo do evento (Cadastro, Adicao_Atleta, etc)
  - `$descricao`: Descrição do evento
  - `$dados`: Array opcional com dados adicionais
- **Uso:**
```php
registrarHistoricoEquipe(
    $pdo,
    $equipeId,
    'Adicao_Atleta',
    'Atleta João adicionado'
);
```

---

## 🔧 FUNÇÕES UTILITÁRIAS

### `gerarProtocolo()`
- **Retorna:** String única para protocolo (ex: INSC6789ABCD)
- **Uso:** `$protocolo = gerarProtocolo();`

### `redirect($url, $mensagem = '', $tipo = 'success')`
- **Parâmetros:**
  - `$url`: URL de destino
  - `$mensagem`: Mensagem flash (opcional)
  - `$tipo`: Tipo da mensagem (success, error, warning, info)
- **Uso:**
```php
redirect('index.php', 'Cadastro realizado com sucesso!', 'success');
```

### `exibirMensagem()`
- **Ação:** Exibe mensagem flash se houver
- **Uso:** `<?php exibirMensagem(); ?>` (no HTML)

---

## 🌐 CONSTANTES DISPONÍVEIS

### URLs
- `BASE_URL` - URL base do sistema
- `UPLOAD_URL` - URL da pasta uploads
- `BANNER_URL` - URL dos banners
- `FOTO_URL` - URL das fotos
- `DOCUMENTO_URL` - URL dos documentos

### Caminhos
- `UPLOAD_PATH` - Caminho físico uploads
- `BANNER_PATH` - Caminho físico banners
- `FOTO_PATH` - Caminho físico fotos
- `DOCUMENTO_PATH` - Caminho físico documentos

### Upload
- `MAX_FILE_SIZE` - 5MB (5 * 1024 * 1024)
- `MAX_FOTO_SIZE` - 2MB (2 * 1024 * 1024)
- `ALLOWED_IMAGE_EXTENSIONS` - ['jpg', 'jpeg', 'png']
- `ALLOWED_DOC_EXTENSIONS` - ['pdf', 'jpg', 'jpeg', 'png']

---

## 📋 EXEMPLO COMPLETO DE USO

### Cadastrar Atleta com Validações

```php
<?php
require_once '../config/config.php';
requireEquipeLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        // 1. Validar e sanitizar
        $nome = sanitize($_POST['nome']);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);

        if (!validarCPF($cpf)) {
            throw new Exception('CPF inválido');
        }

        // 2. Calcular idade
        $idade = calcularIdade($_POST['data_nascimento']);

        // 3. Upload de foto
        $foto = uploadFile(
            $_FILES['foto'],
            'foto',
            FOTO_PATH,
            ALLOWED_IMAGE_EXTENSIONS,
            MAX_FOTO_SIZE
        );

        // 4. Inserir no banco
        $stmt = $pdo->prepare("INSERT INTO atletas ...");
        $stmt->execute([...]);
        $atletaId = $pdo->lastInsertId();

        // 5. Registrar histórico
        registrarHistoricoAtleta(
            $pdo,
            $atletaId,
            'Cadastro',
            "Atleta {$nome} cadastrado"
        );

        // 6. Redirecionar com mensagem
        redirect(
            'atletas.php',
            'Atleta cadastrado com sucesso!',
            'success'
        );

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastrar</title>
</head>
<body>
    <?php exibirMensagem(); ?>
    <!-- Formulário -->
</body>
</html>
```

---

## ✅ VERIFICAÇÃO RÁPIDA

Todas estas funções estão em: **`config/config.php`**

Para usar, basta incluir no início de cada arquivo:
```php
require_once '../config/config.php';
```

Isso já carrega:
- ✅ Sessão iniciada
- ✅ Conexão com banco (via database.php)
- ✅ Todas as constantes
- ✅ Todas as funções auxiliares
