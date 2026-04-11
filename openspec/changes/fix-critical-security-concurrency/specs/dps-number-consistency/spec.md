## ADDED Requirements

### Requirement: DPS number generation MUST be atomic
The system SHALL guarantee that each DPS number is unique across all concurrent emission requests for the same company, using database-level locking.

#### Scenario: Concurrent emissions
- **WHEN** two simultaneous requests attempt to emit NFSe for the same company
- **THEN** the system assigns different DPS numbers to each request
- **AND** uses FOR UPDATE lock on the companies table
- **AND** increments dps_next_number within the same transaction
- **AND** both requests complete successfully with unique numbers

#### Scenario: Lock timeout
- **WHEN** a request waits longer than 30 seconds for the lock
- **THEN** the system throws DpsGenerationException
- **AND** logs a WARNING about lock timeout
- **AND** returns 503 Service Unavailable to the client

### Requirement: DPS number increment MUST happen inside transaction
The system SHALL update the dps_next_number counter within the same database transaction that creates the invoice, ensuring rollback capability.

#### Scenario: Successful emission
- **WHEN** an NFSe emission succeeds
- **THEN** the system increments dps_next_number within the transaction
- **AND** commits the transaction
- **AND** the counter reflects the new value

#### Scenario: Emission failure
- **WHEN** an NFSe emission fails after getting the DPS number
- **THEN** the system rolls back the entire transaction
- **AND** the dps_next_number counter remains unchanged
- **AND** the same DPS number can be reused

### Requirement: Gap in DPS sequence MUST be detectable
The system SHALL log when a gap is detected in the DPS sequence for audit purposes.

#### Scenario: Gap detection
- **WHEN** a manual adjustment or system error creates a gap in dps_next_number
- **THEN** the system logs an INFO message with company ID and gap range
- **AND** continues normal operation
- **AND** does not prevent subsequent emissions
