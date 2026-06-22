<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserInviteRepository;
use App\Repositories\UserRepository;
use App\Support\Database;

/**
 * Public, unauthenticated flow for completing a registration from an invite link.
 * The invite carries the company + role; the invitee supplies their own details.
 */
final class InviteController extends BaseController
{
    private UserInviteRepository $invites;
    private UserRepository       $users;

    public function __construct()
    {
        $this->invites = UserInviteRepository::default();
        // No company scope — invitee is not yet authenticated and the company
        // comes from the invite record itself.
        $this->users   = new UserRepository(Database::connection(), null);
    }

    /** GET /invite.php?token=X — render the completion form. */
    public function show(): void
    {
        $token  = trim((string) ($_GET['token'] ?? ''));
        $invite = $token !== '' ? $this->invites->findValidByToken($token) : null;

        $this->view('invite.index', [
            'invite' => $invite,
            'token'  => $token,
            'error'  => $_GET['error'] ?? null,
        ]);
    }

    /** POST /invite-complete.php — create the user and consume the invite. */
    public function complete(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->abort(405, 'Method not allowed');
        }

        $token  = trim((string) ($this->input('token') ?? ''));
        $invite = $token !== '' ? $this->invites->findValidByToken($token) : null;
        if ($invite === null) {
            $this->redirect('/SRMT/public/invite.php?token=' . urlencode($token) . '&error=invalid');
        }

        $name     = trim((string) ($this->input('name') ?? ''));
        $email     = trim((string) ($this->input('email') ?? ''));
        $phone    = trim((string) ($this->input('phone') ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $this->redirect('/SRMT/public/invite.php?token=' . urlencode($token) . '&error=missing');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/SRMT/public/invite.php?token=' . urlencode($token) . '&error=email');
        }
        if (strlen($password) < 6) {
            $this->redirect('/SRMT/public/invite.php?token=' . urlencode($token) . '&error=weak');
        }
        if ($this->users->emailExists($email)) {
            $this->redirect('/SRMT/public/invite.php?token=' . urlencode($token) . '&error=exists');
        }

        $createPayload = [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone !== '' ? $phone : null,
            'password'   => $password,
            'role'       => (int) $invite['role'],
            'company_id' => (int) $invite['company_id'],
        ];
        if (!empty($invite['driver_meta'])) {
            $meta = json_decode((string) $invite['driver_meta'], true);
            if (is_array($meta)) {
                if (!empty($meta['driver_code']))       $createPayload['driver_code']       = $meta['driver_code'];
                if (!empty($meta['default_pay_basis'])) $createPayload['default_pay_basis'] = $meta['default_pay_basis'];
            }
        }

        $user = $this->users->create($createPayload);

        $this->invites->markUsed((int) $invite['id'], $user->id);

        $this->view('invite.done', ['name' => $user->name]);
    }
}
