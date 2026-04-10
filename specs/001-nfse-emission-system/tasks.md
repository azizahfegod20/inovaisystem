# Tasks: Sistema de Emissão de NFS-e Padrão Nacional

**Input**: Design documents from `/specs/001-nfse-emission-system/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/api-endpoints.md, quickstart.md

**Tests**: Incluídos para lógica fiscal (obrigatório conforme Constitution Princípio III). Testes de UI são opcionais.

**Organization**: Tasks agrupadas por user story para implementação e teste independentes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Pode rodar em paralelo (arquivos diferentes, sem dependências)
- **[Story]**: User story à qual a task pertence (US1, US2, etc.)
- Caminhos absolutos a partir da raiz do repo

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Configuração do projeto, containers e estrutura base

- [x] T001 Adicionar serviço MinIO ao `docker-compose.yml` usando imagem `quay.io/minio/minio:RELEASE.2024-01-13T07-53-03Z-cpuv1` com volumes, portas 9000/9001 e credenciais
- [x] T002 Criar arquivo de configuração `backend/config/nfse.php` com URLs do ADN (produção/homologação), path do XSD, faixas de série DPS
- [x] T003 [P] Configurar disco MinIO S3 em `backend/config/filesystems.php` com endpoint, bucket, credenciais e path_style
- [x] T004 [P] Adicionar variáveis de ambiente MinIO e NFS-e no `backend/.env.example`
- [x] T005 [P] Instalar dependências PHP: `robrichards/xmlseclibs` e `league/flysystem-aws-s3-v3` via composer no `backend/`
- [x] T006 [P] Copiar arquivos XSD do Padrão Nacional (V1.00.02) para `backend/resources/xsd/`
- [x] T007 [P] Criar enums `backend/app/Enums/InvoiceStatus.php`, `backend/app/Enums/CompanyRole.php` e `backend/app/Enums/AuditOperation.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Models, migrations, auth, middleware e serviços base que BLOQUEIAM todas as user stories

**⚠️ CRITICAL**: Nenhuma user story pode começar até esta fase estar completa

- [x] T008 Criar migration `create_companies_table` em `backend/database/migrations/` conforme data-model.md (cnpj, razao_social, codigo_ibge, dps_serie, dps_next_number, ambiente, etc.)
- [x] T009 [P] Criar migration `create_company_user_table` em `backend/database/migrations/` com user_id, company_id, role e unique index
- [x] T010 [P] Criar migration `create_certificates_table` em `backend/database/migrations/` com pfx_content (encrypted), pfx_password (encrypted), cnpj, valid_from, valid_to, is_active
- [x] T011 [P] Criar migration `create_customers_table` em `backend/database/migrations/` conforme data-model.md (tipo_documento, documento, razao_social, codigo_ibge, etc.)
- [x] T012 [P] Criar migration `create_services_table` em `backend/database/migrations/` com codigo_lc116, codigo_nbs, descricao, aliquota_iss, is_favorite
- [x] T013 [P] Criar migration `create_invoices_table` em `backend/database/migrations/` conforme data-model.md (id_dps 42 chars, dps_number, dps_serie, chave_acesso, valores, paths MinIO, status, indexes)
- [x] T014 [P] Criar migration `create_audit_logs_table` em `backend/database/migrations/` sem updated_at (imutável), com operation, payload_summary, result, error_code, ip_address
- [x] T015 Criar model `backend/app/Models/Company.php` com fillable, casts, relacionamentos (users, certificates, customers, services, invoices)
- [x] T016 [P] Criar model `backend/app/Models/CompanyUser.php` (pivot) com cast de role para enum CompanyRole
- [x] T017 [P] Criar model `backend/app/Models/Certificate.php` com casts encrypted para pfx_content e pfx_password, relacionamento company
- [x] T018 [P] Criar model `backend/app/Models/Customer.php` com fillable, validação documento, relacionamento company e invoices
- [x] T019 [P] Criar model `backend/app/Models/Service.php` com fillable, cast aliquota_iss decimal, relacionamento company
- [x] T020 [P] Criar model `backend/app/Models/Invoice.php` com fillable, casts (status enum, decimais), relacionamentos (company, customer, service, user, replacedInvoice)
- [x] T021 [P] Criar model `backend/app/Models/AuditLog.php` sem timestamps (apenas created_at), com cast payload_summary para array, relacionamentos (company, user, invoice)
- [x] T022 Atualizar model `backend/app/Models/User.php` adicionando relacionamento belongsToMany companies via CompanyUser com pivot role
- [x] T023 Configurar Laravel Sanctum SPA auth: atualizar `backend/config/sanctum.php` com stateful domains, `backend/config/cors.php` com origins, e `backend/.env` com SESSION_DOMAIN
- [x] T024 Criar middleware `backend/app/Http/Middleware/EnsureCompanySelected.php` que verifica company_id na sessão e retorna 403 se ausente
- [x] T025 [P] Criar middleware `backend/app/Http/Middleware/CheckCompanyRole.php` que verifica role do usuário na empresa selecionada (admin, contador, operador)
- [x] T026 Implementar `backend/app/Http/Controllers/Api/AuthController.php` com métodos register, login, logout, user conforme contracts/api-endpoints.md
- [x] T027 [P] Criar FormRequest `backend/app/Http/Requests/StoreCompanyRequest.php` com validação de CNPJ (11 ou 14 dígitos, check digit), campos obrigatórios, código IBGE
- [x] T028 Implementar `backend/app/Http/Controllers/Api/CompanyController.php` com store, show, update, select (define company na sessão) conforme contracts
- [x] T029 Registrar rotas de auth e company em `backend/routes/api.php` com middleware sanctum e company conforme estrutura do contracts
- [x] T030 Criar service `backend/app/Services/Storage/MinioService.php` com métodos upload, download, getUrl usando Flysystem S3 disco minio
- [x] T031 Criar observer `backend/app/Observers/InvoiceObserver.php` que cria AuditLog automaticamente em created/updated para operações fiscais
- [x] T032 [P] Implementar `backend/app/Services/Cnpj/CnpjLookupService.php` — consultar `GET https://publica.cnpj.ws/cnpj/{cnpj}`, mapear campos (razao_social, nome_fantasia, logradouro, numero, complemento, bairro, cep, cidade.ibge_id→codigo_ibge, estado.sigla→uf, email, ddd1+telefone1→telefone), cache Redis TTL 7 dias, tratar rate limit (429) e CNPJ não encontrado (404)
- [x] T033 [P] Implementar `backend/app/Http/Controllers/Api/CnpjLookupController.php` — endpoint GET /cnpj-lookup/{cnpj} com validação de formato CNPJ, proxy para CnpjLookupService, resposta normalizada conforme contracts
- [x] T034 Registrar rota GET /cnpj-lookup/{cnpj} em `backend/routes/api.php` com middleware sanctum

