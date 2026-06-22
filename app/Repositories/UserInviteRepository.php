<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Invite slots: an admin creates a vacancy (role + company), shares the link,
 * and the invitee fills in their own details to finish registration.
 */
final class UserInviteRepository
{
    public function __construct(private readonly PDO $db) {}

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** Creates an invite and returns [id, token]. Expires in $days days. */
    public function create(
        int     $companyId,
        int     $role,
        ?string $label,
        ?int    $createdBy,
        int     $days            = 7,
        ?string $driverCode      = null,
        ?string $defaultPayBasis = null,
    ): array {
        $token    = bin2hex(random_bytes(24));
        $driverMeta = null;
        if ($driverCode !== null || $defaultPayBasis !== null) {
            $driverMeta = json_encode(array_filter([
                'driver_code'       => $driverCode      ?: null,
                'default_pay_basis' => $defaultPayBasis ?: null,
            ]));
        }
        $this->db->prepare('
            INSERT INTO UserInvites (token, company_id, role, label, created_by, expires_at, driver_meta)
            VALUES (:t, :cid, :role, :label, :by, DATE_ADD(NOW(), INTERVAL :days DAY), :dm)
        ')->execute([
            't'     => $token,
            'cid'   => $companyId,
            'role'  => $role,
            'label' => $label !== '' ? $label : null,
            'by'    => $createdBy,
            'days'  => $days,
            'dm'    => $driverMeta,
        ]);
        return ['id' => (int) $this->db->lastInsertId(), 'token' => $token];
    }

    /** Returns a valid (unused, unexpired) invite for the token, or null. */
    public function findValidByToken(string $token): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM UserInvites
            WHERE token = :t AND used_at IS NULL AND expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markUsed(int $id, int $userId): void
    {
        $this->db->prepare('UPDATE UserInvites SET used_at = NOW(), used_by = :u WHERE id = :id')
            ->execute(['u' => $userId, 'id' => $id]);
    }

    /** Pending + recently-used invites for a company. */
    public function allFor(int $companyId): array
    {
        $stmt = $this->db->prepare('
            SELECT i.*, u.name AS used_by_name
            FROM UserInvites i
            LEFT JOIN Users u ON i.used_by = u.id
            WHERE i.company_id = :cid
            ORDER BY i.created_at DESC
            LIMIT 50
        ');
        $stmt->execute(['cid' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id, int $companyId): void
    {
        $this->db->prepare('DELETE FROM UserInvites WHERE id = :id AND company_id = :cid')
            ->execute(['id' => $id, 'cid' => $companyId]);
    }
}
