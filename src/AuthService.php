<?php declare(strict_types=1);

namespace Skim\Auth;

use Skim\Db\Db;
use Skim\Log\Log;
use Skim\Session\Session;

final class AuthService {
    public const PASSWORD_RESET_MAILER_REQUIRED = 'Password reset requires skim/mailer — run: composer require skim/mailer';
    public const VERIFY_MAILER_REQUIRED = 'Email verification requires skim/mailer — run: composer require skim/mailer';

    private const SESSION_KEY = 'skim_auth_user_id';
    private const REMEMBER_COOKIE = 'skim_remember';
    private const CACHE_MISSING = '__skim_auth_missing__';

    private ?object $fake_user = null;
    private bool $has_fake_user = false;

    public function attempt(string $email, string $password, bool $remember = false): bool {
        $user = $this->findUserByEmail($email);

        if ($user === null || empty($user->password) || !$this->verifyPassword($password, (string) $user->password)) {
            return false;
        }

        $this->login($user, $remember);
        return true;
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public function login(object $user, bool $remember = false): void {
        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user->id);
        $this->cacheUser($user);

        if ($remember && (bool) config('skim_auth.remember_me', true)) {
            $this->remember($user);
        }
    }

    public function logout(): void {
        $user = $this->user();
        if ($user !== null) {
            Db::query(
                'UPDATE ' . $this->table('users') . ' SET remember_token = NULL WHERE id = :id',
                [':id' => (int) $user->id],
            );
        }

        Session::delete(self::SESSION_KEY);
        Session::regenerate();
        $this->clearRememberCookie();
        $this->cacheUser(null);
        $this->fake_user = null;
        $this->has_fake_user = false;
    }

    public function user(): ?object {
        if ($this->has_fake_user) {
            return $this->fake_user;
        }

        $cached = appUserGet('auth.user', self::CACHE_MISSING);
        if ($cached !== self::CACHE_MISSING) {
            return is_object($cached) ? $cached : null;
        }

        $id = Session::get(self::SESSION_KEY);
        if ($id !== null) {
            return $this->cacheUser($this->findUserById((int) $id));
        }

        return $this->cacheUser($this->userFromRememberCookie());
    }

    public function id(): ?int {
        $user = $this->user();

        return $user !== null ? (int) $user->id : null;
    }

    public function check(): bool {
        return $this->user() !== null;
    }

    public function guest(): bool {
        return !$this->check();
    }

    public function fake(object $user): void {
        $this->fake_user = $user;
        $this->has_fake_user = true;
        $this->cacheUser($user);
    }

    public function findUserById(int $id): ?object {
        $row = Db::row('SELECT * FROM ' . $this->table('users') . ' WHERE id = :id', [':id' => $id]);

        return $row !== null ? (object) $row : null;
    }

    public function findUserByEmail(string $email): ?object {
        $row = Db::row('SELECT * FROM ' . $this->table('users') . ' WHERE email = :email', [
            ':email' => strtolower(trim($email)),
        ]);

        return $row !== null ? (object) $row : null;
    }

    public function createUser(string $name, string $email, ?string $password, ?string $email_verified_at = null): object {
        $values = [
            'name'              => trim($name),
            'email'             => strtolower(trim($email)),
            'password'          => $password !== null ? password_hash($password, PASSWORD_BCRYPT) : null,
            'email_verified_at' => $email_verified_at,
            'created_at'        => $this->now(),
            'updated_at'        => $this->now(),
        ];

        if ($this->driver() === 'pgsql') {
            $rows = Db::query('INSERT INTO ' . $this->table('users') . ' %values% RETURNING id', ['values' => $values]);
            $id = (int) ($rows[0]['id'] ?? 0);
        } else {
            Db::query('INSERT INTO ' . $this->table('users') . ' %values%', ['values' => $values]);
            $id = (int) Db::pdo()->lastInsertId();
        }

        return $this->findUserById($id) ?? throw new \RuntimeException('Unable to load created user.');
    }

    public function updatePassword(int $user_id, string $password): void {
        Db::query(
            'UPDATE ' . $this->table('users') . ' SET password = :password, updated_at = :updated_at WHERE id = :id',
            [
                ':id'         => $user_id,
                ':password'   => password_hash($password, PASSWORD_BCRYPT),
                ':updated_at' => $this->now(),
            ],
        );
    }

