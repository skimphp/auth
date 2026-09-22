<?php declare(strict_types=1);

use Skim\Auth\AuthService;

it('rejects wrong password', function(): void {
    $service = new AuthService();
    expect($service->verifyPassword('wrong', password_hash('correct', PASSWORD_BCRYPT)))
        ->toBeFalse();
});

it('accepts correct password', function(): void {
    $service = new AuthService();
    expect($service->verifyPassword('correct', password_hash('correct', PASSWORD_BCRYPT)))
        ->toBeTrue();
});
