# API Contracts: Sistema de Emissão de NFS-e

**Base URL**: `http://localhost:8000/api`
**Auth**: Laravel Sanctum (cookie-based SPA auth)
**Format**: JSON (request/response)

## Auth

### POST /auth/register

**Request**:
```json
{
  "name": "Ana Silva",
  "email": "ana@email.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

**Response** (201):
```json
{
  "user": { "id": 1, "name": "Ana Silva", "email": "ana@email.com" },
  "redirect": "/onboarding"
}
```

### POST /auth/login

**Request**:
```json
{ "email": "ana@email.com", "password": "senha123" }
```

**Response** (200):
```json
{
  "user": { "id": 1, "name": "Ana Silva", "email": "ana@email.com" },
  "companies": [
    { "id": 1, "cnpj": "12345678000190", "razao_social": "Tech Ltda", "role": "admin" }
  ]
}
```

### POST /auth/logout

**Response** (204): No content

### GET /auth/user

**Response** (200):
```json
{
  "id": 1, "name": "Ana Silva", "email": "ana@email.com",
  "companies": [...]
}
```

## Companies

### POST /companies

**Request**:
```json
{
  "cnpj": "12345678000190",
  "razao_social": "Tech Solutions Ltda",
  "nome_fantasia": "Tech Solutions",
  "logradouro": "Rua Exemplo",
  "numero": "100",
  "bairro": "Centro",
  "codigo_ibge": "3550308",
  "uf": "SP",
  "cep": "01001000",
  "regime_tributario": 1,
  "dps_serie": "00001"
}
```

**Response** (201):
```json
{
  "id": 1,
  "cnpj": "12345678000190",
  "razao_social": "Tech Solutions Ltda",
  "ambiente": 2,
  "dps_serie": "00001"
}
```

### GET /companies/{id}

**Response** (200): Company object completo

### PUT /companies/{id}

**Request**: Campos parciais para atualização
**Response** (200): Company atualizado

### POST /companies/{id}/select

Header de sessão: define company ativa para chamadas subsequentes
**Response** (200):
```json
{ "message": "Empresa selecionada", "company_id": 1 }
```

## Certificates

*Todas as rotas abaixo assumem company selecionada via middleware*

### POST /certificates

**Request**: `multipart/form-data`
- `pfx_file`: arquivo .pfx (certificado A1)
- `password`: senha do certificado

**Response** (201):
```json
{
  "id": 1,
  "cnpj": "12345678000190",
  "common_name": "TECH SOLUTIONS LTDA:12345678000190",
  "valid_from": "2025-01-01",
  "valid_to": "2026-01-01",
  "is_active": true
}
```

**Errors**:
- 422: `{ "message": "CNPJ do certificado não confere com a empresa" }`
- 422: `{ "message": "Certificado expirado" }`
- 422: `{ "message": "Senha incorreta" }`

### GET /certificates

**Response** (200): Lista de certificados da empresa com status de validade

### DELETE /certificates/{id}

**Response** (204): No content

## Customers (Tomadores)

### GET /customers

**Query**: `?search=termo&page=1&per_page=10`
**Response** (200): Lista paginada de tomadores

### POST /customers

**Request**:
```json
{
  "tipo_documento": "2",
  "documento": "98765432000190",
  "razao_social": "Cliente ABC Ltda",
  "logradouro": "Av Principal",
  "numero": "500",
  "bairro": "Jardins",
  "codigo_ibge": "3550308",
  "uf": "SP",
  "cep": "04567000",
  "email": "contato@clienteabc.com.br"
}
```

**Response** (201): Customer criado

### PUT /customers/{id}

**Response** (200): Customer atualizado

### DELETE /customers/{id}

**Response** (204): No content

## Services

### GET /services

**Query**: `?search=consultoria&favorites_only=true`
**Response** (200): Lista de serviços

### POST /services

**Request**:
```json
{
  "codigo_lc116": "01.01",
  "codigo_nbs": "1.0101",
  "descricao": "Análise e desenvolvimento de sistemas",
  "aliquota_iss": 0.0500,
  "is_favorite": true
}
```

**Response** (201): Service criado

### PUT /services/{id}

**Response** (200): Service atualizado

### DELETE /services/{id}

**Response** (204): No content

## Invoices (NFS-e)

### POST /invoices

**Request**:
```json
{
  "customer_id": 1,
  "service_id": 1,
  "valor_servico": 5000.00,
  "valor_deducoes": 0,
  "valor_desconto": 0,
  "descricao_servico": "Consultoria em desenvolvimento de sistemas — competência abril/2026",
  "aliquota_iss": 0.0500,
  "iss_retido": false,
  "valor_ir": 0,
  "valor_csll": 0,
  "valor_cofins": 0,
  "valor_pis": 0,
  "valor_inss": 0
}
```

**Response** (201):
```json
{
  "id": 1,
  "id_dps": "355030821234567800019000001000000000000001",
  "dps_number": 1,
  "dps_serie": "00001",
  "chave_acesso": "NFSe35503082123456780001900000100000000000000001",
  "numero_nfse": 12345,
  "status": "authorized",
  "valor_servico": 5000.00,
  "valor_iss": 250.00,
  "valor_liquido": 5000.00,
  "data_emissao": "2026-04-10T14:30:00Z",
  "pdf_url": "/api/invoices/1/pdf"
}
```

**Errors**:
- 422: Validação de campos (tomador, serviço, valor)
- 409: `{ "message": "DPS já emitida (idempotência)", "invoice_id": 1 }`
- 502: `{ "message": "Erro de comunicação com o ADN", "error_code": "ADN_TIMEOUT" }`
- 422: `{ "message": "Rejeição ADN: E1235 — Falha no esquema XML", "error_code": "E1235" }`

### GET /invoices

**Query**: `?status=authorized&customer_id=1&date_from=2026-04-01&date_to=2026-04-30&search=tech&page=1&per_page=10`
**Response** (200): Lista paginada de NFS-e

### GET /invoices/{id}

**Response** (200): NFS-e com todos os detalhes

### POST /invoices/{id}/cancel

**Request**:
```json
{ "motivo": "Serviço não foi prestado conforme contratado" }
```

**Response** (200):
```json
{
  "id": 1,
  "status": "cancelled",
  "data_cancelamento": "2026-04-10T15:00:00Z",
  "motivo_cancelamento": "Serviço não foi prestado conforme contratado"
}
```

**Errors**:
- 422: `{ "message": "Motivo deve ter pelo menos 15 caracteres" }`
- 409: `{ "message": "Nota já cancelada" }`
- 422: `{ "message": "Prazo de cancelamento excedido. Considere substituição." }`

### POST /invoices/{id}/replace

**Request**:
```json
{
  "customer_id": 1,
  "service_id": 1,
  "valor_servico": 6000.00,
  "descricao_servico": "Consultoria em desenvolvimento de sistemas — valor corrigido",
  "motivo": "Correção de valor do serviço prestado"
}
```

**Response** (201): Nova NFS-e com referência à substituída

### GET /invoices/{id}/pdf

**Response**: Binary PDF (application/pdf)
**Headers**: `Content-Disposition: attachment; filename="DANFSe-{chave_acesso}.pdf"`

### GET /invoices/{id}/xml

**Response**: XML da NFS-e (application/xml)

## Dashboard

### GET /dashboard/stats

**Query**: `?date_from=2026-04-01&date_to=2026-04-30`
**Response** (200):
```json
{
  "total_notas": 45,
  "total_receita": 150000.00,
  "total_canceladas": 2,
  "total_iss": 7500.00,
  "total_retencoes": 3000.00
}
```

### GET /dashboard/chart

**Query**: `?date_from=2026-04-01&date_to=2026-04-30&period=daily`
**Response** (200):
```json
{
  "labels": ["2026-04-01", "2026-04-02", "..."],
  "datasets": {
    "receita": [5000, 8000, "..."],
    "notas": [2, 3, "..."]
  }
}
```

## CNPJ Lookup (Auto-preenchimento)

### GET /cnpj-lookup/{cnpj}

Proxy para API pública `publica.cnpj.ws`. Cache Redis 7 dias.

**Response** (200):
```json
{
  "cnpj": "27865757000102",
  "razao_social": "GLOBO COMUNICACAO E PARTICIPACOES S/A",
  "nome_fantasia": "TV/REDE/GLOBO.COM",
  "logradouro": "RUA LOPES QUINTAS",
  "numero": "303",
  "complemento": null,
  "bairro": "JARDIM BOTANICO",
  "cep": "22460901",
  "codigo_ibge": "3304557",
  "uf": "RJ",
  "email": "inteligenciafiscal@tvglobo.com.br",
  "telefone": "2121554551",
  "situacao_cadastral": "Ativa",
  "natureza_juridica": "Sociedade Anônima Fechada",
  "porte": "Demais",
  "simples_nacional": false,
  "mei": false,
  "source": "cnpj.ws",
  "cached_at": "2026-04-10T14:30:00Z"
}
```

**Errors**:
- 404: `{ "message": "CNPJ não encontrado na base da Receita Federal" }`
- 422: `{ "message": "CNPJ inválido" }`
- 429: `{ "message": "Rate limit excedido. Aguarde 1 minuto." }`

## Municipal Parameters

### GET /municipal-params/{codigoIbge}

**Response** (200):
```json
{
  "codigo_ibge": "3550308",
  "municipio": "São Paulo",
  "uf": "SP",
  "aderente_padrao_nacional": true,
  "aliquota_iss_minima": 0.02,
  "aliquota_iss_maxima": 0.05,
  "cached_at": "2026-04-10T10:00:00Z",
  "expires_at": "2026-04-11T10:00:00Z"
}
```

## Members (Company Users)

### GET /companies/{id}/members

**Response** (200):
```json
[
  { "user_id": 1, "name": "Ana Silva", "email": "ana@email.com", "role": "admin" },
  { "user_id": 2, "name": "João", "email": "joao@email.com", "role": "operador" }
]
```

### POST /companies/{id}/members

**Request**:
```json
{ "email": "joao@email.com", "role": "operador" }
```

**Response** (201): Member added

### PUT /companies/{id}/members/{userId}

**Request**:
```json
{ "role": "contador" }
```

**Response** (200): Role updated

### DELETE /companies/{id}/members/{userId}

**Response** (204): Member removed