**Checkpoint**: Fundação pronta — models, migrations, auth, middleware e MinIO operacionais. User stories podem iniciar.

---

## Phase 3: User Story 1 — Emissão de NFS-e (Priority: P1) 🎯 MVP

**Goal**: Prestador emite NFS-e preenchendo tomador, serviço e valor. Sistema monta DPS, assina XML, valida XSD, envia ao ADN e retorna nota autorizada com PDF.

**Independent Test**: Cadastrar tomador, selecionar serviço, preencher valor, confirmar emissão → NFS-e autorizada com chave de acesso e PDF disponível no ambiente de Produção Restrita.

### Testes para User Story 1 (lógica fiscal — obrigatório)

- [x] T035 [P] [US1] Criar teste unitário `backend/tests/Unit/DpsBuilderTest.php` — validar montagem de XML DPS com campos obrigatórios, idDps 42 posições, valores e namespace corretos
- [x] T036 [P] [US1] Criar teste unitário `backend/tests/Unit/XmlSignerTest.php` — validar assinatura XMLDSIG com certificado de teste, canonicalization C14N e referência por ID
- [x] T037 [P] [US1] Criar teste unitário `backend/tests/Unit/XsdValidatorTest.php` — validar XML correto passa, XML com campos faltantes/inválidos falha com erros legíveis
- [x] T038 [P] [US1] Criar teste unitário `backend/tests/Unit/TaxCalculationTest.php` — validar cálculo ISS, valor líquido, retenções (IR, CSLL, PIS, COFINS, INSS) com cenários reais

### Implementação para User Story 1

