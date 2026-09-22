<?php declare(strict_types=1);

namespace Skim\Auth\Social;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Skim\Auth\AuthService;
use Skim\Db\Db;

final class SocialAuthService {
    public function __construct(
        private readonly object $auth,
    ) {}

    public function resolve(string $provider_name, object $oauth_user): object {
        $email = strtolower((string) $this->oauthValue($oauth_user, 'email', 'getEmail'));
        $name = (string) ($this->oauthValue($oauth_user, 'name', 'getName') ?: $email);

        $existing = $this->repoFindByEmail($email);
        if ($existing !== null) {
            return $existing;
        }

        if (method_exists($this->auth, 'createUser')) {
            return $this->auth->createUser($name, $email, null, method_exists($this->auth, 'now') ? $this->auth->now() : null);
        }

        if (method_exists($this->auth, 'create')) {
            return $this->auth->create([
                'provider' => $provider_name,
                'name'     => $name,
                'email'    => $email,
            ]);
        }

        throw new \RuntimeException('OAuth user repository cannot create users.');
    }

    public function handleCallback(string $provider_name, AbstractProvider $provider, string $code): object {
        $token = $provider->getAccessToken('authorization_code', ['code' => $code]);
        $owner = $provider->getResourceOwner($token);
        $profile = $this->profile($owner);

        if ($profile['id'] === '') {
            throw new \RuntimeException('OAuth provider did not return an account id.');
        }

        $user = Db::transaction(function() use ($provider_name, $profile, $token): object {
            $existing = Db::row(
                'SELECT * FROM ' . $this->auth->table('social_accounts') . ' WHERE provider = :provider AND provider_id = :provider_id',
                [
                    ':provider'    => $provider_name,
                    ':provider_id' => $profile['id'],
                ],
            );

            if ($existing !== null) {
                return $this->auth->findUserById((int) $existing['user_id'])
                    ?? throw new \RuntimeException('Linked social account user was not found.');
            }

            $user = $profile['email'] !== '' ? $this->auth->findUserByEmail($profile['email']) : null;
            if ($user === null) {
                $user = $this->auth->createUser(
                    $profile['name'] !== '' ? $profile['name'] : $profile['email'],
                    $profile['email'],
                    null,
                    $this->auth->now(),
                );
            }

            $this->linkAccount($user, $provider_name, $profile['id'], $token->getToken());

            return $user;
        });

        $this->auth->login($user);
        return $user;
    }

    private function linkAccount(object $user, string $provider, string $provider_id, string $token): void {
        Db::query('INSERT INTO ' . $this->auth->table('social_accounts') . ' %values%', [
            'values' => [
                'user_id'     => (int) $user->id,
                'provider'    => $provider,
                'provider_id' => $provider_id,
                'token'       => $token,
                'created_at'  => $this->auth->now(),
            ],
        ]);
    }

    private function repoFindByEmail(string $email): ?object {
        if (method_exists($this->auth, 'findUserByEmail')) {
            return $this->auth->findUserByEmail($email);
        }

        if (method_exists($this->auth, 'findByEmail')) {
            return $this->auth->findByEmail($email);
        }

        return null;
    }

    private function oauthValue(object $oauth_user, string $property, string $method): mixed {
        if (method_exists($oauth_user, $method)) {
            return $oauth_user->{$method}();
        }

        return $oauth_user->{$property} ?? '';
    }

    private function profile(ResourceOwnerInterface $owner): array {
        $data = $owner->toArray();
        $email = $this->callOrData($owner, $data, 'getEmail', ['email', 'mail']);
        $name = $this->callOrData($owner, $data, 'getName', ['name', 'login', 'displayName']);

        return [
            'id'    => (string) $owner->getId(),
            'email' => strtolower((string) $email),
            'name'  => (string) $name,
        ];
    }

    private function callOrData(ResourceOwnerInterface $owner, array $data, string $method, array $keys): mixed {
        if (method_exists($owner, $method)) {
            $value = $owner->{$method}();
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return '';
    }
}
