# Implementation Plan: Sistema de Emissão de NFS-e Padrão Nacional

**Branch**: `001-nfse-emission-system` | **Date**: 2026-04-10 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-nfse-emission-system/spec.md`

## Summary

Sistema completo de emissão de NFS-e no Padrão Nacional brasileiro. O backend Laravel monta a DPS (XML XSD V1.00.02), assina com XMLDSIG via certificado ICP-Brasil A1, comprime (GZip+Base64) e envia ao ADN via mTLS. XMLs e PDFs armazenados no MinIO. Frontend Nuxt consome API REST para oferecer interface de emissão, cancelamento, substituição, consulta e dashboard. RBAC por empresa com soft multi-tenancy (tenant_id).

## Technical Context

**Language/Version**: PHP 8.3 (backend) + TypeScript (frontend)
**Primary Dependencies**: Laravel 13, Nuxt 4, Nuxt UI v4, Vue 3, TailwindCSS v4, `robrichards/xmlseclibs`, `league/flysystem-aws-s3-v3` (MinIO), API pública `publica.cnpj.ws` (auto-preenchimento CNPJ)
**Storage**: PostgreSQL 16 (dados), Redis 7 (cache/filas), MinIO (XMLs/PDFs via S3)
**Testing**: PHPUnit 12 (backend), Vitest (frontend quando aplicável)
**Target Platform**: Linux server (Docker containers)
**Project Type**: Web application (SPA frontend + API backend)
**Performance Goals**: Emissão E2E < 5s p95, consulta < 500ms p95, dashboard < 2s
**Constraints**: mTLS obrigatório (ADN), certificados AES-256, XMLs validados XSD, soft multi-tenancy
**Scale/Scope**: 500+ NFS-e/mês, 20+ empresas ativas, ~10 telas no frontend

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Status | Evidência |
|---|---|---|
| **I. Conformidade Fiscal** (NON-NEGOTIABLE) | ✅ PASS | XSD V1.00.02 validação local, XMLDSIG, GZip+Base64, idempotência por idDps 42 posições. Regras LC 116 + NT 007 implementadas. |
| **II. Segurança por Design** | ✅ PASS | Certificados AES-256 encrypted, mTLS com ADN, Sanctum auth, audit logs imutáveis, XMLs no MinIO (não no repo). |
| **III. Testes para Lógica Fiscal** | ✅ PASS | PHPUnit para cálculo ISS, montagem XML, assinatura, validação XSD. Testes em Produção Restrita antes de deploy. |
| **IV. Arquitetura API-First** | ✅ PASS | Backend expõe API REST JSON. Frontend Nuxt consome API. FormRequest para validação. Laravel Queue + Redis para jobs. |
| **V. Lean & Iterativo** | ✅ PASS | 6 user stories independentes (P1→P6). Must Have primeiro. YAGNI (soft multi-tenancy, sem lote). |

**GATE RESULT**: ✅ Todos os princípios satisfeitos. Prosseguir para Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/001-nfse-emission-system/
├── plan.md              # Este arquivo
├── research.md          # Phase 0: decisões técnicas
├── data-model.md        # Phase 1: modelo de dados
├── quickstart.md        # Phase 1: guia de setup
├── contracts/           # Phase 1: contratos de API
│   └── api-endpoints.md
└── tasks.md             # Phase 2: tarefas (/speckit.tasks)
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── CompanyController.php
│   │   │   ├── CnpjLookupController.php
│   │   │   ├── CertificateController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── ServiceController.php
│   │   │   ├── InvoiceController.php
│   │   │   └── DashboardController.php
│   │   ├── Requests/
│   │   │   ├── StoreCompanyRequest.php
│   │   │   ├── StoreCertificateRequest.php
│   │   │   ├── StoreCustomerRequest.php
│   │   │   ├── StoreInvoiceRequest.php
│   │   │   └── CancelInvoiceRequest.php
│   │   └── Middleware/
│   │       ├── EnsureCompanySelected.php
│   │       └── CheckCompanyRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── CompanyUser.php
│   │   ├── Certificate.php
│   │   ├── Customer.php
│   │   ├── Service.php
│   │   ├── Invoice.php
│   │   └── AuditLog.php
│   ├── Services/
│   │   ├── Nfse/
│   │   │   ├── DpsBuilder.php          # Monta XML da DPS
│   │   │   ├── XmlSigner.php           # Assinatura XMLDSIG
│   │   │   ├── XsdValidator.php        # Validação contra XSD
│   │   │   ├── AdnClient.php           # HTTP client mTLS com ADN
│   │   │   ├── InvoiceEmitter.php      # Orquestra emissão
│   │   │   ├── InvoiceCanceller.php    # Orquestra cancelamento
│   │   │   └── InvoiceReplacer.php     # Orquestra substituição
│   │   ├── Certificate/
│   │   │   ├── CertificateParser.php   # Extrai dados do .pfx
│   │   │   └── CertificateStorage.php  # Encrypt/decrypt AES-256
│   │   ├── Municipal/
│   │   │   └── ParameterService.php    # Consulta parâmetros municipais
│   │   ├── Cnpj/
│   │   │   └── CnpjLookupService.php   # Consulta CNPJ via publica.cnpj.ws
│   │   └── Storage/
│   │       └── MinioService.php        # Upload/download MinIO
│   ├── Enums/
│   │   ├── InvoiceStatus.php
│   │   ├── CompanyRole.php
│   │   └── AuditOperation.php
│   └── Observers/
│       └── InvoiceObserver.php         # Audit log automático
├── database/
│   └── migrations/
│       ├── xxxx_create_companies_table.php
│       ├── xxxx_create_company_user_table.php
│       ├── xxxx_create_certificates_table.php
│       ├── xxxx_create_customers_table.php
│       ├── xxxx_create_services_table.php
│       ├── xxxx_create_invoices_table.php
│       └── xxxx_create_audit_logs_table.php
├── routes/
│   └── api.php
├── config/
│   ├── nfse.php               # Config ADN URLs, XSD path, séries
│   └── filesystems.php        # Disco MinIO S3
└── tests/
    ├── Unit/
    │   ├── DpsBuilderTest.php
    │   ├── XmlSignerTest.php
    │   ├── XsdValidatorTest.php
    │   └── TaxCalculationTest.php
    └── Feature/
        ├── InvoiceEmissionTest.php
        ├── InvoiceCancellationTest.php
        └── AuthenticationTest.php

frontend/
├── app/
│   ├── pages/
│   │   ├── index.vue              # Dashboard (US5)
│   │   ├── login.vue              # Login
│   │   ├── register.vue           # Registro
│   │   ├── onboarding.vue         # Wizard 2 passos
│   │   ├── invoices/
│   │   │   ├── index.vue          # Listagem NFS-e (US4)
│   │   │   ├── new.vue            # Emissão (US1)
│   │   │   └── [id].vue           # Detalhe/Cancelar/Substituir
│   │   ├── customers/
│   │   │   └── index.vue          # Tomadores (US1)
│   │   ├── services/
│   │   │   └── index.vue          # Serviços
│   │   └── settings/
│   │       ├── index.vue          # Config empresa
│   │       ├── certificates.vue   # Certificados (US2)
│   │       └── members.vue        # Membros + roles
│   ├── composables/
│   │   ├── useAuth.ts
│   │   ├── useCompany.ts
│   │   └── useInvoice.ts
│   └── types/
│       └── index.d.ts
└── server/
    └── api/                       # Proxy para backend Laravel
```

**Structure Decision**: Web application (Option 2) — backend Laravel como API REST + frontend Nuxt como SPA consumidor. Estrutura alinhada com o scaffolding existente no repo.

## Complexity Tracking

Nenhuma violação de constitution identificada. Complexidade justificada:

| Decisão | Justificativa | Alternativa Rejeitada |
|---|---|---|
| MinIO como serviço adicional | Requisito explícito do usuário (clarify C4) + melhor para armazenar binários (XML/PDF) | Salvar XMLs no PostgreSQL (coluna TEXT) — OK para MVP mas não escalável |
| Service layer (Nfse/) com 7 classes | Lógica fiscal complexa exige separação clara (builder, signer, validator, client) | Controller fat — viola SRP e dificulta testes isolados |
