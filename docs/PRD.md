# PRD — InovaiSystem: Plataforma de Emissão de NFS-e Padrão Nacional

> **Autor**: Product & Tech Lead  
> **Versão**: 1.0.0  
> **Data**: 2026-04-10  
> **Status**: Draft  

---

## 1. Executive Summary

- **Nome do Produto**: InovaiSystem
- **Visão**: Ser a plataforma mais simples e confiável para prestadores de serviço emitirem NFS-e no Padrão Nacional brasileiro.
- **Problema principal**: Emitir NFS-e no Brasil exige lidar com APIs governamentais complexas (mTLS, XML assinado, GZip+Base64, regras municipais), certificados digitais e regras fiscais que mudam frequentemente — tudo isso é inviável para a maioria dos prestadores de serviço e pequenos ERPs.
- **Proposta de valor**: Abstrair toda a complexidade da integração com o Ambiente de Dados Nacional (ADN), oferecendo uma interface intuitiva onde o usuário preenche os dados do serviço e o sistema cuida de assinatura digital, validação, envio, consulta, cancelamento e geração de PDF — tudo em um só lugar.

---

## 2. Problema & Oportunidade

### Problema Atual

O Padrão Nacional de NFS-e (SNNFSe) foi criado para unificar os potenciais 5.570 modelos municipais de nota fiscal de serviço. Porém, a integração direta com a API do governo exige:

- **Certificado digital ICP-Brasil** (A1 ou A3) com configuração mTLS
- **Assinatura XML** no padrão XMLDSIG (W3C)
- **Compressão GZip + codificação Base64** dos documentos
- Conhecimento de **regras tributárias municipais** que variam por cidade
- Tratamento de **rejeições** com códigos específicos (E1235, etc.)
- Acompanhamento de **atualizações regulatórias** (NT 007, IBS/CBS)

### Impacto no Usuário/Negócio

| Impacto | Descrição |
|---|---|
| **Tempo** | Integração direta com a API leva 2-4 meses para um dev experiente |
| **Custo** | Empresas gastam R$ 5k-20k/mês com softwares fiscais legados |
| **Erro** | Rejeições por XML malformado, assinatura inválida ou campos faltantes causam atraso no faturamento |
| **Risco fiscal** | Notas emitidas incorretamente geram multas e problemas com a Receita |

### Oportunidade de Mercado

- **22+ milhões** de empresas ativas no Brasil (dados Receita Federal)
- **15+ milhões** são prestadoras de serviço (potenciais emissoras de NFS-e)
- Padrão Nacional em expansão — municípios aderindo progressivamente
- Reforma Tributária (IBS/CBS) vai exigir adaptação de **todos** os emissores
- Mercado fragmentado: maioria das soluções existentes são caras, legadas ou engessadas

**Referências de mercado**: Nota Carioca, eNotas, Tecnospeed PlugNotas, Nota Gateway, NFe.io

---

## 3. Usuários & Personas

### Persona 1: Ana — Contadora de Escritório

| Atributo | Detalhe |
|---|---|
| **Perfil** | Contadora, 35 anos, atende 40+ clientes PJ/PF |
| **Dor principal** | Emitir notas para múltiplos clientes em municípios diferentes é caótico |
| **Job to be Done** | "Quero emitir NFS-e para qualquer cliente em poucos cliques, sem me preocupar com XML" |
| **Ferramenta atual** | Portais de prefeitura (um por município) + planilhas Excel |

### Persona 2: Roberto — Dono de Empresa de TI

| Atributo | Detalhe |
|---|---|
| **Perfil** | Empresário, 42 anos, empresa de consultoria com 15 funcionários |
| **Dor principal** | Perde tempo todo mês gerando notas manualmente e conferindo impostos |
| **Job to be Done** | "Quero que meu faturamento mensal seja gerado automaticamente com os impostos corretos" |
| **Ferramenta atual** | Software ERP legado com integração quebrada |

### Persona 3: Lucas — Dev de ERP

