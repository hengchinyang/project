<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>My Patients</title>
<style>body{font-family:Arial,sans-serif;margin:40px}table{border-collapse:collapse;width:100%;max-width:650px}th,td{border:1px solid #ccc;padding:10px;text-align:left}th{background:#eee}.button{display:inline-block;padding:8px 14px;text-decoration:none;border:1px solid #333;border-radius:4px;color:#000}</style>
<link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="assets/css/pharmacy.css"></head><body><main class="container">
<h1>My Patients</h1>
<p>Patients assigned to doctor ID <?= htmlspecialchars($_SESSION['user_id'] ?? '') ?> are shown from existing records and the Appointment REST service.</p>
<table><thead><tr><th>Patient ID</th><th>Patient Name</th><th>Action</th></tr></thead><tbody>
<?php foreach ($patients as $patient): ?>
<tr><td><?= htmlspecialchars($patient['id']) ?></td><td><?= htmlspecialchars($patient['name']) ?></td><td><a class="button" href="index.php?action=index&patient_id=<?= urlencode($patient['id']) ?>">View Records</a></td></tr>
<?php endforeach; ?>
<?php if ($patients === []): ?><tr><td colspan="3">No patients are currently assigned to you.</td></tr><?php endif; ?>
</tbody></table>
</main></body></html>
