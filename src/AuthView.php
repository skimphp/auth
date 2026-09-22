<?php declare(strict_types=1);

namespace Skim\Auth;

final class AuthView {
    public static function render(string $template, array $data = []): string {
        $file = self::path($template);
        if (!is_file($file)) {
            throw new \RuntimeException("Auth view not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function path(string $template): string {
        $relative = ltrim($template, '/');
        if (!str_ends_with($relative, '.php')) {
            $relative .= '.php';
        }

        if ((bool) config('skim_auth.ui.published', false)) {
            $published = basePath('app/views/skim_auth/' . $relative);
            if (is_file($published)) {
                return $published;
            }
        }

        return dirname(__DIR__) . '/views/' . $relative;
    }
}
