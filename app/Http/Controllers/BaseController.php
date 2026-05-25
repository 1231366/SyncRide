<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\View;
use App\Repositories\TenantSettingsRepository;
use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Common controller behaviour: rendering, redirects, input access,
 * JSON responses, and short-lived flash messages.
 *
 * Every concrete controller should extend this and stay thin —
 * push data access into App\Repositories and business logic into
 * App\Services so controllers read like the URL → response wiring.
 */
abstract class BaseController
{
    protected function db(): PDO
    {
        return Database::connection();
    }

    /**
     * Render a template, automatically wrapped in the layout the
     * template declared (or unwrapped if it declared none).
     * Injects ui_theme globally so every layout can read it.
     */
    protected function view(string $template, array $data = []): void
    {
        $data['ui_theme'] ??= $this->settings()->uiTheme();
        View::render($template, $data);
    }

    /** Lazily-instantiated TenantSettings repo (one instance per request). */
    protected function settings(): TenantSettingsRepository
    {
        static $repo = null;
        return $repo ??= TenantSettingsRepository::default();
    }

    /**
     * Emit JSON and terminate. Use for AJAX endpoints.
     */
    protected function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $location, int $status = 302): never
    {
        http_response_code($status);
        header("Location: {$location}");
        exit;
    }

    protected function back(string $fallback = '/SRMT/public/'): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? $fallback);
        $this->redirect($referer);
    }

    protected function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message ?: "HTTP {$status}"]);
        } else {
            $safe = htmlspecialchars($message ?: "HTTP {$status}", ENT_QUOTES, 'UTF-8');
            echo "<!DOCTYPE html><meta charset='utf-8'><title>{$status}</title><h1>{$status}</h1><p>{$safe}</p>";
        }
        exit;
    }

    /** Pulls a value from POST first, then GET. Trims strings. */
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** Reads JSON payload (Content-Type: application/json) once. */
    protected function jsonBody(): array
    {
        static $cached = null;
        if ($cached === null) {
            $raw = (string) file_get_contents('php://input');
            $cached = $raw === '' ? [] : (json_decode($raw, true) ?? []);
        }
        return $cached;
    }

    protected function userId(): int
    {
        $id = Session::userId();
        if ($id === null) {
            $this->abort(401, 'Not authenticated');
        }
        return $id;
    }

    protected function role(): int
    {
        return Session::role() ?? -1;
    }

    /** Lightweight CSRF-style guard for state-changing endpoints. */
    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->abort(405, 'Method not allowed');
        }
    }

    protected function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $xrw    = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json') || $xrw === 'xmlhttprequest';
    }
}
