# Quickstart: Sistema de Emissão de NFS-e

## Pré-requisitos

- Docker e docker-compose instalados
- Certificado digital ICP-Brasil A1 (.pfx) para testes (pode ser auto-assinado em homologação)
- Acesso ao ambiente de Produção Restrita do ADN (https://adn.producaorestrita.nfse.gov.br)

## 1. Clone e suba os containers

```bash
git clone <repo-url> && cd inovaisystem
git checkout 001-nfse-emission-system
docker compose up -d
```

Serviços disponíveis:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379
- **MinIO Console**: http://localhost:9001 (admin / minio_secret)
- **MinIO API (S3)**: http://localhost:9000

## 2. Setup do backend

```bash
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed  # Dados iniciais (tabela LC 116)
```

## 3. Configuração do ambiente

Variáveis de ambiente importantes (`backend/.env`):

```env
# ADN (Padrão Nacional NFS-e)
NFSE_AMBIENTE=2                    # 1=Produção, 2=Homologação
NFSE_ADN_URL=https://adn.producaorestrita.nfse.gov.br
NFSE_XSD_PATH=resources/xsd/nfse_v1.00.02.xsd

# MinIO (Object Storage)
MINIO_ENDPOINT=http://minio:9000
MINIO_ACCESS_KEY=admin
MINIO_SECRET_KEY=minio_secret
MINIO_BUCKET=inovai-nfse
MINIO_USE_PATH_STYLE=true

# Sanctum (SPA Auth)
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

## 4. Fluxo de teste manual

1. **Registrar**: POST /api/auth/register
2. **Criar empresa**: POST /api/companies (CNPJ válido)
3. **Upload certificado**: POST /api/certificates (arquivo .pfx + senha)
4. **Cadastrar tomador**: POST /api/customers
5. **Cadastrar serviço**: POST /api/services (código LC 116)
6. **Emitir NFS-e**: POST /api/invoices
7. **Baixar PDF**: GET /api/invoices/{id}/pdf
8. **Cancelar**: POST /api/invoices/{id}/cancel

## 5. Testes automatizados

```bash
# Backend (PHPUnit)
docker compose exec backend php artisan test

# Testes específicos de lógica fiscal
docker compose exec backend php artisan test --filter=DpsBuilder
docker compose exec backend php artisan test --filter=XmlSigner
docker compose exec backend php artisan test --filter=XsdValidator
docker compose exec backend php artisan test --filter=TaxCalculation
```

## 6. Verificação de saúde

| Serviço | Healthcheck |
|---|---|
| Backend | GET /api/health → 200 |
| PostgreSQL | `pg_isready -U inovai` |
| Redis | `redis-cli ping` → PONG |
| MinIO | GET /minio/health/live → 200 |
