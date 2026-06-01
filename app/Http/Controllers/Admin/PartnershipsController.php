<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\CompanyPartnershipRepository;
use App\Repositories\CompanyRepository;
use App\Support\Session;

final class PartnershipsController extends BaseController
{
    private CompanyPartnershipRepository $partnerships;
    private CompanyRepository            $companies;

    public function __construct()
    {
        $this->partnerships = CompanyPartnershipRepository::default();
        $this->companies    = CompanyRepository::default();
    }

    /** GET /admin/partnerships.php */
    public function index(): void
    {
        $companyId = Session::companyId() ?? 0;

        // Filters — default to current month
        $defaultFrom = date('Y-m-01');
        $defaultTo   = date('Y-m-t');
        $from        = trim((string) ($_GET['from']       ?? $defaultFrom));
        $to          = trim((string) ($_GET['to']         ?? $defaultTo));
        $partnerId   = isset($_GET['partner_id']) && ctype_digit((string) $_GET['partner_id'])
                        ? (int) $_GET['partner_id']
                        : null;

        $from = $from !== '' && strtotime($from) ? $from : $defaultFrom;
        $to   = $to   !== '' && strtotime($to)   ? $to   : $defaultTo;

        $partnerships   = $this->partnerships->allFor($companyId);
        $activePartners = $this->partnerships->activePartnersFor($companyId);
        $allCompanies   = $this->companies->all();
        $stats          = $this->partnerships->crossStats($companyId, $from, $to, $partnerId);

        $this->view('admin.partnerships.index', [
            'partnerships'   => $partnerships,
            'activePartners' => $activePartners,
            'allCompanies'   => $allCompanies,
            'myCompanyId'    => $companyId,
            'stats'          => $stats,
            'filterFrom'     => $from,
            'filterTo'       => $to,
            'filterPartner'  => $partnerId,
            'flash'          => $_GET['success'] ?? null,
        ]);
    }

    /** POST /admin/partnership-invite.php */
    public function invite(): never
    {
        $this->requirePost();

        $companyId = Session::companyId() ?? 0;
        $targetId  = (int) $this->input('target_company_id', 0);

        if ($targetId <= 0 || $targetId === $companyId) {
            $this->json(['success' => false, 'error' => 'Invalid company.'], 422);
        }

        if ($this->partnerships->exists($companyId, $targetId)) {
            $this->json(['success' => false, 'error' => 'A partnership with that company already exists.'], 409);
        }

        $this->partnerships->create($companyId, $targetId);
        $this->json(['success' => true]);
    }

    /** POST /admin/partnership-respond.php — accept or reject an invitation */
    public function respond(): never
    {
        $this->requirePost();

        $companyId = Session::companyId() ?? 0;
        $id        = (int) $this->input('id', 0);
        $action    = (string) $this->input('action', '');

        if ($id <= 0 || !in_array($action, ['accept', 'reject'], true)) {
            $this->json(['success' => false, 'error' => 'Invalid data.'], 422);
        }

        $partnership = $this->partnerships->find($id);
        if ($partnership === null || (int) $partnership['company_id_b'] !== $companyId) {
            $this->json(['success' => false, 'error' => 'Not found or not authorised.'], 403);
        }

        $this->partnerships->updateStatus($id, $action === 'accept' ? 'active' : 'rejected');
        $this->json(['success' => true]);
    }
}
