# Configuração de Deploy Automático

Este projeto está configurado para fazer deploy automático na Hostinger via GitHub Actions.

## Como Configurar

### 1. Obter Credenciais FTP/SFTP da Hostinger

1. Acesse o painel da Hostinger (hpanel.hostinger.com)
2. Vá em **Hospedagem** → Selecione seu domínio
3. Procure por **Gerenciador de Arquivos** ou **FTP/SSH**
4. Anote as seguintes informações:
   - **FTP Server** (hostname): Geralmente `ftp.seudominio.com` ou IP
   - **FTP Username**: Seu usuário FTP
   - **FTP Password**: Sua senha FTP
   - **Caminho do servidor**: Geralmente `/public_html/` ou `/domains/seudominio.com/public_html/`

### 2. Configurar GitHub Secrets

1. Acesse seu repositório no GitHub
2. Vá em **Settings** → **Secrets and variables** → **Actions**
3. Clique em **New repository secret**
4. Adicione os seguintes secrets:

   - **Nome:** `FTP_SERVER`
     **Valor:** Seu servidor FTP (ex: `ftp.mediumblue-rhinoceros-869852.hostingersite.com` ou IP)

   - **Nome:** `FTP_USERNAME`
     **Valor:** Seu usuário FTP

   - **Nome:** `FTP_PASSWORD`
     **Valor:** Sua senha FTP

### 3. Como Funciona

Após configurar os secrets:

1. Toda vez que você fizer **push** para a branch `main` ou `master`, o GitHub Actions:
   - Faz checkout do código
   - Conecta no servidor FTP da Hostinger
   - Faz upload apenas dos arquivos modificados
   - Exclui arquivos desnecessários (.git, node_modules, etc.)

2. Você também pode executar o deploy manualmente:
   - Vá em **Actions** no GitHub
   - Selecione o workflow "Deploy to Hostinger"
   - Clique em **Run workflow**

### 4. Verificar Deploy

1. Após fazer push, vá em **Actions** no GitHub
2. Você verá o workflow rodando
3. Clique nele para ver os logs em tempo real
4. Quando terminar (✓ verde), seu site estará atualizado

### 5. Estrutura de Pastas na Hostinger

Certifique-se de que o `server-dir` no arquivo `.github/workflows/deploy.yml` corresponde à sua estrutura:

- Se seus arquivos devem estar em `/public_html/`, deixe como está
- Se for diferente, edite o arquivo `.github/workflows/deploy.yml` e altere a linha `server-dir`

### 6. Dicas de Segurança

- ✅ **NUNCA** comite credenciais no código
- ✅ Use apenas GitHub Secrets para senhas
- ✅ Mantenha o arquivo `config/database.php` com variáveis de ambiente ou secrets
- ✅ Adicione arquivos sensíveis ao `.gitignore`

### 7. Solução de Problemas

**Erro: "Could not connect to server"**
- Verifique se o FTP_SERVER está correto
- Tente com `ftp.seudominio.com` ou o IP fornecido pela Hostinger

**Erro: "Login incorrect"**
- Verifique FTP_USERNAME e FTP_PASSWORD
- Teste as credenciais em um cliente FTP (FileZilla) primeiro

**Arquivos não aparecem no servidor**
- Verifique se o `server-dir` está correto
- Alguns servidores usam `/domains/seudominio.com/public_html/` em vez de `/public_html/`

## Alternativa: Deploy via SFTP (Mais Seguro)

Se sua hospedagem Hostinger suporta SSH/SFTP, você pode usar:

```yaml
- name: Deploy via SFTP
  uses: wlixcc/SFTP-Deploy-Action@v1.2.4
  with:
    server: ${{ secrets.SFTP_HOST }}
    username: ${{ secrets.SFTP_USERNAME }}
    password: ${{ secrets.SFTP_PASSWORD }}
    local_path: './*'
    remote_path: '/public_html'
    sftp_only: true
```

E adicione os secrets correspondentes (SFTP_HOST, SFTP_USERNAME, SFTP_PASSWORD).
