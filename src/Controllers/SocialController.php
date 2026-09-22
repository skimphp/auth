<?php declare(strict_types=1);

namespace Skim\Auth\Controllers;

use Skim\Auth\Social\ProviderFactory;
use Skim\Auth\Social\SocialAuthService;
use Skim\Core\Request;
use Skim\Core\Response;
use Skim\Session\Session;

final class SocialController {
    public function redirect(string $provider, Request $req, Response $res, ProviderFactory $factory): Response {
        try {
            $oauth = $factory->make($provider, $this->callbackUrl($req, $provider));
            $url = $oauth->getAuthorizationUrl();
            Session::set('skim_auth_oauth_state', $oauth->getState());

            return $res->redirect($url);
        } catch (\Throwable $e) {
            return $res->status(400)->setBody($e->getMessage());
        }
    }

    public function callback(string $provider, Request $req, Response $res, ProviderFactory $factory, SocialAuthService $social): Response {
        try {
            $state = (string) $req->input('state', '');
            if ($state === '' || $state !== (string) Session::get('skim_auth_oauth_state', '')) {
                return $res->status(400)->setBody('Invalid OAuth state.');
            }

            $code = (string) $req->input('code', '');
            if ($code === '') {
                return $res->status(400)->setBody('OAuth callback is missing a code.');
            }

            $oauth = $factory->make($provider, $this->callbackUrl($req, $provider));
            $social->handleCallback($provider, $oauth, $code);

            return $res->redirect((string) config('skim_auth.after_login', '/dashboard'));
        } catch (\Throwable $e) {
            return $res->status(400)->setBody($e->getMessage());
        }
    }

    private function callbackUrl(Request $req, string $provider): string {
        $parts = parse_url($req->url());
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}/auth/{$provider}/callback";
    }
}
