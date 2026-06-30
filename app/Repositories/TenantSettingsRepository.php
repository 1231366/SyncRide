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
 * ui_theme                   "light"|"dark"
 *
 * trip_report_enabled        "1"|"0"
 * trip_report_agency_email   TO for non-partner rides (single email)
 * trip_report_cc             comma-separated extra CCs (both cases)
 * trip_report_my_copy        admin self-copy email (empty = disabled)
 *
 * voucher_enabled            "1"|"0"
 * voucher_agency_email       TO for non-partner rides
 * voucher_cc                 comma-separated CCs
 * voucher_my_copy            admin self-copy email
 *
 * no_show_enabled            "1"|"0"
 * no_show_agency_email       TO for non-partner no-shows
 * no_show_cc                 comma-separated CC list
 * no_show_my_copy            admin self-copy email
 *
 * schedule_enabled           "1"|"0"
 * schedule_recipient         comma-separated TO list
 * schedule_my_copy           admin self-copy email
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
        return $this->get('trip_report_enabled', '0') === '1';
    }

    /** TO for non-partner rides. */
    public function tripReportAgencyEmail(): string
    {
        return $this->get('trip_report_agency_email', '');
    }

    /** Extra CCs applied to both partner and non-partner trip reports. @return string[] */
    public function tripReportRecipients(): array
    {
        return $this->emailList('trip_report_cc');
    }

    /** Admin self-copy email — empty means disabled. */
    public function tripReportMyCopy(): string
    {
        return $this->get('trip_report_my_copy', '');
    }

    // Voucher ──────────────────────────────────────────────────────────────────

    public function voucherEnabled(): bool
    {
        return $this->get('voucher_enabled', '0') === '1';
    }

    /** TO for non-partner vouchers. */
    public function voucherAgencyEmail(): string
    {
        return $this->get('voucher_agency_email', '');
    }

    /** @return string[] */
    public function voucherCcList(): array
    {
        return $this->emailList('voucher_cc');
    }

    public function voucherMyCopy(): string
    {
        return $this->get('voucher_my_copy', '');
    }

    // No-show ──────────────────────────────────────────────────────────────────

    public function noShowEnabled(): bool
    {
        return $this->get('no_show_enabled', '0') === '1';
    }

    /** TO for non-partner no-shows. */
    public function noShowAgencyEmail(): string
    {
        return $this->get('no_show_agency_email', '');
    }

    /** CC list applied only to AGENCY (non-partner) no-show alerts. @return string[] */
    public function noShowCcList(): array
    {
        return $this->emailList('no_show_cc');
    }

    /** CC list applied to EVERY no-show alert — partner and agency alike. @return string[] */
    public function noShowCcAlways(): array
    {
        return $this->emailList('no_show_cc_always');
    }

    public function noShowMyCopy(): string
    {
        return $this->get('no_show_my_copy', '');
    }

    // Schedule ─────────────────────────────────────────────────────────────────

    public function scheduleEnabled(): bool
    {
        return $this->get('schedule_enabled', '0') === '1';
    }

    /** @return string[] */
    public function scheduleRecipients(): array
    {
        return $this->emailList('schedule_recipient');
    }

    public function scheduleMyCopy(): string
    {
        return $this->get('schedule_my_copy', '');
    }

    // WhatsApp agenda ──────────────────────────────────────────────────────────

    public function wppAgendaEnabled(): bool
    {
        return $this->get('wpp_agenda_enabled', '0') === '1';
    }

    // WhatsApp tracking link ───────────────────────────────────────────────────

    public function wppTrackEnabled(): bool
    {
        return $this->get('wpp_track_enabled', '0') === '1';
    }
}
