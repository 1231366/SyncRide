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
 * getters sit here so callers never need to know the raw key names.
 *
 * Setting keys
 * ────────────
 * ui_theme                 "light"|"dark"
 * trip_report_enabled      "1"|"0"   — end-of-trip email to partner + CC
 * trip_report_cc           comma-separated extra CCs
 * voucher_enabled          "1"|"0"   — voucher-photo email when driver uploads
 * voucher_cc               comma-separated recipients
 * no_show_enabled          "1"|"0"   — no-show alert email
 * no_show_cc               comma-separated internal recipients
 * schedule_enabled         "1"|"0"   — daily operations schedule email
 * schedule_recipient       single email (or comma-separated)
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

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** @return string[] validated, trimmed emails from a comma-separated value */
    private function emailList(string $key, string $default = ''): array
    {
        $raw = $this->get($key, $default);
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn(string $e) => filter_var($e, FILTER_VALIDATE_EMAIL) !== false
        ));
    }

    // ── Typed convenience getters ──────────────────────────────────────────────

    public function uiTheme(): string
    {
        $t = $this->get('ui_theme', 'light');
        return in_array($t, ['light', 'dark'], true) ? $t : 'light';
    }

    // Trip report ──────────────────────────────────────────────────────────────

    public function tripReportEnabled(): bool
    {
        return $this->get('trip_report_enabled', '1') === '1';
    }

    /** @return string[] */
    public function tripReportRecipients(): array
    {
        return $this->emailList('trip_report_cc');
    }

    // Voucher ──────────────────────────────────────────────────────────────────

    public function voucherEnabled(): bool
    {
        return $this->get('voucher_enabled', '1') === '1';
    }

    /** @return string[] */
    public function voucherRecipients(): array
    {
        return $this->emailList('voucher_cc');
    }

    // No-show ──────────────────────────────────────────────────────────────────

    public function noShowEnabled(): bool
    {
        return $this->get('no_show_enabled', '1') === '1';
    }

    /** Internal CC list — partner is always notified separately by the mailer. @return string[] */
    public function noShowInternalRecipients(): array
    {
        return $this->emailList('no_show_cc');
    }

    // Schedule ─────────────────────────────────────────────────────────────────

    public function scheduleEnabled(): bool
    {
        return $this->get('schedule_enabled', '1') === '1';
    }

    /** @return string[] */
    public function scheduleRecipients(): array
    {
        return $this->emailList('schedule_recipient');
    }
}
