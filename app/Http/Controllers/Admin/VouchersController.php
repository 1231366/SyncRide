<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class VouchersController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
    }

    public function index(): void
    {
        $this->view('admin.vouchers.index', []);
    }

    public function data(): void
    {
        $rows = $this->services->listVouchersForAdmin();
        $data = array_map(function (array $r): array {
            $photoUrl = '/SRMT/public/' . ltrim((string) ($r['voucher_photo'] ?? ''), '/');
            return [
                'id'         => (int) $r['ID'],
                'date'       => $r['serviceDate'] . ' ' . substr((string) $r['serviceStartTime'], 0, 5),
                'client'     => htmlspecialchars((string) ($r['NomeCliente'] ?? '—')),
                'route'      => htmlspecialchars((string) $r['serviceStartPoint']) . ' → ' . htmlspecialchars((string) $r['serviceTargetPoint']),
                'driver'     => htmlspecialchars((string) ($r['driverName'] ?? '—')),
                'photo_url'  => $photoUrl,
            ];
        }, $rows);
        $this->json(['data' => $data]);
    }
}
