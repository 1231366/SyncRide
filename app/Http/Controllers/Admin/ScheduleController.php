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
        $ok = ScheduleMailer::default()->sendForTomorrow();

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
