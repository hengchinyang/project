USE medicare_connect2;

INSERT INTO patients (id, name) VALUES
    ('PA001', 'John Tan'),
    ('PA002', 'Aisyah Rahman'),
    ('PA003', 'Lim Wei Jian'),
    ('PA004', 'Siti Nur Iman'),
    ('PA005', 'Arjun Kumar')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO conditions (id, name, description) VALUES
    ('C001', 'Asthma', 'A respiratory condition affecting breathing.'),
    ('C002', 'Headache', 'Pain or discomfort affecting the head.'),
    ('C003', 'Fever', 'Elevated body temperature.'),
    ('C004', 'Hypertension', 'Persistently elevated blood pressure.'),
    ('C005', 'Type 2 Diabetes', 'A condition affecting regulation of blood glucose.'),
    ('C006', 'Allergic Rhinitis', 'Inflammation caused by an allergic reaction.'),
    ('C007', 'Gastritis', 'Inflammation of the stomach lining.'),
    ('C008', 'Migraine', 'Recurring headache that may include nausea or light sensitivity.'),
    ('C009', 'Bronchitis', 'Inflammation of the bronchial tubes.'),
    ('C010', 'Sprained Ankle', 'Soft-tissue injury involving ankle ligaments.')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

-- Sensitive Patient Record remarks are not inserted as plaintext SQL.
-- After configuring the external encryption key, run:
-- C:\xampp\php\php.exe scripts\seed_patient_records.php
