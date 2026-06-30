<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Services\FCMSender;
use App\Services\NoShowMailer;
use App\Services\NoShowReportGenerator;

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
        // Drivers (incl. shared) act on rides assigned to them across companies;
        // admins are scoped to their own company.
        $this->services = \App\Support\Session::role() === 2
            ? ServiceRepository::forDriverContext()
            : ServiceRepository::default();
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

        // Authorisation: a driver may only act on rides assigned to them (any company
        // they belong to); an admin only on rides within their own company.
        $ride = $this->services->find($tripId);
        if ($ride === null) {
            $this->json(['success' => false, 'message' => 'Ride not found.'], 404);
        }
        if (\App\Support\Session::role() === 2) {
            if ($this->services->assignedDriver($tripId) !== \App\Support\Session::userId()) {
                $this->json(['success' => false, 'message' => 'Not your ride.'], 403);
            }
        } else {
            $companyId = \App\Support\Session::companyId();
            if ($companyId !== null && $ride->companyId !== $companyId) {
                $this->json(['success' => false, 'message' => 'Not authorised.'], 403);
            }
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

        $this->logs->record("Driver no-show reported for ride #{$tripId}");

        if ($ride->companyId !== null) {
            $d = \DateTime::createFromFormat('Y-m-d', $ride->date);
            $t = \DateTime::createFromFormat('H:i:s', $ride->startTime)
              ?: \DateTime::createFromFormat('H:i', $ride->startTime);
            $dateStr = $d ? $d->format('d/m') : $ride->date;
            $timeStr = $t ? $t->format('H:i') : $ride->startTime;
            $client  = $ride->clientName ?? 'Cliente';
            $origin  = $this->shortAddress($ride->pickupAddress);
            $dest    = $this->shortAddress($ride->dropoffAddress);

            FCMSender::sendToAdmins(
                $ride->companyId,
                '⚠️ No-show reportado',
                "{$client} · {$dateStr} às {$timeStr}\n{$origin} → {$dest}",
                ['ride_id' => (string) $tripId]
            );
        }

        $tripData   = $this->services->findWithPartner($tripId);
        $reportPath = null;
        $reportDb   = null;

        // Moment the no-show is declared — used both in the report (waiting time)
        // and persisted, so it always matches what the PDF shows.
        $noShowAt = date('Y-m-d H:i:s');

        // Generate PDF report
        try {
            $reportDb   = (new NoShowReportGenerator())->generate($tripId, $tripData ?? [], $serverPath, $lat, $lng, $noShowAt);
            $reportPath = dirname(__DIR__, 4) . '/public/' . $reportDb;
        } catch (\Throwable $e) {
            error_log('NoShowReportGenerator failed for ride #' . $tripId . ': ' . $e->getMessage());
        }

        $this->services->markNoShow($tripId, $dbPath, $lat, $lng, $reportDb, $noShowAt);

        // Use the settings of the company that OWNS the ride (not the driver's session
        // company — a shared driver may report a no-show for another company's ride).
        $ownerCompanyId = (int) ($tripData['company_id'] ?? $ride->companyId ?? 0) ?: null;
        $s = new \App\Repositories\TenantSettingsRepository($this->db(), $ownerCompanyId);
        if ($tripData !== null && $s->noShowEnabled()) {
            try {
                (new NoShowMailer())->send(
                    $tripId, $tripData, $serverPath, $lat, $lng,
                    $s->noShowAgencyEmail(),
                    $s->noShowCcList(),
                    $s->noShowMyCopy(),
                    $reportPath,
                    $s->noShowCcAlways()
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
        $rawPath    = (string) ($row['noShowPhotoPath']   ?? '');
        $rawReport  = (string) ($row['noShowReportPath'] ?? '');
        $photoUrl   = $rawPath   !== '' ? '/SRMT/public/' . ltrim($rawPath,   '/') : '';
        $reportUrl  = $rawReport !== '' ? '/SRMT/public/' . ltrim($rawReport, '/') : '';
        $photoPath  = htmlspecialchars($photoUrl);
        $reportHref = htmlspecialchars($reportUrl);
        $id         = (int) $row['ID'];

        $pdfBtn = $reportUrl !== ''
            ? '<a href="' . $reportHref . '" class="btn btn-primary rounded-circle" download="NoShow-Report-' . $id . '.pdf" title="Download Report PDF"><i class="bi bi-file-earmark-pdf-fill"></i></a>'
            : '';

        $actions = '<div class="btn-group-sm d-flex justify-content-center gap-1">'
            . '<a href="#" class="btn btn-info rounded-circle" onclick="event.preventDefault();openPhotoModal(' . $id . ',\'' . $photoPath . '\')" title="View Photo"><i class="bi bi-camera-fill"></i></a>'
            . '<a href="' . $photoPath . '" class="btn btn-success rounded-circle" download="NoShow-Ride-' . $id . '.jpg" title="Download Photo"><i class="bi bi-download"></i></a>'
            . $pdfBtn
            . '</div>';

        return [
            'id'        => $id,
            'data_hora' => $dateTime,
            'condutor'  => '<span class="badge text-bg-secondary">' . $driverName . '</span>',
            'rota'      => $route,
            'acoes'     => $actions,
        ];
    }

    private function shortAddress(string $addr): string
    {
        $short = preg_split('/[,(]/', $addr)[0] ?? $addr;
        $short = trim($short);
        return mb_strlen($short) > 28 ? mb_substr($short, 0, 26) . '…' : $short;
    }
}