- [x] T039 [P] [US1] Implementar `backend/app/Services/Certificate/CertificateParser.php` — ler .pfx com openssl_pkcs12_read, extrair CNPJ, CN, validade, cert e private key
- [x] T040 [P] [US1] Implementar `backend/app/Services/Certificate/CertificateStorage.php` — encrypt/decrypt conteúdo pfx usando cast encrypted do Laravel, salvar/recuperar do model
- [x] T041 [US1] Criar FormRequest `backend/app/Http/Requests/StoreCertificateRequest.php` e implementar `backend/app/Http/Controllers/Api/CertificateController.php` com store (upload + parse + validação CNPJ match), index, destroy
- [x] T042 [P] [US1] Criar FormRequest `backend/app/Http/Requests/StoreCustomerRequest.php` e implementar `backend/app/Http/Controllers/Api/CustomerController.php` com CRUD completo conforme contracts
- [x] T043 [P] [US1] Implementar `backend/app/Http/Controllers/Api/ServiceController.php` com CRUD e filtro favorites_only conforme contracts
- [x] T044 [US1] Implementar `backend/app/Services/Nfse/DpsBuilder.php` — montar XML da DPS conforme XSD V1.00.02 (infDPS, ide, emit, toma, serv, valores) com namespace e encoding corretos
- [x] T045 [US1] Implementar `backend/app/Services/Nfse/XmlSigner.php` — assinar XML usando `robrichards/xmlseclibs` com XMLDSIG, RSA-SHA256, canonicalization C14N exclusiva, referência por Id
- [x] T046 [US1] Implementar `backend/app/Services/Nfse/XsdValidator.php` — validar XML contra XSD local usando DOMDocument::schemaValidate, retornar erros formatados via libxml_get_errors
- [x] T047 [US1] Implementar `backend/app/Services/Nfse/AdnClient.php` — HTTP client com mTLS (cert + key temporários extraídos do pfx), GZip + Base64 encoding, retry com backoff exponencial, circuit breaker básico
- [x] T048 [US1] Implementar `backend/app/Services/Nfse/InvoiceEmitter.php` — orquestrar fluxo: gerar idDps (42 posições) → incrementar dps_number com lock FOR UPDATE → DpsBuilder → XsdValidator → XmlSigner → GZip+Base64 → AdnClient → salvar XMLs no MinIO → salvar Invoice → AuditLog
- [x] T049 [US1] Criar FormRequest `backend/app/Http/Requests/StoreInvoiceRequest.php` com validação de customer_id, service_id, valor_servico, campos de retenção
- [x] T050 [US1] Implementar `backend/app/Http/Controllers/Api/InvoiceController.php` método store — chamar InvoiceEmitter, tratar idempotência (409), rejeições ADN (422 com código traduzido), timeout (502)
- [x] T051 [US1] Implementar endpoint GET /invoices/{id}/pdf no InvoiceController — baixar PDF do MinIO ou buscar no ADN via GET /danfse/{chaveAcesso} e cachear no MinIO
- [x] T052 [US1] Implementar endpoint GET /invoices/{id}/xml no InvoiceController — baixar XML do MinIO
- [x] T053 [US1] Registrar rotas de certificates, customers, services, invoices em `backend/routes/api.php` com middleware sanctum + company
- [x] T054 [US1] Criar teste feature `backend/tests/Feature/InvoiceEmissionTest.php` — testar fluxo completo de emissão com mock do AdnClient, verificar Invoice criada, XMLs salvos, AuditLog registrado
- [x] T055 [P] [US1] Implementar página de emissão `frontend/app/pages/invoices/new.vue` — formulário com seleção de tomador, serviço, valor, cálculo automático de impostos, preview e botão emitir
- [x] T056 [P] [US1] Implementar página de tomadores `frontend/app/pages/customers/index.vue` — CRUD com tabela, filtros, modal de criação/edição com campo CNPJ que auto-preenche via GET /cnpj-lookup/{cnpj}
- [x] T057 [P] [US1] Implementar página de serviços `frontend/app/pages/services/index.vue` — CRUD com filtro favoritos, toggle favorite
- [x] T058 [US1] Criar composable `frontend/app/composables/useInvoice.ts` com métodos emit, getById, downloadPdf, downloadXml usando $fetch para API backend
- [x] T059 [US1] Implementar página de detalhe `frontend/app/pages/invoices/[id].vue` — exibir dados da NFS-e, botões baixar PDF/XML, cancelar, substituir

**Checkpoint**: Emissão de NFS-e funcional end-to-end. Testar com certificado real no ambiente de Produção Restrita.

---

## Phase 4: User Story 2 — Autenticação e Gestão de Empresa (Priority: P2)

**Goal**: Registro, login, wizard de onboarding (2 passos: usuário → empresa), gestão de certificados com alertas de vencimento, e gerenciamento de membros por empresa.

**Independent Test**: Registrar novo usuário → wizard de onboarding → cadastrar empresa → upload certificado A1 → certificado validado e armazenado. Testar rejeição de certificado com CNPJ divergente e certificado expirado.

