USE medicare_connect2;

CREATE TABLE IF NOT EXISTS patient_record_prescriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_record_id VARCHAR(10) NOT NULL,
    prescription_reference VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_patient_record_prescription (patient_record_id, prescription_reference),
    INDEX idx_prescription_reference (prescription_reference),
    CONSTRAINT fk_record_prescription_record
        FOREIGN KEY (patient_record_id) REFERENCES patient_records (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
