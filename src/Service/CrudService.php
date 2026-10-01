<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Container;
use App\Core\Request;
use App\Repository\BaseRepository;
use App\Repository\UserRepository;

/**
 * Generic admin CRUD driven by config/admin-crud.php: builds list rows and form fields,
 * validates and normalises submitted values, and persists them.
 */
final class CrudService
{
    /** @var array<string, array<string, mixed>> */
    private readonly array $definitions;

    public function __construct(
        Config $config,
        private readonly Container $container,
        private readonly AuthService $auth,
        private readonly UserRepository $users,
    ) {
        $this->definitions = require $config->get('paths.root') . '/config/admin-crud.php';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definition(string $entity): ?array
    {
        return $this->definitions[$entity] ?? null;
    }

    /**
     * @return array<string, string> entity key => title
     */
    public function titles(): array
    {
        return array_map(fn(array $d) => $d['title'], $this->definitions);
    }

    public function repository(string $entity): BaseRepository
    {
        return $this->container->get($this->definitions[$entity]['repository']);
    }

    /**
     * @return list<array{id: int, cells: list<array{value: string, image: bool}>}>
     */
    public function listRows(string $entity): array
    {
        $definition = $this->definitions[$entity];
        $fieldsByName = array_column($this->fields($entity), null, 'name');
        $rows = [];
        foreach ($this->repository($entity)->all($definition['orderBy']) as $row) {
            $cells = [];
            foreach ($definition['columns'] as $column) {
                [$field, , $kind] = $column + [2 => null];
                $value = $row[$field] ?? '';
                $options = isset($fieldsByName[$field]) ? $this->options($fieldsByName[$field]) : [];
                $cells[] = [
                    'value' => match (true) {
                        is_bool($value) => $value ? 'yes' : '',
                        is_array($value) => implode(', ', array_map(fn(int|string $v): string => $options[$v] ?? (string) $v, $value)),
                        isset($options[$value]) => $options[$value],
                        default => (string) $value,
                    },
                    'image' => $kind === 'image',
                ];
            }
            $rows[] = ['id' => $row['_id'], 'cells' => $cells];
        }
        return $rows;
    }

    /**
     * @param array<string, mixed>|null $values current values (stored row, or re-displayed submission)
     * @return list<array<string, mixed>> visible form fields with their current value and options
     */
    public function formFields(string $entity, ?array $values): array
    {
        $fields = [];
        foreach ($this->fields($entity) as $field) {
            $default = match ($field['type']) {
                'date' => date('Y-m-d'),
                'checkbox' => $field['default'] ?? false,
                'checkboxes' => [],
                default => '',
            };
            $field['value'] = $field['type'] === 'password' ? '' : ($values[$field['name']] ?? $default);
            if ($field['type'] === 'select') {
                $field['value'] = (string) $field['value'];
            }
            if ($field['type'] === 'checkboxes') {
                $field['value'] = array_map('strval', $field['value']);
            }
            $field['options'] = in_array($field['type'], ['select', 'checkboxes'], true) ? $this->options($field) : [];
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * @param array<string, mixed>|null $existing the stored row when editing
     * @return array{0: array<string, mixed>, 1: list<string>} [data to store, validation errors]
     */
    public function collect(string $entity, Request $request, ?array $existing): array
    {
        $data = [];
        $errors = [];
        foreach ($this->fields($entity) as $field) {
            $name = $field['name'];
            $label = $field['label'];
            $required = $field['required'];

            switch ($field['type']) {
                case 'password':
                    continue 2; // handled in collectUser()
                case 'checkbox':
                    $data[$name] = $request->postFlag($name);
                    continue 2;
                case 'checkboxes':
                    $submitted = $request->postList($name);
                    if ($required && $submitted === []) {
                        $errors[] = "{$label} is required.";
                    }
                    if (array_diff($submitted, array_map('strval', array_keys($this->options($field)))) !== []) {
                        $errors[] = "{$label} has an invalid value.";
                    }
                    $data[$name] = array_values(array_unique(array_map('intval', $submitted)));
                    continue 2;
                default:
                    $value = $request->post($name);
            }

            if ($required && $value === '') {
                $errors[] = "{$label} is required.";
            }
            if ($field['type'] === 'date' && $value !== '' && !$this->isDate($value)) {
                $errors[] = "{$label} must be a valid date.";
            }
            if ($field['type'] === 'select' && $value !== '' && !array_key_exists($value, $this->options($field))) {
                $errors[] = "{$label} has an invalid value.";
            }
            if ($field['type'] === 'number') {
                $value = $this->wholeNumber($label, $value, $field, $errors);
            }
            if (($field['cast'] ?? null) === 'int') {
                $value = $value === '' ? null : (int) $value;
            }
            $data[$name] = $value;
        }

        $data = [...$data, ...$this->autoValues($entity, $existing === null)];

        if ($entity === 'users') {
            $errors = [...$errors, ...$this->collectUser($data, $request, $existing)];
        }

        return [$data, $errors];
    }

    /**
     * Fields with an "auto" flag, filled in automatically with the logged-in user's id: "creator" fields on create only,
     * "editor" fields on every create and edit.
     *
     * @return array<string, int|null>
     */
    public function autoValues(string $entity, bool $creating): array
    {
        $values = [];
        foreach ($this->definitions[$entity]['fields'] as $def) {
            $auto = $def['auto'] ?? null;
            if ($auto === 'editor' || ($auto === 'creator' && $creating)) {
                $values[$def['name']] = $this->auth->user()['_id'] ?? null;
            }
        }
        return $values;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(string $entity, array $data, ?int $id): void
    {
        $repository = $this->repository($entity);
        $id === null ? $repository->insert($data) : $repository->update($id, $data);
    }

    /**
     * @return string|null reason why the row cannot be deleted
     */
    public function deleteBlocker(string $entity, int $id): ?string
    {
        if ($entity === 'users' && ($this->auth->user()['_id'] ?? null) === $id) {
            return 'You cannot delete your own account.';
        }
        return null;
    }

    /**
     * Users carry an email (unique, stored lowercase) and a hashed password instead of the plain field.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $existing
     * @return list<string>
     */
    private function collectUser(array &$data, Request $request, ?array $existing): array
    {
        $errors = [];

        $data['email'] = mb_strtolower((string) $data['email']);
        if ($data['email'] !== '') {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email is not valid.';
            } else {
                $other = $this->users->findOneBy(['email', '=', $data['email']]);
                if ($other !== null && $other['_id'] !== ($existing['_id'] ?? null)) {
                    $errors[] = 'This email is already used.';
                }
            }
        }

        $password = $request->post('password');
        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                $errors[] = 'Password must have at least 8 characters.';
            }
            $data['passwordHash'] = $this->auth->hashPassword($password);
        } elseif ($existing === null) {
            $errors[] = 'Password is required for a new user.';
        }

        return $errors;
    }

    /**
     * The entity's form fields: the entries of its definition that have a label, with "type" turned into
     * the widget (text, email, number, textarea, markdown, date, select, checkbox, checkboxes, password).
     *
     * @return list<array<string, mixed>>
     */
    private function fields(string $entity): array
    {
        $fields = [];
        foreach ($this->definitions[$entity]['fields'] as $def) {
            if (!isset($def['label'])) {
                continue;
            }
            $name = $def['name'];
            $field = [
                'name' => $name,
                'label' => $def['label'],
                'type' => match ($def['type']) {
                    'text' => 'textarea',
                    'bool' => 'checkbox',
                    'enum', 'ref' => 'select',
                    'refs' => 'checkboxes',
                    'string' => 'text',
                    'int' => 'number',
                    default => $def['type'], // html, email, date
                },
                'required' => $def['required'] ?? false,
            ];
            if (isset($def['default'])) {
                $field['default'] = $def['default'];
            }
            foreach (['min', 'max', 'step'] as $attribute) {
                if (isset($def[$attribute])) {
                    $field[$attribute] = $def[$attribute];
                }
            }
            if ($def['type'] === 'enum') {
                $field['options'] = $def['options'];
            } elseif ($def['type'] === 'ref') {
                $field['optionsFrom'] = $def['ref'];
                $field['cast'] = 'int';
            } elseif ($def['type'] === 'refs') {
                $field['optionsFrom'] = $def['ref'];
                $field['multiple'] = true;
            }
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, string> value => label
     */
    private function options(array $field): array
    {
        if (isset($field['options'])) {
            return $field['options'];
        }
        if (isset($field['optionsFrom'])) {
            [$repositoryClass, $labelField] = $field['optionsFrom'];
            $labelFields = (array) $labelField; // several fields are joined with a space
            $options = ($field['multiple'] ?? false) ? [] : ['' => '—']; // multi-value inputs have no "none" entry
            foreach ($this->container->get($repositoryClass)->all([$labelFields[0] => 'asc']) as $row) {
                $options[(string) $row['_id']] = implode(' ', array_map(fn (string $f): string => (string) ($row[$f] ?? ''), $labelFields));
            }
            return $options;
        }
        return [];
    }

    /**
     * Parses a whole number and checks it against the field's optional min and max.
     *
     * @param array<string, mixed> $field
     * @param list<string> $errors
     * @return int|null null when empty or invalid
     */
    private function wholeNumber(string $label, string $value, array $field, array &$errors): ?int
    {
        if ($value === '') {
            return null;
        }
        if (preg_match('/^-?\d+$/', $value) !== 1) {
            $errors[] = "{$label} must be a whole number.";
            return null;
        }
        $number = (int) $value;
        if ((isset($field['min']) && $number < $field['min']) || (isset($field['max']) && $number > $field['max'])) {
            $errors[] = "{$label} must be between {$field['min']} and {$field['max']}.";
            return null;
        }
        return $number;
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
