# skim/auth

`skim/auth` is a SKIM extension for credential login, registration, password reset,
email verification, remember-me cookies, and optional OAuth social login.

## Install

```bash
composer require skim/auth
php skim ext:install skim/auth
php skim auth:publish all
```

Migrations are tracked as `skim/auth: <filename>` in `_migrations`. Auth table
names use `config('skim_auth.table_prefix')`, defaulting to `skim_`.

## Facade

```php
use Skim\Auth\Auth;

auth::check();
auth::user();
auth::id();
auth::attempt($email, $password, remember: true);
auth::login($user);
auth::logout();
auth::fake($user);
```

## Middleware

```php
use Skim\Auth\Middleware\AuthMiddleware;
use Skim\Auth\Middleware\VerifiedMiddleware;

$app->router->group('/dashboard', function($r) {
    $r->get('/', [dashboard_controller::class, 'index']);
}, middleware: [auth_middleware::class, verified_middleware::class]);
```

## Mailer Soft Dependency

Credential registration, login, and social login work without `skim/mailer`.
Password reset returns HTTP 501 until `skim/mailer` is installed. Email
verification is disabled without `skim/mailer`, and new users are marked verified
immediately with a log warning.

## Social Login

Set provider credentials in `.env`; enabled providers appear in the packaged
login/register views automatically.

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
```
