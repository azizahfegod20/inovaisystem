# Repository Guidelines

Sistema de emissao de NFS-e (Nota Fiscal de Servico Eletronica) Padrão Nacional. Stack: **Laravel 13** (backend) + **Nuxt 4** (frontend) + **PostgreSQL/Redis/Minio** (infra).

## Project Structure

```
backend/               # Laravel API (PHP 8.3)
  app/
    Http/Controllers/Api/  # API controllers (Sanctum auth)
    Http/Middleware/        # Auth, company selection, role checks
    Http/Requests/          # Form request validation
    Models/                 # Eloquent models
    Services/               # Business logic (Nfse/, Certificate/, Cnpj/, Storage/, Municipal/)
    Enums/                  # InvoiceStatus, CompanyRole, AuditOperation
    Observers/              # InvoiceObserver for audit logging
  config/                # cors, sanctum, nfse, scramble (API docs)
  database/migrations/   # PostgreSQL schema
  routes/api.php         # API routes
frontend/              # Nuxt UI dashboard (Vue 3 + TypeScript)
  app/
    pages/              # File-based routing
    components/         # Vue components (home/, nfse/, customers/, settings/, etc.)
    composables/        # useAuth, useApi, useCompany, useInvoice, useDashboard
    middleware/         # auth.global.ts (client-side auth guard)
    layouts/            # default.vue (sidebar + navbar)
  server/api/           # Nitro server routes (mock data - to be replaced)
docker-compose.yml     # Dev environment (frontend:3000, backend:8000, postgres, redis, minio)
```

## Build & Development Commands

```bash
# Start full dev environment
docker compose up --build

# Backend (inside backend container or with PHP 8.3 locally)
cd backend
composer install                      # Install PHP dependencies
cp .env.example .env && php artisan key:generate
php artisan migrate                   # Run migrations
composer test                         # Run tests (clears config + phpunit)
php artisan test                      # Run phpunit directly
composer dev                          # Concurrent: serve + queue + logs + vite

# Frontend (inside frontend container or with Node 22 locally)
cd frontend
corepack enable && pnpm install       # Install JS dependencies
pnpm dev                              # Dev server on :3000
pnpm build                            # Production build
pnpm lint                             # ESLint
pnpm typecheck                        # Vue type checking
```

## Coding Style

- **PHP**: PSR-12, 4-space indent, EditorConfig enforced. Use Laravel Pint for formatting (`vendor/bin/pint`).
- **TypeScript/Vue**: 2-space indent (Nuxt default), ESLint with `@nuxt/eslint`. Single-line max 3 Vue attributes.
- **Commits**: Conventional commits in English (`feat:`, `fix:`, `refactor:`). See git history for examples.
- **Language**: Code, comments, and commits in English. All user-facing interaction in Portuguese (pt-BR).
- **Naming**: Controllers plural (`InvoiceController`), models singular (`Invoice`), migrations with timestamps.

## Architecture

- **Auth**: Laravel Sanctum with SPA authentication (cookie-based). Frontend proxies `/api/` and `/sanctum/` to backend via Nitro `devProxy`/`routeRules`.
- **Multi-tenant**: Users belong to companies via `company_user` pivot with roles (`admin`, `contador`, `operador`). Middleware `company` sets session, `company.role:admin` gates access.
- **NFS-e Flow**: `InvoiceController` -> `InvoiceEmitter` -> `AdnClient` (API Nacional) -> `XmlSigner` (digital signature) -> `MinioService` (XML/PDF storage).
- **API Docs**: Auto-generated via `dedoc/scramble` at `/docs/api`.

## Testing

- **Backend**: PHPUnit with in-memory SQLite. Tests in `tests/Unit/` and `tests/Feature/`. Run with `composer test`.
- **Frontend**: No test framework configured yet.

## Key Environment Variables

| Variable | Purpose |
|----------|---------|
| `NUXT_PUBLIC_API_BASE` | Backend URL (default: `http://localhost:8000`) |
| `NFSE_AMBIENTE` | NFS-e environment: `1` (producao), `2` (homologacao) |
| `MINIO_*` | S3-compatible storage for XML/PDF |
| `SANCTUM_STATEFUL_DOMAINS` | CORS domains for SPA auth |

## Rules

Follow the conventions documented in `.factory/rules/`:
- **Language**: `.factory/rules/language.md` - All user interaction in Portuguese (pt-BR)
