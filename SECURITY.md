# Patient Record Secure Coding Practices

## 1. SQL Injection - strongly typed parameterized queries

### Threat

An attacker may submit SQL control characters through record IDs or form fields in an attempt to change the meaning of a database query, read another patient's records, or modify database data.

### Controls implemented

- PHP files use `declare(strict_types=1)` and repository/controller method parameters declare `string` types.
- Record, appointment, doctor, and condition IDs are checked against allow-list regular expressions before database use.
- Severity is checked against the allow-list `Mild`, `Moderate`, and `Severe`.
- Eloquent ORM sends values such as `patient_id`, `record_id`, and `condition_id` to PDO as bound parameters. User values are not concatenated into SQL.
- The Patient Record repository uses `where()`, `find()`, and `whereKey()` so the generated SQL contains placeholders such as `where id = ?` with separate string bindings.

Relevant files:

- `Controller/PatientRecordController.php`
- `Model/PatientRecord.php`
- `Model/Condition.php`

## 2. Data breach through unencrypted stored medical information

### Threat

Patient Record remarks may contain sensitive clinical information. If an attacker obtains a database copy or SQL backup, plaintext remarks can be read without using the application.

### Controls implemented

- `Shared/SensitiveDataCipher.php` encrypts remarks with AES-256-GCM before Eloquent writes them to MySQL.
- AES-GCM provides confidentiality and authentication. A random 96-bit IV is generated for every encryption, and an authentication tag detects modified ciphertext.
- `PatientRecordEntity` uses an Eloquent attribute accessor/mutator to encrypt on write and decrypt on authorised application reads.
- Stored values use the versioned prefix `enc:v1:` to support controlled future key/cipher migrations.
- The 256-bit key is stored outside the web root at `C:\xampp\medicare-connect-secrets\patient-record.key`, not in source code, Git, or MySQL.
- The pre-migration backup is stored outside the web root at `C:\xampp\medicare-connect-secrets\backups\`.
- `scripts/encrypt_existing_patient_records.php` performs an idempotent transaction-based migration of legacy plaintext remarks.

Relevant files:

- `Shared/SensitiveDataCipher.php`
- `Model/PatientRecordEntity.php`
- `scripts/encrypt_existing_patient_records.php`

## Deployment requirement

The encryption key must be backed up securely and transferred separately from the source code. Losing the key makes encrypted remarks unrecoverable. Never commit or send the key with the application repository.

On another computer, either create the same external key path or set `MEDICARE_ENCRYPTION_KEY_FILE` to an absolute path containing a base64-encoded 32-byte key. Production access to the key should be restricted to the web-server account and authorised administrators.
