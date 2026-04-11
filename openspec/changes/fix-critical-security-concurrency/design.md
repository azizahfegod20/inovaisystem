## Context

O sistema atual de emissão de NFS-e possui duas vulnerabilidades críticas identificadas durante análise de código:

1. **Senhas em texto plano**: A tabela `certificates.pfx_password` armazena senhas de certificados digitais em claro, representando risco de segurança significativo caso o banco de dados seja comprometido.

2. **Race condition no DPS**: O número sequencial de DPS (`dps_next_number`) é lido com lock FOR UPDATE, mas o update acontece fora do transaction principal, permitindo que requisições concorrentes obtenham o mesmo número.

**Stack atual**: Laravel 13 + PostgreSQL + Redis, com certificados digitais para assinatura de XMLs na API Nacional de NFS-e.

## Goals / Non-Goals

**Goals:**
- Implementar criptografia AES-256-GCM para todas as senhas de certificados (novas e existentes)
- Eliminar race condition na geração do número de DPS mantendo performance
- Criar hierarquia de exceções para tratamento de erro apropriado
- Migration segura de dados em produção sem downtime

**Non-Goals:**
- Mudança na estrutura da API (interface mantida)
- Alteração do tipo de armazenamento (continua sendo string no BD)
- Implementação de HSM ou key management externo
- Modificação do circuit breaker (será tratado em mudança futura)

## Decisions

### 1. Criptografia com Laravel Crypt

**Decisão**: Usar `Crypt::encryptString()` / `Crypt::decryptString()` do Laravel

**Rationale**:
- Usa AES-256-GCM com autenticação embutida
- Integrado com APP_KEY do .env (sem configuração adicional)
- Performance adequada (< 1ms para criptografar senha)
- Já disponível no framework, sem novas dependências

**Alternativas consideradas**:
- OpenSSL manual: Rejeitado - mais complexo, sem benefício
- defuse/php-encryption: Rejeitado - sobrecarga para caso simples

### 2. Race Condition: Mover Update para Transaction

**Decisão**: Manter lock FOR UPDATE mas mover `update()` para dentro do transaction

```php
// ANTES (vulnerável):
$dpsNumber = $this->getNextDpsNumber($company); // Lock aqui
// ... processo demorado ...
$company->update(['dps_next_number' => $dpsNumber + 1]); // Update fora do lock

// DEPOIS (seguro):
return DB::transaction(function () use (...) {
    $dpsNumber = $this->getNextDpsNumber($company);
    $company->update(['dps_next_number' => $dpsNumber + 1]); // Dentro do transaction
    
    // ... resto do processo ...
});
```

**Rationale**:
- Minimiza mudança no código (só move uma linha)
- Lock_FOR UPDATE garante que ninguém mais leia enquanto transaction ativo
- PostgreSQL mantém lock até commit do transaction

**Alternativas consideradas**:
- PostgreSQL SEQUENCE: Rejeitado - quebraria lógica de múltiplas empresas
- Optimistic locking: Rejeitado - complexidade desnecessária para este caso

### 3. Exceções Customizadas

**Decisão**: Criar 4 novas exceções estendendo `RuntimeException`

- `CertificateException`: Base para erros de certificado
- `CertificateStorageException`: Erros ao armazenar/recuperar certificado
- `DpsGenerationException`: Falhas na geração de número de DPS
- `NfseEmissionException`: Base para erros de emissão de NFS-e

**Rationale**:
- Permite tratamento específico por tipo de erro
- Facilita retry automatizado para erros recuperáveis
- Melhora logging e monitoring

**Alternativas consideradas**:
- Usar só RuntimeException: Rejeitado - perde contexto do erro
- Exceções específicas por caso: Rejeitado - overhead desnecessário

## Risks / Trade-offs

### Risco 1: Perda de senhas durante migration

**Risco**: Migration falhar e deixar banco em estado inconsistente

**Mitigação**:
- Backup da tabela `certificates` antes da migration
- Migration em batch (100 registros por vez)
- Testar exaustivamente em staging primeiro
- Script de rollback manual documentado

### Risco 2: APP_KEY corrompido = dados perdidos

**Risco**: Se APP_KEY for perdida/regenerada, senhas ficam irrecuperáveis

**Mitigação**:
- Documentar claramente: APP_KEY é credencial crítica
- Adicionar warning na migration
- Backup do .env antes da implementação

### Trade-off 1: Performance da criptografia

**Impacto**: +0.5ms por emissão de NFSe (decrypt da senha)

**Justificativa**: Aceitável dado o ganho de segurança (operações não são em alta frequência)

### Trade-off 2: Lock mantido por mais tempo

**Impacto**: Transaction agora inclui update do DPS, mantendo lock por ~1-2 segundos adicionais

**Justificativa**: Necessário para garantir unicidade; lock é por empresa, não global

## Migration Plan

### Fase 1: Preparação (Staging)
1. Implementar criptografia em `CertificateStorage`
2. Criar migration que criptografa senhas existentes
3. Testar emissão completa com senhas criptografadas
4. Testar rollback da migration

### Fase 2: Produção
1. **Backup**: Exportar tabela `certificates`
2. **Deploy**: Nova versão do código (compatível com ambos os formatos)
3. **Migration**: Executar migration de criptografia
4. **Validação**: Emitir 10 NFs-e de teste
5. **Cleanup**: Remover código de compatibilidade (próximo deploy)

### Rollback Strategy
- Se migration falhar: Restaurar backup da tabela
- Se código tiver bug: Revert para versão anterior (descriptografa ao ler)

## Open Questions

1. **Timeout de certificados expirados**: Devemos tentar auto-descriptografar ou falhar rápido?
   - **Decisão**: Falhar rápido com exceção específica (`CertificateExpiredException`)

2. **Logging de tentativas falhas de decrypt**: Devemos alertar sobre possíveis senhas corrompidas?
   - **Decisão**: Sim, log com nível WARNING e incluir ID do certificado

3. **Compressão do certificado PFX**: O PFX é base64 + podia ser comprimido?
   - **Decisão**: Fora do escopo - pode ser otimização futura separada
