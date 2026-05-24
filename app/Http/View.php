<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/**
 * Minimal PHP-template renderer.
 *
 * Templates live under `resources/views/`. A template can declare a layout
 * by calling {@see View::layout()} at the top; the layout will then be
 * rendered with `$content` set to the captured template output.
 *
 * Example:
 *
 *   // resources/views/admin/users/index.php
 *   <?php
 *   /** @var array $users *\/
 *   App\Http\View::layout('layouts.admin', ['title' => 'Users']);
 *   ?>
 *   <h1>Users</h1>
 *   <?php foreach ($users as $u): ?>…<?php endforeach ?>
 */
final class View
{
    private static string $basePath = '';

    /** Captured layout request from a child template (null = no wrapper). */
    private static ?string $pendingLayout = null;

    /** Extra data forwarded to the layout. */
    private static array $pendingLayoutData = [];

    public static function configure(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    /**
     * Render a template and stream the result. Variables in `$data` become
     * locally-scoped inside the template (e.g. `$users` from data['users']).
     */
    public static function render(string $template, array $data = []): void
    {
        if (self::$basePath === '') {
            self::$basePath = dirname(__DIR__, 2) . '/resources/views';
        }

        $previousLayout     = self::$pendingLayout;
        $previousLayoutData = self::$pendingLayoutData;
        self::$pendingLayout     = null;
        self::$pendingLayoutData = [];

        $content = self::capture($template, $data);

        $layout      = self::$pendingLayout;
        $layoutData  = self::$pendingLayoutData;

        // Restore in case render() was nested.
        self::$pendingLayout     = $previousLayout;
        self::$pendingLayoutData = $previousLayoutData;

        if ($layout === null) {
            echo $content;
            return;
        }

        echo self::capture($layout, $layoutData + ['content' => $content]);
    }

    /**
     * Called from inside a template to request being wrapped in a layout.
     * Dot-notation: 'layouts.admin' → resources/views/layouts/admin.php.
     */
    public static function layout(string $name, array $data = []): void
    {
        self::$pendingLayout     = $name;
        self::$pendingLayoutData = $data;
    }

    /**
     * Escape user-provided values when interpolating into HTML.
     * Templates should call this for any dynamic non-trusted content.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function capture(string $template, array $data): string
    {
        $path = self::$basePath . '/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($path)) {
            throw new RuntimeException("View template not found: {$template} (looked at {$path})");
        }

        ob_start();
        try {
            (static function () use ($path, $data): void {
                extract($data, EXTR_SKIP);
                require $path;
            })();
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
