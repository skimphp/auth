<?php declare(strict_types=1);

namespace Skim\Auth\Cli;

use Skim\Cli\Command;

final class AuthPublishCommand extends Command {
    protected string $description = 'publish skim/auth Config, views, or migrations';
    protected string $usage = 'views|config|migrations|all';

    public function handle(): int {
        $target = (string) $this->arg(0, 'all');
        if (!in_array($target, ['views', 'config', 'migrations', 'all'], true)) {
            $this->error('Usage: php skim auth:publish views|config|migrations|all');
            return 1;
        }

        if ($target === 'config' || $target === 'all') {
            $this->publishConfig();
        }
        if ($target === 'views' || $target === 'all') {
            $this->publishViews();
        }
        if ($target === 'migrations' || $target === 'all') {
            $this->publishMigrations();
        }

        return 0;
    }

    private function publishConfig(): void {
        $source = $this->root() . '/config/skim_auth.php';
        $destination = basePath('config/skim_auth.php');
        $this->ensureDir(dirname($destination));

        if (is_file($destination)) {
            $this->info('config/skim_auth.php already exists — skipping');
            return;
        }

        copy($source, $destination);
        $this->success('Published config/skim_auth.php');
    }

    private function publishViews(): void {
        $this->publishConfig();
        $source = $this->root() . '/src/views';
        $destination = basePath('app/views/skim_auth');
        $copied = $this->copyTree($source, $destination);
        $this->markViewsPublished();
        $this->success("Published {$copied} auth view file(s)");
    }

    private function publishMigrations(): void {
        $source = $this->root() . '/src/migrations';
        $destination = basePath('app/migrations');
        $this->ensureDir($destination);
        $count = 0;

        foreach (glob($source . '/*.php') ?: [] as $file) {
            $target = $destination . '/skim_auth_' . basename($file);
            if (is_file($target)) {
                $this->info('app/migrations/' . basename($target) . ' already exists — skipping');
                continue;
            }

            copy($file, $target);
            $count++;
        }

        $this->success("Published {$count} auth migration file(s)");
    }

    private function copyTree(string $source, string $destination): int {
        $this->ensureDir($destination);
        $count = 0;

        foreach (array_diff(scandir($source) ?: [], ['.', '..']) as $item) {
            $from = $source . '/' . $item;
            $to = $destination . '/' . $item;

            if (is_dir($from)) {
                $count += $this->copyTree($from, $to);
                continue;
            }

            if (is_file($to)) {
                $this->info(str_replace(basePath() . '/', '', $to) . ' already exists — skipping');
                continue;
            }

            copy($from, $to);
            $count++;
        }

        return $count;
    }

    private function markViewsPublished(): void {
        $path = basePath('config/skim_auth.php');
        if (!is_file($path)) {
            return;
        }

        $config = require $path;
        if (!is_array($config)) {
            return;
        }

        $config['ui'] ??= [];
        $config['ui']['published'] = true;

        file_put_contents($path, "<?php declare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n");
    }

    private function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function root(): string {
        return dirname(__DIR__, 2);
    }
}
