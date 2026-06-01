<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

final class CompanyPartnershipRepository
{
    public function __construct(private readonly PDO $db) {}

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** All partnerships (any status) involving this company. */
    public function allFor(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT cp.*,
                   ca.name AS company_a_name,
                   cb.name AS company_b_name
            FROM CompanyPartnerships cp
            JOIN Companies ca ON cp.company_id_a = ca.id
            JOIN Companies cb ON cp.company_id_b = cb.id
            WHERE cp.company_id_a = :cid OR cp.company_id_b = :cid2
            ORDER BY cp.created_at DESC
        ");
        $stmt->execute(['cid' => $companyId, 'cid2' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<array{partner_id:int, partner_name:string}> */
    public function activePartnersFor(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CASE WHEN cp.company_id_a = :cid  THEN cp.company_id_b ELSE cp.company_id_a END AS partner_id,
                CASE WHEN cp.company_id_a = :cid2 THEN cb.name         ELSE ca.name         END AS partner_name
            FROM CompanyPartnerships cp
            JOIN Companies ca ON cp.company_id_a = ca.id
            JOIN Companies cb ON cp.company_id_b = cb.id
            WHERE (cp.company_id_a = :cid3 OR cp.company_id_b = :cid4)
              AND cp.status = 'active'
        ");
        $stmt->execute(['cid' => $companyId, 'cid2' => $companyId, 'cid3' => $companyId, 'cid4' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isActive(int $companyA, int $companyB): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM CompanyPartnerships
            WHERE ((company_id_a = :a AND company_id_b = :b)
                OR (company_id_a = :b2 AND company_id_b = :a2))
              AND status = 'active'
        ");
        $stmt->execute(['a' => $companyA, 'b' => $companyB, 'a2' => $companyA, 'b2' => $companyB]);
        return (bool) $stmt->fetchColumn();
    }

    public function exists(int $companyA, int $companyB): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM CompanyPartnerships
            WHERE (company_id_a = :a AND company_id_b = :b)
               OR (company_id_a = :b2 AND company_id_b = :a2)
        ");
        $stmt->execute(['a' => $companyA, 'b' => $companyB, 'a2' => $companyA, 'b2' => $companyB]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $fromCompanyId, int $toCompanyId): int
    {
        $this->db->prepare("
            INSERT INTO CompanyPartnerships (company_id_a, company_id_b, status)
            VALUES (:a, :b, 'pending')
        ")->execute(['a' => $fromCompanyId, 'b' => $toCompanyId]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM CompanyPartnerships WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE CompanyPartnerships SET status = :s WHERE id = :id')
            ->execute(['s' => $status, 'id' => $id]);
    }

    /**
     * Cross-company delegation stats, optionally filtered by date range and partner company.
     * @return array{sent: array<array<string,mixed>>, received: array<array<string,mixed>>}
     */
    public function crossStats(
        int     $companyId,
        ?string $from      = null,
        ?string $to        = null,
        ?int    $partnerId = null
    ): array {
        $dateWhere  = '';
        $dateParams = [];
        if ($from !== null) { $dateWhere .= ' AND s.serviceDate >= :from'; $dateParams['from'] = $from; }
        if ($to   !== null) { $dateWhere .= ' AND s.serviceDate <= :to';   $dateParams['to']   = $to; }

        $sentPartnerWhere  = $partnerId !== null ? ' AND s.company_id = :pid'          : '';
        $recvPartnerWhere  = $partnerId !== null ? ' AND s.original_company_id = :pid' : '';
        $partnerParam      = $partnerId !== null ? ['pid' => $partnerId]               : [];

        $sentStmt = $this->db->prepare("
            SELECT s.company_id AS to_company_id,
                   c.name       AS to_company_name,
                   COUNT(*)     AS count
            FROM Services s
            JOIN Companies c ON s.company_id = c.id
            WHERE s.original_company_id = :cid{$dateWhere}{$sentPartnerWhere}
            GROUP BY s.company_id, c.name
            ORDER BY count DESC
        ");
        $sentStmt->execute(array_merge(['cid' => $companyId], $dateParams, $partnerParam));

        $recvStmt = $this->db->prepare("
            SELECT s.original_company_id AS from_company_id,
                   c.name                AS from_company_name,
                   COUNT(*)              AS count
            FROM Services s
            JOIN Companies c ON s.original_company_id = c.id
            WHERE s.company_id = :cid AND s.original_company_id IS NOT NULL{$dateWhere}{$recvPartnerWhere}
            GROUP BY s.original_company_id, c.name
            ORDER BY count DESC
        ");
        $recvStmt->execute(array_merge(['cid' => $companyId], $dateParams, $partnerParam));

        return [
            'sent'     => $sentStmt->fetchAll(PDO::FETCH_ASSOC),
            'received' => $recvStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
