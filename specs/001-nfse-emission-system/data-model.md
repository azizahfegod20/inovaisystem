# Data Model: Sistema de Emissão de NFS-e Padrão Nacional

**Date**: 2026-04-10

## Entity Relationship Diagram (textual)

```
User 1──N CompanyUser N──1 Company
                              │
              ┌───────┬───────┼───────┬───────┐
              │       │       │       │       │
           1──N    1──N    1──N    1──N    1──N
       Certificate Customer Service Invoice AuditLog
                              │
                           N──1
                          Customer
```

## Entities

### users

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| name | VARCHAR(255) | NOT NULL | Nome completo |
| email | VARCHAR(255) | NOT NULL, UNIQUE | E-mail de login |
| password | VARCHAR(255) | NOT NULL | Hash bcrypt |
| email_verified_at | TIMESTAMP | NULLABLE | Verificação de e-mail |
| remember_token | VARCHAR(100) | NULLABLE | Token "lembrar-me" |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

### companies

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| cnpj | VARCHAR(14) | NOT NULL, UNIQUE | CNPJ sem formatação |
| razao_social | VARCHAR(255) | NOT NULL | Razão social |
| nome_fantasia | VARCHAR(255) | NULLABLE | Nome fantasia |
| inscricao_municipal | VARCHAR(20) | NULLABLE | IM |
| inscricao_estadual | VARCHAR(20) | NULLABLE | IE |
| logradouro | VARCHAR(255) | NOT NULL | Endereço |
| numero | VARCHAR(20) | NOT NULL | Número |
| complemento | VARCHAR(100) | NULLABLE | Complemento |
| bairro | VARCHAR(100) | NOT NULL | Bairro |
| codigo_ibge | VARCHAR(7) | NOT NULL | Código IBGE município |
| uf | CHAR(2) | NOT NULL | UF |
| cep | VARCHAR(8) | NOT NULL | CEP sem formatação |
| telefone | VARCHAR(15) | NULLABLE | Telefone |
| email | VARCHAR(255) | NULLABLE | E-mail da empresa |
| regime_tributario | SMALLINT | NOT NULL, DEFAULT 1 | 1=Simples, 2=Lucro Presumido, 3=Lucro Real |
| reg_esp_trib | SMALLINT | NOT NULL, DEFAULT 0 | Regime especial (0=Nenhum, 1=Cooperativa, etc.) |
| dps_serie | VARCHAR(5) | NOT NULL, DEFAULT '00001' | Série DPS (faixa 00001-49999) |
| dps_next_number | BIGINT | NOT NULL, DEFAULT 1 | Próximo nº sequencial DPS |
| ambiente | SMALLINT | NOT NULL, DEFAULT 2 | 1=Produção, 2=Homologação |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Index**: `idx_companies_cnpj` UNIQUE on (cnpj)

### company_user (pivot)

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| user_id | BIGINT | FK → users.id, NOT NULL | |
| company_id | BIGINT | FK → companies.id, NOT NULL | |
| role | VARCHAR(20) | NOT NULL, DEFAULT 'operador' | admin, contador, operador |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Index**: `idx_company_user_unique` UNIQUE on (user_id, company_id)

### certificates

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| company_id | BIGINT | FK → companies.id, NOT NULL | |
| pfx_content | TEXT | NOT NULL | Conteúdo .pfx encrypted (AES-256 via Laravel) |
| pfx_password | TEXT | NOT NULL | Senha encrypted (AES-256 via Laravel) |
| cnpj | VARCHAR(14) | NOT NULL | CNPJ extraído do certificado |
| common_name | VARCHAR(255) | NOT NULL | Nome titular (CN) |
| valid_from | DATE | NOT NULL | Início validade |
| valid_to | DATE | NOT NULL | Fim validade |
| is_active | BOOLEAN | NOT NULL, DEFAULT true | Certificado ativo |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Index**: `idx_certificates_company` on (company_id)
**Validation**: cnpj MUST match companies.cnpj

### customers (tomadores)

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| company_id | BIGINT | FK → companies.id, NOT NULL | Empresa dona |
| tipo_documento | CHAR(1) | NOT NULL | '1'=CPF, '2'=CNPJ |
| documento | VARCHAR(14) | NOT NULL | CPF/CNPJ sem formatação |
| razao_social | VARCHAR(255) | NOT NULL | Razão social / nome |
| nome_fantasia | VARCHAR(255) | NULLABLE | |
| inscricao_municipal | VARCHAR(20) | NULLABLE | |
| logradouro | VARCHAR(255) | NOT NULL | |
| numero | VARCHAR(20) | NOT NULL | |
| complemento | VARCHAR(100) | NULLABLE | |
| bairro | VARCHAR(100) | NOT NULL | |
| codigo_ibge | VARCHAR(7) | NOT NULL | Código IBGE município |
| uf | CHAR(2) | NOT NULL | |
| cep | VARCHAR(8) | NOT NULL | |
| email | VARCHAR(255) | NULLABLE | Para envio de NFS-e |
| telefone | VARCHAR(15) | NULLABLE | |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Index**: `idx_customers_company_doc` UNIQUE on (company_id, documento)

