<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Session;

final class RegisterController
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->showForm(null, []);
            return;
        }

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $name        = trim((string) ($_POST['name']         ?? ''));
        $email       = strtolower(trim((string) ($_POST['email']    ?? '')));
        $password    = (string) ($_POST['password'] ?? '');
        $confirm     = (string) ($_POST['confirm']  ?? '');
        $terms       = isset($_POST['terms']);

        $old = compact('companyName', 'name', 'email');

        if (!$terms)                                       { $this->showForm('terms', $old); return; }
        if (mb_strlen($companyName) < 2)                   { $this->showForm('company_name', $old); return; }
        if (mb_strlen($name) < 2)                          { $this->showForm('name', $old); return; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    { $this->showForm('email', $old); return; }
        if (strlen($password) < 8)                         { $this->showForm('password_weak', $old); return; }
        if ($password !== $confirm)                        { $this->showForm('password_mismatch', $old); return; }

        $db = Database::connection();

        // Email uniqueness check
        $stmt = $db->prepare('SELECT id FROM Users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) { $this->showForm('email_taken', $old); return; }

        try {
            $db->beginTransaction();

            $companies = new CompanyRepository($db);

            // Generate unique slug from company name
            $slug = $this->uniqueSlug($companyName, $companies);

            // Create company — sub_status stays 'none' until Stripe checkout completes
            $companyId = $companies->create($companyName, $slug);

            // Create admin user
            $users = new UserRepository($db);
            $user  = $users->create([
                'name'       => $name,
                'email'      => $email,
                'password'   => $password,
                'role'       => 1,
                'phone'      => 0,
                'company_id' => $companyId,
            ]);

            $db->commit();

            // Auto-login
            Session::start();
            session_regenerate_id(true);
            $_SESSION['user_id']            = $user->id;
            $_SESSION['role']               = 1;
            $_SESSION['name']               = $user->name;
            $_SESSION['email']              = $user->email;
            $_SESSION['company_id']         = $companyId;
            $_SESSION['profile_photo_path'] = null;
            $_SESSION['admin_lang']         = 'pt';
            $_SESSION['csrf_token']         = bin2hex(random_bytes(32));

            // Send to billing to start trial (card required, 7 days free via Stripe)
            header('Location: /SRMT/public/admin/billing.php?welcome=1');
            exit;

        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            error_log('RegisterController: ' . $e->getMessage());
            $this->showForm('server_error', $old);
        }
    }

    private function showForm(?string $errorCode, array $old): never
    {
        $data = compact('errorCode', 'old');
        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 4) . '/resources/views/auth/register.php';
        exit;
    }

    private function uniqueSlug(string $name, CompanyRepository $companies): string
    {
        $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name) ?: $name));
        $base = trim($base, '-') ?: 'company';
        $slug = $base;
        $i    = 2;
        while ($companies->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
