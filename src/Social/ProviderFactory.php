<?php declare(strict_types=1);

namespace Skim\Auth\Social;

use League\OAuth2\Client\Provider\AbstractProvider;

final class ProviderFactory {
    public static function make(string $provider, string|array $redirect_uri = ''): AbstractProvider {
        $provider = strtolower($provider);
        $config = is_array($redirect_uri)
            ? $redirect_uri
            : (array) config("skim_auth.social.{$provider}", []);

        if (!is_array($redirect_uri) && !(bool) ($config['enabled'] ?? false)) {
            throw new \RuntimeException(ucfirst($provider) . ' login is not enabled.');
        }

        $class = self::providerClass($provider);
        if (!class_exists($class)) {
            throw new \RuntimeException(ucfirst($provider) . ' login requires: composer require league/oauth2-' . $provider);
        }

        return new $class([
            'clientId'     => (string) ($config['client_id'] ?? ''),
            'clientSecret' => (string) ($config['client_secret'] ?? ''),
            'redirectUri'  => is_string($redirect_uri) ? $redirect_uri : (string) ($config['redirect_uri'] ?? ''),
        ]);
    }

    private static function providerClass(string $provider): string {
        return match ($provider) {
            'google'   => 'League\\OAuth2\\Client\\Provider\\Google',
            'github'   => 'League\\OAuth2\\Client\\Provider\\Github',
            'facebook' => 'League\\OAuth2\\Client\\Provider\\Facebook',
            default    => throw new \InvalidArgumentException("Unsupported social provider: {$provider}"),
        };
    }
}
