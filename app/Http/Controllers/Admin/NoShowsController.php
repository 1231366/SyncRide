<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Services\NoShowMailer;

/**
 * Manages the no-show incident records for the admin panel,
 * and receives photo uploads from drivers in the field.
 */
final class NoShowsController extends BaseController
{
    private ServiceRepository $services;
    private LogRepository     $logs;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
        $this->logs     = LogRepository::default();
    }

    /** GET /admin/no-shows.php */
    public function index(): void
    {
        $this->view('admin.no-shows.index', []);
    }

    /** GET /admin/no-shows-data.php — DataTable JSON feed. */
    public function data(): void
    {
        $rows = $this->services->listNoShowsForAdmin();
        $data = array_map([$this, 'formatRow'], $rows);
        $this->json(['data' => $data]);
    }

    /**
     * POST /admin/upload-no-show.php — receive photo from driver app.
     *
     * Accessible to both admins (role=1) and drivers (role=2).
     * Returns JSON; does not redirect.
     */
    public function upload(): void
    {
        header('Content-Type: application/json');

        $body    = $this->jsonBody();
        $tripId  = (int) ($body['trip_id'] ?? 0);
        $imgData = (string) ($body['image_data'] ?? '');
        $lat     = isset($body['lat'])  ? (string) $body['lat']  : null;
        $lng     = isset($body['lng'])  ? (string) $body['lng']  : null;

        if ($tripId <= 0 || $imgData === '') {
            $this->json(['success' => false, 'message' => 'Missing data.'], 400);
        }

        $parts     = explode(';base64,', $imgData, 2);
        $imgBytes  = base64_decode($parts[1] ?? '');
        if ($imgBytes === false || $imgBytes === '') {
            $this->json(['success' => false, 'message' => 'Invalid image data.'], 400);
        }

        $appRoot   = dirname(__DIR__, 4);
        $uploadDir = $appRoot . '/public/uploads/no_shows/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName   = 'noshow_' . $tripId . '_' . time() . '.jpg';
        $serverPath = $uploadDir . $fileName;
        $dbPath     = 'uploads/no_shows/' . $fileName;

        if (file_put_contents($serverPath, $imgBytes) === false) {
            $this->json(['success' => false, 'message' => 'Failed to save image.'], 500);
        }

        $this->services->markNoShow($tripId, $dbPath, $lat, $lng);
        $this->logs->record("Driver no-show reported for ride #{$tripId}");

        $s        = $this->settings();
        $tripData = $this->services->findWithPartner($tripId);
        if ($tripData !== null && $s->noShowEnabled()) {
            try {
                (new NoShowMailer())->send(
                    $tripId, $tripData, $serverPath, $lat, $lng,
                    $s->noShowAgencyEmail(),
                    $s->noShowCcList(),
                    $s->noShowMyCopy()
                );
            } catch (\Throwable $e) {
                error_log('NoShowMailer failed for ride #' . $tripId . ': ' . $e->getMessage());
            }
        }

        $this->json(['success' => true, 'message' => 'No-show reported successfully!']);
    }

    private function formatRow(array $row): array
    {
        $dateTime   = htmlspecialchars($row['serviceDate'] . ' ' . substr((string) $row['serviceStartTime'], 0, 5));
        $driverName = $row['driverName'] ? htmlspecialchars((string) $row['driverName']) : 'N.A.';
        $route      = htmlspecialchars($row['serviceStartPoint'] . ' → ' . $row['serviceTargetPoint']);
        $photoPath  = htmlspecialchars((string) ($row['noShowPhotoPath'] ?? ''));
        $id         = (int) $row['ID'];

        $actions = '<div class="btn-group-sm d-flex justify-content-center">'
            . '<a href="#" class="btn btn-info rounded-circle" onclick="event.preventDefault();openPhotoModal(' . $id . ',\'' . $photoPath . '\')" title="View Photo"><i class="bi bi-camera-fill"></i></a>'
            . '<a href="' . $photoPath . '" class="btn btn-success rounded-circle" download="NoShow-Ride-' . $id . '.jpg" title="Download"><i class="bi bi-download"></i></a>'
            . '</div>';

        return [
            'id'        => $id,
            'data_hora' => $dateTime,
            'condutor'  => '<span class="badge text-bg-secondary">' . $driverName . '</span>',
            'rota'      => $route,
            'acoes'     => $actions,
        ];
    }
}
