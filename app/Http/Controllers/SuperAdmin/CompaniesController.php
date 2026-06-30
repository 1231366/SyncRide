<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\BaseController;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserInviteRepository;
use App\Repositories\LogRepository;
use App\Models\User;

final class CompaniesController extends BaseController
{
    private CompanyRepository    $companies;
    private UserRepository       $users;
    private UserInviteRepository $invites;
    private LogRepository        $logs;

    public function __construct()
    {
        $db              = $this->db();
        $this->companies = new CompanyRepository($db);
        $this->users     = new UserRepository($db, null);
        $this->invites   = new UserInviteRepository($db);
        $this->logs      = new LogRepository($db, null);
    }

    public function index(): void
    {
        $companies    = $this->companies->all();
        $adminsByCompany = $this->companies->allAdminsByCompany();
        $this->view('superadmin.companies.index', compact('companies', 'adminsByCompany'));
    }

    public function store(): never
    {
        $this->requirePost();

        $name = trim((string) $this->input('name', ''));
        $slug = trim((string) $this->input('slug', ''));

        if ($name === '' || $slug === '') {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=missing_fields');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? $slug);

        if ($this->companies->slugExists($slug)) {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=slug_taken');
        }

        $id = $this->companies->create($name, $slug);
        $this->logs->record("Super-admin created company #{$id}: {$name}");
        $this->redirect('/SRMT/public/superadmin/companies.php?success=created');
    }

    public function update(): never
    {
        $this->requirePost();

        $id   = (int) $this->input('id', 0);
        $name = trim((string) $this->input('name', ''));
        $slug = trim((string) $this->input('slug', ''));

        if ($id === 0 || $name === '' || $slug === '') {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=missing_fields');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? $slug);

        if ($this->companies->slugExists($slug, $id)) {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=slug_taken');
        }

        $this->companies->update($id, $name, $slug);
        $this->logs->record("Super-admin updated company #{$id}: {$name}");
        $this->redirect('/SRMT/public/superadmin/companies.php?success=updated');
    }

    public function destroy(): never
    {
        $this->requirePost();
        $id = (int) $this->input('id', 0);
        if ($id === 0) {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=invalid_id');
        }
        $company = $this->companies->find($id);
        if ($company === null) {
            $this->redirect('/SRMT/public/superadmin/companies.php?error=not_found');
        }
        $this->companies->delete($id);
        $this->logs->record("Super-admin deleted company #{$id}: {$company->name}");
        $this->redirect('/SRMT/public/superadmin/companies.php?success=deleted');
    }

    /** Generate an admin invite link for a given company (superadmin only). */
    public function createInvite(): never
    {
        $this->requirePost();

        $companyId = (int) $this->input('company_id', 0);
        if ($companyId === 0 || $this->companies->find($companyId) === null) {
            $this->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $invite = $this->invites->create($companyId, User::ROLE_ADMIN, 'Superadmin invite', null, 7);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $link   = $scheme . '://' . $host . '/SRMT/public/invite.php?token=' . $invite['token'];

        $this->logs->record("Super-admin generated admin invite for company #{$companyId}");
        $this->json(['success' => true, 'link' => $link]);
    }

    /** Create a company admin (role=1) for a given company. */
    public function storeUser(): never
    {
        $this->requirePost();

        $companyId = (int) $this->input('company_id', 0);
        $email     = trim((string) $this->input('email', ''));
        $name      = trim((string) $this->input('name', ''));
        $password  = (string) $this->input('password', '');

        if ($companyId === 0 || $email === '' || $name === '' || $password === '') {
            $this->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        try {
            $user = $this->users->create([
                'email'      => $email,
                'name'       => $name,
                'password'   => $password,
                'role'       => 1, // always Admin — drivers/partners are created by the company admin
                'phone'      => 0,
                'company_id' => $companyId,
            ]);
            $this->logs->record("Super-admin created admin #{$user->id} ({$user->name}) for company #{$companyId}");
            $this->json(['success' => true, 'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
        } catch (\RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** POST /superadmin/companies.php?action=toggle_grace — grant/revoke manual access. */
    public function toggleGrace(): never
    {
        $this->requirePost();
        $companyId = (int) $this->input('company_id', 0);
        $grace     = (bool) $this->input('grace', false);
        if ($companyId === 0) {
            $this->json(['success' => false, 'message' => 'Missing company_id'], 400);
        }
        $this->companies->toggleGrace($companyId, $grace);
        $this->logs->record("Super-admin " . ($grace ? 'granted' : 'revoked') . " grace access for company #{$companyId}");
        $this->json(['success' => true, 'grace' => $grace]);
    }
}