| Atributo | Detalhe |
|---|---|
| **Perfil** | Desenvolvedor, 28 anos, trabalha em startup de gestão empresarial |
| **Dor principal** | Integrar com a API do governo é complexo e mal documentado |
| **Job to be Done** | "Quero uma API simples que eu chame com JSON e receba a NFS-e pronta" |
| **Ferramenta atual** | Integração caseira com SOAP/XML que vive quebrando |

---

## 4. Jornada do Usuário

### Fluxo Atual (AS-IS) — Emissão Manual

```
1. Acessa portal da prefeitura (login gov.br ou municipal)
2. Preenche formulário extenso manualmente
3. Confere código de serviço, alíquota, regime tributário
4. Calcula impostos na mão (ISS, IR, CSLL, PIS, COFINS)
5. Emite a nota
6. Baixa o PDF e envia por e-mail ao cliente
7. Registra em planilha para controle
8. Repete para cada nota/cliente
```

**Pontos de fricção**: login diferente por município, cálculo manual de impostos, sem controle centralizado, retrabalho constante.

### Fluxo Proposto (TO-BE) — InovaiSystem

```
1. Faz login no InovaiSystem
2. Seleciona cliente (já cadastrado) ou cadastra novo
3. Seleciona serviço (código LC 116 pré-configurado)
4. Sistema calcula impostos automaticamente (baseado em parâmetros municipais)
5. Usuário revisa e confirma
6. Sistema monta DPS → assina XML → envia ao ADN → recebe NFS-e
7. PDF (DANFSe) gerado automaticamente
8. Nota enviada por e-mail ao cliente (opcional)
9. Dashboard atualizado em tempo real
```

**Eliminação de fricção**: login único, cálculo automático, envio automatizado, histórico centralizado.

---

## 5. Funcionalidades

### Must Have (MVP)

#### F01 — Autenticação e Multi-Empresa

| Item | Detalhe |
|---|---|
| **Descrição** | Login seguro com suporte a múltiplas empresas emissoras por conta |
| **Regra de negócio** | Cada empresa vinculada a um CNPJ com certificado digital próprio. Um usuário pode gerenciar N empresas. |
| **Critérios de aceitação** | **Given** um usuário autenticado **When** acessa o sistema **Then** visualiza apenas as empresas às quais tem permissão |

#### F02 — Gestão de Certificados Digitais

| Item | Detalhe |
|---|---|
| **Descrição** | Upload, armazenamento seguro e monitoramento de certificados ICP-Brasil A1 |
| **Regra de negócio** | Certificado A1 (.pfx) armazenado criptografado. Alertas 30/15/7 dias antes do vencimento. Validação de CNPJ no certificado vs empresa cadastrada. |
| **Critérios de aceitação** | **Given** um certificado A1 válido **When** o usuário faz upload **Then** o sistema valida, extrai CNPJ/validade e armazena criptografado |

#### F03 — Cadastro de Clientes (Tomadores)

| Item | Detalhe |
|---|---|
| **Descrição** | CRUD de tomadores de serviço com dados fiscais completos |
| **Regra de negócio** | CPF/CNPJ validado. Endereço completo com código IBGE do município. Campos: razão social, nome fantasia, inscrição municipal, e-mail, telefone. |
| **Critérios de aceitação** | **Given** dados válidos de um tomador **When** o usuário salva **Then** o tomador fica disponível para seleção na emissão de NFS-e |

#### F04 — Cadastro de Serviços

| Item | Detalhe |
|---|---|
| **Descrição** | Tabela de serviços conforme Lista de Serviços da LC 116/2003 com código NBS |
| **Regra de negócio** | Cada serviço vinculado a item/subitem da LC 116, código NBS e alíquota ISS padrão. Permite criar "favoritos" (serviços frequentes da empresa). |
| **Critérios de aceitação** | **Given** uma empresa cadastrada **When** o usuário configura seus serviços frequentes **Then** esses serviços aparecem como opção rápida na emissão |

#### F05 — Emissão de NFS-e

