# SKIM Auth Extension

`skim/auth` provides authentication, email verification, password reset, and social logins (Google, GitHub, Facebook) for SKIM applications.

## Integration & Installation

To install the extension, add it to your SKIM application's `composer.json` or require it:

```bash
composer require skim/auth
```

Because of SKIM Framework's automatic discovery, the framework will detect the extension, perform dependency validation (`session` capability must be active), and boot the authentication services automatically.

## Configuration

Publish the configuration file using the built-in SKIM CLI:

```bash
php skim auth:publish all
```

This will copy the default auth configuration to your application's `config/auth.php` and set up any default database migrations. Run the database migrations using:

```bash
php skim migrate:status
php skim migrate
```

Configure your environment variables in `.env` for OAuth providers if social login is enabled:

```dotenv
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
```

## Code Examples & Usage

### 1. Registering/Logging In Users

```php
use Skim\Auth\AuthService;

// Obtain the authentication service from the app DI container
$auth = app()->make(auth_service::class);

// Or retrieve via the bound shorthand
$auth = app('auth');

// Attempt credentials login
try {
    $session = $auth->attempt([
        'email'    => 'user@example.com',
        'password' => 'secret-password'
    ]);
    
    // User is now authenticated
    $user = $auth->user();
} catch (\Exception $e) {
    // Handle invalid credentials
}
```

### 2. Guarding Routes via Middleware

Secure your routes using the built-in `auth_middleware` (provided by the `auth` capability):

```php
// routes/web.php or inside boot()
$app->router->get('/dashboard', [dashboard_controller::class, 'show'])
    ->middleware(\Skim\Auth\Middleware\AuthMiddleware::class);
```

### 3. Social OAuth Authentication

The extension automatically exposes routes for OAuth redirection and callbacks based on configuration:

```php
// Redirect to Github
return response()->redirect(app('auth')->oauth('github')->get_redirect_url());
```
