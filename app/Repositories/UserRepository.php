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

    /** @return array<User> */
    public function all(): array
    {
        $sql  = 'SELECT * FROM Users WHERE 1=1 ' . $this->companyClause('AND') . ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<User> */
    public function byRole(int $role): array
    {
        $sql  = 'SELECT * FROM Users WHERE role = :role ' . $this->companyClause('AND') . ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['role' => $role], $this->companyBindings()));
        return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE email = :email');
        $stmt->execute(['email' => $email]);
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

        $id   = (int) $this->db->lastInsertId();
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
            $sets[]              = 'company_id = :company_id';
            $params['company_id'] = $data['company_id'];
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
