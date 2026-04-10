# Feature Specification: Sistema de Emissão de NFS-e Padrão Nacional

**Feature Branch**: `001-nfse-emission-system`
**Created**: 2026-04-10
**Status**: Clarified
**Input**: User description: "Montar a especificação baseada no PRD (docs/PRD.md) para implementar o projeto do sistema de emissão de notas fiscais NFS-e Padrão Nacional"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Emissão de NFS-e (Priority: P1)

Como prestador de serviço, quero emitir uma NFS-e preenchendo apenas os dados essenciais (tomador, serviço, valor), para que o sistema monte a DPS, assine o XML, envie ao ADN e me retorne a nota autorizada com o PDF pronto para download.

**Why this priority**: É a funcionalidade central do produto — sem emissão, não há produto. Toda a proposta de valor se baseia em simplificar este processo. Corresponde às features F03 (Tomadores), F04 (Serviços), F05 (Emissão) e F08 (DANFSe) do PRD.

**Independent Test**: Pode ser testado de ponta a ponta: cadastrar um tomador, selecionar um serviço, preencher o valor, confirmar a emissão e verificar que a NFS-e foi autorizada com chave de acesso e PDF disponível. Testável no ambiente de Produção Restrita (homologação) do ADN.

**Acceptance Scenarios**:

1. **Given** um prestador autenticado com certificado digital A1 válido e empresa configurada, **When** preenche tomador (CNPJ válido), serviço (item LC 116) e valor (R$ 5.000,00) e confirma a emissão, **Then** o sistema retorna NFS-e autorizada com chave de acesso de 50 caracteres e disponibiliza DANFSe em PDF
2. **Given** um prestador autenticado, **When** tenta emitir NFS-e sem certificado digital configurado, **Then** o sistema exibe mensagem clara informando que é necessário fazer upload do certificado antes de emitir
3. **Given** um prestador autenticado com certificado válido, **When** envia DPS com dados incompletos (tomador sem CNPJ, serviço sem código), **Then** o sistema valida localmente e exibe erros de validação antes de enviar ao ADN
4. **Given** um prestador autenticado, **When** o ADN retorna rejeição (ex: E1235 — falha no esquema XML), **Then** o sistema exibe mensagem de erro traduzida e registra o evento para diagnóstico
5. **Given** uma NFS-e emitida com sucesso, **When** o usuário clica em "Baixar PDF", **Then** o DANFSe é baixado no formato PDF

---

### User Story 2 - Autenticação e Gestão de Empresa (Priority: P2)

Como usuário, quero me cadastrar, fazer login e configurar minha empresa emissora (CNPJ, certificado digital, dados fiscais), para que eu possa emitir NFS-e de forma segura e com os dados corretos.

**Why this priority**: Pré-requisito para a emissão. Sem autenticação e configuração de empresa/certificado, nenhuma operação fiscal é possível. Corresponde às features F01 (Autenticação) e F02 (Certificados) do PRD.

**Independent Test**: Pode ser testado registrando um novo usuário, fazendo login, criando uma empresa, fazendo upload de certificado A1 e verificando que os dados são extraídos e armazenados corretamente.

**Acceptance Scenarios**:

1. **Given** um visitante na página de registro, **When** preenche nome, e-mail e senha válidos, **Then** a conta é criada e o usuário é redirecionado ao wizard de onboarding (2 passos: dados do usuário → cadastro da empresa)
2. **Given** um usuário no wizard de onboarding, **When** cadastra uma empresa com CNPJ válido, **Then** a empresa é criada, vinculada à conta do usuário, e o wizard é concluído redirecionando ao dashboard
2a. **Given** um usuário autenticado sem certificado digital configurado, **When** tenta emitir NFS-e, **Then** o sistema bloqueia e redireciona para a tela de upload de certificado
3. **Given** uma empresa cadastrada, **When** o usuário faz upload de um certificado A1 (.pfx) com senha correta, **Then** o sistema extrai CNPJ, validade e nome, valida que o CNPJ do certificado bate com o da empresa, e armazena criptografado
4. **Given** um certificado A1 com CNPJ diferente da empresa, **When** o usuário tenta fazer upload, **Then** o sistema rejeita e informa a divergência
5. **Given** um certificado A1 vencido, **When** o usuário tenta fazer upload, **Then** o sistema rejeita e informa que o certificado está expirado
6. **Given** um certificado válido armazenado com vencimento em menos de 30 dias, **When** o usuário acessa o dashboard, **Then** o sistema exibe alerta de vencimento próximo

