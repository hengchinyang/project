<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/pharmacy.css">
</head>
<body>
<main class="container">
    <section class="login-shell">
        <div class="login-panel">
            <p class="eyebrow">Patient Record</p>
            <h1><?= htmlspecialchars((string) $status) ?></h1>
            <h2><?= htmlspecialchars($title) ?></h2>
            <p class="muted"><?= htmlspecialchars($message) ?></p>
            <?php if ((int) $status === 401): ?>
                <a class="button primary" href="login.php">Open Patient Record demo sign in</a>
            <?php else: ?>
                <a class="button secondary" href="index.php?action=index">Return to Patient Records</a>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
