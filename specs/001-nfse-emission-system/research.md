# Research: Sistema de Emissão de NFS-e Padrão Nacional

**Date**: 2026-04-10
**Status**: Complete

## R1 — Assinatura XML (XMLDSIG) em PHP

- **Decision**: Usar `robrichards/xmlseclibs` (versão mais recente)
- **Rationale**: Biblioteca PHP consolidada para XMLDSIG, usada por quase todos os projetos de NF-e/NFS-e em PHP (sped-nfse, nfephp). Suporta canonicalization C14N, algoritmo RSA-SHA1/SHA256, e referências por ID — exatamente o que o Padrão Nacional exige.
- **Alternatives considered**:
  - `openssl` nativo via CLI — funcional mas exige manuseio manual de XML canonicalizado, propenso a erros
  - `spatie/xml-signer` — wrapper mais alto nível, mas menos controle sobre canonicalization específica exigida pelo governo

## R2 — Comunicação mTLS com ADN

- **Decision**: Usar `Illuminate\Http\Client` (wrapper Guzzle) com opções `cert` e `ssl_key` do cURL
- **Rationale**: Laravel HTTP Client suporta nativamente mTLS via opções Guzzle (`cert`, `ssl_key`, `verify`). O certificado A1 (.pfx) precisa ser separado em cert.pem + key.pem temporários para uso na requisição. Arquivo temporário criado em runtime e removido após uso.
- **Alternatives considered**:
  - cURL direto via `curl_exec` — funcional mas perde retry, logging e tratamento de erros do Laravel HTTP Client
  - Guzzle standalone — redundante, Laravel já encapsula

## R3 — Armazenamento de certificados (AES-256)

- **Decision**: Usar `encrypted` cast do Laravel (Eloquent) com `APP_KEY` como chave de criptografia + coluna `TEXT` no PostgreSQL
- **Rationale**: Laravel Eloquent `encrypted` cast usa AES-256-CBC com APP_KEY automaticamente. O conteúdo do .pfx é armazenado como string Base64 encrypted. A senha do certificado é armazenada em coluna `encrypted` separada. Zero complexidade adicional, seguro por padrão.
- **Alternatives considered**:
  - HashiCorp Vault — excelente mas overengineering para MVP (Princípio V: Lean)
  - Salvar .pfx no MinIO com path encrypted — adiciona latência na emissão (precisa baixar antes de assinar)

## R4 — Validação XSD em PHP

- **Decision**: Usar `DOMDocument::schemaValidate()` nativo do PHP
- **Rationale**: PHP possui validação XSD nativa via libxml2. Não precisa de dependência extra. Retorna erros detalhados via `libxml_get_errors()`. Os arquivos XSD do Padrão Nacional (.xsd) são salvos localmente no projeto em `backend/resources/xsd/`.
- **Alternatives considered**:
  - `xmllint` via CLI — funcional mas processo externo, dificulta captura de erros
  - Biblioteca Java (Saxon) via bridge — overengineering

## R5 — MinIO como Object Storage

- **Decision**: Usar `league/flysystem-aws-s3-v3` via Laravel Filesystem com disco `minio`
- **Rationale**: Laravel Filesystem suporta S3-compatível nativamente. MinIO é 100% compatível com API S3. Configuração via `config/filesystems.php` com disco customizado. Imagem: `quay.io/minio/minio:RELEASE.2024-01-13T07-53-03Z-cpuv1` (requisito do usuário).
- **Alternatives considered**:
  - AWS S3 real — custo + latência para operação local
  - Filesystem local (storage/app) — não escalável, sem redundância

## R6 — Autenticação (Laravel Sanctum)

- **Decision**: Laravel Sanctum com SPA authentication (cookie-based) para o frontend Nuxt
- **Rationale**: Sanctum é o padrão do Laravel para SPAs. Usa cookies HTTP-only com CSRF token. Mais seguro que JWT para SPA (sem token no localStorage). Já incluso no Laravel 13.
- **Alternatives considered**:
  - JWT (tymon/jwt-auth) — token no localStorage é vulnerável a XSS
  - Laravel Passport (OAuth2) — overengineering para MVP, mais adequado para API pública (future Nice to Have F17)

## R7 — Compressão GZip + Base64

