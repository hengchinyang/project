<?php

declare(strict_types=1);

if (getenv('MEDICARE_ENABLE_DEMO_LOGIN') !== '1') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The Patient Record demo login is disabled. Sign in through the MediCare Connect Authentication module.';
    exit;
}

require_once __DIR__ . '/Shared/SessionSecurity.php';
require_once __DIR__ . '/Shared/Csrf.php';
require_once __DIR__ . '/Shared/ErrorRenderer.php';

SessionSecurity::start();

$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$isLocal = in_array($remoteAddress, ['127.0.0.1', '::1', ''], true);
if (!$isLocal) {
    ErrorRenderer::render(403, 'Demo sign-in disabled', 'The temporary Patient Record sign-in is available only from this computer.');
    exit;
}

$identities = [
    'doctor_dc001' => ['role' => 'doctor', 'user_id' => 'DC001', 'username' => 'Doctor DC001'],
    'doctor_dc002' => ['role' => 'doctor', 'user_id' => 'DC002', 'username' => 'Doctor DC002'],
    'doctor_dc003' => ['role' => 'doctor', 'user_id' => 'DC003', 'username' => 'Doctor DC003'],
    'patient_pa001' => ['role' => 'patient', 'user_id' => 'PA001', 'patient_id' => 'PA001', 'username' => 'John Tan'],
    'patient_pa002' => ['role' => 'patient', 'user_id' => 'PA002', 'patient_id' => 'PA002', 'username' => 'Patient PA002'],
    'patient_pa003' => ['role' => 'patient', 'user_id' => 'PA003', 'patient_id' => 'PA003', 'username' => 'Patient PA003'],
    'admin' => ['role' => 'admin', 'user_id' => 'AD001', 'username' => 'Patient Record Administrator'],
];

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedToken = is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null;
    $identityKey = is_string($_POST['identity'] ?? null) ? $_POST['identity'] : '';
    if (!Csrf::verify($providedToken)) {
        $error = 'The sign-in form expired. Please try again.';
    } elseif (!isset($identities[$identityKey])) {
        $error = 'Select a valid demo identity.';
    } else {
        session_regenerate_id(true);
        unset($_SESSION['role'], $_SESSION['user_id'], $_SESSION['username'], $_SESSION['patient_id']);
        foreach ($identities[$identityKey] as $name => $value) {
            $_SESSION[$name] = $value;
        }
        header('Location: index.php?action=index');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Record Demo Sign In</title>
    <link rel="icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/pharmacy.css">
</head>
<body>
<main class="container">
    <section class="login-shell">
        <div class="login-panel">
            <p class="eyebrow">Patient Record</p>
            <h1>Demo sign in</h1>
            <p class="muted">Choose a Patient Record identity for local testing. Pharmacy authentication is separate.</p>
            <?php if ($error !== null): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form class="form-grid single" method="post" action="login.php">
                <?= Csrf::input() ?>
                <label for="identity">Test identity
                    <select id="identity" name="identity">
                        <option value="">Select identity</option>
                        <optgroup label="Doctors">
                            <option value="doctor_dc001">Doctor DC001</option>
                            <option value="doctor_dc002">Doctor DC002</option>
                            <option value="doctor_dc003">Doctor DC003</option>
                        </optgroup>
                        <optgroup label="Patients">
                            <option value="patient_pa001">Patient PA001 — John Tan</option>
                            <option value="patient_pa002">Patient PA002</option>
                            <option value="patient_pa003">Patient PA003</option>
                        </optgroup>
                        <option value="admin">Administrator</option>
                    </select>
                </label>
                <button class="button primary full-button" type="submit">Enter Patient Record</button>
            </form>
            <p class="security-note">Development only: replace this page with the team authentication module before deployment.</p>
        </div>
    </section>
</main>
</body>
</html>
