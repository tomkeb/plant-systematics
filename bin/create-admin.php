<?php

declare(strict_types=1);

// Usage: php bin/create-admin.php <email> <password> <name> <surname>
// Creates an admin user, or resets the password of an existing one with the same email.

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Config;
use App\Repository\UserRepository;

if ($argc < 5) {
    fwrite(STDERR, "Usage: php bin/create-admin.php <email> <password> <name> <surname>\n");
    exit(1);
}
[, $email, $password, $name, $surname] = $argv;
$email = mb_strtolower($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
    fwrite(STDERR, "Invalid email or password shorter than 8 characters.\n");
    exit(1);
}

$users = new UserRepository(new Config(require dirname(__DIR__) . '/config/config.php'));
$data = [
    'name' => $name,
    'surname' => $surname,
    'email' => $email,
    'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
    'adminRights' => true,
];
$existing = $users->findOneBy(['email', '=', $email]);
if ($existing === null) {
    $users->insert($data + ['registered' => date('Y-m-d')]);
    echo "Created admin {$email}\n";
} else {
    $users->update($existing['_id'], $data);
    echo "Updated admin {$email}\n";
}
