<?php declare(strict_types=1);

use Skim\Core\App;
use Skim\Testing\HttpClient;

it('redirects to dashboard on valid credentials', function(): void {
    $app = App::testInstance(['skim_auth' => ['after_login' => '/dashboard']]);
    $app->router->post('/login', fn($req, $res) => $res->redirect('/dashboard'));

    (new HttpClient($app))
        ->post('/login', ['email' => 'user@example.com', 'password' => 'secret'])
        ->assertRedirect('/dashboard');
});

it('returns 422 on invalid credentials', function(): void {
    $app = App::testInstance();
    $app->router->post('/login', fn($req, $res) => $res->status(422)->json(['error' => 'Invalid credentials']));

    (new HttpClient($app))
        ->post('/login', ['email' => 'x@x.com', 'password' => 'bad'])
        ->assertUnprocessable();
});
