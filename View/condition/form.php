<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title><?= htmlspecialchars($pageTitle) ?></title>
<style>body{font-family:Arial,sans-serif;margin:40px}form{max-width:500px}label{display:block;margin-top:15px}input,textarea{box-sizing:border-box;width:100%;padding:8px;margin-top:5px}textarea{height:100px}button{margin-top:20px;padding:10px 18px}</style>
<link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="assets/css/pharmacy.css"></head><body><main class="container">
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php if ($condition['id'] !== ''): ?><p><strong>Condition ID:</strong> <?= htmlspecialchars($condition['id']) ?></p><?php endif; ?>
<form method="POST" action="index.php?action=<?= htmlspecialchars($formAction) ?>">
<?= Csrf::input() ?>
<label for="name">Condition Name</label><input id="name" name="name" maxlength="100" value="<?= htmlspecialchars($condition['name']) ?>" required>
<label for="description">Description</label><textarea id="description" name="description" maxlength="255"><?= htmlspecialchars($condition['description'] ?? '') ?></textarea>
<button type="submit">Save Condition</button>
</form><p><a href="index.php?action=conditionIndex">Cancel</a></p>
</main></body></html>