### Implementação para User Story 2

- [x] T060 [P] [US2] Implementar página de registro `frontend/app/pages/register.vue` com formulário nome, e-mail, senha, confirmação e redirect ao onboarding
- [x] T061 [P] [US2] Implementar página de login `frontend/app/pages/login.vue` com formulário e-mail/senha, link para registro e tratamento de erros
- [x] T062 [US2] Implementar página de onboarding `frontend/app/pages/onboarding.vue` — wizard 2 passos (dados do usuário confirmados → formulário cadastro empresa com campo CNPJ que auto-preenche via GET /cnpj-lookup/{cnpj}: razão social, nome fantasia, endereço, CEP, bairro, cidade, UF, e-mail, telefone) com redirect ao dashboard
- [x] T063 [US2] Criar composable `frontend/app/composables/useAuth.ts` com métodos login, register, logout, getUser, useCurrentUser usando Sanctum CSRF + cookie auth
- [x] T064 [P] [US2] Criar composable `frontend/app/composables/useCompany.ts` com métodos create, select, getMembers, addMember, updateMemberRole, removeMember
- [x] T065 [US2] Implementar página de certificados `frontend/app/pages/settings/certificates.vue` — upload .pfx com campo de senha, lista de certificados com status (ativo/expirado/vencendo), botão remover
- [x] T066 [US2] Implementar alerta de vencimento de certificado no layout `frontend/app/layouts/default.vue` — banner warning quando certificado vence em < 30 dias, error quando vencido
- [x] T067 [US2] Implementar página de membros `frontend/app/pages/settings/members.vue` — lista de membros com role, adicionar membro por e-mail, alterar role, remover
- [x] T068 [US2] Implementar endpoint GET/POST/PUT/DELETE `/companies/{id}/members` no `backend/app/Http/Controllers/Api/CompanyController.php` conforme contracts
- [x] T069 [US2] Adicionar middleware de proteção de rotas no frontend — redirect para /login se não autenticado, redirect para /onboarding se sem empresa
- [x] T070 [US2] Criar teste feature `backend/tests/Feature/AuthenticationTest.php` — testar register, login, logout, acesso negado sem auth, acesso negado sem company selecionada

**Checkpoint**: Fluxo completo de autenticação e gestão de empresa funcional. Wizard de onboarding testado.

---

## Phase 5: User Story 3 — Cancelamento de NFS-e (Priority: P3)

**Goal**: Cancelar NFS-e autorizada informando motivo. Sistema registra evento de cancelamento no ADN.

**Independent Test**: Emitir NFS-e → solicitar cancelamento com motivo (min 15 chars) → status muda para "Cancelada". Testar cancelamento duplicado (409) e prazo excedido (422).

### Implementação para User Story 3

- [x] T071 [US3] Implementar `backend/app/Services/Nfse/InvoiceCanceller.php` — validar status (só authorized), montar evento cancelamento XML, assinar, enviar ao ADN, atualizar status, salvar XML retorno no MinIO, registrar AuditLog
- [x] T072 [US3] Criar FormRequest `backend/app/Http/Requests/CancelInvoiceRequest.php` com validação motivo min:15 e implementar método cancel no InvoiceController conforme contracts
- [x] T073 [US3] Adicionar botão "Cancelar" e modal com campo motivo na página `frontend/app/pages/invoices/[id].vue` com feedback de sucesso/erro
- [x] T074 [US3] Adicionar método cancel ao composable `frontend/app/composables/useInvoice.ts`
- [x] T075 [US3] Criar teste feature `backend/tests/Feature/InvoiceCancellationTest.php` — testar cancelamento com mock ADN, cancelamento duplicado (409), motivo curto (422)

**Checkpoint**: Cancelamento de NFS-e funcional. Nota muda para status "Cancelada" com registro de auditoria.

---

## Phase 6: User Story 4 — Consulta e Listagem de NFS-e (Priority: P4)

**Goal**: Listar e filtrar NFS-e por período, tomador, status e valor com paginação.

**Independent Test**: Emitir 3+ notas com diferentes status/tomadores → filtrar por período, status, tomador → resultados corretos e paginados.

### Implementação para User Story 4

