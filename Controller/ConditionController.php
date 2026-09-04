<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/Condition.php';

/** Handles condition-management screens separately from Patient Records. */
final class ConditionController
{
    private Condition $conditionModel;

    public function __construct()
    {
        $this->conditionModel = new Condition();
    }

    public function index(): void
    {
        if (!$this->canManage()) { $this->forbidden(); return; }
        $conditions = $this->conditionModel->getAll();
        require __DIR__ . '/../View/condition/index.php';
    }

    public function create(): void
    {
        if (!$this->canManage()) { $this->forbidden(); return; }
        $condition = ['id' => '', 'name' => '', 'description' => ''];
        $formAction = 'conditionStore';
        $pageTitle = 'Add Condition';
        require __DIR__ . '/../View/condition/form.php';
    }

    public function store(): void
    {
        if (!$this->canManage()) { $this->forbidden(); return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->methodNotAllowed(); return; }
        $data = $this->validatedInput();
        if ($data === null) return;
        $this->conditionModel->create($data['name'], $data['description']);
        header('Location: index.php?action=conditionIndex');
        exit;
    }

    public function edit(string $id): void
    {
        if (!$this->canManage()) { $this->forbidden(); return; }
        $condition = $this->conditionModel->findById($id);
        if ($condition === null) {
            ErrorRenderer::render(404, 'Condition not found', 'The requested medical condition does not exist.');
            return;
        }
        $formAction = 'conditionUpdate&id=' . urlencode($id);
        $pageTitle = 'Edit Condition';
        require __DIR__ . '/../View/condition/form.php';
    }

    public function update(string $id): void
    {
        if (!$this->canManage()) { $this->forbidden(); return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->methodNotAllowed(); return; }
        $data = $this->validatedInput();
        if ($data === null) return;
        if (!$this->conditionModel->update($id, $data['name'], $data['description'])) {
            ErrorRenderer::render(404, 'Condition not found', 'The requested medical condition does not exist.');
            return;
        }
        header('Location: index.php?action=conditionIndex');
        exit;
    }

    /** @return array{name:string,description:string}|null */
    private function validatedInput(): ?array
    {
        $name = $this->postString('name');
        $description = $this->postString('description');
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($description) > 255) {
            ErrorRenderer::render(422, 'Invalid condition', 'Condition name is required (maximum 100 characters); description maximum is 255 characters.');
            return null;
        }

        return ['name' => $name, 'description' => $description];
    }

    private function postString(string $field): string
    {
        $value = $_POST[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function canManage(): bool
    {
        return in_array(strtolower((string) ($_SESSION['role'] ?? '')), ['doctor', 'admin'], true);
    }

    private function forbidden(): void
    {
        ErrorRenderer::render(403, 'Access denied', 'Only a doctor or administrator can manage medical conditions.');
    }

    private function methodNotAllowed(): void
    {
        ErrorRenderer::render(405, 'Method not allowed', 'This action requires an HTTP POST request.');
    }
}
