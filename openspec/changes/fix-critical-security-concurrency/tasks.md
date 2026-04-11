## 1. Foundation - Exception Hierarchy

- [x] 1.1 Create `app/Exceptions/CertificateException.php` extending RuntimeException
- [x] 1.2 Create `app/Exceptions/CertificateStorageException.php` extending CertificateException
- [x] 1.3 Create `app/Exceptions/DpsGenerationException.php` extending RuntimeException
- [x] 1.4 Create `app/Exceptions/NfseEmissionException.php` extending RuntimeException
- [x] 1.5 Update all `throw new RuntimeException` in certificate services to use new exceptions

## 2. Certificate Encryption Implementation

- [x] 2.1 Update `app/Services/Certificate/CertificateStorage.php` to encrypt password on store
  - Replace direct assignment with `Crypt::encryptString($password)`
  - Add try-catch for encryption failures
- [x] 2.2 Update `app/Services/Certificate/CertificateStorage.php` to decrypt password on extract
  - Replace direct password access with `Crypt::decryptString()`
  - Handle `DecryptException` with `CertificateStorageException`
- [x] 2.3 Update `app/Services/Certificate/CertificateParser.php` to handle pre-decrypted password
  - Remove encryption logic (caller handles it)
  - Add validation for empty password after decryption

## 3. DPS Race Condition Fix

- [x] 3.1 Refactor `app/Services/Nfse/InvoiceEmitter.php::emit()` method
  - Move `$company->update(['dps_next_number' => $dpsNumber + 1])` inside DB::transaction
  - Ensure update happens immediately after `$dpsNumber = $this->getNextDpsNumber($company)`
  - Verify lock is maintained throughout transaction
- [x] 3.2 Add logging for DPS number allocation
  - Log INFO when DPS number is allocated
  - Log WARNING if gap detection triggers
- [x] 3.3 Add timeout handling for lock acquisition
  - Wrap lock acquisition in try-catch
  - Throw `DpsGenerationException` on timeout

## 4. Database Migration

- [x] 4.1 Create migration `database/migrations/xxxx_encrypt_certificate_passwords.php`
  - Add `batchEncrypt()` method to process 100 records at a time
  - Add progress logging every 100 records
  - Include `down()` method for rollback (decrypt all passwords)
- [x] 4.2 Add safety checks to migration
  - Verify APP_KEY is set before proceeding
  - Backup certificates table to `certificates_backup` before encryption
  - Validate encryption succeeded before deleting backup
- [ ] 4.3 Test migration on staging database
  - Run with existing test certificates
  - Verify encryption/decryption cycle works
  - Test rollback procedure

## 5. Testing

- [x] 5.1 Add unit tests for certificate encryption
  - Test `CertificateStorage::store()` encrypts password
  - Test `CertificateStorage::extractPemFiles()` decrypts password
  - Test invalid encrypted data throws `CertificateStorageException`
- [x] 5.2 Add unit tests for DPS consistency
  - Test concurrent requests get different numbers
  - Test transaction rollback on failure
  - Test lock timeout scenario
- [x] 5.3 Add integration test for full emission flow
  - Create test company with certificate
  - Emit 10 concurrent NFSe
  - Verify all DPS numbers are unique and sequential

## 6. Documentation & Cleanup

- [x] 6.1 Update backend README.md
  - Document APP_KEY as critical credential
  - Add migration procedure for production deployment
  - Document rollback procedure
- [x] 6.2 Add comments to critical code sections
  - Comment why password encryption happens in Storage not Parser
  - Comment lock scope and duration in InvoiceEmitter
- [x] 6.3 Remove debug console.logs from frontend (identified during exploration)
  - Remove `console.log` from `app/pages/settings/notifications.vue:42`
  - Remove `console.log` from `app/components/settings/MembersList.vue:11,15`

## 7. Production Deployment

- [ ] 7.1 Pre-deployment checklist
  - Backup production database (certificates table)
  - Backup .env file (APP_KEY)
  - Verify staging migration completed successfully
- [ ] 7.2 Deploy code changes
  - Deploy new version with encryption support
  - Verify system works with both encrypted and plain text passwords
- [ ] 7.3 Execute migration
  - Run migration during low-traffic period
  - Monitor progress logs
  - Verify encryption completed successfully
- [ ] 7.4 Post-deployment validation
  - Emit 5 test NFSe in production
  - Check logs for any decryption errors
  - Monitor for 24 hours for issues
