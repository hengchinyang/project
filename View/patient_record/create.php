<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Create Patient Record</title>

    <style>

        form { max-width: 880px; }
        .patient-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin:0 0 22px; }
        .patient-summary div { background:#fff; border:1px solid #dce6ea; border-radius:10px; padding:13px 15px; }
        .patient-summary span { display:block; color:#657b86; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .patient-summary strong { display:block; margin-top:4px; color:#123149; }
        .record-form { background:#fff; border:1px solid #dce6ea; border-radius:14px; box-shadow:0 12px 35px rgba(25,57,73,.08); padding:26px; }
        .record-form label { display:flex; flex-direction:column; gap:6px; margin:16px 0 0; color:#18313f; font-size:13px; font-weight:700; }
        .record-form input,.record-form select,.record-form textarea { width:100%; border:1px solid #b9cbd3; border-radius:8px; padding:10px 11px; background:#fff; color:#18313f; font:inherit; }
        .record-form input:focus,.record-form select:focus,.record-form textarea:focus { outline:3px solid rgba(44,179,183,.18); border-color:#2cb3b7; }
        .record-form textarea { min-height:120px; resize:vertical; }
        .medicine-fieldset { margin:22px 0 0; border:1px solid #b9cbd3; border-radius:11px; padding:18px; background:#f9fcfc; }
        .medicine-fieldset legend { padding:0 8px; color:#123149; font-size:17px; font-weight:800; }
        .medicine-help { color:#657b86; margin:0 0 15px; }
        .medicine-row { display:grid; grid-template-columns:minmax(210px,2fr) 110px minmax(190px,1.5fr) auto; gap:12px; align-items:end; padding:14px 0; border-top:1px solid #dce6ea; }
        .medicine-row:first-child { border-top:0; padding-top:0; }
        .medicine-row label { margin:0; }
        .stock-note { display:block; min-height:18px; margin-top:5px; color:#087269; font-size:12px; font-weight:700; }
        .stock-note.out { color:#b93b52; }
        .remove-medicine,.medicine-add { min-height:42px; margin:0; border:1px solid #b9cbd3; border-radius:8px; padding:9px 15px; background:#fff; color:#123149; font:inherit; font-weight:700; cursor:pointer; }
        .medicine-add { margin-top:15px; background:#e8f7f4; border-color:#addfdc; color:#087269; }
        .record-submit { margin-top:24px; min-height:44px; border:0; border-radius:8px; padding:10px 19px; background:#0b6f86; color:#fff; font:inherit; font-weight:800; cursor:pointer; }
        .error { color:#a40000; font-weight:700; }
        @media(max-width:680px) { .patient-summary,.medicine-row{grid-template-columns:1fr}.remove-medicine{width:100%} }

    </style>

    <link rel="icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/css/pharmacy.css">
</head>


<body>
<main class="container">


<h1>Create Patient Record</h1>


<div class="patient-summary">
    <div><span>Patient</span><strong><?= htmlspecialchars($patient['name']) ?></strong></div>
    <div><span>Patient ID</span><strong><?= htmlspecialchars($patient['id']) ?></strong></div>
    <?php if ($isDoctorUser): ?><div><span>Assigned doctor</span><strong><?= htmlspecialchars($_SESSION['user_id'] ?? '') ?></strong></div><?php endif; ?>
</div>


<form class="record-form"
    action="index.php?action=store<?= $isDoctorCreate ? '&patient_id=' . urlencode($patient['id']) : '' ?>"
    method="POST"
>
    <?= Csrf::input() ?>


    <?php if ($isDoctorUser): ?>
        <p class="security-note">Doctor ID <strong><?= htmlspecialchars($_SESSION['user_id'] ?? '') ?></strong> is assigned automatically.</p>
    <?php else: ?>
        <label for="doctor_id">Doctor ID</label>
        <input id="doctor_id" name="doctor_id" maxlength="20" pattern="DC[0-9]{3,18}" placeholder="DC001" required>
    <?php endif; ?>

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

    <fieldset class="medicine-fieldset">
        <legend>Medicines</legend>
        <p class="medicine-help">Select one or more medicines. The quantity cannot exceed the displayed stock. Pharmacy checks stock again through its REST XML API when you save.</p>
        <?php if ($pharmacyCatalogError !== null): ?><p class="error"><?= htmlspecialchars($pharmacyCatalogError) ?></p><?php endif; ?>
        <div id="medicine-rows"></div>
        <button type="button" class="medicine-add" id="add-medicine" <?= $medicines === [] ? 'disabled' : '' ?>>+ Add another medicine</button>
    </fieldset>


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


    <button type="submit" class="record-submit">
        Save Patient Record
    </button>


</form>

<template id="medicine-row-template">
    <div class="medicine-row">
        <label>Medicine<select name="medicine_sku[]" required><option value="">Select medicine</option><?php foreach ($medicines as $medicine): ?><option value="<?= htmlspecialchars($medicine['sku']) ?>" data-stock="<?= (int) $medicine['availableQuantity'] ?>"><?= htmlspecialchars($medicine['name'] . ' ' . $medicine['strength'] . ' (' . $medicine['sku'] . ')') ?></option><?php endforeach; ?></select><small class="stock-note">Choose a medicine to see stock.</small></label>
        <label>Quantity<input type="number" name="medicine_quantity[]" min="1" max="1" value="1" required disabled><small class="stock-note quantity-note">Select a medicine first.</small></label>
        <label>Instructions<input name="medicine_instructions[]" maxlength="255" placeholder="Take after food"></label>
        <button type="button" class="remove-medicine">Remove</button>
    </div>
</template>
<script>
const rows = document.getElementById('medicine-rows');
const template = document.getElementById('medicine-row-template');
const addButton = document.getElementById('add-medicine');
function updateMedicineRow(row) {
    const select = row.querySelector('select[name="medicine_sku[]"]');
    const quantity = row.querySelector('input[name="medicine_quantity[]"]');
    const stockNote = row.querySelector('.stock-note');
    const quantityNote = row.querySelector('.quantity-note');
    const selected = select.options[select.selectedIndex];
    const stock = Number(selected?.dataset.stock ?? 0);
    if (!select.value) {
        quantity.value = 1; quantity.max = 1; quantity.disabled = true;
        stockNote.textContent = 'Choose a medicine to see stock.'; stockNote.classList.remove('out');
        quantityNote.textContent = 'Select a medicine first.'; quantityNote.classList.remove('out');
        return;
    }
    quantity.disabled = stock < 1; quantity.max = Math.max(1, stock);
    if (Number(quantity.value) > stock) quantity.value = Math.max(1, stock);
    stockNote.textContent = `Available stock: ${stock}`;
    quantityNote.textContent = stock > 0 ? `Maximum quantity: ${stock}` : 'This medicine is currently unavailable.';
    stockNote.classList.toggle('out', stock < 1); quantityNote.classList.toggle('out', stock < 1);
}
function addMedicineRow() {
    const row = template.content.cloneNode(true);
    const rowElement = row.querySelector('.medicine-row');
    rowElement.querySelector('select[name="medicine_sku[]"]').addEventListener('change', () => updateMedicineRow(rowElement));
    rowElement.querySelector('input[name="medicine_quantity[]"]').addEventListener('input', () => updateMedicineRow(rowElement));
    rowElement.querySelector('.remove-medicine').addEventListener('click', event => {
        if (rows.children.length > 1) event.target.closest('.medicine-row').remove();
    });
    rows.appendChild(row);
    updateMedicineRow(rows.lastElementChild);
}
addButton.addEventListener('click', addMedicineRow);
if (!addButton.disabled) addMedicineRow();
</script>


<p>

    <a href="index.php?action=index">
        Cancel
    </a>

</p>


</main>
</body>
</html>
