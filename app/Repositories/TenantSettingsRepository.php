<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Persists and retrieves per-tenant configuration stored in TenantSettings.
 *
 * Keys are free-form strings. Values are stored as TEXT; typed convenience
 * getters (uiTheme, tripReportEnabled, tripReportRecipients) sit here so
 * callers never need to know the raw key names.
 */
final class TenantSettingsRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    public function get(string $key, string $default = ''): string
    {
        if ($this->companyId === null) {
            return $default;
        }
        $stmt = $this->db->prepare(
            'SELECT `value` FROM TenantSettings WHERE company_id = ? AND `key` = ? LIMIT 1'
        );
        $stmt->execute([$this->companyId, $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    public function set(string $key, string $value): void
    {
        if ($this->companyId === null) {
            return;
        }
        $this->db->prepare('
            INSERT INTO TenantSettings (company_id, `key`, `value`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ')->execute([$this->companyId, $key, $value]);
    }

    // ── Typed convenience getters ──────────────────────────────────────────

    /** Returns the preferred UI theme: "light" (default) or "dark". */
    public function uiTheme(): string
    {
        $t = $this->get('ui_theme', 'light');
        return in_array($t, ['light', 'dark'], true) ? $t : 'light';
    }

    /** Whether the automated end-of-trip email report is enabled. */
    public function tripReportEnabled(): bool
    {
        return $this->get('trip_report_enabled', '1') === '1';
    }

    /**
     * Extra CC recipients for trip-report emails (comma-separated in DB).
     *
     * @return string[]
     */
    public function tripReportRecipients(): array
    {
        $raw = $this->get('trip_report_cc', '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
