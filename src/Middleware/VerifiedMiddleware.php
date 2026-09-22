<?php declare(strict_types=1);

namespace Skim\Auth\Middleware;

use Skim\Auth\Auth;
use Skim\Core\Middleware;
use Skim\Core\Request;
use Skim\Core\Response;

final class VerifiedMiddleware implements Middleware {
    public function handle(Request $req, Response $res, callable $next): mixed {
        $user = Auth::user();
        if ($user !== null && (bool) config('skim_auth.verify_email', true) && empty($user->email_verified_at)) {
            if ($req->isJson()) {
                return $res->status(403)->json(['error' => 'Email address is not verified']);
            }

            return $res->redirect('/email/verify');
        }

        return $next($req, $res);
    }
}
