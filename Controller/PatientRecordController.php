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
require_once __DIR__ . '/../Service/PharmacyServiceClient.php';
require_once __DIR__ . '/../Service/PharmacyServiceConfig.php';
require_once __DIR__ . '/../Service/PatientRecordCreationService.php';
require_once __DIR__ . '/../Service/AppointmentServiceClient.php';
require_once __DIR__ . '/../Service/AppointmentServiceConfig.php';

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
        $page = $this->requestedPage();
        $perPage = 10;
        if ($this->currentRole() === 'doctor') {
            $patientId = $this->requestedPatientId();
            if ($patientId === '') {
                $this->doctorPatients();
                return;
            }
            if (!$this->doctorCanManagePatient($patientId)) {
                $this->forbidden();
                return;
            }
            $patient = $this->patientModel->findById($patientId);
            $recordPage = $this->patientRecordModel->paginateByPatient($patientId, $page, $perPage, $this->currentUserId());
            $records = $recordPage['records'];
            $canManageRecords = true;
            $isDoctorView = true;
            require __DIR__ . '/../View/patient_record/index.php';
            return;
        }
        $patientId = $this->currentPatientId();
        $patient = $this->patientModel->findById($patientId);
        if ($patient === null) {
            $this->notFound();
            return;
        }
        $recordPage = $this->patientRecordModel->paginateByPatient($patientId, $page, $perPage);
        $records = $recordPage['records'];
        $canManageRecords = $this->canManageRecords();
        $isDoctorView = false;
        require __DIR__ . '/../View/patient_record/index.php';
    }

    public function doctorPatients(): void
    {
        if ($this->currentRole() !== 'doctor') {
            $this->forbidden();
            return;
        }
        $patients = $this->patientRecordModel->getPatientsForDoctor($this->currentUserId());
        try {
            foreach ($this->appointmentClient()->getDoctorPatientAssignments($this->currentUserId()) as $assignment) {
                $this->patientModel->ensureExists($assignment['patient_id'], $assignment['patient_name']);
                $patients[] = ['id' => $assignment['patient_id'], 'name' => $assignment['patient_name']];
            }
            $unique = [];
            foreach ($patients as $patient) $unique[$patient['id']] = $patient;
            $patients = array_values($unique);
        } catch (Throwable $exception) {
            error_log('Appointment assignment REST lookup unavailable: ' . $exception->getMessage());
        }
        require __DIR__ . '/../View/patient_record/doctor_patients.php';
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
        if (!$this->canAccessRecord($record)) {
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
        $pharmacyPrescriptions = [];
        $pharmacyError = null;
        foreach ($record['prescription_references'] as $reference) {
            try {
                $pharmacyPrescriptions[] = $this->pharmacyClient()->getPrescription($reference);
            } catch (Throwable) {
                $pharmacyError = 'The linked prescription could not be retrieved from Pharmacy right now.';
            }
        }
        $canManageRecords = $this->canManageRecords();
        require __DIR__ . '/../View/patient_record/show.php';
    }

    public function create(): void
    {
        if (!$this->canManageRecords()) {
            $this->forbidden();
            return;
        }
        $patientId = $this->currentRole() === 'doctor' ? $this->requestedPatientId() : $this->currentPatientId();
        if ($patientId === '' || ($this->currentRole() === 'doctor' && !$this->doctorCanManagePatient($patientId))) {
            $this->forbidden();
            return;
        }
        $patient = $this->patientModel->findById($patientId);
        if ($patient === null) {
            $this->notFound();
            return;
        }
        $conditions = $this->conditionModel->getAll();
        try {
            $medicines = $this->pharmacyClient()->getMedicineCatalog();
            $pharmacyCatalogError = null;
        } catch (Throwable) {
            $medicines = [];
            $pharmacyCatalogError = 'The Pharmacy medicine catalogue is unavailable. Please try again.';
        }
        $isDoctorCreate = $this->currentRole() === 'doctor';
        $isDoctorUser = $isDoctorCreate;
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
        $patientId = $this->currentRole() === 'doctor' ? $this->requestedPatientId() : $this->currentPatientId();
        if ($patientId === '' || ($this->currentRole() === 'doctor' && !$this->doctorCanManagePatient($patientId))) {
            $this->forbidden();
            return;
        }
        $data = $this->validatedInput($patientId);
        if ($data === null) {
            return;
        }
        $medicines = $this->validatedMedicineItems();
        if ($medicines === null) return;
        $patient = $this->patientModel->findById($patientId);
        if ($patient === null) { $this->notFound(); return; }
        $workflow = new PatientRecordCreationService(
            $this->pharmacyClient(),
            fn (array $recordData, string $reference): string => $this->patientRecordModel->create($recordData, $reference),
            static function (string $reference, Throwable $exception): void {
                error_log('RECONCILIATION_REQUIRED prescription=' . $reference . ' local_error=' . $exception->getMessage());
            }
        );
        try {
            $recordId = $workflow->create(
                $data,
                $patient,
                $this->currentRole() === 'doctor' ? $this->currentUserName() : 'Doctor ' . $data['doctor_id'],
                $medicines
            );
        } catch (MedicineUnavailableException $exception) {
            $this->renderError(422, 'Medicine unavailable', $exception->getMessage());
            return;
        } catch (IntegrationConsistencyException $exception) {
            $this->renderError(503, 'Patient Record save failed', 'Pharmacy accepted the prescription, but the local record failed. Reference ' . $exception->prescriptionReference . ' was logged for reconciliation.');
            return;
        } catch (Throwable) {
            $this->renderError(502, 'Pharmacy unavailable', 'The Patient Record could not check stock or create the approved Pharmacy dispensing request.');
            return;
        }
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
        if (!$this->canAccessRecord($record)) { $this->forbidden(); return; }
        $conditions = $this->conditionModel->getAll();
        $isDoctorUser = $this->currentRole() === 'doctor';
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
        if (!$this->canAccessRecord($existingRecord)) { $this->forbidden(); return; }
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
            'doctor_id' => $this->currentRole() === 'doctor' ? $this->currentUserId() : $this->postString('doctor_id'),
            'condition_id' => $this->postString('condition_id'),
            'severity' => $this->postString('severity'),
            'remark' => $this->postString('remark'),
            'record_date' => $this->postString('record_date'),
        ];
        if (in_array('', $data, true)) {
            $this->renderError(422, 'Invalid record', 'All fields are required.');
            return null;
        }
        if (!in_array($data['severity'], ['Mild', 'Moderate', 'Severe'], true)) {
            $this->renderError(422, 'Invalid record', 'Invalid severity.');
            return null;
        }
        if (!preg_match('/^DC[0-9]{3,18}$/', $data['doctor_id'])) {
            $this->renderError(422, 'Invalid record', 'Invalid doctor ID.');
            return null;
        }
        if (!preg_match('/^C[A-Za-z0-9_-]{1,9}$/', $data['condition_id'])) {
            $this->renderError(422, 'Invalid record', 'Invalid condition.');
            return null;
        }
        if (mb_strlen($data['remark']) > 5000) {
            $this->renderError(422, 'Invalid record', 'Remark must not exceed 5000 characters.');
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['record_date']);
        if ($date === false || $date->format('Y-m-d') !== $data['record_date']) {
            $this->renderError(422, 'Invalid record', 'Invalid record date.');
            return null;
        }
        if (!$this->conditionModel->exists($data['condition_id'])) {
            $this->renderError(422, 'Invalid record', 'Invalid condition.');
            return null;
        }

        return $data;
    }

    private function postString(string $field): string
    {
        $value = $_POST[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** @return list<array{sku:string,quantity:int,instructions:string}>|null */
    private function validatedMedicineItems(): ?array
    {
        $skus = $_POST['medicine_sku'] ?? [];
        $quantities = $_POST['medicine_quantity'] ?? [];
        $instructions = $_POST['medicine_instructions'] ?? [];
        if (!is_array($skus) || !is_array($quantities) || !is_array($instructions) || $skus === []) {
            $this->renderError(422, 'Invalid prescription', 'Select at least one medicine.');
            return null;
        }
        $items = [];
        foreach ($skus as $index => $rawSku) {
            $sku = is_string($rawSku) ? strtoupper(trim($rawSku)) : '';
            $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
            $instruction = is_string($instructions[$index] ?? null) ? trim($instructions[$index]) : '';
            if (preg_match('/^[A-Z0-9][A-Z0-9_-]{2,49}$/', $sku) !== 1 || $quantity === false || mb_strlen($instruction) > 255) {
                $this->renderError(422, 'Invalid prescription', 'Each medicine must have a valid SKU, quantity from 1 to 1000, and instructions up to 255 characters.'); return null;
            }
            if (isset($items[$sku])) { $this->renderError(422, 'Invalid prescription', 'The same medicine cannot be selected twice.'); return null; }
            $items[$sku] = ['sku' => $sku, 'quantity' => $quantity, 'instructions' => $instruction ?: 'Follow doctor instructions.'];
        }
        return array_values($items);
    }

    /** @param list<array{sku:string,quantity:int,instructions:string}> $items */
    private function createApprovedPharmacyRequest(array $items, array $patient, string $doctorId, string $doctorName): ?string
    {
        try {
            foreach ($items as $item) {
                $availability = $this->pharmacyClient()->getMedicineAvailability(
                    (string) $item['sku'],
                    (int) $item['quantity']
                );
                if (($availability['available'] ?? 'false') !== 'true') {
                    $this->renderError(422, 'Medicine unavailable', 'A prescribed medicine is currently unavailable: ' . (string) $item['sku']);
                    return null;
                }
            }
            $response = $this->pharmacyClient()->createApprovedDispensingRequest(
                (string) $patient['id'],
                (string) $patient['name'],
                $doctorId,
                $doctorName,
                $items
            );
            return (string) $response['prescriptionReference'];
        } catch (Throwable) {
            $this->renderError(502, 'Pharmacy unavailable', 'The Patient Record could not check stock or create the approved Pharmacy dispensing request.');
            return null;
        }
    }

    private function pharmacyClient(): PharmacyServiceClient
    {
        return new PharmacyServiceClient(
            PharmacyServiceConfig::url(),
            PharmacyServiceConfig::apiKey(),
            5
        );
    }

    private function appointmentClient(): AppointmentServiceClient
    {
        return new AppointmentServiceClient(AppointmentServiceConfig::url(), AppointmentServiceConfig::apiKey(), 5);
    }

    private function doctorCanManagePatient(string $patientId): bool
    {
        if ($this->patientRecordModel->doctorIsResponsibleForPatient($this->currentUserId(), $patientId)) return true;
        try {
            foreach ($this->appointmentClient()->getDoctorPatientAssignments($this->currentUserId()) as $assignment) {
                if ($assignment['patient_id'] === $patientId) {
                    $this->patientModel->ensureExists($assignment['patient_id'], $assignment['patient_name']);
                    return true;
                }
            }
        } catch (Throwable $exception) {
            error_log('Appointment assignment authorisation unavailable: ' . $exception->getMessage());
        }
        return false;
    }

    private function currentPatientId(): string
    {
        $patientId = $_SESSION['patient_id'] ?? $_SESSION['user_id'] ?? 'PA001';

        return is_string($patientId) && preg_match('/^PA[0-9]{3,8}$/', $patientId)
            ? $patientId
            : 'PA001';
    }

    private function requestedPatientId(): string
    {
        $patientId = $_GET['patient_id'] ?? '';
        return is_string($patientId) && preg_match('/^PA[0-9]{3,8}$/', $patientId) ? $patientId : '';
    }

    private function requestedPage(): int
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($page) ? $page : 1;
    }

    private function canAccessRecord(array $record): bool
    {
        if ($this->currentRole() === 'admin') return true;
        if ($this->currentRole() === 'doctor') return $record['doctor_id'] === $this->currentUserId();
        return $record['patient_id'] === $this->currentPatientId();
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
        $this->renderError(404, 'Patient record not found', 'The requested Patient Record does not exist.');
    }

    private function methodNotAllowed(): void
    {
        $this->renderError(405, 'Method not allowed', 'This action does not support the requested HTTP method.');
    }

    private function forbidden(): void
    {
        $this->renderError(403, 'Access denied', 'You are not authorised to access or modify this Patient Record.');
    }

    private function renderError(int $status, string $title, string $message): void
    {
        ErrorRenderer::render($status, $title, $message);
    }
}
