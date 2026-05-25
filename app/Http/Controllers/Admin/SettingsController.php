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
            'trip_report_enabled'    => $s->tripReportEnabled(),
            'trip_report_cc'         => $s->get('trip_report_cc', ''),
            'flash'                  => $_GET['success'] ?? null,
            'error'                  => $_GET['error']   ?? null,
        ]);
    }

    /** POST /admin/settings-save.php */
    public function save(): void
    {
        $this->requirePost();

        $s = $this->settings();

        $s->set('trip_report_enabled', $this->input('trip_report_enabled') === '1' ? '1' : '0');

        $rawCc = (string) $this->input('trip_report_cc', '');
        $emails = array_filter(array_map('trim', explode(',', $rawCc)));
        $invalid = array_filter($emails, static fn(string $e) => !filter_var($e, FILTER_VALIDATE_EMAIL));

        if (!empty($invalid)) {
            $this->redirect('/SRMT/public/admin/settings.php?error=' . urlencode('Invalid emails: ' . implode(', ', $invalid)));
        }

        $s->set('trip_report_cc', implode(', ', $emails));

        $this->redirect('/SRMT/public/admin/settings.php?success=1');
    }
}