| Item | Detalhe |
|---|---|
| **Descrição** | Formulário de emissão que monta a DPS, assina, envia ao ADN e retorna a NFS-e autorizada |
| **Regra de negócio** | Processamento síncrono. XML montado conforme XSD V1.00.02. Assinatura XMLDSIG. Envio compactado (GZip+Base64) via mTLS. Idempotência por `idDps` + CNPJ + série + número. Chave de acesso de 50 caracteres retornada na resposta. |
| **Critérios de aceitação** | **Given** dados válidos de serviço e tomador **When** o usuário confirma a emissão **Then** o sistema retorna a NFS-e autorizada com chave de acesso e disponibiliza o DANFSe em PDF |

**Exemplo real de uso**:
> Ana (contadora) seleciona o cliente "Tech Solutions Ltda", escolhe o serviço "Consultoria em TI" (item 1.01 da LC 116), informa o valor R$ 5.000,00. O sistema calcula ISS (5% = R$ 250), mostra preview da nota, Ana confirma. Em 3 segundos a NFS-e é autorizada e o PDF é gerado.

#### F06 — Cancelamento de NFS-e

| Item | Detalhe |
|---|---|
| **Descrição** | Cancelar uma NFS-e já emitida via registro de evento |
| **Regra de negócio** | Cancelamento individual por chave de acesso. Motivo obrigatório. Prazo de cancelamento conforme regra municipal. Nota já cancelada retorna mensagem informativa. |
| **Critérios de aceitação** | **Given** uma NFS-e autorizada **When** o usuário solicita cancelamento com motivo **Then** o sistema registra o evento e a nota muda para status "Cancelada" |

#### F07 — Consulta de NFS-e

| Item | Detalhe |
|---|---|
| **Descrição** | Consultar NFS-e por chave de acesso, número da DPS, período ou tomador |
| **Regra de negócio** | Busca local (banco interno) + verificação remota (GET /nfse/{chaveAcesso}). Filtros: data, status, tomador, valor. |
| **Critérios de aceitação** | **Given** filtros de busca preenchidos **When** o usuário pesquisa **Then** o sistema retorna lista paginada de NFS-e com status atualizado |

#### F08 — Geração de DANFSe (PDF)

| Item | Detalhe |
|---|---|
| **Descrição** | Gerar e baixar o Documento Auxiliar da NFS-e em PDF |
| **Regra de negócio** | PDF gerado via endpoint GET /danfse/{chaveAcesso} da API Nacional. Cache local do PDF para evitar chamadas repetidas. |
| **Critérios de aceitação** | **Given** uma NFS-e autorizada **When** o usuário clica em "Baixar PDF" **Then** o DANFSe é baixado no navegador |

#### F09 — Dashboard com Métricas

| Item | Detalhe |
|---|---|
| **Descrição** | Painel principal com visão geral de notas emitidas, receita e status |
| **Regra de negócio** | Cards: total de notas no período, receita faturada, notas canceladas, impostos retidos. Gráfico de evolução por período (diário/semanal/mensal). Filtro por empresa e data. |
| **Critérios de aceitação** | **Given** notas emitidas no período **When** o usuário acessa o dashboard **Then** os indicadores são calculados e exibidos em tempo real |

### Should Have

#### F10 — Substituição de NFS-e

| Item | Detalhe |
|---|---|
| **Descrição** | Emitir nova NFS-e substituindo uma anterior (cancela a original automaticamente) |
| **Regra de negócio** | Usa mesma estrutura de emissão + campos de referência à nota substituída + motivo. |
| **Critérios de aceitação** | **Given** uma NFS-e que precisa ser corrigida **When** o usuário emite substituta **Then** a original é cancelada e a nova é autorizada |

#### F11 — Consulta de Parâmetros Municipais

| Item | Detalhe |
|---|---|
| **Descrição** | Consultar e cachear alíquotas, regimes, benefícios e convênios por município |
| **Regra de negócio** | Fonte: API /parametros_municipais/{codMunicipio}. Cache com TTL de 24h. Invalidação manual disponível. |
| **Critérios de aceitação** | **Given** um município selecionado **When** o sistema precisa de alíquotas **Then** consulta cache local ou API e aplica automaticamente |

