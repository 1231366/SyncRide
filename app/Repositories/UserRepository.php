<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Support\Database;
use App\Support\Session;
use PDO;
use RuntimeException;

final class UserRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    /** Reads company scope from the active session automatically. */
    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    /**
     * All users belonging to this company — including drivers shared in via
     * UserCompanies. Super-admin (companyId = null) sees everyone.
     * @return array<User>
     */
    public function all(): array
    {
        if ($this->companyId === null) {
            $stmt = $this->db->query('SELECT * FROM Users ORDER BY name');
            return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $sql = '
            SELECT DISTINCT u.* FROM Users u
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE u.company_id = :cid OR uc.company_id = :cid3
            ORDER BY u.name
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->companyId, 'cid2' => $this->companyId, 'cid3' => $this->companyId]);
        return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Users with a given role belonging to this company — including shared users
     * added via UserCompanies (e.g. drivers shared across partner companies).
     * @return array<User>
     */
    public function byRole(int $role): array
    {
        if ($this->companyId === null) {
            $sql  = 'SELECT * FROM Users WHERE role = :role ORDER BY name';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['role' => $role]);
        } else {
            // Primary company members + shared members via UserCompanies
            $sql  = '
                SELECT DISTINCT u.* FROM Users u
                LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
                WHERE u.role = :role
                  AND (u.company_id = :cid OR uc.company_id = :cid3)
                ORDER BY u.name
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['role' => $role, 'cid' => $this->companyId, 'cid2' => $this->companyId, 'cid3' => $this->companyId]);
        }
        return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Add an existing user to a company via the UserCompanies join table. */
    public function addToCompany(int $userId, int $companyId): void
    {
        $this->db->prepare('
            INSERT IGNORE INTO UserCompanies (user_id, company_id) VALUES (:uid, :cid)
        ')->execute(['uid' => $userId, 'cid' => $companyId]);
    }

    /** True if the user belongs to the company (primary OR shared). */
    public function isInCompany(int $userId, int $companyId): bool
    {
        $stmt = $this->db->prepare('
            SELECT 1 FROM Users u
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE u.id = :uid AND (u.company_id = :cid OR uc.company_id = :cid3)
            LIMIT 1
        ');
        $stmt->execute(['uid' => $userId, 'cid' => $companyId, 'cid2' => $companyId, 'cid3' => $companyId]);
        return (bool) $stmt->fetchColumn();
    }

    public function find(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    /** Fuzzy name match, scoped to this repo's company — SyncAI resolving "the Diogo driver" to a real user id. */
    public function findByNameLike(string $name): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE name LIKE :name ' . $this->companyClause('AND') . ' LIMIT 1');
        $stmt->execute(array_merge(['name' => '%' . $name . '%'], $this->companyBindings()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    /**
     * Base de pagamento habitual do motorista (viatura empresa vs carro próprio).
     * Usada para pré-selecionar o preçário-motorista na atribuição.
     */
    public function defaultPayBasis(int $driverId): string
    {
        $stmt = $this->db->prepare('SELECT default_pay_basis FROM Users WHERE id = :id');
        $stmt->execute(['id' => $driverId]);
        $basis = (string) $stmt->fetchColumn();
        return $basis === 'own_vehicle' ? 'own_vehicle' : 'company_vehicle';
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    /**
     * Procura um condutor pela sigla interna (driver_code) no contexto desta empresa.
     * Devolve o primeiro condutor com esse código, ou null se não encontrar.
     */
    public function findByDriverCode(string $code): ?User
    {
        if (trim($code) === '') {
            return null;
        }
        if ($this->companyId !== null) {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT u.* FROM Users u
                 LEFT JOIN UserCompanies uc ON uc.user_id = u.id
                 WHERE u.driver_code = :code AND u.role = 2
                   AND (u.company_id = :cid OR uc.company_id = :cid2)
                 LIMIT 1'
            );
            $stmt->execute(['code' => $code, 'cid' => $this->companyId, 'cid2' => $this->companyId]);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM Users WHERE driver_code = :code AND role = 2 LIMIT 1');
            $stmt->execute(['code' => $code]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM Users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Insert a new user scoped to the current company.
     * Pass `company_id` in $data to override (useful for super-admin creating users).
     */
    public function create(array $data): User
    {
        $this->validateForCreate($data);
        $cid = isset($data['company_id']) ? (int) $data['company_id'] : $this->companyId;

        $stmt = $this->db->prepare('
            INSERT INTO Users (email, password, role, name, phone, profile_photo_path, company_id)
            VALUES (:email, :password, :role, :name, :phone, :photo, :company_id)
        ');
        $stmt->execute([
            'email'      => $data['email'],
            'password'   => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'       => (int) $data['role'],
            'name'       => $data['name'],
            'phone'      => $data['phone'] ?? null,
            'photo'      => $data['profile_photo_path'] ?? null,
            'company_id' => $cid,
        ]);

        $id = (int) $this->db->lastInsertId();

        // Persist driver-specific fields when provided at creation time
        $driverUpdate = array_filter([
            'driver_code'       => $data['driver_code']       ?? null,
            'default_pay_basis' => $data['default_pay_basis'] ?? null,
        ], static fn($v) => $v !== null && $v !== '');
        if ($driverUpdate !== []) {
            $this->update($id, $driverUpdate);
        }

        $user = $this->find($id);
        if ($user === null) {
            throw new RuntimeException("UserRepository::create — could not reload user {$id}");
        }
        return $user;
    }

    public function update(int $id, array $data): User
    {
        if ($this->find($id) === null) {
            throw new RuntimeException("UserRepository::update — user {$id} not found");
        }

        $sets   = [];
        $params = ['id' => $id];

        foreach (['email', 'name', 'phone', 'profile_photo_path'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]       = "{$col} = :{$col}";
                $params[$col] = $data[$col];
            }
        }
        if (array_key_exists('role', $data)) {
            $sets[]         = 'role = :role';
            $params['role'] = (int) $data['role'];
        }
        if (array_key_exists('company_id', $data)) {
            $sets[]               = 'company_id = :company_id';
            $params['company_id'] = $data['company_id'];
        }
        if (array_key_exists('driver_code', $data)) {
            $dc = $data['driver_code'] !== null && trim((string) $data['driver_code']) !== ''
                ? trim((string) $data['driver_code'])
                : null;
            $sets[]              = 'driver_code = :driver_code';
            $params['driver_code'] = $dc;
        }
        if (array_key_exists('default_pay_basis', $data) && in_array($data['default_pay_basis'], ['company_vehicle', 'own_vehicle'], true)) {
            $sets[]                    = 'default_pay_basis = :default_pay_basis';
            $params['default_pay_basis'] = $data['default_pay_basis'];
        }
        if (!empty($data['password'])) {
            $sets[]             = 'password = :password';
            $params['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        if ($sets !== []) {
            $this->db->prepare('UPDATE Users SET ' . implode(', ', $sets) . ' WHERE id = :id')
                ->execute($params);
        }

        return $this->find($id) ?? throw new RuntimeException('User vanished after update');
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Users WHERE id = :id')->execute(['id' => $id]);
    }

    /** Verifies a plaintext password against the stored hash for a given user id. */
    public function verifyPassword(int $userId, string $plain): bool
    {
        $stmt = $this->db->prepare('SELECT password FROM Users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        return $hash !== false && password_verify($plain, (string) $hash);
    }

    /** Updates only the password for a given user (used by the self-service profile flow). */
    public function setPassword(int $userId, string $plain): void
    {
        $this->db->prepare('UPDATE Users SET password = :p WHERE id = :id')
            ->execute(['p' => password_hash($plain, PASSWORD_BCRYPT), 'id' => $userId]);
    }

    public function authenticate(string $email, string $password): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($password, (string) $row['password'])) {
            return null;
        }
        return User::fromRow($row);
    }

    // ------------------------------------------------------------------ helpers

    private function companyClause(string $prefix = 'WHERE'): string
    {
        return $this->companyId !== null ? "{$prefix} company_id = :company_id" : '';
    }

    private function companyBindings(): array
    {
        return $this->companyId !== null ? ['company_id' => $this->companyId] : [];
    }

    private function validateForCreate(array $data): void
    {
        foreach (['email', 'password', 'role', 'name'] as $required) {
            if (empty($data[$required])) {
                throw new RuntimeException("UserRepository::create — missing required field: {$required}");
            }
        }
        if ($this->emailExists((string) $data['email'])) {
            throw new RuntimeException("UserRepository::create — email already in use: {$data['email']}");
        }
    }
}
