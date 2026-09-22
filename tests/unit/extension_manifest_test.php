<?php declare(strict_types=1);

use Skim\Auth\AuthExtension;

it('is inspectable without calling register or boot', function(): void {
    $ext = new AuthExtension();

    expect($ext->capabilities)->toContain('auth');
    expect($ext->capabilities)->toContain('oauth');
    expect($ext->requires)->toContain('session');
    expect($ext->migrations())->not->toBeEmpty();
});
