<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vehicle;
use App\Support\Database;
use App\Support\Session;
use PDO;
use RuntimeException;

final class VehicleRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    /** @return array<Vehicle> */
    public function all(): array
    {
        $sql  = 'SELECT * FROM Vehicles WHERE 1=1 ' . $this->companyClause('AND') . ' ORDER BY brand, model';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        return array_map(Vehicle::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Vehicle> */
    public function active(): array
    {
        $sql  = 'SELECT * FROM Vehicles WHERE status = 1 ' . $this->companyClause('AND') . ' ORDER BY brand, model';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        return array_map(Vehicle::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?Vehicle
    {
        $stmt = $this->db->prepare('SELECT * FROM Vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Vehicle::fromRow($row) : null;
    }

    public function findByPlate(string $licensePlate): ?Vehicle
    {
        $stmt = $this->db->prepare('SELECT * FROM Vehicles WHERE license_plate = :p');
        $stmt->execute(['p' => $licensePlate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Vehicle::fromRow($row) : null;
    }

    public function create(array $data): Vehicle
    {
        foreach (['brand', 'model', 'license_plate'] as $r) {
            if (empty($data[$r])) {
                throw new RuntimeException("VehicleRepository::create — missing field: {$r}");
            }
        }
        if ($this->findByPlate((string) $data['license_plate']) !== null) {
            throw new RuntimeException("Vehicle with plate {$data['license_plate']} already exists.");
        }
        $cid  = isset($data['company_id']) ? (int) $data['company_id'] : $this->companyId;
        $stmt = $this->db->prepare('
            INSERT INTO Vehicles (brand, model, license_plate, inspection_date, insurance_date, status, photo_path, company_id)
            VALUES (:brand, :model, :plate, :insp, :ins, :status, :photo, :company_id)
        ');
        $stmt->execute([
            'brand'      => $data['brand'],
            'model'      => $data['model'],
            'plate'      => $data['license_plate'],
            'insp'       => $data['inspection_date'] ?? null,
            'ins'        => $data['insurance_date']  ?? null,
            'status'     => (int) ($data['status'] ?? Vehicle::STATUS_ACTIVE),
            'photo'      => $data['photo_path'] ?? null,
            'company_id' => $cid,
        ]);
        return $this->find((int) $this->db->lastInsertId())
            ?? throw new RuntimeException('VehicleRepository::create — reload failed');
    }

    public function update(int $id, array $data): Vehicle
    {
        $sets   = [];
        $params = ['id' => $id];
        foreach (['brand', 'model', 'license_plate', 'inspection_date', 'insurance_date', 'photo_path'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]       = "{$col} = :{$col}";
                $params[$col] = $data[$col];
            }
        }
        if (array_key_exists('status', $data)) {
            $sets[]           = 'status = :status';
            $params['status'] = (int) $data['status'];
        }
        if ($sets !== []) {
            $this->db->prepare('UPDATE Vehicles SET ' . implode(', ', $sets) . ' WHERE id = :id')
                ->execute($params);
        }
        return $this->find($id) ?? throw new RuntimeException('Vehicle vanished after update');
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Vehicles WHERE id = :id')->execute(['id' => $id]);
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
}
