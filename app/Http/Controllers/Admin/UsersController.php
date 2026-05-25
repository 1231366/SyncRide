<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\LogRepository;
use App\Repositories\UserRepository;

/**
 * CRUD for the `Users` table — admin only.
 *
 * The view is a single-page experience: list + two modals (create / edit)
 * that POST to this same controller's `store()` / `update()` actions.
 */
final class UsersController extends BaseController
{
    private UserRepository $users;
    private LogRepository  $logs;

    public function __construct()
    {
        $this->users = UserRepository::default();
        $this->logs  = LogRepository::default();
    }

    /** GET /admin/users.php — list + open the create/edit modals from JS. */
    public function index(): void
    {
        $all = $this->users->all();

        $this->view('admin.users.index', [
            'users'         => $all,
            'totalAdmins'   => count(array_filter($all, fn(User $u) => $u->role === User::ROLE_ADMIN)),
            'totalDrivers'  => count(array_filter($all, fn(User $u) => $u->role === User::ROLE_DRIVER)),
            'totalPartners' => count(array_filter($all, fn(User $u) => $u->role === User::ROLE_PARTNER)),
            'flash'         => $_GET['success'] ?? null,
        ]);
    }

    /** POST /admin/user-create.php — create a new user. */
    public function store(): void
    {
        $this->requirePost();

        $payload = [
            'name'     => $this->input('name'),
            'email'    => $this->input('email'),
            'password' => $_POST['password'] ?? '',
            'phone'    => $this->input('phone'),
            'role'     => (int) ($this->input('role') ?? 0),
        ];

        $this->validate($payload);

        $user = $this->users->create($payload);
        $this->logs->record("Admin created user #{$user->id} ({$user->email}, role={$user->role})");

        $this->redirect('/SRMT/public/admin/users.php?success=user_created');
    }

    /** POST /admin/user-edit.php — update an existing user. */
    public function update(): void
    {
        $this->requirePost();

        $id = (int) ($this->input('id') ?? 0);
        if ($id <= 0) {
            $this->abort(400, 'Missing user id.');
        }
        $existing = $this->users->find($id);
        if ($existing === null) {
            $this->abort(404, 'User not found.');
        }
        // Prevent cross-tenant BOLA: super-admin (null) may edit any user.
        $sessionCompanyId = \App\Support\Session::companyId();
        if ($sessionCompanyId !== null && $existing->companyId !== $sessionCompanyId) {
            $this->abort(403, 'Forbidden.');
        }

        $payload = [
            'name'  => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'role'  => (int) ($this->input('role') ?? 0),
        ];
        $newPassword = $_POST['password'] ?? '';
        if ($newPassword !== '') {
            $payload['password'] = $newPassword;
        }

        $this->validate($payload, isUpdate: true);

        $user = $this->users->update($id, $payload);
        $this->logs->record("Admin updated user #{$user->id} ({$user->email})");

        $this->redirect('/SRMT/public/admin/users.php?success=user_updated');
    }

    /** POST /admin/user-delete.php — delete a user. */
    public function destroy(): void
    {
        $this->requirePost();

        $id = (int) ($this->input('id') ?? 0);
        if ($id <= 0) {
            $this->abort(400, 'Missing user id.');
        }
        if ($id === $this->userId()) {
            $this->abort(403, 'You cannot delete your own account.');
        }
        $target = $this->users->find($id);
        if ($target === null) {
            $this->abort(404, 'User not found.');
        }
        $sessionCompanyId = \App\Support\Session::companyId();
        if ($sessionCompanyId !== null && $target->companyId !== $sessionCompanyId) {
            $this->abort(403, 'Forbidden.');
        }

        $this->users->delete($id);
        $this->logs->record("Admin deleted user #{$id}");

        if ($this->wantsJson()) {
            $this->json(['success' => true]);
        }
        $this->redirect('/SRMT/public/admin/users.php?success=user_deleted');
    }

    /** GET /admin/delete.php?id=X — legacy direct-link delete for users. */
    public function destroyLink(): void
    {
        $id = (int) ($this->input('id') ?? 0);
        if ($id <= 0) {
            $this->abort(400, 'Missing user id.');
        }
        if ($id === $this->userId()) {
            $this->abort(403, 'You cannot delete your own account.');
        }
        $target = $this->users->find($id);
        if ($target === null) {
            $this->abort(404, 'User not found.');
        }
        $sessionCompanyId = \App\Support\Session::companyId();
        if ($sessionCompanyId !== null && $target->companyId !== $sessionCompanyId) {
            $this->abort(403, 'Forbidden.');
        }

        $this->users->delete($id);
        $this->logs->record("Admin deleted user #{$id}");
        $this->redirect('/SRMT/public/admin/users.php?success=user_deleted');
    }

    private function validate(array $data, bool $isUpdate = false): void
    {
        $required = $isUpdate ? ['name', 'email', 'role'] : ['name', 'email', 'role', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->abort(422, "Missing required field: {$field}");
            }
        }
        if (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->abort(422, 'Invalid email address.');
        }
        if (!empty($data['phone']) && !preg_match('/^\+?\d{9,15}$/', (string) $data['phone'])) {
            $this->abort(422, 'Invalid phone number.');
        }
        if (!in_array((int) $data['role'], [User::ROLE_ADMIN, User::ROLE_DRIVER, User::ROLE_PARTNER], true)) {
            $this->abort(422, 'Invalid role.');
        }
    }
}
