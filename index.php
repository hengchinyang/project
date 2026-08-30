<?php

declare(strict_types=1);

session_start();

$_SESSION['role'] = 'doctor';
$_SESSION['user_id'] = 'DC001';
$_SESSION['username'] = 'Dr Test';
$_SESSION['patient_id'] = 'PA002';



require __DIR__ . '/Shared/orm.php';
require_once __DIR__ . '/Controller/PatientRecordController.php';

$controller = new PatientRecordController();
$action = $_GET['action'] ?? 'index';
$id = trim($_GET['id'] ?? '');

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
    default:
        http_response_code(404);
        echo 'Page not found.';
}
