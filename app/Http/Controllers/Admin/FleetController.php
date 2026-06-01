<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\LogRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use DateTimeImmutable;

/**
 * Fleet management: list vehicles, create/edit/delete, assign a driver.
 *
 * Photos are uploaded to /public/uploads/vehicles/ and the DB column
 * stores the public URL (`/SRMT/public/uploads/vehicles/<file>`).
 */
final class FleetController extends BaseController
{
    private const UPLOAD_DIR    = __DIR__ . '/../../../../public/uploads/vehicles/';
    private const UPLOAD_URL    = '/SRMT/public/uploads/vehicles/';
    private const ALERT_WINDOW  = 30; // days

    private VehicleRepository $vehicles;
    private UserRepository    $users;
    private LogRepository     $logs;

    public function __construct()
    {
        $this->vehicles = VehicleRepository::default();
        $this->users    = UserRepository::default();
        $this->logs     = LogRepository::default();
    }

    public function index(): void
    {
        $vehicles    = $this->loadVehiclesWithMeta();
        $drivers     = $this->users->byRole(User::ROLE_DRIVER);

        $alertCount = 0;
        foreach ($vehicles as $v) {
            if ($v['alert']) $alertCount++;
        }

        $this->view('admin.fleet.index', [
            'vehicles'      => $vehicles,
            'drivers'       => $drivers,
            'totalVehicles' => count($vehicles),
            'activeCount'   => count(array_filter($vehicles, static fn(array $v) => $v['status'] === 1)),
            'alertCount'    => $alertCount,
            'flash'         => $_GET['success'] ?? null,
        ]);
    }

    /** POST /admin/save-vehicle.php — create or update a vehicle. */
    public function save(): never
    {
        $this->requirePost();

        $id            = (int) ($this->input('vehicle_id') ?? 0);
        $brand         = (string) $this->input('brand');
        $model         = (string) $this->input('model');
        $plate         = strtoupper((string) $this->input('license_plate'));
        $inspection    = $this->input('inspection_date') ?: null;
        $insurance     = $this->input('insurance_date') ?: null;
        $status        = (int) ($this->input('status') ?? 1);
        $assignedDriver= (int) ($this->input('assigned_driver_id') ?? 0);
        $existingPhoto = $this->input('existing_photo_path');

        if ($brand === '' || $model === '' || $plate === '') {
            $this->abort(422, 'Brand, model and license plate are required.');
        }

        $photoPath = is_string($existingPhoto) && $existingPhoto !== '' ? $existingPhoto : null;
        if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->storePhoto($_FILES['vehicle_photo'], $existingPhoto) ?? $photoPath;
        }

        $data = [
            'brand'            => $brand,
            'model'            => $model,
            'license_plate'    => $plate,
            'inspection_date'  => $inspection,
            'insurance_date'   => $insurance,
            'status'           => $status,
            'photo_path'       => $photoPath,
        ];

        $vehicle = $id > 0
            ? $this->vehicles->update($id, $data)
            : $this->vehicles->create($data);

        // Driver assignment (single-driver-per-vehicle constraint)
        $db = $this->db();
        $db->prepare('UPDATE Users SET assigned_vehicle_id = NULL WHERE assigned_vehicle_id = :v')
            ->execute(['v' => $vehicle->id]);

        if ($assignedDriver > 0) {
            $db->prepare('UPDATE Users SET assigned_vehicle_id = NULL WHERE id = :d')
                ->execute(['d' => $assignedDriver]);
            $db->prepare('UPDATE Users SET assigned_vehicle_id = :v WHERE id = :d')
                ->execute(['v' => $vehicle->id, 'd' => $assignedDriver]);
        }

        $verb = $id > 0 ? 'updated' : 'created';
        $this->logs->record("Admin {$verb} vehicle {$vehicle->licensePlate} (#{$vehicle->id})");
        $this->redirect('/SRMT/public/admin/fleet.php?success=' . $verb);
    }

    /** POST /admin/save-vehicle.php?action=delete — delete vehicle (POST-only, prevents CSRF via GET). */
    public function delete(): never
    {
        $this->requirePost();
        $id = (int) ($this->input('id') ?? 0);
        if ($id <= 0) {
            $this->abort(400, 'Missing vehicle id.');
        }

        $vehicle = $this->vehicles->find($id);
        if ($vehicle === null) {
            $this->abort(404, 'Vehicle not found.');
        }

        if ($vehicle->photoPath !== null) {
            $absolute = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($vehicle->photoPath, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }

        $this->db()->prepare('UPDATE Users SET assigned_vehicle_id = NULL WHERE assigned_vehicle_id = :v')
            ->execute(['v' => $id]);
        $this->vehicles->delete($id);

        $this->logs->record("Admin deleted vehicle {$vehicle->licensePlate} (#{$id})");
        $this->redirect('/SRMT/public/admin/fleet.php?success=deleted');
    }

    /** Decorates Vehicles with their assigned driver + paperwork alert. */
    private function loadVehiclesWithMeta(): array
    {
        $rows  = $this->vehicles->allWithDriver();
        $today = new DateTimeImmutable('today');
        return array_map(function (array $row) use ($today): array {
            $alert = false;
            if (!empty($row['inspection_date']) && !empty($row['insurance_date'])) {
                $insp = new DateTimeImmutable($row['inspection_date']);
                $insu = new DateTimeImmutable($row['insurance_date']);
                $alert = $today->diff($insp)->days < self::ALERT_WINDOW
                      || $today->diff($insu)->days < self::ALERT_WINDOW;
            }
            $row['alert']      = $alert;
            $row['photo_url']  = !empty($row['photo_path']) ? $row['photo_path'] : null;
            return $row;
        }, $rows);
    }

    /** @param array<string,mixed> $file move_uploaded_file payload */
    private function storePhoto(array $file, ?string $existing): ?string
    {
        if (!is_dir(self::UPLOAD_DIR)) {
            @mkdir(self::UPLOAD_DIR, 0755, true);
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        $filename = 'vehicle_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
        $target   = self::UPLOAD_DIR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            error_log('vehicle photo upload failed: ' . ($file['error'] ?? 'unknown'));
            return null;
        }

        if ($existing !== null && $existing !== '') {
            $oldAbs = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($existing, '/');
            if (is_file($oldAbs)) {
                @unlink($oldAbs);
            }
        }
        return self::UPLOAD_URL . $filename;
    }
}
