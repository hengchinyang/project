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
</head>

<body>

<h1>Patient Record History</h1>

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
        <th>Appointment ID</th>
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

            <td><?= htmlspecialchars($record['appointment_id']) ?></td>

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


<?php if ($canManageRecords): ?>
    <a
        class="button create-button"
        href="index.php?action=create"
    >
        Create New Record
    </a>
<?php endif; ?>


</body>
</html>
