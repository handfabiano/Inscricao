# 🚀 Implementações Enterprise - Sistema de Gestão de Eventos Esportivos

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Implementações Concluídas](#implementações-concluídas)
- [Instalação e Configuração](#instalação-e-configuração)
- [Documentação da API](#documentação-da-api)
- [Sistema de Pagamentos](#sistema-de-pagamentos)
- [Multi-Tenancy](#multi-tenancy)
- [Próximos Passos](#próximos-passos)

---

## 🎯 Visão Geral

Este documento descreve as implementações enterprise realizadas no sistema para transformá-lo em uma solução pronta para federações e instituições esportivas de grande porte.

### Status das Implementações

✅ **CONCLUÍDO** - Multi-Tenancy (Organizações)
✅ **CONCLUÍDO** - API RESTful v1 com autenticação
✅ **CONCLUÍDO** - Sistema de Pagamentos (PIX + Cartão)
⏳ **PENDENTE** - Autenticação Multifator (2FA)
⏳ **PENDENTE** - Proteção CSRF
⏳ **PENDENTE** - Sistema de Cache (Redis)
⏳ **PENDENTE** - Testes Automatizados
⏳ **PENDENTE** - Dashboard Executivo Avançado

---

## ✅ Implementações Concluídas

### 1. Multi-Tenancy (Organizações)

**Descrição:** Sistema completo para suportar múltiplas organizações (federações, confederações, clubes) em uma única instalação.

**Características:**
- ✅ Tabela de organizações com dados completos
- ✅ 3 planos de assinatura (Free, Pro, Enterprise)
- ✅ Limites por plano (eventos, equipes, atletas, armazenamento)
- ✅ Isolamento total de dados por organização
- ✅ Branding personalizado (logo, cores)
- ✅ Domínios customizados
- ✅ Controle de API por organização

**Arquivos Criados:**
```
migrations/001_create_multi_tenancy_structure.sql
migrations/002_add_organization_id_to_existing_tables.sql
includes/multi_tenancy_helper.php
migrations/run_migrations.php
```

**Tabelas Criadas:**
- `planos_assinatura` - Planos disponíveis (Free, Pro, Enterprise)
- `organizacoes` - Dados das organizações
- `usuarios_organizacao` - Usuários de cada organização
- `historico_assinaturas` - Histórico de mudanças de plano
- `api_tokens` - Tokens de API individuais

**Funções Disponíveis:**
```php
getCurrentOrganization()           // Obtém organização atual
getCurrentOrganizationId()          // Obtém ID da organização
organizationHasFeature($feature)    // Verifica acesso a feature
checkOrganizationLimit($resource)   // Verifica limites
incrementOrganizationUsage($res)    // Incrementa contador
validateOrganizationOwnership()     // Valida propriedade de registro
```

### 2. API RESTful v1

**Descrição:** API completa para integração com sistemas externos.

**Características:**
- ✅ Autenticação via API Key + Secret
- ✅ Rate limiting (requisições/minuto configurável)
- ✅ Versionamento (v1)
- ✅ Respostas padronizadas JSON
- ✅ CORS habilitado
- ✅ Logs de acesso
- ⏳ JWT tokens (estrutura pronta, implementação pendente)

**Arquivos Criados:**
```
api/v1/index.php                    // Router principal
api/v1/middleware/auth.php          // Autenticação
api/v1/middleware/rate_limit.php    // Rate limiting
api/v1/routes/auth.php              // Rotas de autenticação
api/v1/routes/competitions.php      // Rotas de competições
api/v1/routes/teams.php             // Rotas de equipes
api/v1/routes/athletes.php          // Rotas de atletas
api/v1/routes/registrations.php     // Rotas de inscrições
api/v1/routes/rankings.php          // Rotas de rankings
api/v1/.htaccess                    // Redirecionamento
```

**Endpoints Disponíveis:**

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/v1/auth/login` | Login e obtenção de credenciais |
| GET | `/api/v1/auth/me` | Informações da organização autenticada |
| GET | `/api/v1/competitions` | Listar competições |
| POST | `/api/v1/competitions` | Criar competição |
| GET | `/api/v1/competitions/{id}` | Detalhes da competição |
| PUT | `/api/v1/competitions/{id}` | Atualizar competição |
| DELETE | `/api/v1/competitions/{id}` | Excluir competição |
| GET | `/api/v1/teams` | Listar equipes |
| POST | `/api/v1/teams` | Criar equipe |
| GET | `/api/v1/athletes` | Listar atletas |
| POST | `/api/v1/athletes` | Criar atleta |
| POST | `/api/v1/registrations` | Criar inscrição |
| GET | `/api/v1/rankings` | Obter rankings |

### 3. Sistema de Pagamentos

**Descrição:** Sistema completo de pagamentos integrado com gateways brasileiros.

**Características:**
- ✅ Suporte a PIX, Cartão de Crédito, Boleto
- ✅ Integração Mercado Pago (implementada)
- ✅ Integração Asaas (estrutura pronta)
- ✅ Webhooks para notificações
- ✅ Split de pagamento
- ✅ Sistema de reembolsos
- ✅ Criptografia de credenciais
- ✅ Log completo de transações

**Arquivos Criados:**
```
migrations/003_create_payment_system.sql
includes/payment_helper.php
```

**Tabelas Criadas:**
- `payment_gateways` - Configuração de gateways
- `payment_transactions` - Transações de pagamento
- `payment_webhooks_log` - Log de webhooks
- `payment_splits` - Split de pagamentos
- `payment_refunds` - Reembolsos

**Funções Disponíveis:**
```php
getDefaultPaymentGateway()          // Obtém gateway padrão
createPaymentTransaction($dados)    // Cria transação
processarPagamentoPix()             // Processa PIX
processarPagamentoCartao()          // Processa cartão
processPaymentWebhook()             // Processa webhook
solicitarReembolso()                // Solicita reembolso
```

---

## 🔧 Instalação e Configuração

### Passo 1: Executar Migrations

Execute o script de migrations para criar todas as tabelas:

```bash
php migrations/run_migrations.php
```

Ou execute manualmente cada migration na ordem:
```bash
mysql -u usuario -p database < migrations/001_create_multi_tenancy_structure.sql
mysql -u usuario -p database < migrations/002_add_organization_id_to_existing_tables.sql
mysql -u usuario -p database < migrations/003_create_payment_system.sql
```

### Passo 2: Configurar Gateway de Pagamento

#### Mercado Pago

1. Acesse: https://www.mercadopago.com.br/developers
2. Obtenha suas credenciais (Access Token)
3. Configure no banco de dados:

```sql
UPDATE payment_gateways
SET access_token = 'SEU_ACCESS_TOKEN_AQUI',
    ambiente = 'production',
    ativo = TRUE
WHERE gateway = 'mercadopago' AND organizacao_id = 1;
```

**⚠️ IMPORTANTE:** Em produção, criptografe as credenciais:
```php
$encrypted = encryptPaymentData('SEU_ACCESS_TOKEN');
```

#### Webhook URL

Configure no painel do Mercado Pago:
```
https://seudominio.com.br/webhooks/payment.php
```

### Passo 3: Configurar API

1. Gere credenciais de API para a organização:

```sql
UPDATE organizacoes
SET api_key = 'CHAVE_GERADA_AUTOMATICAMENTE',
    api_secret = 'SECRET_HASH',
    api_ativa = TRUE,
    api_rate_limit = 100
WHERE id = 1;
```

Ou use a função PHP:
```php
setCurrentOrganization(1);
$credentials = regenerateOrganizationApiCredentials();
echo "API Key: " . $credentials['api_key'];
echo "API Secret: " . $credentials['api_secret'];
```

### Passo 4: Configurar .htaccess

Certifique-se de que o Apache tem mod_rewrite habilitado:
```bash
a2enmod rewrite
service apache2 restart
```

---

## 📖 Documentação da API

### Autenticação

Todas as requisições (exceto `/auth/login`) devem incluir headers de autenticação:

```http
X-API-Key: sua_api_key_aqui
X-API-Secret: seu_api_secret_aqui
```

Ou use Bearer token (quando implementado):
```http
Authorization: Bearer seu_token_jwt
```

### Exemplo: Login

**Requisição:**
```bash
curl -X POST https://seudominio.com.br/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@federacao.com",
    "senha": "senha123",
    "tipo": "admin"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "user": {
      "id": 1,
      "nome": "Administrador",
      "email": "admin@federacao.com",
      "tipo": "admin",
      "organizacao_id": 1
    },
    "api_key": "abc123...",
    "expires_at": "2025-11-09T10:00:00+00:00"
  }
}
```

### Exemplo: Criar Competição

**Requisição:**
```bash
curl -X POST https://seudominio.com.br/api/v1/competitions \
  -H "X-API-Key: abc123..." \
  -H "X-API-Secret: xyz789..." \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Campeonato Estadual Sub-15",
    "modalidade_id": 1,
    "data_inicio_inscricoes": "2025-11-10",
    "data_fim_inscricoes": "2025-11-30",
    "data_inicio_evento": "2025-12-05",
    "data_fim_evento": "2025-12-15",
    "taxa_inscricao": 150.00
  }'
```

### Exemplo: Listar Competições

**Requisição:**
```bash
curl -X GET "https://seudominio.com.br/api/v1/competitions?status=Aberta&page=1&per_page=20" \
  -H "X-API-Key: abc123..." \
  -H "X-API-Secret: xyz789..."
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "competicoes": [
      {
        "id": 1,
        "nome": "Campeonato Estadual",
        "status": "Aberta",
        "total_inscricoes": 15
      }
    ],
    "pagination": {
      "total": 50,
      "page": 1,
      "per_page": 20,
      "total_pages": 3
    }
  }
}
```

### Rate Limiting

O sistema limita requisições por minuto conforme o plano:
- **Free:** 60 req/min
- **Pro:** 100 req/min
- **Enterprise:** 1000 req/min

Se exceder, receberá:
```json
{
  "success": false,
  "message": "Rate limit excedido. Tente novamente em 45 segundos",
  "details": {
    "retry_after": 45
  }
}
```

Headers de resposta:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 2025-11-08T10:05:00+00:00
```

---

## 💳 Sistema de Pagamentos

### Criar Pagamento PIX

```php
require_once 'includes/payment_helper.php';

// 1. Criar transação
$resultado = createPaymentTransaction([
    'tipo_referencia' => 'inscricao',
    'referencia_id' => 123, // ID da inscrição
    'valor' => 150.00,
    'metodo_pagamento' => 'pix',
    'pagador_nome' => 'João Silva',
    'pagador_email' => 'joao@email.com',
    'pagador_cpf_cnpj' => '12345678901'
]);

if ($resultado['success']) {
    $transaction_id = $resultado['transaction_id'];

    // 2. Processar PIX
    $pix = processarPagamentoPix(
        $transaction_id,
        150.00,
        'Inscrição Campeonato Estadual'
    );

    if ($pix['success']) {
        // Exibir QR Code para o usuário
        echo '<img src="data:image/png;base64,' . $pix['qr_code_base64'] . '">';
        echo '<p>Código PIX Copia e Cola:</p>';
        echo '<code>' . $pix['qr_code'] . '</code>';
    }
}
```

### Webhook de Pagamento

Crie o arquivo `webhooks/payment.php`:

```php
<?php
require_once '../includes/payment_helper.php';

// Receber payload
$payload = json_decode(file_get_contents('php://input'), true);

// Processar webhook
$resultado = processPaymentWebhook('mercadopago', $payload);

if ($resultado['success']) {
    http_response_code(200);
    echo json_encode(['status' => 'processed']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error']);
}
```

### Consultar Status de Pagamento

```php
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT * FROM payment_transactions
    WHERE id = ? AND organizacao_id = ?
");
$stmt->execute([$transaction_id, $org_id]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Status: " . $transacao['status']; // approved, pending, rejected
```

### Solicitar Reembolso

```php
$resultado = solicitarReembolso(
    $transaction_id,  // ID da transação
    150.00,           // Valor
    'Cancelamento do evento', // Motivo
    $admin_id         // Quem solicitou
);

if ($resultado['success']) {
    echo "Reembolso solicitado com sucesso!";
}
```

---

## 🏢 Multi-Tenancy

### Criar Nova Organização

```sql
INSERT INTO organizacoes (
    nome, sigla, tipo, cnpj, email,
    responsavel_nome, responsavel_email,
    plano_id, data_inicio_assinatura, status_assinatura
) VALUES (
    'Federação Paulista de Futsal',
    'FPFS',
    'federacao',
    '12.345.678/0001-90',
    'contato@fpfs.com.br',
    'Carlos Silva',
    'carlos@fpfs.com.br',
    2, -- Plano Pro
    CURDATE(),
    'ativa'
);
```

### Verificar Limites

```php
setCurrentOrganization(1);

// Verificar limite de eventos
$limite_eventos = checkOrganizationLimit('eventos');
if ($limite_eventos['atingido']) {
    echo "Limite atingido! {$limite_eventos['atual']}/{$limite_eventos['limite']}";
    echo "Faça upgrade do plano!";
} else {
    echo "Você pode criar mais " . ($limite_eventos['limite'] - $limite_eventos['atual']) . " eventos";
}

// Verificar acesso a funcionalidade
if (organizationHasFeature('permite_api')) {
    echo "API disponível!";
} else {
    echo "Faça upgrade para acessar a API";
}
```

### Exibir Alertas de Limite

```php
// Alerta automático ao atingir 80% do limite
echo showLimitAlert('eventos', 80);
echo showLimitAlert('atletas', 90);
```

---

## 📊 Próximos Passos

### Fase 1 - Fundação (Em Andamento)
- [x] Multi-tenancy
- [x] API RESTful
- [x] Sistema de pagamentos
- [ ] Autenticação 2FA
- [ ] Proteção CSRF
- [ ] Testes automatizados
- [ ] App mobile MVP

### Fase 2 - Funcionalidades Esportivas
- [ ] Gestão avançada de competições (chaveamento)
- [ ] Súmula eletrônica
- [ ] Sistema de arbitragem
- [ ] Estatísticas avançadas
- [ ] Rankings dinâmicos

### Fase 3 - Analytics
- [ ] Dashboard executivo
- [ ] Relatórios avançados
- [ ] Previsões com IA
- [ ] Identificação de talentos

### Fase 4 - Integrações
- [ ] CBF/COB
- [ ] WhatsApp Business API
- [ ] Streaming (YouTube Live)
- [ ] ERPs corporativos

---

## 🛠️ Manutenção

### Reset Mensal de Contadores

Execute via cron (1º dia de cada mês):
```bash
0 0 1 * * php /caminho/reset_monthly_counters.php
```

```php
<?php
// reset_monthly_counters.php
require_once 'includes/multi_tenancy_helper.php';
resetMonthlyEmailCounter();
```

### Limpeza de Logs

```sql
-- Limpar webhooks antigos (> 90 dias)
DELETE FROM payment_webhooks_log
WHERE recebido_em < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Limpar rate limit antigo
DELETE FROM api_rate_limit
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Consulte esta documentação
2. Verifique os logs em `logs_sistema`
3. Abra uma issue no repositório

---

**Versão:** 1.0
**Data:** 2025-11-08
**Autor:** Sistema Enterprise - Gestão de Eventos Esportivos