#### F12 — Envio de NFS-e por E-mail

| Item | Detalhe |
|---|---|
| **Descrição** | Enviar DANFSe (PDF) + XML por e-mail ao tomador automaticamente após emissão |
| **Regra de negócio** | E-mail configurável por empresa. Template personalizável. Opt-in na emissão. |
| **Critérios de aceitação** | **Given** emissão concluída com sucesso e e-mail configurado **When** a opção de envio está ativa **Then** o tomador recebe o PDF e XML por e-mail |

#### F13 — Relatórios Fiscais

| Item | Detalhe |
|---|---|
| **Descrição** | Relatórios de ISS a recolher, retenções federais (IR, CSLL, PIS, COFINS) e faturamento |
| **Regra de negócio** | Agrupamento por período, município, tomador. Exportação em PDF e CSV. |
| **Critérios de aceitação** | **Given** um período selecionado **When** o usuário gera relatório de ISS **Then** o sistema apresenta o valor total a recolher detalhado por município |

### Nice to Have

#### F14 — Distribuição de DFe (NSU)

| Item | Detalhe |
|---|---|
| **Descrição** | Consultar NFS-e emitidas contra o CNPJ da empresa (como tomador) via NSU |
| **Regra de negócio** | Polling periódico via GET /DFe/{NSU}. Armazena documentos recebidos. |

#### F15 — Emissão em Lote (via CSV/API)

| Item | Detalhe |
|---|---|
| **Descrição** | Importar planilha CSV ou chamar API para emissão em massa |
| **Regra de negócio** | Validação prévia de todos os registros. Processamento assíncrono via fila. Relatório de sucesso/falha por nota. |

#### F16 — Integração IBS/CBS (Reforma Tributária)

| Item | Detalhe |
|---|---|
| **Descrição** | Calcular e incluir tributos IBS/CBS conforme calculadora nacional |
| **Regra de negócio** | Integração com API da calculadora (consumo.tributos.gov.br). Campos opcionais até publicação de obrigatoriedade. |

#### F17 — API Pública para Terceiros

| Item | Detalhe |
|---|---|
| **Descrição** | API REST documentada (Swagger) para integração de ERPs e sistemas externos |
| **Regra de negócio** | Autenticação via API Key + OAuth2. Rate limit. Endpoints espelham funcionalidades do sistema. |

---

## 6. Métricas de Sucesso (KPIs)

### Métrica Principal

| KPI | Meta MVP (3 meses) | Como Medir |
|---|---|---|
| **NFS-e emitidas com sucesso / mês** | 500+ | Contador no banco de dados, status = autorizada |

### Métricas Secundárias

| KPI | Meta | Como Medir |
|---|---|---|
| **Taxa de rejeição** | < 5% | Notas rejeitadas / notas enviadas |
| **Tempo médio de emissão** | < 5 segundos (E2E) | Timestamp envio → timestamp resposta |
| **Uptime do sistema** | 99.5% | Monitoramento (UptimeRobot / Prometheus) |
| **NPS dos usuários** | > 40 | Pesquisa in-app trimestral |
| **Empresas ativas** | 20+ | Empresas com ≥1 emissão nos últimos 30 dias |
| **Churn mensal** | < 10% | Empresas que pararam de emitir |

---

## 7. Requisitos Não Funcionais

### Performance

- Emissão de NFS-e (E2E): **< 5 segundos** em p95
- Listagem/consulta: **< 500ms** em p95
- Dashboard: **< 2 segundos** para carregamento completo

### Segurança

- Certificados digitais armazenados com **criptografia AES-256** (vault ou coluna encrypted)
- Comunicação com ADN via **mTLS** obrigatório
- **HTTPS** em toda a aplicação
- Autenticação via **Laravel Sanctum** (tokens SPA) ou **JWT**
- RBAC: roles de admin, contador, operador (permissões granulares)
- Logs de auditoria para todas as operações fiscais (imutáveis)
- **Sincronização NTP** obrigatória no servidor (time drift quebra validações de assinatura)

