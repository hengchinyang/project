<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/PatientRecord.php';
require_once __DIR__ . '/../Model/Condition.php';
require_once __DIR__ . '/../Model/Patient.php';
require_once __DIR__ . '/../Model/PatientRecordAccessLog.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/BasicPatientRecord.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/ConfidentialityDecorator.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/AuditTrailDecorator.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/EmergencyAlertDecorator.php';

class PatientRecordController
{
    private PatientRecord $patientRecordModel;
    private Condition $conditionModel;
    private Patient $patientModel;
    private PatientRecordAccessLog $accessLogModel;

    public function __construct()
    {
        $this->patientRecordModel = new PatientRecord();
        $this->conditionModel = new Condition();
        $this->patientModel = new Patient();
        $this->accessLogModel = new PatientRecordAccessLog();
    }

    public function index(): void
    {
        $patientId = $this->currentPatientId();
        $patient = $this->patientModel->findById($patientId);
        if ($patient === null) {
            $this->notFound();
            return;
        }
        $records = $this->patientRecordModel->getAllByPatient($patientId);
        $canManageRecords = $this->canManageRecords();
        require __DIR__ . '/../View/patient_record/index.php';
    }

    public function show(string $id): void
    {
        if (!$this->isValidRecordId($id)) {
            $this->notFound();
            return;
        }

        $record = $this->patientRecordModel->findById($id);
        if ($record === null) {
            $this->notFound();
            return;
        }
        if (!$this->canManageRecords() && $record['patient_id'] !== $this->currentPatientId()) {
            $this->forbidden();
            return;
        }
        $patientRecord = new BasicPatientRecord(
            $record['id'],
            $record['patient_name'],
            $record['condition_name'],
            $record['remark']
        );
        $securityLabel = 'Confidential medical information';
        $accessedBy = $this->currentUserName();
        $accessTime = $this->currentTime();
        $this->accessLogModel->record(
            $record['id'],
            $this->currentUserId(),
            $accessedBy,
            $this->currentRole(),
            'VIEW',
            $accessTime
        );
        $emergencyMessage = null;
        $patientRecord = new ConfidentialityDecorator($patientRecord, $securityLabel);
        $patientRecord = new AuditTrailDecorator(
            $patientRecord,
            $accessedBy,
            $accessTime
        );
        if ($record['severity'] === 'Severe') {
            $emergencyMessage = 'Severe condition requires immediate attention';
            $patientRecord = new EmergencyAlertDecorator(
                $patientRecord,
                $emergencyMessage
            );
        }
        // Build the final decorated representation. The View presents each
        // decorator effect naturally instead of showing duplicate debug text.
        $decoratedDetails = $patientRecord->getDetails();
        $canManageRecords = $this->canManageRecords();
        require __DIR__ . '/../View/patient_record/show.php';
    }

    public function create(): void
    {
        if (!$this->canManageRecords()) {
            $this->forbidden();
            return;
        }
        $patient = $this->patientModel->findById($this->currentPatientId());
        if ($patient === null) {
            $this->notFound();
            return;
        }
        $conditions = $this->conditionModel->getAll();
        require __DIR__ . '/../View/patient_record/create.php';
    }

    public function store(): void
    {
        if (!$this->canManageRecords()) {
            $this->forbidden();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }
        $data = $this->validatedInput($this->currentPatientId());
        if ($data === null) {
            return;
        }
        $recordId = $this->patientRecordModel->create($data);
        $this->writeAuditLog($recordId, 'CREATE');
        header('Location: index.php?action=show&id=' . urlencode($recordId));
        exit;
    }

    public function edit(string $id): void
    {
        if (!$this->canManageRecords()) {
            $this->forbidden();
            return;
        }
        if (!$this->isValidRecordId($id)) {
            $this->notFound();
            return;
        }

        $record = $this->patientRecordModel->findById($id);
        if ($record === null) {
            $this->notFound();
            return;
        }
        $conditions = $this->conditionModel->getAll();
        require __DIR__ . '/../View/patient_record/edit.php';
    }

