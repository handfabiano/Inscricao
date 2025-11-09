# 🚀 Deploy Automático GitHub → Hostinger

## ✅ O que já está pronto:
- ✓ Código configurado
- ✓ Banco de dados funcionando
- ✓ Site rodando em: https://mediumblue-rhinoceros-869852.hostingersite.com/publico/index.php
- ✓ GitHub Actions configurado

## 📋 O que você precisa fazer (5 minutos):

### PASSO 1: Pegar credenciais FTP na Hostinger

1. Acesse: https://hpanel.hostinger.com
2. Clique na sua hospedagem
3. Procure por **"FTP Accounts"** ou **"Gerenciador de Arquivos"**
4. Anote estas 3 informações:
   - **FTP Host**: Exemplo: `ftp.mediumblue-rhinoceros-869852.hostingersite.com` ou `156.67.xxx.xxx`
   - **FTP Username**: Exemplo: `u320952164` ou seu usuário
   - **FTP Password**: Sua senha FTP

> **Dica**: Se não achar, procure por "File Manager" → botão "FTP/SSH" no canto superior direito

---

### PASSO 2: Configurar Secrets no GitHub

1. Acesse: https://github.com/handfabiano/Inscricao/settings/secrets/actions

2. Clique em **"New repository secret"**

3. Adicione 3 secrets (um de cada vez):

**Secret #1:**
```
Name: FTP_SERVER
Secret: [Cole o FTP Host da Hostinger aqui]
```
Clique em **"Add secret"**

**Secret #2:**
```
Name: FTP_USERNAME
Secret: [Cole o FTP Username aqui]
```
Clique em **"Add secret"**

**Secret #3:**
```
Name: FTP_PASSWORD
Secret: [Cole a senha FTP aqui]
```
Clique em **"Add secret"**

---

### PASSO 3: Ativar o Deploy

Volte para o terminal e execute:

```bash
# 1. Ir para branch main
git checkout main

# 2. Fazer merge das correções
git merge claude/fix-null-database-connection-011CUvu7LD2uxL2AftRpfypG

# 3. Enviar para GitHub
git push origin main
```

**PRONTO!** 🎉

---

## 🔍 Verificar se funcionou:

1. Acesse: https://github.com/handfabiano/Inscricao/actions
2. Você verá o workflow "Deploy to Hostinger" rodando
3. Espere 1-2 minutos
4. Quando ficar **verde (✓)**, seu site está atualizado!

---

## 🔄 A partir de agora:

**Toda vez que você fizer:**
```bash
git add .
git commit -m "sua mensagem"
git push origin main
```

**O site será atualizado automaticamente na Hostinger!** 🚀

---

## ⚠️ Importante:

Se o workflow falhar, verifique:
- ✓ Os 3 secrets estão criados corretamente no GitHub
- ✓ As credenciais FTP estão corretas
- ✓ O caminho é `/public_html/` (já está configurado)

---

## 📞 Precisa de ajuda?

Se der erro, me mostre:
1. Print da página Actions no GitHub
2. Ou copie a mensagem de erro
