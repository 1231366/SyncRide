<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\BaseController;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use App\Repositories\LogRepository;

final class CompaniesController extends BaseController
{
    private CompanyRepository $companies;
    private UserRepository    $users;
    private LogRepository     $logs;

    public function __construct()
    {
        // Super-admin repos are unscoped (companyId = null)
        $db             = $this->db();
        $this->companies = new CompanyRepository($db);
        $this->users     = new UserRepository($db, null);
        $this->logs      = new LogRepository($db, null);
    }

    public function index(): void
    {
        $companies = $this->companies->all();
        $this->view('superadmin.companies.index', compact('companies'));
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

    /** Create a user and associate them with a company (called via POST). */
    public function storeUser(): never
    {
        $this->requirePost();

        $companyId = (int) $this->input('company_id', 0);
        $email     = trim((string) $this->input('email', ''));
        $name      = trim((string) $this->input('name', ''));
        $password  = (string) $this->input('password', '');
        $role      = (int) $this->input('role', 1);
        $phone     = trim((string) $this->input('phone', ''));

        if ($companyId === 0 || $email === '' || $name === '' || $password === '') {
            $this->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        try {
            $user = $this->users->create([
                'email'      => $email,
                'name'       => $name,
                'password'   => $password,
                'role'       => $role,
                'phone'      => $phone ?: null,
                'company_id' => $companyId,
            ]);
            $this->logs->record("Super-admin created user #{$user->id} ({$user->name}) for company #{$companyId}");
            $this->json(['success' => true, 'user_id' => $user->id]);
        } catch (\RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
