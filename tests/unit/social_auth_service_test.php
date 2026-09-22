<?php declare(strict_types=1);

use Skim\Auth\Social\SocialAuthService;

it('links oauth account to existing user by email', function(): void {
    $user = mockUser(email: 'john@example.com');
    $service = new SocialAuthService(mockUserRepo($user));
    $result = $service->resolve('google', mockOauthUser(email: 'john@example.com'));

    expect($result->id)->toBe($user->id);
});

it('creates new user when no email match', function(): void {
    $service = new SocialAuthService(mockEmptyUserRepo());
    $result = $service->resolve('google', mockOauthUser(email: 'new@example.com'));

    expect($result->email)->toBe('new@example.com');
});
