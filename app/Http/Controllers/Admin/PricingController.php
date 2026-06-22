<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\PricingRepository;

/**
 * UI de gestão do rate card (PricingRates).
 * Permite ver, editar e apagar tarifas sem ter de re-importar o Excel.
 */
final class PricingController extends BaseController
{
    private PricingRepository $rates;

    public function __construct()
    {
        $this->rates = PricingRepository::default();
    }

    /** GET /admin/pricing.php */
    public function index(): void
    {
        $card = trim((string) ($_GET['card'] ?? 'mts'));
        $cards = ['mts', 'prtours_retail', 'driver_company_vehicle', 'driver_own_vehicle'];
        if (!in_array($card, $cards, true)) {
            $card = 'mts';
        }

        $rows   = $this->rates->listCard($card);
        $totals = [];
        foreach ($cards as $c) {
            $totals[$c] = $this->rates->countByCard($c);
        }

        $this->view('admin.pricing.index', [
            'card'    => $card,
            'cards'   => $cards,
            'rows'    => $rows,
            'totals'  => $totals,
            'flash'   => $_GET['success'] ?? null,
            'error'   => $_GET['error']   ?? null,
        ]);
    }

    /** POST /admin/pricing-save.php — criar ou actualizar uma tarifa. */
    public function save(): never
    {
        $this->requirePost();

        $id    = (int) $this->input('id', 0);
        $card  = (string) $this->input('card', '');
        $cards = ['mts', 'prtours_retail', 'driver_company_vehicle', 'driver_own_vehicle'];

        if (!in_array($card, $cards, true)) {
            $this->json(['success' => false, 'error' => 'Invalid card.'], 422);
        }

        $rawPrice = str_replace(',', '.', (string) $this->input('price', ''));
        $rawHx    = str_replace(',', '.', (string) $this->input('hotel_extra', ''));

        $data = [
            'card'             => $card,
            'supplier'         => $this->input('supplier')         ?: null,
            'resort'           => $this->input('resort')           ?: null,
            'distributor_code' => $this->input('distributor_code') ?: null,
            'vehicle_label'    => $this->input('vehicle_label')    ?: null,
            'pax_tier'         => $this->input('pax_tier') !== null && $this->input('pax_tier') !== '' ? (int) $this->input('pax_tier') : null,
            'price'            => is_numeric($rawPrice) ? (float) $rawPrice : null,
            'hotel_extra'      => is_numeric($rawHx)    ? (float) $rawHx    : null,
            'valid_until'      => $this->input('valid_until') ?: null,
        ];

        if ($id > 0) {
            $this->rates->updateRate($id, $data);
        } else {
            $this->rates->insert($data);
        }

        $this->json(['success' => true]);
    }

    /** POST /admin/pricing-delete.php — apagar uma tarifa. */
    public function delete(): never
    {
        $this->requirePost();

        $id = (int) $this->input('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'Missing id.'], 422);
        }

        $this->rates->deleteRate($id);
        $this->json(['success' => true]);
    }
}