    public function update(string $id): void
    {
        if (!$this->canManageRecords()) {
            $this->forbidden();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }
        if (!$this->isValidRecordId($id)) {
            $this->notFound();
            return;
        }
        $existingRecord = $this->patientRecordModel->findById($id);
        if ($existingRecord === null) {
            $this->notFound();
            return;
        }
        // An edit must never transfer a medical record to another patient.
        $data = $this->validatedInput($existingRecord['patient_id']);
        if ($data === null) {
            return;
        }
        $this->patientRecordModel->update($id, $data);
        $this->writeAuditLog($id, 'UPDATE');
        header('Location: index.php?action=show&id=' . urlencode($id));
        exit;
    }

    private function validatedInput(string $patientId): ?array
    {
        $data = [
            'patient_id' => $patientId,
            'appointment_id' => $this->postString('appointment_id'),
            'doctor_id' => $this->postString('doctor_id'),
            'condition_id' => $this->postString('condition_id'),
            'severity' => $this->postString('severity'),
            'remark' => $this->postString('remark'),
            'record_date' => $this->postString('record_date'),
        ];
        if (in_array('', $data, true)) {
            http_response_code(422);
            echo 'All fields are required.';
            return null;
        }
        if (!in_array($data['severity'], ['Mild', 'Moderate', 'Severe'], true)) {
            http_response_code(422);
            echo 'Invalid severity.';
            return null;
        }
        if (!preg_match('/^APT[0-9]{4,17}$/', $data['appointment_id'])) {
            http_response_code(422);
            echo 'Invalid appointment ID.';
            return null;
        }
        if (!preg_match('/^DC[0-9]{3,18}$/', $data['doctor_id'])) {
            http_response_code(422);
            echo 'Invalid doctor ID.';
            return null;
        }
        if (!preg_match('/^C[A-Za-z0-9_-]{1,9}$/', $data['condition_id'])) {
            http_response_code(422);
            echo 'Invalid condition.';
            return null;
        }
        if (mb_strlen($data['remark']) > 5000) {
            http_response_code(422);
            echo 'Remark must not exceed 5000 characters.';
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['record_date']);
        if ($date === false || $date->format('Y-m-d') !== $data['record_date']) {
            http_response_code(422);
            echo 'Invalid record date.';
            return null;
        }
        if (!$this->conditionModel->exists($data['condition_id'])) {
            http_response_code(422);
            echo 'Invalid condition.';
            return null;
        }

        return $data;
    }

    private function postString(string $field): string
    {
        $value = $_POST[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function currentPatientId(): string
    {
        $patientId = $_SESSION['patient_id'] ?? $_SESSION['user_id'] ?? 'PA001';

        return is_string($patientId) && preg_match('/^PA[0-9]{3,8}$/', $patientId)
            ? $patientId
            : 'PA001';
    }

    private function canManageRecords(): bool
    {
        return in_array($this->currentRole(), ['doctor', 'admin'], true);
    }

    private function currentRole(): string
    {
        $role = $_SESSION['role'] ?? 'patient';
        $normalisedRole = is_string($role) ? strtolower($role) : 'patient';

        return in_array($normalisedRole, ['patient', 'doctor', 'admin'], true)
            ? $normalisedRole
            : 'patient';
    }

    private function currentUserId(): string
    {
        $userId = $_SESSION['user_id'] ?? $this->currentPatientId();

        return is_string($userId) && $userId !== ''
            ? $userId
            : $this->currentPatientId();
    }

    private function currentUserName(): string
    {
        $userName = $_SESSION['username'] ?? ('Patient ' . $this->currentPatientId());

        return is_string($userName) && $userName !== ''
            ? $userName
            : 'Unknown user';
    }

    private function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    }

    private function writeAuditLog(string $recordId, string $accessType): void
    {
        $this->accessLogModel->record(
            $recordId,
            $this->currentUserId(),
            $this->currentUserName(),
            $this->currentRole(),
            $accessType,
            $this->currentTime()
        );
    }

    private function isValidRecordId(string $recordId): bool
    {
        return preg_match('/^PR[0-9]{3,8}$/', $recordId) === 1;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo 'Patient record not found.';
    }

    private function methodNotAllowed(): void
    {
        http_response_code(405);
        echo 'Method not allowed.';
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo 'You are not authorised to modify this patient record.';
    }
}