- **Decision**: Usar `gzencode()` + `base64_encode()` nativos do PHP
- **Rationale**: Funções nativas do PHP, zero dependência. O ADN espera o XML comprimido com GZip nível padrão e codificado em Base64 no corpo da requisição. Decodificação reversa: `base64_decode()` + `gzdecode()`.
- **Alternatives considered**: Nenhuma — funções nativas são a escolha óbvia

## R8 — Numeração DPS (série + número sequencial)

- **Decision**: Coluna `dps_number` auto-incrementada por empresa+série via query `SELECT MAX(dps_number) + 1` com lock `FOR UPDATE` dentro de transação
- **Rationale**: Garante sequencialidade sem gaps. Lock pessimista previne condições de corrida em emissões simultâneas. Série configurável na tabela `companies` (padrão: 00001, faixa 00001-49999 para aplicativo próprio).
- **Alternatives considered**:
  - Sequence do PostgreSQL por empresa — complexo de gerenciar N sequences dinâmicas
  - UUID — não atende requisito de numeração sequencial do Padrão Nacional

## R9 — Cache de Parâmetros Municipais

- **Decision**: Redis cache com TTL de 24h. Key: `municipal_params:{codIBGE}`. Invalidação manual via endpoint admin.
- **Rationale**: Parâmetros municipais mudam raramente (alíquotas, regimes). Cache 24h reduz chamadas à API do ADN. Redis já está na stack. Override manual pelo usuário no formulário de emissão.
- **Alternatives considered**:
  - Banco de dados (tabela cache) — mais lento, Redis já disponível
  - Sem cache — chamadas desnecessárias ao ADN a cada emissão

## R10 — Estrutura do Identificador DPS (42 posições)

- **Decision**: Gerar idDps programaticamente conforme regra do Padrão Nacional
- **Format**: `{codMunicipio:7}{tipoInscricao:1}{inscricaoFederal:14}{serie:5}{numDps:15}` = 42 posições
  - codMunicipio: código IBGE do município do prestador (7 dígitos)
  - tipoInscricao: 1=CPF, 2=CNPJ
  - inscricaoFederal: CNPJ com 14 dígitos (ou CPF com 000 à esquerda)
  - serie: série da DPS com 5 dígitos (faixa 00001-49999)
  - numDps: número sequencial com 15 dígitos (zero-padded)
- **Rationale**: Regra documentada no manual oficial do Padrão Nacional. Identificador deve ser único por emitente.

## R11 — Auto-preenchimento de CNPJ (API Pública)

- **Decision**: Usar API pública gratuita do CNPJ.ws: `GET https://publica.cnpj.ws/cnpj/{cnpj}`
- **Rationale**: API gratuita, sem necessidade de cadastro/API key, retorna JSON completo com razão social, nome fantasia, endereço (logradouro, número, complemento, bairro, CEP), cidade (ibge_id — mapeamento direto para codigo_ibge), estado (sigla), e-mail e telefone. Campos se mapeiam diretamente para as entidades Company e Customer.
- **Rate limit**: 3 consultas por minuto (IP-based)
- **Mapeamento de campos**:
  - `razao_social` → razao_social
  - `estabelecimento.nome_fantasia` → nome_fantasia
  - `estabelecimento.logradouro` → logradouro (prefixar com tipo_logradouro)
  - `estabelecimento.numero` → numero
  - `estabelecimento.complemento` → complemento
  - `estabelecimento.bairro` → bairro
  - `estabelecimento.cep` → cep
  - `estabelecimento.cidade.ibge_id` → codigo_ibge
  - `estabelecimento.estado.sigla` → uf
  - `estabelecimento.email` → email
  - `estabelecimento.ddd1` + `estabelecimento.telefone1` → telefone
- **Implementation**: Proxy no backend (`GET /api/cnpj-lookup/{cnpj}`) para evitar CORS e controlar rate limit. Cache Redis com TTL 7 dias (dados da Receita mudam raramente).
- **Alternatives considered**:
  - ReceitaWS (`receitaws.com.br`) — 3 req/min gratuito também, mas retorno menos estruturado (sem ibge_id da cidade)
  - BrasilAPI (`brasilapi.com.br/api/cnpj/v1`) — gratuita mas sem SLA e menos campos
  - CNPJa (`cnpja.com`) — API paga, melhor para produção com alto volume
