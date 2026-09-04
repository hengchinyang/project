# Module database isolation

Runtime database accounts are separated:

- `patient_record_app` has `SELECT`, `INSERT`, `UPDATE`, and `DELETE` only on `medicare_connect2.*`.
- `pharmacy_app` has data access plus `CREATE`, `ALTER`, and `INDEX` only on `medicare_pharmacy.*`. The extra schema permissions are required because the unchanged Pharmacy bootstrap executes `CREATE TABLE IF NOT EXISTS` on every request.
- Administrative MySQL `root` remains available only for setup and maintenance; neither application uses it at runtime.

Patient Record credentials are stored outside htdocs in `C:/xampp/medicare-connect-secrets/patient-record-db.json`. Pharmacy loads its restricted account from its existing `.env`. API calls use separate service keys and XML payloads; Patient Record does not query Pharmacy tables.

Appointment database isolation cannot be configured from the supplied download because its configuration/bootstrap/schema files are missing. When the complete module is placed in htdocs, give it an `appointment_app` MySQL account scoped only to its database and configure that module to use the account.
