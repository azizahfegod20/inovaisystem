## Why

Corrigir duas vulnerabilidades críticas no sistema de emissão de NFS-e: (1) senhas de certificados digitais armazenadas em texto plano no banco de dados, expondo dados sensíveis a risco de exposição em caso de breach; e (2) race condition na geração do número de DPS que pode causar duplicidade de notas fiscais em cenários de concorrência, resultando em rejeições e problemas legais.

## What Changes

- **Criptografia de senhas de certificados**: Implementar criptografia AES-256-GCM para `pfx_password` na tabela `certificates` usando Laravel Crypt
- **Fix race condition do número de DPS**: Mover o update de `dps_next_number` para dentro do escopo do lock FOR UPDATE
- **Exceções customizadas**: Criar hierarquia de exceções específicas para tratamento de erro adequado
- **Migration de dados**: Script para criptografar senhas existentes em produção

## Capabilities

### New Capabilities
- `certificate-encryption`: Criptografia segura de credenciais de certificados digitais
- `dps-number-consistency`: Garantia de unicidade e consistência na numeração de DPS
- `error-handling-hierarchy`: Exceções estruturadas para diferentes cenários de falha

### Modified Capabilities
- `nfse-emission`: Requisitos de consistência de numeração fortalecidos

## Impact

**Backend Laravel**:
- `App\Services\Certificate\CertificateStorage`: Modificar para criptografar/descriptografar `pfx_password`
- `App\Services\Certificate\CertificateParser`: Atualizar para receber senha já descriptografada
- `App\Services\Nfse\InvoiceEmitter`: Mover update de `dps_next_number` para dentro do lock
- `database/migrations`: Adicionar migration para criptografar senhas existentes
- Novas exceções: `CertificateException`, `DpsGenerationException`, `AdnException`

**Frontend**: Sem mudanças (API inalterada)

**Banco de dados**:
- `certificates.pfx_password`: Passará de texto plano para criptografado (migration)
- Schema mantém o mesmo (string)

**Riscos**:
- Migration em produção requer cuidado para não perder senhas
- Chave de criptografia (APP_KEY) deve ser backupada antes da migration
- Rollback da migration requer decrypt manual
