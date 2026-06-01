<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\View;

final class TrackingController extends BaseController
{
    /** GET /admin/live-map.php */
    public function liveMap(): void
    {
        $raw = $_SESSION['profile_photo_path'] ?? null;
        if ($raw !== null && $raw !== '') {
            $raw       = str_replace('Includes/dist/pages/', '', $raw);
            $userPhoto = str_starts_with($raw, '/') || str_starts_with($raw, 'http')
                ? $raw
                : '/SRMT/public/' . $raw;
        } else {
            $userPhoto = '';
        }

        $this->view('admin.tracking.live-map', ['userPhoto' => $userPhoto]);
    }

    /** GET /admin/tracking-map.php — superseded by live-map; redirect. */
    public function trackingMap(): never
    {
        $this->redirect('/SRMT/public/admin/live-map.php');
    }
}
