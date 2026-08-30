CREATE DATABASE IF NOT EXISTS medicare_connect
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE medicare_connect;

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
    appointment_id VARCHAR(20) NOT NULL,
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

-- appointment_id and doctor_id intentionally have no foreign keys until the
-- teammate-owned Appointment and Doctor/User schemas are finalised.
