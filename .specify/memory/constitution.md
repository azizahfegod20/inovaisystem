<!--
  Sync Impact Report
  ==================
  Version change: 0.0.0 → 1.0.0
  Modified principles: N/A (initial creation)
  Added sections: Core Principles (5), Technology Stack & Constraints, Development Workflow, Governance
  Removed sections: N/A
  Templates requiring updates:
    - .specify/templates/plan-template.md ✅ compatible (Constitution Check section generic)
    - .specify/templates/spec-template.md ✅ compatible (user story format aligns)
    - .specify/templates/tasks-template.md ✅ compatible (phase structure aligns)
  Follow-up TODOs: none
-->

# InovaiSystem Constitution

## Core Principles

### I. Conformidade Fiscal (NON-NEGOTIABLE)

- Toda funcionalidade que gere, altere ou consulte documentos fiscais MUST seguir rigorosamente o Padrão Nacional de NFS-e (SNNFSe) e o schema XSD oficial (V1.00.02).
- XMLs MUST ser validados contra o XSD localmente antes de qualquer envio ao ADN.
- Cálculos de impostos (ISS, retenções federais, valor líquido) MUST reproduzir exatamente as regras definidas pela legislação vigente (LC 116/2003, NT 007) e pelos parâmetros municipais.
- Atualizações regulatórias (novas NTs, mudança de schema, IBS/CBS) MUST ser tratadas como prioridade crítica e aplicadas antes do prazo de vigência.
- Rationale: erros fiscais geram multas, bloqueio de emissão e perda de confiança do usuário.

### II. Segurança por Design

- Certificados digitais ICP-Brasil MUST ser armazenados com criptografia AES-256 (coluna encrypted ou vault dedicado) e NEVER expostos em logs, respostas de API ou repositório.
- Toda comunicação com APIs governamentais MUST usar mTLS com certificado válido.
- Assinatura digital XML MUST seguir o padrão XMLDSIG (W3C) com canonicalization correta.
- Autenticação de usuários MUST usar tokens seguros (Sanctum/JWT) com expiração configurável.
- Logs de auditoria fiscal MUST ser imutáveis e conter: operação, usuário, timestamp, payload resumido e resultado.
- Rationale: o sistema lida com documentos com validade jurídica e dados sensíveis de empresas.

### III. Testes Obrigatórios para Lógica Fiscal

- Toda lógica de cálculo de impostos, montagem de XML, assinatura digital e comunicação com o ADN MUST ter testes automatizados (unitários + integração).
- Testes de regressão MUST ser criados para cada rejeição corrigida (E1235, 403, 409, etc.).
- Validação contra XSD MUST ser coberta por testes com XMLs válidos e inválidos.
- Testes em ambiente de Produção Restrita (homologação) MUST ser executados antes de qualquer deploy em produção.
- Rationale: bugs fiscais são silenciosos e custosos — uma retenção calculada errada pode passar despercebida por meses.

### IV. Arquitetura API-First

- Backend (Laravel) MUST expor toda funcionalidade via API REST JSON, sem lógica de negócio no frontend.
- Frontend (Nuxt) MUST ser um consumidor SPA da API, sem acesso direto ao banco de dados ou serviços externos.
- Cada endpoint MUST ter validação de entrada (FormRequest), tratamento de erro padronizado e documentação (OpenAPI/Swagger).
- Jobs assíncronos (e-mail, lote, DFe) MUST usar Laravel Queue com Redis como driver.
- Rationale: permite futura API pública para ERPs, desacopla frontend de backend e facilita testes isolados.

### V. Lean & Iterativo

- Funcionalidades MUST ser priorizadas por impacto no usuário (Must Have → Should Have → Nice to Have conforme PRD).
- Cada user story MUST ser entregável e testável de forma independente.
- Código MUST seguir YAGNI — não implementar abstrações ou features especulativas.
- Complexidade adicional (ex: multi-tenancy com banco separado, microserviços) MUST ser justificada com dados concretos antes de ser adotada.
- Rationale: startup com recursos limitados — entregar valor rápido e validar com usuários reais.

## Technology Stack & Constraints

- **Frontend**: Nuxt 4 + Nuxt UI v4 + TailwindCSS v4 + Vue 3 + TypeScript
- **Backend**: Laravel 13 + PHP 8.3
- **Banco de Dados**: PostgreSQL 16
- **Cache/Filas**: Redis 7
- **Containerização**: Docker + docker-compose
- **Assinatura XML**: `robrichards/xmlseclibs` (PHP)
- **Validação XSD**: `DOMDocument` nativo do PHP
- **Idioma da interface**: Português do Brasil (pt-BR) exclusivamente
- **Idioma do código**: variáveis, funções e classes em inglês; comentários em português quando solicitado
- **Ambientes obrigatórios**: local (Docker), homologação (Produção Restrita ADN), produção

Constraints:
- Certificado A1 (.pfx) no MVP; A3 (token) diferido para versão futura.
- Soft multi-tenancy (tenant_id) no MVP; banco separado apenas se comprovadamente necessário.
- Emissão síncrona apenas (sem lote) no MVP.
- Apenas municípios aderentes ao Padrão Nacional — sem integração com APIs municipais legadas.

## Development Workflow

- **Branching**: feature branches a partir de `main` (convenção: `###-feature-name`).
- **Commits**: mensagens em português, formato: `tipo: descrição` (ex: `feat: emissão de NFS-e via ADN`).
- **Quality gates antes de merge**:
  1. Testes passando (PHPUnit backend + Vitest/Playwright frontend quando aplicável)
  2. Validação de lint (Laravel Pint + ESLint)
  3. Sem secrets ou certificados no diff
  4. Para lógica fiscal: teste em Produção Restrita aprovado
- **Deploy**: via Docker — build → testes → deploy. Sem deploy manual.
- **Documentação**: PRD em `docs/PRD.md`, specs em `specs/`, constitution em `.specify/memory/`.
- **Referência de produto**: `docs/PRD.md` é a fonte de verdade para priorização e escopo.

## Governance

- Esta constitution é o documento máximo de governança técnica do projeto. Todas as decisões de arquitetura, priorização e qualidade MUST estar alinhadas com os princípios aqui definidos.
- Alterações na constitution MUST ser documentadas com justificativa, versionadas (semver) e comunicadas à equipe.
- Princípios marcados como NON-NEGOTIABLE não podem ser relaxados sem aprovação explícita do Tech Lead e registro formal.
- Em caso de conflito entre velocidade de entrega e conformidade fiscal, conformidade fiscal sempre prevalece (Princípio I).
- Use `docs/PRD.md` como guia de runtime para decisões de produto e priorização de features.

**Version**: 1.0.0 | **Ratified**: 2026-04-10 | **Last Amended**: 2026-04-10
