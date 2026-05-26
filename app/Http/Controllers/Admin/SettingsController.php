<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;

final class SettingsController extends BaseController
{
    /** GET /admin/settings.php */
    public function index(): void
    {
        $s = $this->settings();

        $this->view('admin.settings.index', [
            'trip_report_enabled' => $s->tripReportEnabled(),
            'trip_report_cc'      => $s->get('trip_report_cc', ''),
            'voucher_enabled'     => $s->voucherEnabled(),
            'voucher_cc'          => $s->get('voucher_cc', ''),
            'no_show_enabled'     => $s->noShowEnabled(),
            'no_show_cc'          => $s->get('no_show_cc', ''),
            'schedule_enabled'    => $s->scheduleEnabled(),
            'schedule_recipient'  => $s->get('schedule_recipient', ''),
            'flash'               => $_GET['success'] ?? null,
            'error'               => $_GET['error']   ?? null,
        ]);
    }

    /** POST /admin/settings-save.php */
    public function save(): void
    {
        $this->requirePost();

        $s = $this->settings();

        // Toggles
        $s->set('trip_report_enabled', $this->input('trip_report_enabled') === '1' ? '1' : '0');
        $s->set('voucher_enabled',     $this->input('voucher_enabled')     === '1' ? '1' : '0');
        $s->set('no_show_enabled',     $this->input('no_show_enabled')     === '1' ? '1' : '0');
        $s->set('schedule_enabled',    $this->input('schedule_enabled')    === '1' ? '1' : '0');

        // Email lists — validate all, reject on first bad one
        $fields = [
            'trip_report_cc'    => $this->input('trip_report_cc',    ''),
            'voucher_cc'        => $this->input('voucher_cc',        ''),
            'no_show_cc'        => $this->input('no_show_cc',        ''),
            'schedule_recipient'=> $this->input('schedule_recipient',''),
        ];

        foreach ($fields as $key => $raw) {
            $emails  = array_filter(array_map('trim', explode(',', (string) $raw)));
            $invalid = array_filter($emails, static fn(string $e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
            if (!empty($invalid)) {
                $this->redirect('/SRMT/public/admin/settings.php?error=' . urlencode('[' . $key . '] Invalid: ' . implode(', ', $invalid)));
            }
            $s->set($key, implode(', ', $emails));
        }

        $this->redirect('/SRMT/public/admin/settings.php?success=1');
    }
}
