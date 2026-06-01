<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Session;

/**
 * Self-service profile actions available to every authenticated role.
 */
final class ProfileController extends BaseController
{
    private UserRepository $users;

    public function __construct()
    {
        // Unscoped: a user only ever acts on their own session id.
        $this->users = new UserRepository(Database::connection(), null);
    }

    /** POST /change-password.php */
    public function changePassword(): never
    {
        $this->requirePost();

        $userId = Session::userId();
        if ($userId === null) {
            $this->json(['success' => false, 'error' => 'Not authenticated.'], 401);
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password']     ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $this->json(['success' => false, 'error' => 'All fields are required.'], 422);
        }
        if (!$this->users->verifyPassword($userId, $current)) {
            $this->json(['success' => false, 'error' => 'Current password is incorrect.'], 403);
        }
        if (strlen($new) < 6) {
            $this->json(['success' => false, 'error' => 'New password must be at least 6 characters.'], 422);
        }
        if ($new !== $confirm) {
            $this->json(['success' => false, 'error' => 'New passwords do not match.'], 422);
        }

        $this->users->setPassword($userId, $new);
        $this->json(['success' => true]);
    }
}
