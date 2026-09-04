<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Manage Conditions</title>
<style>body{font-family:Arial,sans-serif;margin:40px}table{border-collapse:collapse;width:100%;max-width:800px}th,td{border:1px solid #ccc;padding:10px;text-align:left}th{background:#eee}.button{display:inline-block;padding:8px 14px;margin:8px 4px 8px 0;text-decoration:none;border:1px solid #333;border-radius:4px;color:#000}</style>
<link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="assets/css/pharmacy.css"></head><body><main class="container">
<h1>Manage Medical Conditions</h1>
<a class="button" href="index.php?action=conditionCreate">Add Condition</a>
<a class="button" href="index.php?action=index">Back to Patient Records</a>
<table><thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Action</th></tr></thead><tbody>
<?php foreach ($conditions as $condition): ?>
<tr><td><?= htmlspecialchars($condition['id']) ?></td><td><?= htmlspecialchars($condition['name']) ?></td><td><?= htmlspecialchars($condition['description'] ?? '') ?></td><td><a class="button" href="index.php?action=conditionEdit&id=<?= urlencode($condition['id']) ?>">Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table></main></body></html>