---

### User Story 3 - Cancelamento de NFS-e (Priority: P3)

Como prestador de serviço, quero cancelar uma NFS-e já emitida informando o motivo, para que a nota seja invalidada junto ao ADN e meu faturamento fique correto.

**Why this priority**: Operação fiscal obrigatória — erros de emissão acontecem e precisam ser corrigidos. Corresponde à feature F06 (Cancelamento) do PRD.

**Independent Test**: Pode ser testado emitindo uma NFS-e em homologação e em seguida solicitando o cancelamento com motivo, verificando que o status muda para "Cancelada".

**Acceptance Scenarios**:

1. **Given** uma NFS-e com status "Autorizada", **When** o usuário solicita cancelamento informando motivo com pelo menos 15 caracteres, **Then** o sistema registra o evento de cancelamento no ADN e altera o status para "Cancelada"
2. **Given** uma NFS-e com status "Cancelada", **When** o usuário tenta cancelar novamente, **Then** o sistema informa que a nota já está cancelada
3. **Given** uma NFS-e fora do prazo de cancelamento do município, **When** o usuário tenta cancelar, **Then** o sistema informa que o prazo foi excedido e sugere a substituição como alternativa

---

### User Story 4 - Consulta e Listagem de NFS-e (Priority: P4)

Como prestador de serviço, quero consultar e filtrar todas as NFS-e emitidas (por período, tomador, status, valor), para ter controle total do meu faturamento.

**Why this priority**: Gestão básica — sem consulta, o usuário não consegue acompanhar o que emitiu. Corresponde à feature F07 (Consulta) do PRD.

**Independent Test**: Pode ser testado emitindo 3+ notas com diferentes tomadores e status, e verificando que os filtros retornam os resultados corretos com paginação.

**Acceptance Scenarios**:

1. **Given** NFS-e emitidas no sistema, **When** o usuário acessa a listagem sem filtros, **Then** o sistema retorna todas as notas paginadas (10 por página) ordenadas por data decrescente
2. **Given** NFS-e emitidas no sistema, **When** o usuário filtra por período (data início e fim), **Then** apenas notas dentro do período são exibidas
3. **Given** NFS-e emitidas no sistema, **When** o usuário filtra por status "Cancelada", **Then** apenas notas canceladas são exibidas
4. **Given** NFS-e emitidas no sistema, **When** o usuário busca por CNPJ ou razão social do tomador, **Then** as notas correspondentes são exibidas

---

### User Story 5 - Dashboard de Métricas (Priority: P5)

Como prestador de serviço, quero visualizar um dashboard com indicadores do meu faturamento (total emitido, notas canceladas, impostos retidos, receita por período), para ter visão gerencial do meu negócio.

**Why this priority**: Agrega valor de gestão ao produto e diferencia de portais governamentais que não oferecem visão consolidada. Corresponde à feature F09 (Dashboard) do PRD.

**Independent Test**: Pode ser testado com dados de notas emitidas, verificando que os cards e gráficos refletem os valores corretos para o período selecionado.

**Acceptance Scenarios**:

1. **Given** notas emitidas no período selecionado, **When** o usuário acessa o dashboard, **Then** os cards exibem: total de notas, receita faturada, notas canceladas e impostos retidos com valores corretos
2. **Given** um período selecionado, **When** o usuário alterna entre visualização diária/semanal/mensal, **Then** o gráfico de evolução atualiza conforme o agrupamento escolhido
3. **Given** nenhuma nota emitida no período, **When** o usuário acessa o dashboard, **Then** os cards exibem zero e o gráfico mostra estado vazio com mensagem orientativa

