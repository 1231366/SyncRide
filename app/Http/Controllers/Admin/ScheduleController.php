<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Services\ScheduleMailer;

/** Sends the next-day operations schedule by email. */
final class ScheduleController extends BaseController
{
    public function send(): never
    {
        $s = $this->settings();

        if (!$s->scheduleEnabled()) {
            LogRepository::default()->record('Schedule email skipped — disabled in settings');
            $this->json(['success' => false, 'message' => 'Schedule email is disabled in settings.']);
        }

        $ok = ScheduleMailer::default()->sendForTomorrow($s->scheduleRecipients(), $s->scheduleMyCopy());

        LogRepository::default()->record(
            $ok ? 'Operations schedule emailed for tomorrow' : 'Schedule email FAILED'
        );

        if ($this->wantsJson()) {
            $this->json(['success' => $ok, 'message' => $ok ? 'Schedule emailed.' : 'Mail server refused the message.']);
        }
        echo $ok ? 'Schedule email sent.' : 'Failed to send schedule email.';
        exit;
    }
}
