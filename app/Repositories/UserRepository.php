<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Support\Database;
use PDO;
use RuntimeException;

/**
 * Data-access layer for the `Users` table.
 *
 * Method names are English; SQL column names stay Portuguese to match
 * the production schema (untouched on purpose). Every method returns
 * either an `App\Models\User`, an `array<User>`, or a scalar — never
 * a raw PDO row.
 */
final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** @return array<User> */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM Users ORDER BY name');
        return array_map(User::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<User> */
    public function byRole(int $role): array
    {
        $stmt = $this->db->prepare('SELECT * FROM Users WHERE role = :role ORDER BY name');
        $stmt->execute(['role' => $role]);
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
     * Insert a new user. Password is hashed with PASSWORD_BCRYPT.
     * Returns the newly-created user.
     */
    public function create(array $data): User
    {
        $this->validateForCreate($data);

        $stmt = $this->db->prepare('
            INSERT INTO Users (email, password, role, name, phone, profile_photo_path)
            VALUES (:email, :password, :role, :name, :phone, :photo)
        ');
        $stmt->execute([
            'email'    => $data['email'],
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'     => (int) $data['role'],
            'name'     => $data['name'],
            'phone'    => $data['phone'] ?? null,
            'photo'    => $data['profile_photo_path'] ?? null,
        ]);

        $id = (int) $this->db->lastInsertId();
        $user = $this->find($id);
        if ($user === null) {
            throw new RuntimeException("UserRepository::create — could not reload user {$id}");
        }
        return $user;
    }

    /**
     * Update an existing user. `password` is optional — when present and
     * non-empty it is re-hashed; otherwise the stored hash is preserved.
     * Returns the refreshed user.
     */
    public function update(int $id, array $data): User
    {
        if ($this->find($id) === null) {
            throw new RuntimeException("UserRepository::update — user {$id} not found");
        }

        $sets   = [];
        $params = ['id' => $id];

        foreach (['email', 'name', 'phone', 'profile_photo_path'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]         = "{$col} = :{$col}";
                $params[$col]   = $data[$col];
            }
        }
        if (array_key_exists('role', $data)) {
            $sets[]         = 'role = :role';
            $params['role'] = (int) $data['role'];
        }
        if (!empty($data['password'])) {
            $sets[]             = 'password = :password';
            $params['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        if ($sets !== []) {
            $sql = 'UPDATE Users SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $this->db->prepare($sql)->execute($params);
        }

        return $this->find($id) ?? throw new RuntimeException('User vanished after update');
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Users WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Verify a credential pair. Returns the user on success, null otherwise.
     * Use this from controllers instead of duplicating password_verify.
     */
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