### Escalabilidade

- Arquitetura stateless no backend (horizontalmente escalável)
- Filas (Redis/Laravel Queue) para jobs assíncronos (e-mail, lote, DFe)
- Cache distribuído (Redis) para parâmetros municipais
- PostgreSQL com particionamento por período para tabela de notas (futuro)

### UX

- Mobile-first (responsivo com breakpoints Tailwind)
- Dark mode nativo (já presente no scaffold Nuxt UI)
- Feedback visual instantâneo (loading states, toasts de sucesso/erro)
- Atalhos de teclado para operações frequentes (já presente no scaffold)
- Idioma: **Português do Brasil** em toda a interface

---

## 8. Dependências

### APIs Governamentais (Padrão Nacional)

| Módulo | Ambiente | Base URL |
|---|---|---|
| **ADN** (emissão/consulta) | Produção | `adn.nfse.gov.br` |
| **ADN** (emissão/consulta) | Homologação | `adn.producaorestrita.nfse.gov.br` |
| **CNC** (cadastro contribuintes) | Produção | `adn.nfse.gov.br/cnc/` |
| **Parametrização** (alíquotas/regimes) | Produção | `adn.nfse.gov.br/parametrizacao/` |
| **DANFSe** (PDF) | Produção | `adn.nfse.gov.br/danfse/` |
| **SEFIN Nacional** | Produção | `sefin.nfse.gov.br` |

### Integrações Externas

| Sistema | Finalidade | Prioridade |
|---|---|---|
| **ICP-Brasil (certificado digital)** | Assinatura XML e mTLS | MVP |
| **Calculadora IBS/CBS** | Cálculo de tributos Reforma Tributária | Nice to Have |
| **SMTP (e-mail)** | Envio de NFS-e ao tomador | Should Have |
| **Consulta CNPJ (ReceitaWS / BrasilAPI)** | Auto-preenchimento de dados do tomador | Should Have |

### Stack Tecnológica (já definida no projeto)

| Camada | Tecnologia |
|---|---|
| **Frontend** | Nuxt 4 + Nuxt UI v4 + TailwindCSS v4 + Vue 3 |
| **Backend** | Laravel 13 (PHP 8.3) |
| **Banco de Dados** | PostgreSQL 16 |
| **Cache/Filas** | Redis 7 |
| **Containerização** | Docker + docker-compose |
| **Assinatura XML** | `robrichards/xmlseclibs` (PHP) |
| **Validação XSD** | `DOMDocument` nativo do PHP |

---

## 9. Riscos

### Riscos Técnicos

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| **API do ADN instável/fora do ar** | Média | Alto | Circuit breaker + retry com backoff exponencial + DLQ para reprocessamento |
| **Mudança no schema XSD sem aviso** | Média | Alto | Monitoramento do portal gov.br + testes automatizados contra XSD + alertas |
| **Certificado digital expirado** | Alta | Alto | Alertas automáticos 30/15/7 dias antes + dashboard de validade |
| **Assinatura XML rejeitada** | Média | Médio | Validação local pré-envio + testes exaustivos em homologação |

### Riscos de Produto

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| **Município do cliente não aderiu ao Padrão Nacional** | Alta | Alto | Informar claramente no onboarding + mapa de adesão municipal visível |
| **UX complexa para contador não-técnico** | Média | Alto | Testes de usabilidade com contadores reais + wizard de primeira emissão |
| **Concorrência de soluções estabelecidas** | Alta | Médio | Diferencial em preço, simplicidade e foco no Padrão Nacional |

### Riscos de Negócio

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| **Reforma Tributária muda regras drasticamente** | Alta | Alto | Arquitetura modular para cálculo de tributos + acompanhar publicações do CGNFS-e |
| **Baixa adoção inicial** | Média | Alto | Freemium para MEIs + onboarding guiado + conteúdo educativo |

---

## 10. Roadmap (Alto Nível)

### Fase 1 — Fundação (Semanas 1-3)

