<?php declare(strict_types=1);

namespace Skim\Auth\Controllers;

use Skim\Auth\AuthService;
use Skim\Auth\AuthView;
use Skim\Core\Request;
use Skim\Core\Response;

final class PasswordController {
    public function forgot(Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        return $this->render($res, 'forgot_password');
    }

    public function sendReset(Request $req, Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        $email = strtolower(trim((string) $req->post('email', '')));
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $auth->findUserByEmail($email) : null;

        if ($user !== null) {
            $token = $auth->createPasswordReset($email);
            $auth->sendPasswordResetEmail($user, $this->baseUrl($req) . '/reset-password/' . $token);
        }

        return $this->render($res, 'forgot_password', [
            'status' => 'If the email exists, a reset link has been sent.',
            'old'    => ['email' => $email],
        ]);
    }

    public function reset(string $token, Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        return $this->render($res, 'reset_password', ['token' => $token]);
    }

    public function update(Request $req, Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        $email = strtolower(trim((string) $req->post('email', '')));
        $token = (string) $req->post('token', '');
        $password = (string) $req->post('password', '');
        $confirm = (string) $req->post('password_confirm', '');
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        $reset = $errors === [] ? $auth->validPasswordReset($email, $token) : null;
        $user = $reset !== null ? $auth->findUserByEmail($email) : null;
        if ($errors === [] && ($reset === null || $user === null)) {
            $errors['token'] = 'This reset link is invalid or expired.';
        }

        if ($errors !== []) {
            return $this->render($res->status(422), 'reset_password', [
                'errors' => $errors,
                'token'  => $token,
                'old'    => ['email' => $email],
            ]);
        }

        $auth->updatePassword((int) $user->id, $password);
        $auth->deletePasswordResets($email);

        return $res->redirect('/login');
    }

    private function disabled(Response $res): Response {
        return $res->status(501)->setBody(AuthService::PASSWORD_RESET_MAILER_REQUIRED);
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
