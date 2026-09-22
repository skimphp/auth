<?php declare(strict_types=1);

namespace Skim\Auth\Controllers;

use Skim\Auth\AuthService;
use Skim\Auth\AuthView;
use Skim\Core\Request;
use Skim\Core\Response;

final class LoginController {
    public function show(Response $res): Response {
        return $this->render($res, 'login');
    }

    public function store(Request $req, Response $res, AuthService $auth): Response {
        $email = (string) $req->post('email', '');
        $password = (string) $req->post('password', '');
        $remember = (bool) $req->post('remember', false);

        if (!$auth->attempt($email, $password, $remember)) {
            return $this->render($res->status(422), 'login', [
                'errors' => ['email' => 'Invalid email or password.'],
                'old'    => ['email' => $email],
            ]);
        }

        return $res->redirect((string) config('skim_auth.after_login', '/dashboard'));
    }

    private function render(Response $res, string $view, array $data = []): Response {
        return $res->setBody(AuthView::render($view, $data));
    }
}
