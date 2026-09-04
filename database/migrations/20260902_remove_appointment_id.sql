-- Run once against an existing medicare_connect2 database.
USE medicare_connect2;

ALTER TABLE patient_records
    DROP COLUMN appointment_id;
