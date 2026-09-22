<?php declare(strict_types=1);

namespace Skim\Auth\Middleware;

use Skim\Auth\Auth;
use Skim\Core\Middleware;
use Skim\Core\Request;
use Skim\Core\Response;

final class AuthMiddleware implements Middleware {
    public function handle(Request $req, Response $res, callable $next): mixed {
        if (Auth::guest()) {
            if ($req->isJson()) {
                return $res->status(401)->json(['error' => 'Unauthenticated']);
            }

            return $res->redirect('/login');
        }

        return $next($req, $res);
    }
}
