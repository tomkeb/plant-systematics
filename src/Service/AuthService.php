<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Request;
use App\Repository\UserRepository;

/**
 * Email + password login with session hardening and per-IP failure throttling.
 */
final class AuthService
{
    private const SessionKey = 'userId';

    public function __construct(
        private readonly UserRepository $users,
        private readonly Config $config,
        private readonly Request $request,
    ) {
    }

    /**
     * @return array<string, mixed>|null the logged-in user row
     */
    public function user(): ?array
    {
        $id = $_SESSION[self::SessionKey] ?? null;
        return $id === null ? null : $this->users->find((int) $id);
    }

    public function isAdmin(): bool
    {
        return !empty($this->user()['adminRights']);
    }

    public function isThrottled(): bool
    {
        return count($this->recentFailures()) >= $this->config->get('login.maxFailures');
    }

    public function login(string $email, string $password): bool
    {
        if ($this->isThrottled()) {
            return false;
        }

        $user = $this->users->findOneBy(['email', '=', mb_strtolower($email)]);
        // Verify against a dummy hash for unknown users to keep timing similar.
        $hash = $user['passwordHash'] ?? '$2y$10$usesomesillystringforsalt.usesomesillystringfore.8GXxJ6dGxGm';
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            $this->recordFailure();
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SessionKey] = $user['_id'];
        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @return list<int> timestamps of failed attempts from this IP inside the window
     */
    private function recentFailures(): array
    {
        $all = $this->readThrottle();
        $window = time() - $this->config->get('login.windowSeconds');
        return array_values(array_filter($all[$this->request->ip] ?? [], fn(int $t) => $t > $window));
    }

    private function recordFailure(): void
    {
        $all = $this->readThrottle();
        $all[$this->request->ip] = [...$this->recentFailures(), time()];
        file_put_contents($this->throttleFile(), json_encode($all), LOCK_EX);
    }

    /**
     * @return array<string, list<int>>
     */
    private function readThrottle(): array
    {
        $file = $this->throttleFile();
        return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    private function throttleFile(): string
    {
        return $this->config->get('paths.var') . '/login-throttle.json';
    }
}
