<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Edit Patient Record</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        form {
            max-width: 500px;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        textarea {
            height: 100px;
        }

        button {
            margin-top: 20px;
            padding: 10px 18px;
        }

    </style>

</head>


<body>


<h1>Edit Patient Record</h1>


<p>

    <strong>Record ID:</strong>

    <?= htmlspecialchars($record['id']) ?>

</p>


<p>

    <strong>Patient:</strong>

    <?= htmlspecialchars($record['patient_name']) ?>

</p>


<form
    action="index.php?action=update&id=<?= urlencode($record['id']) ?>"
    method="POST"
>


    <label for="appointment_id">Appointment ID</label>
    <input
        id="appointment_id"
        name="appointment_id"
        maxlength="20"
        pattern="APT[0-9]{4,17}"
        value="<?= htmlspecialchars($record['appointment_id']) ?>"
        required
    >

    <label for="doctor_id">Doctor ID</label>
    <input
        id="doctor_id"
        name="doctor_id"
        maxlength="20"
        pattern="DC[0-9]{3,18}"
        value="<?= htmlspecialchars($record['doctor_id']) ?>"
        required
    >

    <label for="condition_id">
        Condition
    </label>


    <select
        id="condition_id"
        name="condition_id"
        required
    >

        <?php foreach ($conditions as $condition): ?>

            <option
                value="<?= htmlspecialchars($condition['id']) ?>"

                <?= $record['condition_id'] === $condition['id']
                    ? 'selected'
                    : '' ?>
            >

                <?= htmlspecialchars($condition['name']) ?>

            </option>

        <?php endforeach; ?>

    </select>


    <label for="severity">
        Severity
    </label>


    <select
        id="severity"
        name="severity"
        required
    >

        <?php

        $severityLevels = [
            'Mild',
            'Moderate',
            'Severe'
        ];

        foreach ($severityLevels as $severity):

        ?>

            <option
                value="<?= htmlspecialchars($severity) ?>"

                <?= $record['severity'] === $severity
                    ? 'selected'
                    : '' ?>
            >

                <?= htmlspecialchars($severity) ?>

            </option>

        <?php endforeach; ?>

    </select>


    <label for="remark">
        Remark
    </label>


    <textarea
        id="remark"
        name="remark"
        maxlength="5000"
        required
    ><?= htmlspecialchars($record['remark']) ?></textarea>

    <label for="record_date">Record Date</label>
    <input
        id="record_date"
        name="record_date"
        type="date"
        value="<?= htmlspecialchars($record['record_date']) ?>"
        required
    >


    <button type="submit">
        Update Patient Record
    </button>


</form>


<p>

    <a
        href="index.php?action=show&id=<?= urlencode($record['id']) ?>"
    >
        Cancel
    </a>

</p>


</body>
</html>
