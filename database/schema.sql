CREATE DATABASE IF NOT EXISTS medicare_connect2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE medicare_connect2;

CREATE TABLE IF NOT EXISTS patients (
    id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS conditions (
    id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS patient_records (
    id VARCHAR(10) NOT NULL,
    patient_id VARCHAR(10) NOT NULL,
    condition_id VARCHAR(10) NOT NULL,
    doctor_id VARCHAR(20) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    remark TEXT NOT NULL,
    record_date DATE NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_patient_records_patient (patient_id),
    INDEX idx_patient_records_condition (condition_id),
    CONSTRAINT fk_patient_record_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_record_condition
        FOREIGN KEY (condition_id) REFERENCES conditions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pharmacy owns prescription details. This module stores only the external
-- prescription reference linked to a Patient Record.
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

CREATE TABLE IF NOT EXISTS patient_record_access_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_record_id VARCHAR(10) NOT NULL,
    accessor_id VARCHAR(20) NOT NULL,
    accessed_by VARCHAR(100) NOT NULL,
    accessor_role VARCHAR(20) NOT NULL,
    access_type VARCHAR(20) NOT NULL,
    accessed_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_access_logs_record_time (patient_record_id, accessed_at),
    CONSTRAINT fk_access_log_patient_record
        FOREIGN KEY (patient_record_id) REFERENCES patient_records (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- doctor_id intentionally has no foreign key until the teammate-owned
-- Doctor/User schema is finalised.
