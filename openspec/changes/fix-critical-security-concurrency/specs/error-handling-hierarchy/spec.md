## ADDED Requirements

### Requirement: System SHALL use structured exception hierarchy
The system MUST define specific exception types for different error categories, enabling granular error handling and retry logic.

#### Scenario: Certificate-specific errors
- **WHEN** a certificate operation fails (parse, storage, retrieval)
- **THEN** the system throws CertificateException or its subclasses
- **AND** the exception includes relevant context (certificate ID, operation)
- **AND** the error is logged with appropriate severity

#### Scenario: DPS generation errors
- **WHEN** DPS number generation fails (timeout, lock error)
- **THEN** the system throws DpsGenerationException
- **AND** the exception includes company ID and retry eligibility
- **AND** the error is logged with WARNING severity

#### Scenario: NFSe emission errors
- **WHEN** NFSe emission fails at any stage
- **THEN** the system throws NfseEmissionException
- **AND** the exception includes the stage of failure and recovery options
- **AND** the error is logged with ERROR severity

### Requirement: Exceptions MUST provide actionable error messages
All custom exceptions SHALL include messages that indicate the problem cause and suggested resolution.

#### Scenario: Retryable errors
- **WHEN** an exception is retryable (timeout, temporary failure)
- **THEN** the error message indicates retry eligibility
- **AND** includes suggested retry delay
- **AND** the API returns 503 status code

#### Scenario: Non-retryable errors
- **WHEN** an exception is non-retryable (validation, configuration)
- **THEN** the error message explains the validation failure
- **AND** includes corrective action required
- **AND** the API returns 400 status code

### Requirement: Exception details MUST be logged appropriately
The system SHALL log exception details at appropriate severity levels without exposing sensitive information.

#### Scenario: Logging certificate errors
- **WHEN** a CertificateException is thrown
- **THEN** the system logs the error with certificate ID (not password)
- **AND** includes stack trace for debugging
- **AND** does not expose sensitive data in logs

#### Scenario: Logging ADN communication errors
- **WHEN** an ADN API call fails
- **THEN** the system logs the error with HTTP status and response code
- **AND** does not log full request/response bodies (may contain sensitive XML)
- **AND** includes correlation ID for tracing