- [x] T076 [US4] Implementar método index no `backend/app/Http/Controllers/Api/InvoiceController.php` — query com filtros (status, customer_id, date_from, date_to, search por razão social), ordenação por data_emissao DESC, paginação 10/página
- [x] T077 [US4] Implementar método show no InvoiceController — retornar NFS-e com relacionamentos (customer, service) carregados
- [x] T078 [US4] Implementar página de listagem `frontend/app/pages/invoices/index.vue` — tabela com colunas (nº, data, tomador, valor, status, ações), filtros (período, status, busca), paginação, link para detalhe
- [x] T079 [US4] Registrar rotas GET /invoices e GET /invoices/{id} em `backend/routes/api.php`

**Checkpoint**: Listagem e consulta de NFS-e funcional com filtros e paginação.

---

## Phase 7: User Story 5 — Dashboard de Métricas (Priority: P5)

**Goal**: Painel com indicadores de faturamento (total notas, receita, canceladas, impostos) e gráfico de evolução por período.

**Independent Test**: Emitir notas no período → dashboard exibe totais corretos → alternar agrupamento (diário/semanal/mensal) → gráfico atualiza.

### Implementação para User Story 5

- [x] T080 [US5] Implementar `backend/app/Http/Controllers/Api/DashboardController.php` método stats — query agregada (COUNT, SUM) por company_id e período, retornando total_notas, total_receita, total_canceladas, total_iss, total_retencoes
- [x] T081 [US5] Implementar método chart no DashboardController — query agrupada por dia/semana/mês conforme parâmetro period, retornando labels e datasets (receita, notas)
- [x] T082 [US5] Atualizar página `frontend/app/pages/index.vue` (dashboard) — substituir dados mockados por chamadas reais à API /dashboard/stats e /dashboard/chart, manter filtro de período e componentes existentes (HomeStats, HomeChart)
- [x] T083 [US5] Registrar rotas GET /dashboard/stats e GET /dashboard/chart em `backend/routes/api.php`

**Checkpoint**: Dashboard com métricas reais. Cards e gráficos refletem dados das notas emitidas.

---

## Phase 8: User Story 6 — Substituição de NFS-e (Priority: P6)

**Goal**: Substituir NFS-e autorizada por nova com dados corrigidos. Original é cancelada automaticamente.

**Independent Test**: Emitir NFS-e → solicitar substituição com valor diferente → original cancelada, nova autorizada com referência à substituída.

### Implementação para User Story 6

- [x] T084 [US6] Implementar `backend/app/Services/Nfse/InvoiceReplacer.php` — validar status original (só authorized), emitir nova nota com referência à substituída (invoice_replaced_id), cancelar original via InvoiceCanceller, registrar AuditLog
- [x] T085 [US6] Implementar método replace no InvoiceController — receber dados da nova nota + motivo, chamar InvoiceReplacer, retornar nova NFS-e
- [x] T086 [US6] Adicionar botão "Substituir" e formulário pré-preenchido (editável) na página `frontend/app/pages/invoices/[id].vue` com campo motivo obrigatório
- [x] T087 [US6] Adicionar método replace ao composable `frontend/app/composables/useInvoice.ts`

**Checkpoint**: Substituição funcional. Original cancelada, nova emitida com referência.

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Melhorias transversais a todas as user stories

- [x] T088 [P] Implementar consulta de parâmetros municipais `backend/app/Services/Municipal/ParameterService.php` — GET /parametros_municipais/{codIBGE} no ADN, cache Redis 24h, invalidação manual
- [x] T089 [P] Implementar endpoint GET /municipal-params/{codigoIbge} no backend com controller dedicado
- [x] T090 [P] Criar seeder `backend/database/seeders/ServiceSeeder.php` com tabela completa de serviços LC 116/2003 (códigos, subitens, descrições)
- [x] T091 Implementar tradução de códigos de erro ADN para português em `backend/app/Services/Nfse/AdnErrorTranslator.php` — mapear E1235, 403, 409 etc. para mensagens legíveis
- [x] T092 [P] Atualizar `frontend/app/layouts/default.vue` — ajustar navegação lateral com links para Notas Fiscais, Tomadores, Serviços, Configurações, e labels em português
- [x] T093 [P] Atualizar `frontend/app/types/index.d.ts` — adicionar tipos TypeScript para Company, Certificate, Customer, Service, Invoice, DashboardStats, DashboardChart
- [x] T094 Configurar CORS e Sanctum para ambiente Docker em `backend/config/cors.php` — allowed_origins para localhost:3000, supports_credentials true
- [x] T095 Criar endpoint GET /api/health no backend para healthcheck
- [x] T096 [P] Documentar API com OpenAPI/Swagger annotations nos controllers (ou arquivo openapi.yaml estático)
- [x] T097 Validar quickstart.md — executar fluxo completo conforme `specs/001-nfse-emission-system/quickstart.md` e corrigir divergências

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sem dependências — iniciar imediatamente
- **Foundational (Phase 2)**: Depende de Setup — BLOQUEIA todas as user stories
- **US1 Emissão (Phase 3)**: Depende de Foundational — entrega o MVP
- **US2 Auth/Empresa (Phase 4)**: Depende de Foundational — pode rodar em paralelo com US1
- **US3 Cancelamento (Phase 5)**: Depende de US1 (precisa de Invoice emitida)
- **US4 Consulta (Phase 6)**: Depende de Foundational — pode rodar em paralelo com US1
- **US5 Dashboard (Phase 7)**: Depende de US1 (precisa de dados de notas)
- **US6 Substituição (Phase 8)**: Depende de US1 e US3 (usa InvoiceEmitter + InvoiceCanceller)
- **Polish (Phase 9)**: Depende de todas as user stories desejadas

