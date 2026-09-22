<?php declare(strict_types=1);

use Skim\Auth\Social\ProviderFactory;

it('throws install hint when oauth package missing', function(): void {
    expect(fn() => ProviderFactory::make('google', []))
        ->toThrow(RuntimeException::class, 'composer require league/oauth2-google');
});
