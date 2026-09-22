<?php declare(strict_types=1);

namespace Skim\Auth;

use Skim\Auth\Cli\AuthPublishCommand;
use Skim\Auth\Controllers\LoginController;
use Skim\Auth\Controllers\PasswordController;
use Skim\Auth\Controllers\RegisterController;
use Skim\Auth\Controllers\SocialController;
use Skim\Auth\Controllers\VerifyController;
use Skim\Auth\Middleware\LoginRateLimit;
use Skim\Auth\Social\ProviderFactory;
use Skim\Auth\Social\SocialAuthService;
use Skim\Core\App;
use Skim\Ext\Extension;

final class AuthExtension extends Extension {
    public string $name = 'auth';
    public string $version = '1.0.0';
    public string $description = 'Authentication, email verification, password reset, and social login for SKIM applications.';

    public array $requires = ['session'];
    public array $provides = ['auth'];
    public array $capabilities = ['auth', 'oauth', 'session-auth', 'email-verification', 'password-reset', 'rate-limiting'];

    public function register(App $app): void {
        $app->bind(AuthService::class, fn(): AuthService => new AuthService());
        $app->bind('auth', fn(App $app): AuthService => $app->make(AuthService::class));
        $app->bind(ProviderFactory::class, fn(): ProviderFactory => new ProviderFactory());
        $app->bind(SocialAuthService::class, fn(App $app): SocialAuthService => new SocialAuthService(
            $app->make(AuthService::class),
        ));
    }

    public function boot(App $app): void {
        $app->router->get('/login', [LoginController::class, 'show']);
        $app->router->post('/login', [LoginController::class, 'store'])
            ->middleware(LoginRateLimit::class);

        $app->router->get('/register', [RegisterController::class, 'show']);
        $app->router->post('/register', [RegisterController::class, 'store']);

        $app->router->get('/forgot-password', [PasswordController::class, 'forgot']);
        $app->router->post('/forgot-password', [PasswordController::class, 'send_reset']);
        $app->router->get('/reset-password/@token', [PasswordController::class, 'reset']);
        $app->router->post('/reset-password', [PasswordController::class, 'update']);

        $app->router->get('/email/verify', [VerifyController::class, 'notice']);
        $app->router->get('/email/verify/@id:int/@hash', [VerifyController::class, 'verify']);
        $app->router->post('/email/resend', [VerifyController::class, 'resend']);

        $app->router->get('/auth/@provider', [SocialController::class, 'redirect']);
        $app->router->get('/auth/@provider/callback', [SocialController::class, 'callback']);

        $app->router->command('auth:publish', [AuthPublishCommand::class, 'handle']);
    }

    public function migrations(): string {
        return dirname(__DIR__) . '/migrations';
    }

    public function config(): array {
        return require dirname(__DIR__) . '/config/skim_auth.php';
    }

    public function commands(): array {
        return ['auth:publish' => AuthPublishCommand::class];
    }

    public function envKeys(): array {
        return [
            'GOOGLE_CLIENT_ID',
            'GOOGLE_CLIENT_SECRET',
            'GITHUB_CLIENT_ID',
            'GITHUB_CLIENT_SECRET',
            'FACEBOOK_CLIENT_ID',
            'FACEBOOK_CLIENT_SECRET',
        ];
    }

    public function postInstall(): array {
        return [
            'Run: php skim auth:publish all',
            'Add social provider keys to .env to enable social login',
            'Run: php skim migrate:status to verify migrations',
        ];
    }

    public static function manifest(): array {
        return [
            'name'        => 'auth',
            'version'     => '1.0.0',
            'description' => 'Authentication, email verification, password reset, and social login for SKIM applications.',
            'requires'    => ['session'],
            'provides'    => ['auth'],
            'conflicts'   => [],
            'capabilities' => [
                'auth' => [
                    'strategies'    => ['session', 'api-token'],
                    'middleware'    => 'auth_middleware',
                    'routes_prefix' => '/auth',
                ],
                'oauth' => [
                    'providers' => ['google', 'github', 'facebook'],
                ],
                'session-auth'       => [],
                'email-verification' => [
                    'routes' => ['/email/verify', '/email/resend'],
                ],
                'password-reset' => [
                    'routes' => ['/forgot-password', '/reset-password'],
                ],
                'rate-limiting' => [
                    'applies_to' => ['POST /login'],
                ],
            ],
            'migrations'   => true,
            'commands'     => ['auth:publish'],
            'env_keys'     => [
                'GOOGLE_CLIENT_ID',
                'GOOGLE_CLIENT_SECRET',
                'GITHUB_CLIENT_ID',
                'GITHUB_CLIENT_SECRET',
                'FACEBOOK_CLIENT_ID',
                'FACEBOOK_CLIENT_SECRET',
            ],
            'post_install' => [
                'Run: php skim auth:publish all',
                'Add social provider keys to .env to enable social login',
                'Run: php skim migrate:status to verify migrations',
            ],
        ];
    }
}
