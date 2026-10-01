<?php

declare(strict_types=1);

// Usage: php bin/export-data-model.php [output-file]   (default: docs/data-model.md)
// Writes a plain-language description of the stored data, generated from config/admin-crud.php,
// for the people who commission the site and decide what else should be kept in the database.

$definitions = require dirname(__DIR__) . '/config/admin-crud.php';
$output = $argv[1] ?? dirname(__DIR__) . '/docs/data-model.md';

// Names (singular, plural; lowercase) of the entity a repository class stores, e.g. ["user", "users"].
$entityByRepository = [];
foreach ($definitions as $definition) {
    $entityByRepository[$definition['repository']] = [$definition['singular'], mb_strtolower($definition['title'])];
}

/** "authorId" -> "Author", "lastEditBy" -> "Last edit by" */
function humanize(string $name): string
{
    $name = preg_replace('/Ids?$/', '', $name);
    return ucfirst(mb_strtolower(trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $name))));
}

/** @return array{0: string, 1: string} label without its bracketed hint, and the hint */
function splitLabel(string $label): array
{
    return preg_match('/^(.*?)\s*\((.*)\)$/', $label, $m) === 1 ? [$m[1], $m[2]] : [$label, ''];
}

function describeType(array $field, array $entityByRepository): string
{
    return match ($field['type']) {
        'string' => 'short text',
        'text' => 'plain text, several lines',
        'markdown' => 'formatted text',
        'email' => 'e-mail address',
        'int' => 'whole number' . (isset($field['min'], $field['max']) ? " from {$field['min']} to {$field['max']}" : ''),
        'date' => 'date',
        'bool' => 'yes / no',
        'enum' => 'one of: ' . implode(', ', $field['options']),
        'ref' => 'link to a ' . $entityByRepository[$field['ref'][0]][0],
        'refs' => 'links to several ' . $entityByRepository[$field['ref'][0]][1],
        'password' => 'password (stored encrypted, never shown)',
        default => $field['type'],
    };
}

$lines = [
    '# What the website stores',
    '',
    'This is the list of the kinds of records the website keeps in its database, and what each one holds.',
    'Every record also gets an automatic number (ID). Fields marked "Czech + English" are kept in both languages;',
    'where the English text is empty, the Czech one is shown.',
    '',
    'Contents: ' . implode(', ', array_map(fn (array $d): string => $d['title'], $definitions)) . '.',
    '',
];

foreach ($definitions as $definition) {
    $fields = array_column($definition['fields'], null, 'name');
    $lines[] = '## ' . $definition['title'];
    $lines[] = '';
    $lines[] = $definition['about'] ?? '';
    $lines[] = '';
    $lines[] = '| Field | What it holds | Required | Note |';
    $lines[] = '|---|---|---|---|';

    foreach ($definition['fields'] as $field) {
        $name = $field['name'];
        if (($field['internal'] ?? false) || $field['type'] === 'file') {
            continue;
        }
        if (str_ends_with($name, 'En') && isset($fields[substr($name, 0, -2)])) {
            continue; // shown together with the Czech field
        }

        [$label, $hint] = splitLabel($field['label'] ?? humanize($name));
        $notes = [];
        if (isset($fields[$name . 'En'])) {
            $notes[] = 'Czech + English';
        }
        if ($hint !== '' && $field['type'] !== 'int' && preg_match('/Markdown|English/i', $hint) !== 1) {
            $notes[] = $hint;
        }
        if (isset($field['about'])) {
            $notes[] = $field['about'];
        }
        $auto = $field['auto'] ?? null;
        if ($auto === 'creator') {
            $notes[] = 'filled in automatically: the user who created it';
        } elseif ($auto === 'editor') {
            $notes[] = 'filled in automatically: the user who saved it last';
        }
        if ($field['type'] === 'bool' && ($field['default'] ?? false)) {
            $notes[] = 'ticked by default';
        }

        $lines[] = sprintf(
            '| %s | %s | %s | %s |',
            $label,
            describeType($field, $entityByRepository),
            ($field['required'] ?? false) && $auto === null ? 'yes' : '',
            implode('; ', $notes),
        );
    }
    $lines[] = '';
}

array_push(
    $lines,
    '## Adding something new (for example publications)',
    '',
    'Another kind of record can be added in the same way. To describe it, please send, for each field:',
    'its name, what it holds (text, number, date, yes/no, a choice from a list, a link to another record, a file),',
    'whether it is required, and whether it needs a Czech and an English version. For example:',
    '',
    '| Field | What it holds | Required | Note |',
    '|---|---|---|---|',
    '| Title | short text | yes | Czech + English |',
    '| Authors | short text | yes | e.g. "Novák J., Adler E." |',
    '| Year | whole number | yes | |',
    '| Journal | short text | | |',
    '| Link (DOI) | short text | | |',
    '| PDF file | file | | uploaded like a photo |',
    '| Visible | yes / no | | ticked by default |',
    '',
    'Please also say how the records should appear on the site (for example: a list sorted by year, grouped by year,',
    'or one page per record).',
    '',
);

if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0775, true);
}
file_put_contents($output, implode("\n", $lines));
echo "Written {$output}\n";
