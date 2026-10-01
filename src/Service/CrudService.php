<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Container;
use App\Core\Request;
use App\Repository\BaseRepository;
use App\Repository\UserRepository;

/**
 * Generic admin CRUD driven by config/entities.php: builds list rows and form fields,
 * validates and normalises submitted values, and persists them.
 */
final class CrudService
{
    /** @var array<string, array<string, mixed>> */
    private readonly array $definitions;

    public function __construct(
        Config $config,
        private readonly Container $container,
        private readonly HtmlSanitizer $sanitizer,
        private readonly AuthService $auth,
        private readonly UserRepository $users,
    ) {
        $this->definitions = require $config->get('paths.root') . '/config/entities.php';
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
        $fieldsByName = array_column($definition['fields'], null, 'name');
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
        foreach ($this->definitions[$entity]['fields'] as $field) {
            if ($field['type'] === 'author') {
                continue;
            }
            $default = $field['type'] === 'date' ? date('Y-m-d') : ($field['type'] === 'checkbox' ? ($field['default'] ?? false) : '');
            $field['value'] = $field['type'] === 'password' ? '' : ($values[$field['name']] ?? $default);
            if ($field['type'] === 'select') {
                $field['value'] = (string) $field['value'];
            }
            $field['options'] = $field['type'] === 'select' ? $this->options($field) : [];
            $field['required'] ??= false;
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
        foreach ($this->definitions[$entity]['fields'] as $field) {
            $name = $field['name'];
            $label = $field['label'] ?? $name;
            $required = $field['required'] ?? false;

            switch ($field['type']) {
                case 'author':
                    if ($existing === null) {
                        $data[$name] = $this->auth->user()['_id'] ?? null;
                    }
                    continue 2;
                case 'password':
                    continue 2; // handled in collectUser()
                case 'checkbox':
                    $data[$name] = $request->postFlag($name);
                    continue 2;
                case 'html':
                    $value = $this->sanitizer->clean($request->post($name));
                    break;
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
            if (($field['cast'] ?? null) === 'int') {
                $value = $value === '' ? null : (int) $value;
            }
            $data[$name] = $value;
        }

        if ($entity === 'users') {
            $errors = [...$errors, ...$this->collectUser($data, $request, $existing)];
        }

        return [$data, $errors];
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
            $options = ['' => '—'];
            foreach ($this->container->get($repositoryClass)->all([$labelFields[0] => 'asc']) as $row) {
                $options[(string) $row['_id']] = implode(' ', array_map(fn (string $f): string => (string) ($row[$f] ?? ''), $labelFields));
            }
            return $options;
        }
        return [];
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
