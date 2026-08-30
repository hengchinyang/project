<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Create Patient Record</title>

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


<h1>Create Patient Record</h1>


<p>

    <strong>Patient:</strong>
    <?= htmlspecialchars($patient['name']) ?>

</p>


<p>

    <strong>Patient ID:</strong>
    <?= htmlspecialchars($patient['id']) ?>

</p>


<form
    action="index.php?action=store"
    method="POST"
>


    <label for="appointment_id">Appointment ID</label>
    <input id="appointment_id" name="appointment_id" maxlength="20" pattern="APT[0-9]{4,17}" placeholder="APT0001" required>

    <label for="doctor_id">Doctor ID</label>
    <input id="doctor_id" name="doctor_id" maxlength="20" pattern="DC[0-9]{3,18}" placeholder="DC001" required>

    <label for="condition_id">
        Condition
    </label>

    <select
        id="condition_id"
        name="condition_id"
        required
    >

        <option value="">
            Select Condition
        </option>

        <?php foreach ($conditions as $condition): ?>
            <option value="<?= htmlspecialchars($condition['id']) ?>">
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

        <option value="">
            Select Severity
        </option>

        <option value="Mild">
            Mild
        </option>

        <option value="Moderate">
            Moderate
        </option>

        <option value="Severe">
            Severe
        </option>

    </select>


    <label for="remark">
        Remark
    </label>

    <textarea
        id="remark"
        name="remark"
        maxlength="5000"
        required
    ></textarea>

    <label for="record_date">Record Date</label>
    <input
        id="record_date"
        name="record_date"
        type="date"
        value="<?= htmlspecialchars(date('Y-m-d')) ?>"
        required
    >


    <button type="submit">
        Save Patient Record
    </button>


</form>


<p>

    <a href="index.php?action=index">
        Cancel
    </a>

</p>


</body>
</html>
