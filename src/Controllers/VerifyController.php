<?php declare(strict_types=1);

namespace Skim\Auth\Controllers;

use Skim\Auth\Auth;
use Skim\Auth\AuthService;
use Skim\Auth\AuthView;
use Skim\Core\Request;
use Skim\Core\Response;

final class VerifyController {
    public function notice(Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        return $this->render($res, 'verify_email');
    }

    public function verify(string $id, string $hash, Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        $user_id = (int) $id;
        $user = $auth->findUserById($user_id);
        if ($user === null || !hash_equals($auth->verificationHash($user), $hash)) {
            return $res->status(403)->setBody('Invalid verification link.');
        }

        $auth->markVerified($user_id);
        if (Auth::id() === $user_id) {
            $fresh = $auth->findUserById($user_id);
            if ($fresh !== null) {
                $auth->login($fresh);
            }
        }

        return $res->redirect((string) config('skim_auth.after_login', '/dashboard'));
    }

    public function resend(Request $req, Response $res, AuthService $auth): Response {
        if (!$auth->mailerAvailable()) {
            return $this->disabled($res);
        }

        $user = Auth::user();
        if ($user === null) {
            return $res->redirect('/login');
        }

        if (!empty($user->email_verified_at)) {
            return $res->redirect((string) config('skim_auth.after_login', '/dashboard'));
        }

        $auth->sendVerificationEmail($user, $this->baseUrl($req) . '/email/verify/' . (int) $user->id . '/' . $auth->verificationHash($user));

        return $this->render($res, 'verify_email', ['status' => 'Verification email sent.']);
    }

    private function disabled(Response $res): Response {
        return $res->status(501)->setBody(AuthService::VERIFY_MAILER_REQUIRED);
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
