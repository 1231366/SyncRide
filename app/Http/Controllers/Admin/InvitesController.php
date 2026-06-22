<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\LogRepository;
use App\Repositories\UserInviteRepository;
use App\Support\Session;

/**
 * Admin-side management of invite links: create a vacancy (role + company),
 * list pending/used invites, and revoke them.
 */
final class InvitesController extends BaseController
{
    private UserInviteRepository $invites;
    private LogRepository        $logs;

    public function __construct()
    {
        $this->invites = UserInviteRepository::default();
        $this->logs    = LogRepository::default();
    }

    /** GET /admin/invites-data.php — JSON list of invites for this company. */
    public function data(): never
    {
        $companyId = Session::companyId() ?? 0;
        $rows      = $companyId > 0 ? $this->invites->allFor($companyId) : [];
        $base      = $this->baseUrl();

        $data = array_map(static function (array $r) use ($base): array {
            $roleLabel = match ((int) $r['role']) {
                User::ROLE_DRIVER  => 'Driver',
                User::ROLE_PARTNER => 'Partner',
                User::ROLE_ADMIN   => 'Admin',
                default            => 'User',
            };
            $used    = $r['used_at'] !== null;
            $expired = !$used && strtotime((string) $r['expires_at']) < time();
            return [
                'id'      => (int) $r['id'],
                'label'   => (string) ($r['label'] ?? ''),
                'role'    => $roleLabel,
                'link'    => $base . '/SRMT/public/invite.php?token=' . $r['token'],
                'status'  => $used ? 'used' : ($expired ? 'expired' : 'pending'),
                'used_by' => (string) ($r['used_by_name'] ?? ''),
                'expires' => substr((string) $r['expires_at'], 0, 16),
            ];
        }, $rows);

        $this->json(['data' => $data]);
    }

    /** POST /admin/invite-create.php */
    public function store(): never
    {
        $this->requirePost();

        $companyId = Session::companyId();
        $role      = (int) ($this->input('role') ?? 0);
        $label     = trim((string) ($this->input('label') ?? ''));

        if ($companyId === null) {
            $this->json(['success' => false, 'error' => 'Super-admin must act within a company.'], 422);
        }
        if (!in_array($role, [User::ROLE_DRIVER, User::ROLE_PARTNER, User::ROLE_ADMIN], true)) {
            $this->json(['success' => false, 'error' => 'Invalid role.'], 422);
        }

        $driverCode = $role === User::ROLE_DRIVER ? (trim((string) ($this->input('driver_code') ?? ''))) : null;
        $payBasis   = $role === User::ROLE_DRIVER ? ($this->input('default_pay_basis') ?? null) : null;
        if (!in_array($payBasis, ['company_vehicle', 'own_vehicle'], true)) {
            $payBasis = null;
        }

        $invite = $this->invites->create($companyId, $role, $label, Session::userId(), driverCode: $driverCode, defaultPayBasis: $payBasis);
        $link   = $this->baseUrl() . '/SRMT/public/invite.php?token=' . $invite['token'];

        $this->logs->record("Admin created invite link (role={$role}) for company #{$companyId}");
        $this->json(['success' => true, 'link' => $link]);
    }

    /** POST /admin/invite-delete.php */
    public function destroy(): never
    {
        $this->requirePost();

        $companyId = Session::companyId();
        $id        = (int) ($this->input('id') ?? 0);

        if ($companyId === null || $id <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data.'], 422);
        }

        $this->invites->delete($id, $companyId);
        $this->json(['success' => true]);
    }

    /** Scheme + host for building absolute invite links. */
    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}
