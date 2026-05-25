<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use App\Support\Database;
use PDO;

final class CompanyRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** @return Company[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM Companies ORDER BY name');
        return array_map(Company::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?Company
    {
        $stmt = $this->db->prepare('SELECT * FROM Companies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Company::fromRow($row) : null;
    }

    public function findBySlug(string $slug): ?Company
    {
        $stmt = $this->db->prepare('SELECT * FROM Companies WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Company::fromRow($row) : null;
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO Companies (name, slug, created_at) VALUES (:name, :slug, NOW())'
        );
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $slug): void
    {
        $this->db->prepare('UPDATE Companies SET name = :name, slug = :slug WHERE id = :id')
            ->execute(['name' => $name, 'slug' => $slug, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Companies WHERE id = :id')->execute(['id' => $id]);
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM Companies WHERE slug = :slug AND id != :id');
        $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Returns all admin users (role=1) for a given company.
     * @return array<array{id:int,name:string,email:string}>
     */
    public function adminsForCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email FROM Users WHERE role = 1 AND company_id = :cid ORDER BY name'
        );
        $stmt->execute(['cid' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns admins for all companies, keyed by company_id.
     * @return array<int, array<array{id:int,name:string,email:string}>>
     */
    public function allAdminsByCompany(): array
    {
        $stmt   = $this->db->query('SELECT id, name, email, company_id FROM Users WHERE role = 1 ORDER BY company_id, name');
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['company_id']][] = [
                'id'    => (int) $row['id'],
                'name'  => (string) $row['name'],
                'email' => (string) $row['email'],
            ];
        }
        return $result;
    }

    /** Per-company summary stats for the super-admin dashboard. */
    public function stats(): array
    {
        $stmt = $this->db->query('
            SELECT
                c.id,
                c.name,
                c.slug,
                c.created_at,
                (SELECT COUNT(*) FROM Users    u WHERE u.company_id = c.id AND u.role = 1) AS admins,
                (SELECT COUNT(*) FROM Users    u WHERE u.company_id = c.id AND u.role = 2) AS drivers,
                (SELECT COUNT(*) FROM Users    u WHERE u.company_id = c.id AND u.role = 3) AS partners,
                (SELECT COUNT(*) FROM Services s WHERE s.company_id = c.id)                AS total_rides,
                (SELECT COUNT(*) FROM Services s WHERE s.company_id = c.id
                    AND s.serviceDate = CURDATE())                                          AS rides_today,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.company_id = c.id)                AS vehicles
            FROM Companies c
            ORDER BY c.name
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
