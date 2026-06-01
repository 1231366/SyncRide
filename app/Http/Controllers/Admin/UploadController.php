<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\TenantSettingsRepository;
use App\Services\VoucherMailer;
use App\Services\XmlVoucherImporter;
use App\Support\Session;

/**
 * Handles file upload endpoints that are not tied to a specific resource controller.
 *
 * xml()     — POST: validate + import XML voucher file (admin only)
 * voucher() — POST: save driver voucher photo + send confirmation email (admin + driver)
 * generic() — dead endpoint, returns 404
 */
final class UploadController extends BaseController
{
    private LogRepository    $logs;
    private ServiceRepository $services;

    public function __construct()
    {
        $this->logs     = LogRepository::default();
        // Drivers (incl. shared) act on their assigned rides; admins on their company's.
        $this->services = Session::role() === 2
            ? ServiceRepository::forDriverContext()
            : ServiceRepository::default();
    }

    /** POST /admin/upload-xml.php */
    public function xml(): void
    {
        header('Content-Type: application/json');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'POST required.'], 405);
        }

        $file = $_FILES['fileUpload'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'No file uploaded.'], 400);
        }

        if (strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xml') {
            $this->json(['status' => 'error', 'message' => 'Only XML files are accepted.'], 400);
        }

        if ((int) $file['size'] > 10 * 1024 * 1024) {
            $this->json(['status' => 'error', 'message' => 'File exceeds 10 MB limit.'], 400);
        }

        $contents = (string) file_get_contents((string) $file['tmp_name']);
        $imported = XmlVoucherImporter::default()->importFromString($contents);

        $this->logs->record("XML voucher import: {$imported} rides imported");
        $this->json(['status' => 'success', 'message' => "Imported {$imported} rides.", 'count' => $imported]);
    }

    /** POST /admin/upload-voucher.php — accessible to drivers (role 2) too. */
    public function voucher(): void
    {
        header('Content-Type: application/json');

        $body       = $this->jsonBody();
        $tripId     = (int) ($body['trip_id']    ?? 0);
        $imgData    = (string) ($body['image_data'] ?? '');
        $lat        = isset($body['lat'])  ? (string) $body['lat']  : null;
        $lng        = isset($body['lng'])  ? (string) $body['lng']  : null;
        $driverName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Unknown Driver';

        if ($tripId <= 0 || $imgData === '') {
            $this->json(['success' => false, 'message' => 'Missing data.'], 400);
        }

        // Authorisation: driver may only act on rides assigned to them; admin on own company's.
        $ride = $this->services->find($tripId);
        if ($ride === null) {
            $this->json(['success' => false, 'message' => 'Ride not found.'], 404);
        }
        if (Session::role() === 2) {
            if ($this->services->assignedDriver($tripId) !== Session::userId()) {
                $this->json(['success' => false, 'message' => 'Not your ride.'], 403);
            }
        } else {
            $sessionCompany = Session::companyId();
            if ($sessionCompany !== null && $ride->companyId !== $sessionCompany) {
                $this->json(['success' => false, 'message' => 'Not authorised.'], 403);
            }
        }

        $parts    = explode(';base64,', $imgData, 2);
        $imgBytes = base64_decode($parts[1] ?? '');
        if ($imgBytes === false || $imgBytes === '') {
            $this->json(['success' => false, 'message' => 'Invalid image data.'], 400);
        }

        $appRoot   = dirname(__DIR__, 4);
        $uploadDir = $appRoot . '/public/uploads/vouchers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName   = 'voucher_' . $tripId . '_' . time() . '.jpg';
        $serverPath = $uploadDir . $fileName;

        if (file_put_contents($serverPath, $imgBytes) === false) {
            $this->json(['success' => false, 'message' => 'Failed to save image.'], 500);
        }

        // Use the owning company's settings (shared driver may submit for another company).
        $ownerCompanyId = ($ride->companyId ?? 0) ?: null;
        $s = new TenantSettingsRepository($this->db(), $ownerCompanyId);
        if ($s->voucherEnabled()) {
            try {
                (new VoucherMailer())->send(
                    $tripId, $driverName, $serverPath, $fileName, $lat, $lng,
                    $s->voucherAgencyEmail(),
                    $s->voucherCcList(),
                    $s->voucherMyCopy()
                );
            } catch (\Throwable $e) {
                error_log('VoucherMailer failed for ride #' . $tripId . ': ' . $e->getMessage());
            }
        }

        $this->json(['success' => true, 'message' => 'Voucher submitted successfully!']);
    }

    /** GET /admin/upload.php — dead endpoint, not in use. */
    public function generic(): never
    {
        $this->abort(404, 'Not found.');
    }
}
