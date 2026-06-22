<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;

final class SettingsController extends BaseController
{
    private const SINGLE_EMAIL_KEYS = [
        'trip_report_agency_email',
        'trip_report_my_copy',
        'voucher_agency_email',
        'voucher_my_copy',
        'no_show_agency_email',
        'no_show_my_copy',
        'schedule_my_copy',
    ];

    private const MULTI_EMAIL_KEYS = [
        'trip_report_cc',
        'voucher_cc',
        'no_show_cc',
        'no_show_cc_always',
        'schedule_recipient',
    ];

    /** GET /admin/settings.php */
    public function index(): void
    {
        $s = $this->settings();

        $this->view('admin.settings.index', [
            // Toggles
            'trip_report_enabled' => $s->tripReportEnabled(),
            'voucher_enabled'     => $s->voucherEnabled(),
            'no_show_enabled'     => $s->noShowEnabled(),
            'schedule_enabled'    => $s->scheduleEnabled(),
            'wpp_agenda_enabled'  => $s->wppAgendaEnabled(),
            // Routing — single emails
            'trip_report_agency_email' => $s->tripReportAgencyEmail(),
            'trip_report_my_copy'      => $s->tripReportMyCopy(),
            'voucher_agency_email'     => $s->voucherAgencyEmail(),
            'voucher_my_copy'          => $s->voucherMyCopy(),
            'no_show_agency_email'     => $s->noShowAgencyEmail(),
            'no_show_my_copy'          => $s->noShowMyCopy(),
            'schedule_my_copy'         => $s->scheduleMyCopy(),
            // Multi-email CC lists
            'trip_report_cc'    => $s->get('trip_report_cc',    ''),
            'voucher_cc'        => $s->get('voucher_cc',        ''),
            'no_show_cc'        => $s->get('no_show_cc',        ''),
            'no_show_cc_always' => $s->get('no_show_cc_always', ''),
            'schedule_recipient'=> $s->get('schedule_recipient',''),
            // Flash
            'flash' => $_GET['success'] ?? null,
            'error' => $_GET['error']   ?? null,
            // Admin email for pre-filling "copy to me"
            'admin_email' => (string) ($_SESSION['email'] ?? ''),
        ]);
    }

    /** POST /admin/settings-save.php */
    public function save(): void
    {
        $this->requirePost();
        $s = $this->settings();

        // Toggles (unchecked checkbox = not in POST = '0')
        foreach (['trip_report_enabled', 'voucher_enabled', 'no_show_enabled', 'schedule_enabled', 'wpp_agenda_enabled'] as $key) {
            $s->set($key, $this->input($key) === '1' ? '1' : '0');
        }

        // Single-email fields — validate if not empty
        foreach (self::SINGLE_EMAIL_KEYS as $key) {
            $email = trim((string) $this->input($key, ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirect('/SRMT/public/admin/settings.php?error=' . urlencode("Invalid email in [{$key}]: {$email}"));
            }
            $s->set($key, $email);
        }

        // Multi-email CC lists — split, validate all, save
        foreach (self::MULTI_EMAIL_KEYS as $key) {
            $raw     = (string) $this->input($key, '');
            $emails  = array_values(array_filter(array_map('trim', explode(',', $raw))));
            $invalid = array_filter($emails, static fn(string $e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
            if (!empty($invalid)) {
                $this->redirect('/SRMT/public/admin/settings.php?error=' . urlencode("[{$key}] Invalid: " . implode(', ', $invalid)));
            }
            $s->set($key, implode(', ', $emails));
        }

        $this->redirect('/SRMT/public/admin/settings.php?success=1');
    }
}