### User Story Dependencies

```
                    ┌─────────────┐
                    │   Setup     │
                    │  (Phase 1)  │
                    └──────┬──────┘
                           │
                    ┌──────▼──────┐
                    │ Foundational│
                    │  (Phase 2)  │
                    └──┬───┬───┬──┘
                       │   │   │
              ┌────────▼┐  │  ┌▼────────┐
              │ US1 (P1)│  │  │ US2 (P2)│
              │ Emissão │  │  │Auth/Emp │
              └────┬────┘  │  └─────────┘
                   │       │
          ┌────────┼───────┤
          │        │       │
     ┌────▼───┐ ┌──▼───┐ ┌▼────────┐
     │US3 (P3)│ │US5(P5)│ │ US4 (P4)│
     │Cancelar│ │Dashbd │ │Consulta │
     └────┬───┘ └──────┘ └─────────┘
          │
     ┌────▼───┐
     │US6 (P6)│
     │Substit.│
     └────────┘
```

### Within Each User Story

- Testes escritos PRIMEIRO (lógica fiscal)
- Models antes de services
- Services antes de controllers
- Backend antes de frontend
- Core antes de integração

### Parallel Opportunities

- **Phase 1**: T003, T004, T005, T006, T007 podem rodar em paralelo
- **Phase 2**: T009-T014 (migrations) em paralelo, T016-T021 (models) em paralelo, T025+T027 (middleware/request) em paralelo, T032-T033 (CNPJ lookup) em paralelo
- **Phase 3**: T035-T038 (testes) em paralelo, T039-T040 (certificate services) em paralelo, T042-T043 (customer/service controllers) em paralelo, T055-T057 (frontend pages) em paralelo
- **US1 e US2** podem progredir em paralelo após Phase 2
- **US3 e US4** podem progredir em paralelo após US1

---

## Parallel Example: User Story 1

```bash
# Testes de lógica fiscal em paralelo:
T035: "DpsBuilderTest.php"
T036: "XmlSignerTest.php"
T037: "XsdValidatorTest.php"
T038: "TaxCalculationTest.php"

# Certificate services em paralelo:
T039: "CertificateParser.php"
T040: "CertificateStorage.php"

# Controllers independentes em paralelo:
T042: "CustomerController.php"
T043: "ServiceController.php"

# Frontend pages em paralelo:
T055: "invoices/new.vue"
T056: "customers/index.vue"
T057: "services/index.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL)
3. Complete Phase 3: User Story 1 — Emissão
4. **STOP e VALIDAR**: Testar emissão em Produção Restrita do ADN
5. Deploy/demo se pronto

### Incremental Delivery

1. Setup + Foundational → Infraestrutura pronta
2. US1 (Emissão) → Testar → **MVP entregue!**
3. US2 (Auth/Empresa) → Onboarding e certificados completos
4. US3 (Cancelamento) → Operação fiscal completa
5. US4 (Consulta) → Gestão de notas
6. US5 (Dashboard) → Visão gerencial
7. US6 (Substituição) → Correção de notas
8. Polish → Refinamentos transversais

---

## Notes

- [P] tasks = arquivos diferentes, sem dependências entre si
- [Story] label mapeia task para user story específica
- Testes de lógica fiscal são obrigatórios (Constitution Princípio III)
- Commitar após cada task ou grupo lógico
- Parar em qualquer checkpoint para validar story independentemente
- Evitar: tasks vagas, conflito de arquivo, dependências cross-story que quebrem independência