- Setup da infraestrutura de comunicação com ADN (mTLS, HTTP client)
- Módulo de assinatura digital XML (XMLDSIG)
- Validação contra XSD
- Testes em ambiente de Produção Restrita (homologação)
- Modelo de dados: empresas, certificados, tomadores, serviços, notas

### Fase 2 — Emissão Core (Semanas 4-6)

- F01 — Autenticação e Multi-Empresa
- F02 — Gestão de Certificados Digitais
- F03 — Cadastro de Clientes (Tomadores)
- F04 — Cadastro de Serviços
- F05 — Emissão de NFS-e
- F06 — Cancelamento de NFS-e

### Fase 3 — Consultas e Dashboard (Semanas 7-9)

- F07 — Consulta de NFS-e
- F08 — Geração de DANFSe (PDF)
- F09 — Dashboard com Métricas
- F11 — Consulta de Parâmetros Municipais

### Fase 4 — Valor Agregado (Semanas 10-12)

- F10 — Substituição de NFS-e
- F12 — Envio de NFS-e por E-mail
- F13 — Relatórios Fiscais
- Testes com usuários beta

### Fase 5 — Escala (Semanas 13+)

- F14 — Distribuição de DFe
- F15 — Emissão em Lote
- F16 — Integração IBS/CBS
- F17 — API Pública
- Migração para Produção

---

## 11. Fora de Escopo

Os itens abaixo **não serão abordados** nesta versão:

- **NF-e / NFC-e** (notas de produto/mercadoria) — foco exclusivo em serviço (NFS-e)
- **Integração com APIs municipais legadas** (ABRASF, BETHA, etc.) — apenas Padrão Nacional
- **Contabilidade completa** (plano de contas, razão, balancete) — não é um ERP contábil
- **Emissão para municípios não aderentes** ao Padrão Nacional
- **App mobile nativo** — versão web responsiva é suficiente para MVP
- **Regime especial de exportação** de serviços
- **Integração com bancos** para boletos/pagamentos

---

## 12. Dúvidas em Aberto

| # | Dúvida | Responsável | Prazo |
|---|---|---|---|
| 1 | Qual modelo de precificação? (Freemium? Por nota? Assinatura mensal?) | Product/Business | Antes do beta |
| 2 | Vamos suportar certificado A3 (token/smart card) no MVP ou apenas A1? | Tech Lead | Semana 1 |
| 3 | Qual o prazo máximo de cancelamento por município? (Parametrizável?) | Product | Semana 2 |
| 4 | Precisamos de multi-tenancy real (banco separado) ou soft-tenancy (tenant_id)? | Tech Lead | Semana 1 |
| 5 | Quando o governo vai tornar IBS/CBS obrigatório? (Impacta priorização do F16) | Regulatório | Acompanhar |
| 6 | Vamos oferecer white-label para escritórios de contabilidade? | Product/Business | Pós-MVP |

---

## Sugestões de Melhoria (Baseadas em Mercado)

1. **Onboarding Wizard** (ref: Stripe Atlas): guia passo-a-passo na primeira emissão — upload de certificado → cadastro de serviço → emissão teste em homologação → primeira nota real.

2. **Auto-preenchimento de CNPJ** (ref: ContaAzul, Omie): ao digitar CNPJ do tomador, buscar dados na ReceitaWS/BrasilAPI e preencher automaticamente razão social, endereço e código IBGE.

3. **Templates de Serviço** (ref: eNotas): serviços pré-configurados por segmento (TI, Marketing, Advocacia, Contabilidade) para reduzir configuração inicial.

4. **Webhook de Eventos** (ref: Stripe webhooks): notificar sistemas externos quando uma nota é emitida/cancelada — essencial para quem integra com ERPs.

5. **Modo Recorrente** (ref: Asaas, Vindi): configurar emissão automática mensal para contratos fixos — "todo dia 5, emitir R$ 3.000 para cliente X".

---

*Este PRD é um documento vivo. Deve ser revisado a cada sprint e atualizado conforme descobertas de produto e mudanças regulatórias.*
