<?php declare(strict_types=1);

namespace Skim\Auth\Controllers;

use Skim\Auth\AuthService;
use Skim\Auth\AuthView;
use Skim\Core\Request;
use Skim\Core\Response;
use Skim\Log\Log;

final class RegisterController {
    public function show(Response $res): Response {
        return $this->render($res, 'register');
    }

    public function store(Request $req, Response $res, AuthService $auth): Response {
        $name = trim((string) $req->post('name', ''));
        $email = strtolower(trim((string) $req->post('email', '')));
        $password = (string) $req->post('password', '');
        $confirm = (string) $req->post('password_confirm', '');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        } elseif ($auth->findUserByEmail($email) !== null) {
            $errors['email'] = 'That email is already registered.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            return $this->render($res->status(422), 'register', [
                'errors' => $errors,
                'old'    => ['name' => $name, 'email' => $email],
            ]);
        }

        $verify = (bool) config('skim_auth.verify_email', true);
        $verified_at = $verify && $auth->mailerAvailable() ? null : $auth->now();
        if ($verify && !$auth->mailerAvailable()) {
            Log::warning(AuthService::VERIFY_MAILER_REQUIRED);
        }

        $user = $auth->createUser($name, $email, $password, $verified_at);
        $auth->login($user);

        if ($verify && $auth->mailerAvailable()) {
            $auth->sendVerificationEmail($user, $this->baseUrl($req) . '/email/verify/' . (int) $user->id . '/' . $auth->verificationHash($user));
        }

        return $res->redirect((string) config('skim_auth.after_register', '/dashboard'));
    }

    private function render(Response $res, string $view, array $data = []): Response {
        return $res->setBody(AuthView::render($view, $data));
    }

    private function baseUrl(Request $req): string {
        $parts = parse_url($req->url());
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}
