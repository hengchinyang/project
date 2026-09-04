<?php

declare(strict_types=1);

require_once __DIR__ . '/Shared/SessionSecurity.php';
require_once __DIR__ . '/Shared/Csrf.php';
require_once __DIR__ . '/Shared/ErrorRenderer.php';

SessionSecurity::start();

if (!SessionSecurity::isAuthenticated()) {
    ErrorRenderer::render(
        401,
        'Authentication required',
        'Sign in through the MediCare Connect authentication module before opening Patient Records.'
    );
    exit;
}

require __DIR__ . '/Shared/orm.php';
require_once __DIR__ . '/Controller/PatientRecordController.php';
require_once __DIR__ . '/Controller/ConditionController.php';

$controller = new PatientRecordController();
$conditionController = new ConditionController();
$action = $_GET['action'] ?? 'index';
$id = trim($_GET['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    ErrorRenderer::render(419, 'Invalid security token', 'The form expired or came from an untrusted page. Please go back and try again.');
    exit;
}

try {
switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'show':
        $controller->show($id);
        break;
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'edit':
        $controller->edit($id);
        break;
    case 'update':
        $controller->update($id);
        break;
    case 'conditionIndex':
        $conditionController->index();
        break;
    case 'doctorPatients':
        $controller->doctorPatients();
        break;
    case 'conditionCreate':
        $conditionController->create();
        break;
    case 'conditionStore':
        $conditionController->store();
        break;
    case 'conditionEdit':
        $conditionController->edit($id);
        break;
    case 'conditionUpdate':
        $conditionController->update($id);
        break;
    default:
        ErrorRenderer::render(404, 'Page not found', 'The requested Patient Record page does not exist.');
}
} catch (Throwable $exception) {
    error_log('Patient Record application error: ' . $exception->getMessage());
    ErrorRenderer::render(
        503,
        'Patient Record temporarily unavailable',
        'The application could not complete the request. Confirm that MySQL and the required services are running, then try again.'
    );
}