---

### User Story 6 - Substituição de NFS-e (Priority: P6)

Como prestador de serviço, quero substituir uma NFS-e emitida por uma nova com dados corrigidos, para que a nota original seja automaticamente cancelada e uma nova seja emitida no lugar.

**Why this priority**: Complementa o cancelamento — muitas vezes o erro é apenas um dado trocado, não uma nota inteira indevida. Corresponde à feature F10 (Substituição) do PRD.

**Independent Test**: Pode ser testado emitindo uma NFS-e, solicitando substituição com valor diferente, e verificando que a original foi cancelada e a nova foi autorizada.

**Acceptance Scenarios**:

1. **Given** uma NFS-e com status "Autorizada", **When** o usuário solicita substituição, edita o valor e confirma, **Then** a nota original é cancelada e uma nova NFS-e é emitida com referência à substituída
2. **Given** uma NFS-e com status "Cancelada", **When** o usuário tenta substituir, **Then** o sistema informa que notas canceladas não podem ser substituídas

---

### Edge Cases

- O que acontece quando o ADN está fora do ar durante uma tentativa de emissão? O sistema MUST exibir mensagem amigável, registrar a tentativa e sugerir que o usuário tente novamente em alguns minutos. Retry automático com backoff exponencial MUST ser aplicado internamente.
- O que acontece quando o certificado digital expira entre o início do preenchimento e a confirmação da emissão? O sistema MUST validar a validade do certificado no momento do envio e bloquear a emissão com mensagem clara.
- O que acontece quando dois usuários tentam emitir a mesma DPS simultaneamente (duplicidade)? O sistema MUST usar idempotência (idDps + CNPJ + série + número) para evitar duplicatas. Se o ADN retornar 409 (conflito), o sistema MUST reconciliar e informar que a nota já foi emitida.
- O que acontece quando o XML gerado não passa na validação XSD local? O sistema MUST bloquear o envio, exibir os erros de validação de forma legível e registrar para diagnóstico.
- O que acontece quando o usuário tenta emitir para um município que não aderiu ao Padrão Nacional? O sistema MUST consultar a lista de municípios aderentes e informar claramente que a emissão não é possível para aquele município.
- O que acontece quando a senha do certificado A1 está incorreta no momento do upload? O sistema MUST rejeitar com mensagem informando que a senha está incorreta, sem registrar a tentativa como erro de certificado.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O sistema MUST permitir registro e login de usuários com e-mail e senha
- **FR-002**: O sistema MUST suportar múltiplas empresas emissoras vinculadas a um mesmo usuário
- **FR-003**: O sistema MUST aceitar upload de certificado digital ICP-Brasil A1 (.pfx), validar CNPJ e validade, e armazenar com criptografia AES-256
- **FR-004**: O sistema MUST alertar sobre certificados com vencimento próximo (30/15/7 dias)
- **FR-005**: O sistema MUST permitir CRUD de tomadores (clientes) com validação de CPF/CNPJ e código IBGE do município
- **FR-006**: O sistema MUST disponibilizar tabela de serviços conforme LC 116/2003 com código NBS e permitir seleção de favoritos por empresa
- **FR-006a**: O sistema MUST consultar alíquotas ISS via API de parâmetros municipais do ADN (/parametros_municipais/{codMunicipio}) com cache de 24h, aplicando automaticamente na emissão, e MUST permitir override manual pelo usuário para casos de benefício fiscal ou regime especial
- **FR-007**: O sistema MUST montar a DPS (XML) conforme schema XSD V1.00.02 do Padrão Nacional
- **FR-008**: O sistema MUST assinar digitalmente o XML usando XMLDSIG (W3C) com certificado ICP-Brasil da empresa
- **FR-009**: O sistema MUST comprimir (GZip) e codificar (Base64) o XML antes de enviar ao ADN
- **FR-010**: O sistema MUST comunicar-se com o ADN via mTLS usando o certificado digital da empresa
- **FR-011**: O sistema MUST retornar a chave de acesso da NFS-e autorizada (50 caracteres) após emissão
- **FR-012**: O sistema MUST disponibilizar o DANFSe (PDF) para download após emissão bem-sucedida
- **FR-012a**: O sistema MUST armazenar XMLs (enviado + retorno) e PDFs em object storage MinIO (compatível S3), usando imagem `quay.io/minio/minio:RELEASE.2024-01-13T07-53-03Z-cpuv1`, com referência (path/key) salva no banco de dados
- **FR-013**: O sistema MUST validar o XML localmente contra o XSD antes de enviar ao ADN
- **FR-014**: O sistema MUST permitir cancelamento de NFS-e via registro de evento com motivo obrigatório
- **FR-015**: O sistema MUST permitir substituição de NFS-e, cancelando a original e emitindo nova com referência
- **FR-016**: O sistema MUST listar NFS-e com filtros por período, status, tomador e valor, com paginação
- **FR-017**: O sistema MUST exibir dashboard com métricas: total de notas, receita, canceladas e impostos retidos
- **FR-018**: O sistema MUST garantir idempotência na emissão via idDps (42 posições: cód município + tipo inscrição + CNPJ + série + nº sequencial) conforme Padrão Nacional
- **FR-018a**: O sistema MUST gerar numeração da DPS com série configurável por empresa (faixa 00001-49999 para aplicativo próprio, conforme manual do Padrão Nacional) e número sequencial auto-incrementado por empresa+série
- **FR-019**: O sistema MUST calcular automaticamente o valor líquido: vServ - descontos - retenções
- **FR-020**: O sistema MUST registrar logs de auditoria imutáveis para todas as operações fiscais (emissão, cancelamento, substituição)
- **FR-021**: O sistema MUST implementar RBAC com pelo menos 3 roles (admin, contador, operador) **por empresa** — cada vínculo usuário↔empresa possui sua própria role, permitindo que o mesmo usuário tenha roles diferentes em empresas diferentes
- **FR-022**: O sistema MUST exibir mensagens de erro traduzidas para português quando o ADN retornar rejeições
- **FR-023**: O sistema MUST oferecer auto-preenchimento de dados ao digitar CNPJ de tomador ou empresa, consultando a API pública `https://publica.cnpj.ws/cnpj/{cnpj}` (rate limit: 3 req/min) para preencher automaticamente razão social, nome fantasia, endereço, CEP, bairro, cidade (código IBGE), UF, e-mail e telefone