    public function markVerified(int $user_id): void {
        Db::query(
            'UPDATE ' . $this->table('users') . ' SET email_verified_at = :verified_at, updated_at = :updated_at WHERE id = :id',
            [
                ':id'          => $user_id,
                ':verified_at' => $this->now(),
                ':updated_at'  => $this->now(),
            ],
        );
    }

    public function createPasswordReset(string $email): string {
        $token = bin2hex(random_bytes(32));
        $normalized = strtolower(trim($email));

        Db::query('DELETE FROM ' . $this->table('password_resets') . ' WHERE email = :email', [
            ':email' => $normalized,
        ]);
        Db::query('INSERT INTO ' . $this->table('password_resets') . ' %values%', [
            'values' => [
                'email'      => $normalized,
                'token'      => hash('sha256', $token),
                'created_at' => $this->now(),
            ],
        ]);

        return $token;
    }

    public function validPasswordReset(string $email, string $token): ?object {
        $row = Db::row('SELECT * FROM ' . $this->table('password_resets') . ' WHERE email = :email AND token = :token', [
            ':email' => strtolower(trim($email)),
            ':token' => hash('sha256', $token),
        ]);

        if ($row === null) {
            return null;
        }

        $created = strtotime((string) $row['created_at']);
        if ($created === false || $created < time() - 3600) {
            return null;
        }

        return (object) $row;
    }

    public function deletePasswordResets(string $email): void {
        Db::query('DELETE FROM ' . $this->table('password_resets') . ' WHERE email = :email', [
            ':email' => strtolower(trim($email)),
        ]);
    }

    public function sendVerificationEmail(object $user, string $url): void {
        if (!$this->mailerAvailable()) {
            Log::warning(self::VERIFY_MAILER_REQUIRED);
            return;
        }

        \Skim\Mailer\Mailer::to((string) $user->email)
            ->subject('Verify your email address')
            ->html(AuthView::render('emails/verify_email', ['user' => $user, 'url' => $url]))
            ->send();
    }

    public function sendPasswordResetEmail(object $user, string $url): void {
        if (!$this->mailerAvailable()) {
            Log::warning(self::PASSWORD_RESET_MAILER_REQUIRED);
            return;
        }

        \Skim\Mailer\Mailer::to((string) $user->email)
            ->subject('Reset your password')
            ->html(AuthView::render('emails/reset_password', ['user' => $user, 'url' => $url]))
            ->send();
    }

    public function mailerAvailable(): bool {
        return class_exists(\Skim\Mailer\Mailer::class);
    }

    public function verificationHash(object $user): string {
        return sha1((string) $user->email);
    }

    public function table(string $suffix): string {
        return (string) config('skim_auth.table_prefix', 'skim_') . $suffix;
    }

    public function now(): string {
        return gmdate('Y-m-d H:i:s');
    }

    private function remember(object $user): void {
        $token = bin2hex(random_bytes(32));
        Db::query(
            'UPDATE ' . $this->table('users') . ' SET remember_token = :token, updated_at = :updated_at WHERE id = :id',
            [
                ':id'         => (int) $user->id,
                ':token'      => password_hash($token, PASSWORD_BCRYPT),
                ':updated_at' => $this->now(),
            ],
        );

        setcookie(
            self::REMEMBER_COOKIE,
            (int) $user->id . '|' . $token,
            [
                'expires'  => time() + ((int) config('skim_auth.remember_days', 30) * 86400),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ],
        );
    }

    private function userFromRememberCookie(): ?object {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (!is_string($cookie) || !str_contains($cookie, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $cookie, 2);
        $user = $this->findUserById((int) $id);

        if ($user === null || empty($user->remember_token) || !password_verify($token, (string) $user->remember_token)) {
            $this->clearRememberCookie();
            return null;
        }

        Session::set(self::SESSION_KEY, (int) $user->id);
        return $user;
    }

    private function clearRememberCookie(): void {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function cacheUser(?object $user): ?object {
        appUserSet('auth.user', $user ?? false);

        return $user;
    }

    private function driver(): string {
        return (string) Db::pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
}

function appUserGet(string $key, mixed $default = null): mixed {
    return \Skim\Core\App::instance()->get('user.' . $key, $default);
}

function appUserSet(string $key, mixed $value): void {
    \Skim\Core\App::instance()->set('user.' . $key, $value);
}
