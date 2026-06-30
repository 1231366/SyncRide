<?php

declare(strict_types=1);

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\BaseController;

final class SettingsController extends BaseController
{
    /** GET /driver/settings.php */
    public function index(): void
    {
        $userName = (string) ($_SESSION['name'] ?? 'Driver');
        $lang     = ($_SESSION['admin_lang'] ?? 'en') === 'pt' ? 'pt' : 'en';

        $this->view('driver.settings.index', compact('userName', 'lang'));
    }
}
