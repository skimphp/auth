<?php declare(strict_types=1);

use Skim\Core\App;

uses()->beforeEach(fn() => App::testInstance())->in(__DIR__);

function mockUser(string $email = 'john@example.com', int $id = 1): object {
    return (object) ['id' => $id, 'email' => $email, 'name' => 'John'];
}

function mockOauthUser(string $email = 'john@example.com', int $id = 10): object {
    return new class($email, $id) {
        public function __construct(private readonly string $email, private readonly int $id) {}
        public function getId(): int { return $this->id; }
        public function getEmail(): string { return $this->email; }
        public function getName(): string { return 'OAuth User'; }
        public function toArray(): array { return ['email' => $this->email, 'name' => 'OAuth User']; }
    };
}

function mockUserRepo(?object $user): object {
    return new class($user) {
        public function __construct(private readonly ?object $user) {}
        public function findByEmail(string $email): ?object {
            return $this->user !== null && $this->user->email === $email ? $this->user : null;
        }
        public function create(array $data): object {
            return (object) ['id' => 2, 'email' => $data['email'], 'name' => $data['name']];
        }
    };
}

function mockEmptyUserRepo(): object {
    return mockUserRepo(null);
}