### Key Entities

- **User**: Representa um usuário do sistema. Atributos: nome, e-mail, senha (hash). Relacionamento: pertence a N empresas via tabela pivot (CompanyUser) que contém a role específica por empresa.
- **Company** (Empresa Emissora): Representa uma empresa prestadora de serviço. Atributos: CNPJ, razão social, nome fantasia, inscrição municipal, endereço completo, código IBGE, regime tributário, regime especial (regEspTrib). Relacionamento: possui N certificados, N tomadores, N serviços favoritos, N notas.
- **Certificate** (Certificado Digital): Representa um certificado ICP-Brasil A1. Atributos: conteúdo criptografado (.pfx), CNPJ extraído, nome titular, data validade, status (ativo/expirado). Relacionamento: pertence a 1 empresa.
- **Customer** (Tomador): Representa o tomador do serviço. Atributos: CPF ou CNPJ, razão social, nome fantasia, inscrição municipal, endereço, código IBGE, e-mail, telefone. Relacionamento: pertence a 1 empresa, possui N notas.
- **Service** (Serviço): Representa um tipo de serviço prestado. Atributos: código item LC 116, subitem, código NBS, descrição, alíquota ISS padrão, flag favorito. Relacionamento: pertence a 1 empresa.
- **CompanyUser** (Pivot Empresa↔Usuário): Representa o vínculo entre usuário e empresa. Atributos: role (admin/contador/operador). Relacionamento: 1 user, 1 company.
- **Invoice** (NFS-e): Representa uma NFS-e emitida. Atributos: chave de acesso (50 chars), número DPS, série (faixa 00001-49999), id DPS (42 posições), status (autorizada/cancelada/substituída), valor serviço, valor líquido, ISS, retenções (IR, CSLL, CP), xml_sent_path (referência MinIO), xml_response_path (referência MinIO), pdf_path (referência MinIO), data emissão, data cancelamento, motivo cancelamento, NFS-e substituída (ref). Relacionamento: pertence a 1 empresa, vinculada a 1 tomador, 1 serviço.
- **AuditLog** (Log de Auditoria): Representa um registro imutável de operação fiscal. Atributos: operação, usuário, empresa, timestamp, payload resumido, resultado (sucesso/falha), código erro. Relacionamento: vinculado a 1 empresa, 1 usuário, opcionalmente 1 nota.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Usuários conseguem emitir uma NFS-e do início ao fim (login → preenchimento → autorização → PDF) em menos de 2 minutos na primeira vez e menos de 30 segundos nas subsequentes
- **SC-002**: Taxa de rejeição pelo ADN inferior a 5% após a validação local pré-envio
- **SC-003**: Tempo de resposta da emissão (end-to-end, incluindo ADN) inferior a 5 segundos em 95% dos casos
- **SC-004**: 100% das operações fiscais (emissão, cancelamento, substituição) registradas em log de auditoria imutável
- **SC-005**: Zero certificados digitais expostos em logs, respostas de API ou repositório de código
- **SC-006**: Uptime do sistema superior a 99.5% mensal (excluindo indisponibilidade do ADN)
- **SC-007**: 500+ NFS-e emitidas com sucesso por mês até o 3º mês após lançamento
- **SC-008**: Dashboard carrega em menos de 2 segundos com dados de até 12 meses

