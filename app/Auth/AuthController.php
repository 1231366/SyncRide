<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\Database;
use App\Support\Session;
use PDO;
use Throwable;

/**
 * Front-controller for the login form and the mobile-app JSON login endpoint.
 *
 * Roles (legacy enum preserved):
 *   1 = admin, 2 = driver, 3 = partner
 */
final class AuthController
{
    /** Role → landing dashboard URL. */
    private const ROUTES = [
        0 => '/SRMT/public/superadmin/',
        1 => '/SRMT/public/admin/',
        2 => '/SRMT/public/driver/',
        3 => '/SRMT/public/partner/',
    ];

    public function login(): void
    {
        $isAjax = $this->isAjaxCall();
        if ($isAjax) {
            header('Content-Type: application/json');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/SRMT/public/');
            return;
        }

        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['pass']  ?? ''));
        $remember = isset($_POST['remember']);

        if ($email === '' || $password === '') {
            $this->respond($isAjax, false, 'empty_fields', '/SRMT/public/?error=empty_fields');
            return;
        }

        try {
            $db   = Database::connection();
            $stmt = $db->prepare('SELECT * FROM Users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, (string) $user['password'])) {
                $this->respond($isAjax, false, 'invalid_credentials', '/SRMT/public/?error=invalid_credentials');
                return;
            }

            $this->openSession($user);

            if ($remember) {
                (new RememberMeService($db))->issue((int) $user['id']);
            }

            $role     = (int) $user['role'];
            $redirect = self::ROUTES[$role] ?? null;
            if ($redirect === null) {
                $this->respond($isAjax, false, 'invalid_role', '/SRMT/public/?error=invalid_role');
                return;
            }

            if ($isAjax) {
                echo json_encode([
                    'success'        => true,
                    'message'        => 'Login OK',
                    'user'           => $user,
                    'redirect_route' => $redirect,
                ]);
                return;
            }

            $this->redirect($redirect);
        } catch (Throwable $e) {
            error_log('Login failure: ' . $e->getMessage());
            $this->respond($isAjax, false, 'server_error', '/SRMT/public/?error=server_error');
        }
    }

    public function logout(): void
    {
        $userId = Session::userId();
        if ($userId !== null) {
            (new RememberMeService(Database::connection()))->clear($userId);
        }

        Session::destroy();
        $this->redirect('/SRMT/public/');
    }

    /** @param array<string,mixed> $user */
    private function openSession(array $user): void
    {
        $_SESSION['user_id']            = (int) $user['id'];
        $_SESSION['email']              = $user['email'];
        $_SESSION['role']               = (int) $user['role'];
        $_SESSION['name']               = $user['name'];
        $_SESSION['profile_photo_path'] = $user['profile_photo_path'] ?? null;
        // Super-admin (role=0) has no company — null means "see everything".
        $_SESSION['company_id']         = isset($user['company_id']) && $user['company_id'] !== null
            ? (int) $user['company_id']
            : null;
    }

    private function isAjaxCall(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function respond(bool $isAjax, bool $ok, string $code, string $location): void
    {
        if ($isAjax) {
            echo json_encode(['success' => $ok, 'message' => $code]);
            return;
        }
        $this->redirect($location);
    }

    private function redirect(string $location): void
    {
        header("Location: {$location}");
        exit;
    }
}
