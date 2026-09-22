<?php declare(strict_types=1);

use Skim\Db\Db;
use Skim\Db\Migration;

return new class extends Migration {
    public function up(): string {
        $table = $this->table('password_resets');
        $email_index = 'idx_' . $table . '_email';

        return match ($this->driver()) {
            'pgsql' => "
                CREATE TABLE {$table} (
                    id BIGSERIAL PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX {$email_index} ON {$table} (email)
            ",
            'sqlite' => "
                CREATE TABLE {$table} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX {$email_index} ON {$table} (email)
            ",
            default => "
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX {$email_index} (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
        };
    }

    public function down(): string {
        return 'DROP TABLE IF EXISTS ' . $this->table('password_resets');
    }

    private function table(string $suffix): string {
        return (string) config('skim_auth.table_prefix', 'skim_') . $suffix;
    }

    private function driver(): string {
        return (string) Db::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
};
