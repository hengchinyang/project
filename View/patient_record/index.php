<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Patient Record History</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        h1 {
            margin-bottom: 10px;
        }

        .patient-info {
            margin-bottom: 25px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 900px;
        }

        th,
        td {
            border: 1px solid #cccccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eeeeee;
        }

        .button {
            display: inline-block;
            padding: 8px 14px;
            margin: 3px;
            text-decoration: none;
            border: 1px solid #333333;
            border-radius: 4px;
            color: #000000;
        }

        .create-button {
            margin-top: 20px;
        }

    </style>
    <link rel="icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/pharmacy.css">
</head>

<body>
<main class="container">

<h1>Patient Record History</h1>

<?php if ($isDoctorView): ?>
    <a class="button" href="index.php?action=doctorPatients">Back to My Patients</a>
<?php endif; ?>

<div class="patient-info">

    <strong>Patient:</strong>
    <?= htmlspecialchars($patient['name']) ?>

    <br>

    <strong>Patient ID:</strong>
    <?= htmlspecialchars($patient['id']) ?>

</div>


<table>

    <thead>

    <tr>
        <th>Record ID</th>
        <th>Date</th>
        <th>Doctor ID</th>
        <th>Condition</th>
        <th>Severity</th>
        <th>Action</th>
    </tr>

    </thead>


    <tbody>

    <?php foreach ($records as $record): ?>

        <tr>

            <td>
                <?= htmlspecialchars($record['id']) ?>
            </td>

            <td>
                <?= htmlspecialchars($record['record_date']) ?>
            </td>

            <td><?= htmlspecialchars($record['doctor_id']) ?></td>

            <td>
                <?= htmlspecialchars($record['condition_name']) ?>
            </td>

            <td>
                <?= htmlspecialchars($record['severity']) ?>
            </td>

            <td>

                <a
                    class="button"
                    href="index.php?action=show&id=<?= urlencode($record['id']) ?>"
                >
                    View
                </a>


                <?php if ($canManageRecords): ?>
                    <a
                        class="button"
                        href="index.php?action=edit&id=<?= urlencode($record['id']) ?>"
                    >
                        Edit
                    </a>
                <?php endif; ?>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php
$currentPage = (int) ($recordPage['page'] ?? 1);
$lastPage = max(1, (int) ceil(((int) ($recordPage['total'] ?? 0)) / ((int) ($recordPage['perPage'] ?? 10))));
$patientQuery = $isDoctorView ? '&patient_id=' . urlencode($patient['id']) : '';
?>
<?php if ($lastPage > 1): ?>
    <nav class="pagination" aria-label="Patient Record pages">
        <?php if ($currentPage > 1): ?>
            <a class="button secondary" href="index.php?action=index<?= $patientQuery ?>&page=<?= $currentPage - 1 ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= $currentPage ?> of <?= $lastPage ?></span>
        <?php if ($currentPage < $lastPage): ?>
            <a class="button secondary" href="index.php?action=index<?= $patientQuery ?>&page=<?= $currentPage + 1 ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>


<?php if ($canManageRecords): ?>
    <a class="button create-button" href="index.php?action=conditionIndex">Manage Conditions</a>
    <a
        class="button create-button"
        href="index.php?action=create<?= $isDoctorView ? '&patient_id=' . urlencode($patient['id']) : '' ?>"
    >
        Create New Record
    </a>
<?php endif; ?>


</main>
</body>
</html>
