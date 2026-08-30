<?php

declare(strict_types=1);

require __DIR__ . '/../Shared/orm.php';
require_once __DIR__ . '/../WebService/ServiceApiKey.php';
require_once __DIR__ . '/../WebService/PatientRecordXmlService.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$service = new PatientRecordXmlService(new PatientRecord());

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $result = $service->error(405, 'UNKNOWN', 'Only HTTP POST is allowed.');
        header('Allow: POST');
    } else {
        $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!is_string($providedKey) || !ServiceApiKey::isValid($providedKey)) {
            $result = $service->error(401, 'UNKNOWN', 'Service authentication failed.');
        } else {
            $result = $service->handle((string) file_get_contents('php://input'));
        }
    }
} catch (Throwable $exception) {
    error_log('Patient Record XML service error: ' . $exception->getMessage());
    $result = $service->error(500, 'UNKNOWN', 'The Patient Record service could not process the request.');
}

http_response_code($result['httpStatus']);
echo $result['xml'];
