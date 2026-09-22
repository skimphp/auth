<?php declare(strict_types=1);

namespace Skim\Auth\Middleware;

use Skim\Auth\Auth;
use Skim\Core\Middleware;
use Skim\Core\Request;
use Skim\Core\Response;

final class GuestMiddleware implements Middleware {
    public function handle(Request $req, Response $res, callable $next): mixed {
        if (Auth::check()) {
            return $res->redirect((string) config('skim_auth.after_login', '/dashboard'));
        }

        return $next($req, $res);
    }
}
