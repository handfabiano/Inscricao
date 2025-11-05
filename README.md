# Sistema de Inscrição de Atletas

Sistema completo para gerenciamento de inscrições de atletas em diversas modalidades esportivas.

## Funcionalidades

### Para Atletas/Usuários
- Formulário completo de inscrição com validação de dados
- Upload de documentos (foto 3x4, RG, comprovante de residência, atestado médico)
- Validação automática de CPF
- Busca automática de endereço por CEP (integração com ViaCEP)
- Consulta de inscrição por CPF ou Protocolo
- Geração de comprovante em PDF
- Design responsivo (mobile-first)
- Máscara automática para CPF, telefone e CEP

### Para Administradores
- Painel administrativo completo
- Dashboard com estatísticas
- Visualização detalhada de inscrições
- Aprovação/Rejeição de inscrições
- Sistema de filtros e busca
- Adição de observações
- Visualização de documentos enviados
- Sistema de logs de ações

## Tecnologias Utilizadas

- **Frontend:**
  - HTML5
  - CSS3 (Design responsivo)
  - JavaScript (ES6+)
  - Font Awesome (Ícones)

- **Backend:**
  - PHP 7.4+
  - MySQL 5.7+
  - PDO (PHP Data Objects)

- **APIs Externas:**
  - ViaCEP (Busca de endereço por CEP)

## Estrutura do Projeto

```
Inscricao/
├── admin/                      # Painel administrativo
│   ├── index.php              # Dashboard
│   ├── login.php              # Login do admin
│   ├── logout.php             # Logout
│   ├── visualizar.php         # Visualizar inscrição
│   ├── aprovar.php            # Aprovar inscrição
│   └── rejeitar.php           # Rejeitar inscrição
├── config/                     # Configurações
│   ├── config.php             # Configurações gerais
│   └── database.php           # Configuração do banco de dados
├── database/                   # Scripts SQL
│   └── schema.sql             # Schema do banco de dados
├── includes/                   # Arquivos de processamento
│   └── processar_inscricao.php # Processar formulário de inscrição
├── public/                     # Arquivos públicos
│   ├── css/
│   │   └── style.css          # Estilos CSS
│   ├── js/
│   │   └── script.js          # Scripts JavaScript
│   ├── images/                # Imagens do site
│   └── uploads/               # Arquivos enviados pelos usuários
├── index.php                   # Página principal (formulário de inscrição)
├── consulta.php               # Consultar inscrição
├── sucesso.php                # Página de sucesso após inscrição
├── comprovante.php            # Gerar comprovante em PDF
└── README.md                  # Este arquivo
```

## Instalação

### Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL, GD (opcional, para manipulação de imagens)

### Passo a Passo

1. **Clone ou baixe o projeto**
   ```bash
   git clone [url-do-repositorio]
   cd Inscricao
   ```

2. **Configure o banco de dados**
   - Crie um banco de dados MySQL
   - Importe o arquivo `database/schema.sql`
   ```sql
   mysql -u root -p < database/schema.sql
   ```

3. **Configure as credenciais do banco de dados**
   - Edite o arquivo `config/database.php`
   - Altere as constantes conforme seu ambiente:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');
   define('DB_NAME', 'inscricao_atletas');
   ```

4. **Configure a URL base**
   - Edite o arquivo `config/config.php`
   - Altere a constante `BASE_URL`:
   ```php
   define('BASE_URL', 'http://localhost/inscricao');
   ```

5. **Configure permissões da pasta de uploads**
   ```bash
   chmod 755 public/uploads
   ```

6. **Acesse o sistema**
   - Frontend: `http://localhost/inscricao`
   - Admin: `http://localhost/inscricao/admin/login.php`

## Credenciais Padrão do Admin

- **Usuário:** admin
- **Senha:** admin123

**IMPORTANTE:** Altere a senha padrão após o primeiro login!

## Alterando a Senha do Admin

Execute o seguinte SQL para gerar uma nova senha:

```php
<?php
echo password_hash('sua_nova_senha', PASSWORD_DEFAULT);
?>
```

Depois atualize no banco:

```sql
UPDATE usuarios SET password = 'hash_gerado_acima' WHERE username = 'admin';
```

## Modalidades e Categorias

O sistema vem com modalidades e categorias pré-cadastradas. Para adicionar novas:

```sql
-- Adicionar nova modalidade
INSERT INTO modalidades (nome, descricao) VALUES ('Nome da Modalidade', 'Descrição');

-- Adicionar nova categoria
INSERT INTO categorias (nome, idade_minima, idade_maxima, descricao)
VALUES ('Sub-XX', idade_min, idade_max, 'Descrição');
```

## Personalizações

### Alterar cores do tema
Edite as variáveis CSS em `public/css/style.css`:

```css
:root {
    --primary-color: #2563eb;
    --primary-dark: #1e40af;
    --secondary-color: #64748b;
    /* ... outras cores ... */
}
```

### Adicionar novos campos ao formulário
1. Adicione a coluna no banco de dados
2. Adicione o campo no formulário (`index.php`)
3. Adicione o processamento em `includes/processar_inscricao.php`
4. Adicione a validação em `public/js/script.js`

## Funcionalidades de Validação

- **CPF:** Validação completa com dígito verificador
- **Email:** Validação de formato
- **Data de Nascimento:** Verifica se é menor de idade para exigir responsável
- **CEP:** Busca automática de endereço
- **Arquivos:** Validação de tipo e tamanho (máx. 5MB)
- **Campos obrigatórios:** Validação client-side e server-side

## Segurança

- Senhas criptografadas com `password_hash()` (bcrypt)
- Proteção contra SQL Injection (PDO com prepared statements)
- Sanitização de inputs
- Validação de tipos de arquivo
- Sistema de sessões para área administrativa
- Logs de ações administrativas

## Suporte a Dispositivos Móveis

O sistema é totalmente responsivo e funciona em:
- Smartphones
- Tablets
- Desktops
- Impressão (layout otimizado para impressão de comprovantes)

## Problemas Comuns

### Erro de upload de arquivos
- Verifique as permissões da pasta `public/uploads`
- Verifique as configurações de `upload_max_filesize` e `post_max_size` no php.ini

### Erro de conexão com banco de dados
- Verifique as credenciais em `config/database.php`
- Certifique-se de que o MySQL está rodando

### CEP não encontra endereço
- Verifique sua conexão com a internet
- A API ViaCEP pode estar temporariamente indisponível

## Desenvolvimento Futuro

Melhorias planejadas:
- [ ] Envio de emails automáticos
- [ ] Exportação de relatórios em Excel
- [ ] Sistema de pagamento de taxas
- [ ] Notificações push
- [ ] API REST para integração
- [ ] Área do atleta para acompanhamento
- [ ] QR Code no comprovante
- [ ] Multi-idioma

## Licença

Este projeto é de código aberto. Sinta-se livre para usar e modificar conforme necessário.

## Suporte

Para suporte ou dúvidas, entre em contato através de:
- Email: suporte@inscricoes.com
- GitHub Issues: [criar issue]

## Autores

Sistema desenvolvido para gerenciamento de inscrições esportivas.

---

**Versão:** 1.0.0
**Data:** 2025
**Status:** Em Produção
