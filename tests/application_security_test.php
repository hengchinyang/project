<?php

declare(strict_types=1);

require_once __DIR__ . '/../Shared/SessionSecurity.php';
require_once __DIR__ . '/../Shared/Csrf.php';
require_once __DIR__ . '/../Shared/SensitiveDataCipher.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/BasicPatientRecord.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/ConfidentialityDecorator.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/AuditTrailDecorator.php';
require_once __DIR__ . '/../Pattern/PatientRecordSummary/EmergencyAlertDecorator.php';

session_save_path(sys_get_temp_dir());

function assertApplication(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo '[PASS] ' . $message . PHP_EOL;
}

SessionSecurity::start();
$_SESSION = [];
assertApplication(!SessionSecurity::isAuthenticated(), 'missing identity is not authenticated');

$_SESSION = ['loggedIn' => true, 'userId' => 'PA001', 'role' => 'patient', 'username' => 'Patient Test'];
$normalise = new ReflectionMethod(SessionSecurity::class, 'normaliseAuthenticationContract');
$normalise->invoke(null);
assertApplication(($_SESSION['user_id'] ?? '') === 'PA001' && ($_SESSION['patient_id'] ?? '') === 'PA001', 'Appointment authentication session is adapted to the agreed contract');

$_SESSION['role'] = 'doctor';
$_SESSION['user_id'] = 'DC001';
$_SESSION['username'] = 'Doctor Test';
assertApplication(SessionSecurity::isAuthenticated(), 'valid doctor session is authenticated');

$_SESSION = ['role' => 'patient', 'username' => 'John Tan', 'loggedIn' => true, 'userId' => 'PA001', 'user_id' => 'PA001', 'patient_id' => 'PA001'];
$normalise = new ReflectionMethod(SessionSecurity::class, 'normaliseAuthenticationContract');
$_SESSION['userId'] = 'PA002';
$_SESSION['username'] = 'Aisyah Rahman';
$normalise->invoke(null);
assertApplication($_SESSION['user_id'] === 'PA002' && $_SESSION['patient_id'] === 'PA002', 'switching User Management accounts replaces the previous patient identity');

$_SESSION['userId'] = 'DC001';
$_SESSION['role'] = 'doctor';
$normalise->invoke(null);
assertApplication($_SESSION['user_id'] === 'DC001' && !isset($_SESSION['patient_id']), 'switching to a staff account clears the previous patient identity');

$token = Csrf::token();
assertApplication(Csrf::verify($token), 'valid CSRF token is accepted');
assertApplication(!Csrf::verify('invalid-token'), 'invalid CSRF token is rejected');

$cipher = SensitiveDataCipher::instance();
$encrypted = $cipher->encrypt('Sensitive clinical remark');
assertApplication(SensitiveDataCipher::isEncrypted($encrypted), 'clinical remark is encrypted');
assertApplication($cipher->decrypt($encrypted) === 'Sensitive clinical remark', 'encrypted remark decrypts correctly');

$tampered = substr($encrypted, 0, -1) . ($encrypted[-1] === 'A' ? 'B' : 'A');
$tamperRejected = false;
try {
    $cipher->decrypt($tampered);
} catch (RuntimeException) {
    $tamperRejected = true;
}
assertApplication($tamperRejected, 'tampered encrypted remark is rejected');

$record = new BasicPatientRecord('PR001', 'Patient Test', 'Asthma', 'Stable');
$record = new ConfidentialityDecorator($record, 'Confidential medical information');
$record = new AuditTrailDecorator($record, 'Doctor Test', new DateTimeImmutable('2026-09-04 12:00:00'));
$record = new EmergencyAlertDecorator($record, 'Immediate attention required');
$details = $record->getDetails();
assertApplication(str_contains($details, 'Confidential medical information'), 'confidentiality decorator is applied');
assertApplication(str_contains($details, 'Doctor Test'), 'audit decorator is applied');
assertApplication(str_contains($details, 'Immediate attention required'), 'emergency decorator is applied');
