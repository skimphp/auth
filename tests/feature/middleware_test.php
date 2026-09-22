<?php declare(strict_types=1);

use Skim\Auth\Middleware\AuthMiddleware;
use Skim\Core\App;
use Skim\Testing\HttpClient;

it('auth_middleware redirects guest', function(): void {
    $app = App::testInstance();
    $app->router->get('/secret', fn($req, $res) => $res->json(['ok' => true]), middleware: [AuthMiddleware::class]);

    (new HttpClient($app))->get('/secret')->assertRedirect('/login');
});

it('auth_middleware passes authenticated user', function(): void {
    $app = App::testInstance();
    $app->router->get('/secret', fn($req, $res) => $res->json(['ok' => true]), middleware: [AuthMiddleware::class]);

    (new HttpClient($app))
        ->actingAs(mockUser())
        ->get('/secret')
        ->assertOk();
});