## Assumptions

- Usuários possuem certificado digital ICP-Brasil A1 (.pfx) — certificado A3 (token/smart card) está fora do escopo do MVP
- O município do prestador aderiu ao Padrão Nacional de NFS-e — municípios não aderentes estão fora do escopo
- Usuários possuem conexão estável à internet (não há modo offline)
- O ambiente de Produção Restrita (homologação) do ADN está disponível para testes durante o desenvolvimento
- Soft multi-tenancy (tenant_id) é suficiente para o MVP — banco de dados separado por empresa não é necessário
- Emissão síncrona (sem lote) é suficiente para o MVP
- Não haverá integração com APIs municipais legadas (ABRASF, BETHA) — apenas Padrão Nacional
- O sistema não emitirá NF-e (produtos) ou NFC-e (consumidor) — apenas NFS-e (serviços)
- O schema XSD V1.00.02 e as regras da NT 007 estão vigentes e estáveis durante o desenvolvimento
- IBS/CBS (Reforma Tributária) permanece opcional e não será obrigatório durante o MVP

## Clarifications (resolvidas em 2026-04-10)

| # | Pergunta | Decisão | Impacto |
|---|---|---|---|
| C1 | Roles são globais ou por empresa? | **Por empresa** — tabela pivot CompanyUser com role | Data model (RBAC) |
| C2 | Origem das alíquotas ISS? | **API parâmetros municipais** (cache 24h) + override manual | Fluxo de emissão |
| C3 | Numeração da DPS? | **Série configurável** (faixa 00001-49999) + **nº auto-incrementado** por empresa+série | Data model + idempotência |
| C4 | Armazenamento de XMLs/PDFs? | **MinIO** (imagem `quay.io/minio/minio:RELEASE.2024-01-13T07-53-03Z-cpuv1`) com referência no banco | Infraestrutura + docker-compose |
| C5 | Fluxo de onboarding? | **Wizard 2 passos** (usuário → empresa). Certificado e serviço depois, com bloqueio na emissão | UX + fluxo de autenticação |
| C6 | Auto-preenchimento CNPJ? | **API pública CNPJ.ws** (`publica.cnpj.ws/cnpj/{cnpj}`) — gratuita, 3 req/min, retorna dados completos da empresa | UX + cadastro de tomadores e empresas |
