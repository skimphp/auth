<?php declare(strict_types=1);

use Skim\Db\Db;
use Skim\Db\Migration;

return new class extends Migration {
    public function up(): string {
        $table = $this->table('users');

        return match ($this->driver()) {
            'pgsql' => "
                CREATE TABLE {$table} (
                    id BIGSERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NULL,
                    email_verified_at TIMESTAMP NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'sqlite' => "
                CREATE TABLE {$table} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NULL,
                    email_verified_at TIMESTAMP NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",
            default => "
                CREATE TABLE {$table} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NULL,
                    email_verified_at TIMESTAMP NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
        };
    }

    public function down(): string {
        return 'DROP TABLE IF EXISTS ' . $this->table('users');
    }

    private function table(string $suffix): string {
        return (string) config('skim_auth.table_prefix', 'skim_') . $suffix;
    }

    private function driver(): string {
        return (string) Db::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
};
