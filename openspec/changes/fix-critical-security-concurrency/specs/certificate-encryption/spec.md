## ADDED Requirements

### Requirement: Certificate passwords SHALL be encrypted at rest
The system MUST encrypt all certificate passwords using AES-256-GCM before storing them in the database. Plain text passwords MUST NEVER be persisted.

#### Scenario: Storing new certificate
- **WHEN** a new certificate is uploaded via POST /api/certificates
- **THEN** the system encrypts the password using Laravel Crypt
- **AND** stores only the encrypted password in the certificates.pfx_password column
- **AND** returns 201 status

#### Scenario: Reading certificate for use
- **WHEN** a certificate is retrieved for NFSe emission
- **THEN** the system decrypts the password before using it
- **AND** the decrypted password is used only in memory
- **AND** never logged or exposed in API responses

### Requirement: Existing passwords MUST be migrated
The system SHALL provide a database migration to encrypt all existing plain text passwords without data loss.

#### Scenario: Migration execution
- **WHEN** the migration runs on a database with existing certificates
- **THEN** the system encrypts each password in batches of 100
- **AND** updates the certificates.pfx_password column
- **AND** logs progress for monitoring
- **AND** completes without downtime

#### Scenario: Migration rollback
- **WHEN** the migration is rolled back
- **THEN** the system decrypts all passwords back to plain text
- **AND** restores the original state
- **AND** no certificate data is lost

### Requirement: Decryption failures MUST be handled gracefully
The system SHALL handle decryption failures with specific exceptions and logging.

#### Scenario: Invalid encrypted data
- **WHEN** a stored password cannot be decrypted (corrupted data)
- **THEN** the system throws CertificateStorageException
- **AND** logs a WARNING with the certificate ID
- **AND** returns 500 error to the client

#### Scenario: APP_KEY mismatch
- **WHEN** the APP_KEY used for encryption differs from decryption
- **THEN** the system throws CertificateStorageException
- **AND** logs an ERROR explaining the APP_KEY mismatch
- **AND** returns 500 error to the client
