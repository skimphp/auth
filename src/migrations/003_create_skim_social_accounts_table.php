<?php declare(strict_types=1);

use Skim\Db\Db;
use Skim\Db\Migration;

return new class extends Migration {
    public function up(): string {
        $users = $this->table('users');
        $table = $this->table('social_accounts');
        $unique = 'uq_' . $table . '_provider_id';
        $user_index = 'idx_' . $table . '_user_id';

        return match ($this->driver()) {
            'pgsql' => "
                CREATE TABLE {$table} (
                    id BIGSERIAL PRIMARY KEY,
                    user_id BIGINT NOT NULL REFERENCES {$users}(id) ON DELETE CASCADE,
                    provider VARCHAR(50) NOT NULL,
                    provider_id VARCHAR(255) NOT NULL,
                    token TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (provider, provider_id)
                );
                CREATE INDEX {$user_index} ON {$table} (user_id)
            ",
            'sqlite' => "
                CREATE TABLE {$table} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    provider VARCHAR(50) NOT NULL,
                    provider_id VARCHAR(255) NOT NULL,
                    token TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (provider, provider_id),
                    FOREIGN KEY (user_id) REFERENCES {$users}(id) ON DELETE CASCADE
                );
                CREATE INDEX {$user_index} ON {$table} (user_id)
            ",
            default => "
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    provider VARCHAR(50) NOT NULL,
                    provider_id VARCHAR(255) NOT NULL,
                    token TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY {$unique} (provider, provider_id),
                    INDEX {$user_index} (user_id),
                    CONSTRAINT fk_{$table}_user FOREIGN KEY (user_id) REFERENCES {$users}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
        };
    }

    public function down(): string {
        return 'DROP TABLE IF EXISTS ' . $this->table('social_accounts');
    }

    private function table(string $suffix): string {
        return (string) config('skim_auth.table_prefix', 'skim_') . $suffix;
    }

    private function driver(): string {
        return (string) Db::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
};