### services

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| company_id | BIGINT | FK → companies.id, NOT NULL | |
| codigo_lc116 | VARCHAR(10) | NOT NULL | Item/subitem LC 116 (ex: "01.01") |
| codigo_nbs | VARCHAR(10) | NULLABLE | Código NBS |
| descricao | VARCHAR(500) | NOT NULL | Descrição do serviço |
| aliquota_iss | DECIMAL(5,4) | NOT NULL | Alíquota ISS padrão (ex: 0.0500 = 5%) |
| is_favorite | BOOLEAN | NOT NULL, DEFAULT false | Serviço frequente |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Index**: `idx_services_company` on (company_id)

### invoices (NFS-e)

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| company_id | BIGINT | FK → companies.id, NOT NULL | Empresa emissora |
| customer_id | BIGINT | FK → customers.id, NOT NULL | Tomador |
| service_id | BIGINT | FK → services.id, NOT NULL | Serviço prestado |
| user_id | BIGINT | FK → users.id, NOT NULL | Usuário que emitiu |
| status | VARCHAR(20) | NOT NULL, DEFAULT 'pending' | pending, authorized, cancelled, replaced |
| id_dps | VARCHAR(42) | NOT NULL, UNIQUE | Identificador DPS (42 posições) |
| dps_number | BIGINT | NOT NULL | Nº sequencial DPS |
| dps_serie | VARCHAR(5) | NOT NULL | Série DPS |
| chave_acesso | VARCHAR(50) | NULLABLE | Chave de acesso NFS-e (retorno ADN) |
| numero_nfse | BIGINT | NULLABLE | Número oficial da NFS-e (retorno ADN) |
| valor_servico | DECIMAL(15,2) | NOT NULL | Valor bruto do serviço |
| valor_deducoes | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Deduções |
| valor_desconto | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Desconto incondicional |
| valor_liquido | DECIMAL(15,2) | NOT NULL | Valor líquido calculado |
| aliquota_iss | DECIMAL(5,4) | NOT NULL | Alíquota ISS aplicada |
| valor_iss | DECIMAL(15,2) | NOT NULL | Valor ISS |
| iss_retido | BOOLEAN | NOT NULL, DEFAULT false | ISS retido pelo tomador |
| valor_ir | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Retenção IR |
| valor_csll | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Retenção CSLL |
| valor_cofins | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Retenção COFINS |
| valor_pis | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Retenção PIS |
| valor_inss | DECIMAL(15,2) | NOT NULL, DEFAULT 0 | Retenção INSS |
| descricao_servico | TEXT | NOT NULL | Discriminação do serviço |
| xml_sent_path | VARCHAR(500) | NULLABLE | Path no MinIO do XML enviado |
| xml_response_path | VARCHAR(500) | NULLABLE | Path no MinIO do XML retorno |
| pdf_path | VARCHAR(500) | NULLABLE | Path no MinIO do DANFSe PDF |
| data_emissao | TIMESTAMP | NOT NULL | Data/hora emissão |
| data_cancelamento | TIMESTAMP | NULLABLE | Data/hora cancelamento |
| motivo_cancelamento | TEXT | NULLABLE | Motivo (min 15 chars) |
| invoice_replaced_id | BIGINT | FK → invoices.id, NULLABLE | NFS-e substituída |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Indexes**:
- `idx_invoices_company` on (company_id)
- `idx_invoices_chave` UNIQUE on (chave_acesso) WHERE chave_acesso IS NOT NULL
- `idx_invoices_id_dps` UNIQUE on (id_dps)
- `idx_invoices_company_serie_number` UNIQUE on (company_id, dps_serie, dps_number)

**State Transitions**:
```
pending → authorized (ADN retornou sucesso)
pending → rejected (ADN retornou erro — nota não é persistida como "rejected", apenas logada)
authorized → cancelled (evento de cancelamento aceito)
authorized → replaced (substituída por nova nota)
```

### audit_logs

| Column | Type | Constraints | Description |
|---|---|---|---|
| id | BIGINT | PK, auto-increment | |
| company_id | BIGINT | FK → companies.id, NOT NULL | |
| user_id | BIGINT | FK → users.id, NOT NULL | |
| invoice_id | BIGINT | FK → invoices.id, NULLABLE | |
| operation | VARCHAR(30) | NOT NULL | emission, cancellation, replacement, certificate_upload |
| payload_summary | TEXT | NOT NULL | JSON resumido da operação |
| result | VARCHAR(10) | NOT NULL | success, failure |
| error_code | VARCHAR(20) | NULLABLE | Código erro ADN |
| ip_address | VARCHAR(45) | NOT NULL | IP do usuário |
| created_at | TIMESTAMP | NOT NULL | Imutável — sem updated_at |

**Index**: `idx_audit_company_date` on (company_id, created_at)
**Note**: Tabela sem UPDATE/DELETE — apenas INSERT (imutável por design)
