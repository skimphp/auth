<?php declare(strict_types=1);

namespace Skim\Auth\Middleware;

use Skim\Middleware\RateLimit;

final class LoginRateLimit extends RateLimit {
    public function __construct() {
        parent::__construct(5, 60, 'auth:login:');
    }
}
