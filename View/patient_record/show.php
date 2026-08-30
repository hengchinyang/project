<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Record Details</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f7f9; color: #203038; font-family: Arial, sans-serif; }
        .page { width: min(920px, calc(100% - 32px)); margin: 42px auto; }
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        h1 { margin: 0 0 6px; font-size: 30px; }
        h2 { margin: 0 0 18px; font-size: 18px; }
        .subtitle { margin: 0; color: #64757d; }
        .badge { padding: 7px 11px; border-radius: 999px; background: #e7f3f0; color: #176b5c; font-size: 12px; font-weight: bold; letter-spacing: .04em; text-transform: uppercase; }
        .alert { margin-bottom: 18px; padding: 14px 16px; border: 1px solid #e4a1a1; border-left: 5px solid #b42318; border-radius: 8px; background: #fff1f0; color: #8e1b12; font-weight: bold; }
        .card { overflow: hidden; border: 1px solid #dce4e7; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(35, 55, 65, .06); }
        .section { padding: 24px; }
        .section + .section { border-top: 1px solid #e7ecee; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 32px; }
        .field-label { display: block; margin-bottom: 6px; color: #718087; font-size: 12px; font-weight: bold; letter-spacing: .04em; text-transform: uppercase; }
        .field-value { font-size: 16px; line-height: 1.5; }
        .severity { display: inline-block; padding: 4px 9px; border-radius: 6px; font-size: 14px; font-weight: bold; }
        .severity-Mild { background: #e9f7ef; color: #217a45; }
        .severity-Moderate { background: #fff5d6; color: #8a6300; }
        .severity-Severe { background: #fde8e7; color: #aa251b; }
        .remark { margin: 0; padding: 16px; border-radius: 8px; background: #f6f9fa; line-height: 1.6; }
        .access-info { display: flex; flex-wrap: wrap; gap: 10px 30px; color: #63747b; font-size: 13px; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .button { display: inline-block; padding: 10px 17px; border: 1px solid #b7c4c9; border-radius: 7px; background: #fff; color: #26383f; text-decoration: none; font-weight: bold; }
        .button-primary { border-color: #167565; background: #167565; color: #fff; }
        .button:hover { filter: brightness(.96); }
        @media (max-width: 640px) { .page { margin: 24px auto; } .page-header { flex-direction: column; } .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="page">
    <header class="page-header">
        <div>
            <h1>Patient Record Details</h1>
            <p class="subtitle">Record <?= htmlspecialchars($record['id']) ?></p>
        </div>
        <span class="badge"><?= htmlspecialchars($securityLabel) ?></span>
    </header>

    <?php if ($emergencyMessage !== null): ?>
        <div class="alert" role="alert">Emergency alert: <?= htmlspecialchars($emergencyMessage) ?></div>
    <?php endif; ?>

    <article class="card">
        <section class="section">
            <h2>Patient information</h2>
            <div class="grid">
                <div><span class="field-label">Patient name</span><span class="field-value"><?= htmlspecialchars($record['patient_name']) ?></span></div>
                <div><span class="field-label">Patient ID</span><span class="field-value"><?= htmlspecialchars($record['patient_id']) ?></span></div>
                <div><span class="field-label">Record date</span><span class="field-value"><?= htmlspecialchars($record['record_date']) ?></span></div>
                <div><span class="field-label">Severity</span><span class="severity severity-<?= htmlspecialchars($record['severity']) ?>"><?= htmlspecialchars($record['severity']) ?></span></div>
            </div>
        </section>

        <section class="section">
            <h2>Clinical information</h2>
            <div class="grid">
                <div><span class="field-label">Condition</span><span class="field-value"><?= htmlspecialchars($record['condition_name']) ?></span></div>
                <div><span class="field-label">Doctor ID</span><span class="field-value"><?= htmlspecialchars($record['doctor_id']) ?></span></div>
                <div><span class="field-label">Appointment ID</span><span class="field-value"><?= htmlspecialchars($record['appointment_id']) ?></span></div>
            </div>
            <div style="margin-top: 22px;">
                <span class="field-label">Remark</span>
                <p class="remark"><?= nl2br(htmlspecialchars($record['remark'])) ?></p>
            </div>
        </section>

        <footer class="section access-info">
            <span><strong>Accessed by:</strong> <?= htmlspecialchars($accessedBy) ?></span>
            <span><strong>Access time:</strong> <?= htmlspecialchars($accessTime->format('Y-m-d H:i:s')) ?></span>
        </footer>
    </article>

    <nav class="actions" aria-label="Record actions">
        <?php if ($canManageRecords): ?>
            <a class="button button-primary" href="index.php?action=edit&amp;id=<?= urlencode($record['id']) ?>">Edit record</a>
        <?php endif; ?>
        <a class="button" href="index.php?action=index">Back to history</a>
    </nav>
</main>
</body>
</html>
