<?php declare(strict_types=1);

namespace Skim\Auth;

use Skim\Core\App;

final class Auth {
    public static function attempt(string $email, string $password, bool $remember = false): bool {
        return self::service()->attempt($email, $password, $remember);
    }

    public static function login(object $user, bool $remember = false): void {
        self::service()->login($user, $remember);
    }

    public static function logout(): void {
        self::service()->logout();
    }

    public static function user(): ?object {
        return self::service()->user();
    }

    public static function id(): ?int {
        return self::service()->id();
    }

    public static function check(): bool {
        return self::service()->check();
    }

    public static function guest(): bool {
        return self::service()->guest();
    }

    public static function fake(object $user): void {
        self::service()->fake($user);
    }

    public static function assertAuthenticated(): void {
        if (self::guest()) {
            throw new \RuntimeException('Expected an authenticated user.');
        }
    }

    public static function assertGuest(): void {
        if (self::check()) {
            throw new \RuntimeException('Expected a guest user.');
        }
    }

    public static function assertUser(object $user): void {
        $current = self::user();
        if ($current === null || (string) ($current->id ?? '') !== (string) ($user->id ?? '')) {
            throw new \RuntimeException('Expected authenticated user did not match.');
        }
    }

    private static function service(): object {
        return App::instance()->make(AuthService::class);
    }
}
