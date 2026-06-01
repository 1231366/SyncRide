<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\CompanyPartnershipRepository;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Support\Session;

/**
 * Manages the Services table from the admin perspective:
 * list, create, edit, delete, driver assignment, trip-type toggle,
 * DataTable JSON feed, ride-log timeline, partner-request approval,
 * and cross-company trip delegation.
 */
final class RidesController extends BaseController
{
    private ServiceRepository            $services;
    private UserRepository               $users;
    private LogRepository                $logs;
    private CompanyPartnershipRepository $partnerships;

    public function __construct()
    {
        $this->services     = ServiceRepository::default();
        $this->users        = UserRepository::default();
        $this->logs         = LogRepository::default();
        $this->partnerships = CompanyPartnershipRepository::default();
    }

    /** GET /admin/rides.php */
    public function index(): void
    {
        $companyId      = Session::companyId() ?? 0;
        $activePartners = $companyId > 0 ? $this->partnerships->activePartnersFor($companyId) : [];

        $this->view('admin.rides.index', [
            'drivers'              => $this->users->byRole(User::ROLE_DRIVER),
            'pendingRequestsCount' => $this->services->countPendingRequests(),
            'todayCount'           => $this->services->countToday(),
            'tomorrowCount'        => $this->services->countTomorrow(),
            'unassignedCount'      => $this->services->countUnassigned(),
            'activePartners'       => $activePartners,
            'flash'                => $_GET['success'] ?? null,
        ]);
    }

    /** GET /admin/rides-data.php?status=X — DataTable JSON feed. */
    public function data(): void
    {
        $filter   = trim((string) ($_GET['status'] ?? 'today'));
        $draw     = (int) ($_GET['draw']   ?? 1);
        $start    = max(0, (int) ($_GET['start']  ?? 0));
        $length   = min(200, max(1, (int) ($_GET['length'] ?? 25)));
        $search   = trim((string) ($_GET['search']['value'] ?? ''));
        $orderCol = (int) ($_GET['order'][0]['column'] ?? 2);
        $orderDir = strtoupper(trim((string) ($_GET['order'][0]['dir'] ?? 'asc'))) === 'DESC' ? 'DESC' : 'ASC';
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo   = trim((string) ($_GET['date_to']   ?? ''));

        $result = $this->services->listForAdminPaginated($filter, $start, $length, $search, $orderCol, $orderDir, $dateFrom, $dateTo);

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => array_map([$this, 'formatRow'], $result['data']),
        ]);
    }

    /** POST /admin/ride-add.php — create a ride. */
    public function store(): void
    {
        $this->requirePost();

        $date = $this->input('serviceDate');
        $time = $this->input('serviceStartTime');
        if (!$date || !$time) {
            $this->abort(422, 'Date and time are required.');
        }

        $rawPrice = str_replace(',', '.', (string) $this->input('totalPrice', ''));
        $price    = is_numeric($rawPrice) ? (float) $rawPrice : null;

        $ride = $this->services->create([
            'serviceDate'        => $date,
            'serviceStartTime'   => $time,
            'paxADT'             => (int) $this->input('paxADT', 0),
            'paxCHD'             => (int) $this->input('paxCHD', 0),
            'paxBBY'             => (int) $this->input('paxBBY', 0),
            'serviceStartPoint'  => $this->input('serviceStartPoint'),
            'serviceTargetPoint' => $this->input('serviceTargetPoint'),
            'serviceType'        => (int) $this->input('serviceType', 1),
            'FlightNumber'       => $this->input('FlightNumber') ?: null,
            'NomeCliente'        => $this->input('NomeCliente')  ?: null,
            'ClientNumber'       => $this->input('ClientNumber') ?: null,
            'total_price'        => $price,
        ]);

        $driver = $this->input('driver', '');
        if ($driver !== '' && $driver !== 'later' && ctype_digit((string) $driver)) {
            $this->services->assignDriver($ride->id, (int) $driver);
        }

        $this->logs->record("Admin created ride #{$ride->id}");
        $this->redirect('/SRMT/public/admin/rides.php?success=ride_created');
    }

    /** POST /admin/ride-update.php — update ride details. */
    public function update(): void
    {
        $this->requirePost();

        $id = (int) $this->input('edit_trip_id', 0);
        if ($id <= 0) {
            $this->abort(422, 'Missing ride id.');
        }

        $dt = (string) $this->input('edit_departure_datetime', '');
        if ($dt === '' || !str_contains($dt, 'T')) {
            $this->abort(422, 'Invalid departure datetime.');
        }

        [$date, $timePart] = explode('T', $dt, 2);
        $time = $timePart . ':00';

        $rawPrice = str_replace(',', '.', (string) $this->input('edit_totalPrice', '0'));
        $price    = is_numeric($rawPrice) ? (float) $rawPrice : 0.0;

        $this->services->update($id, [
            'serviceDate'        => $date,
            'serviceStartTime'   => $time,
            'serviceStartPoint'  => $this->input('edit_origin'),
            'serviceTargetPoint' => $this->input('edit_destination'),
            'paxADT'             => (int) $this->input('edit_paxADT', 0),
            'paxCHD'             => (int) $this->input('edit_paxCHD', 0),
            'paxBBY'             => (int) $this->input('edit_paxBBY', 0),
            'FlightNumber'       => $this->input('edit_flightNumber') ?: null,
            'NomeCliente'        => $this->input('edit_clientName')   ?: null,
            'ClientNumber'       => $this->input('edit_clientNumber') ?: null,
            'total_price'        => $price,
        ]);

        $this->logs->record("Admin updated ride #{$id}");
        $this->redirect('/SRMT/public/admin/rides.php?success=rideUpdated');
    }

    /** GET|POST /admin/delete-ride.php — single or bulk delete. */
    public function destroy(): void
    {
        $fromTab = (string) $this->input('from_tab', 'today');
        $ids     = [];

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->abort(405, 'Method not allowed');
        }

        if (isset($_POST['ids_bulk'])) {
            $decoded = json_decode((string) $_POST['ids_bulk'], true);
            $ids     = is_array($decoded) ? array_map('intval', $decoded) : [];
        } elseif ($this->input('id') !== null) {
            $ids = [(int) $this->input('id')];
        }

        if (empty($ids)) {
            $this->redirect('/SRMT/public/admin/rides.php?error=' . urlencode('No rides selected') . '&tab=' . urlencode($fromTab));
        }

        $this->services->deleteBulk($ids);
        $this->logs->record('Admin deleted ride(s): ' . implode(',', $ids));
        $this->redirect('/SRMT/public/admin/rides.php?success=ride_deleted&tab=' . urlencode($fromTab));
    }

    /** GET /admin/ride-logs.php?id=X — ride timeline JSON. */
    public function logs(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Missing id'], 400);
        }

        $ts = $this->services->getTimestamps($id);
        if ($ts === null) {
            $this->json(['success' => false, 'message' => 'Ride not found'], 404);
        }

        $this->json(['success' => true, 'data' => $ts]);
    }

    /** POST /admin/assign-driver.php */
    public function assignDriver(): void
    {
        $this->requirePost();

        $rideId   = (int) $this->input('viagemId',   0);
        $driverId = (int) $this->input('condutorId', 0);

        if ($rideId <= 0 || $driverId <= 0) {
            $this->redirect('/SRMT/public/admin/rides.php?error=dadosInvalidos');
        }

        $this->services->assignDriver($rideId, $driverId);
        $this->logs->record("Admin assigned driver #{$driverId} to ride #{$rideId}");
        $this->redirect('/SRMT/public/admin/rides.php?success=viagemAtribuida');
    }

    /** POST /admin/update-trip-type.php */
    public function setTripType(): void
    {
        $this->requirePost();

        $id   = (int) $this->input('tripId',   0);
        $type = (int) $this->input('tripType', 1);

        if ($id <= 0) {
            $this->redirect('/SRMT/public/admin/rides.php?success=false');
        }

        $this->services->setTripType($id, $type);
        $this->redirect('/SRMT/public/admin/rides.php?success=TypeChanged');
    }

    /** POST /admin/ride-recall.php — recall a delegated trip (sender) or return it (receiver). */
    public function recall(): never
    {
        $this->requirePost();

        $companyId = Session::companyId() ?? 0;
        $rideId    = (int) $this->input('ride_id', 0);

        if ($rideId <= 0 || $companyId <= 0) {
            $this->json(['success' => false, 'error' => 'Missing data.'], 422);
        }

        $ok = $this->services->recallDelegation($rideId, $companyId);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'Could not recall — trip not delegated or not authorised.'], 409);
        }

        $this->logs->record("Admin recalled/returned ride #{$rideId} (company #{$companyId})");
        $this->json(['success' => true]);
    }

    /** POST /admin/ride-delegate.php — delegate a trip to a partner company. */
    public function delegate(): never
    {
        $this->requirePost();

        $companyId = Session::companyId() ?? 0;
        $rideId    = (int) $this->input('ride_id', 0);
        $targetId  = (int) $this->input('target_company_id', 0);

        if ($rideId <= 0 || $targetId <= 0 || $companyId <= 0) {
            $this->json(['success' => false, 'error' => 'Missing data.'], 422);
        }

        if (!$this->partnerships->isActive($companyId, $targetId)) {
            $this->json(['success' => false, 'error' => 'No active partnership with that company.'], 403);
        }

        $ok = $this->services->delegateTo($rideId, $targetId, $companyId);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'Could not delegate — trip already delegated or not owned by your company.'], 409);
        }

        $this->logs->record("Admin delegated ride #{$rideId} to company #{$targetId}");
        $this->json(['success' => true]);
    }

    /** POST /api/request-handle.php — approve or reject a partner request. */
    public function handleRequest(): void
    {
        $id     = (int) $this->input('id', 0);
        $action = (string) $this->input('action', '');

        if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }

        // Verify the ride belongs to this company before acting
        $ride      = $this->services->find($id);
        $companyId = Session::companyId();
        if ($ride === null || ($companyId !== null && $ride->companyId !== $companyId)) {
            $this->json(['success' => false, 'message' => 'Not found or not authorised'], 403);
        }

        $status = $action === 'approve' ? 'aprovado' : 'rejeitado';
        $this->services->setApprovalStatus($id, $status);
        $this->json(['success' => true]);
    }

    /** Format a raw DB row for the DataTable JSON response. */
    private function formatRow(array $row): array
    {
        $isPending   = isset($row['status_pedido']) && $row['status_pedido'] === 'pendente';
        $partnerName = $row['partner_name'] ? htmlspecialchars((string) $row['partner_name']) : 'Agency';
        $driverName  = $row['driverName'] ?? null;
        $dateTime    = htmlspecialchars(
            $row['serviceDate'] . ' ' . substr((string) $row['serviceStartTime'], 0, 5)
        );

        if (!empty($row['partner_id'])) {
            $keyBadge = $row['has_key'] == 1
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2"><i class="bi bi-key-fill"></i> With Key</span>'
                : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2"><i class="bi bi-key-fill"></i> No Key</span>';
        } else {
            $keyBadge = '<span class="text-muted small">—</span>';
        }

        $delegationBadge = '';
        if (!empty($row['_delegated_out']) && !empty($row['driverName'])) {
            $targetName      = htmlspecialchars((string) $row['driverName']);
            $delegationBadge = '<div class="mt-1"><span class="badge" style="background:rgba(234,88,12,.1);color:#ea580c;border:1px solid rgba(234,88,12,.2);font-size:.68rem"><i class="bi bi-send-fill me-1"></i>' . t('partnerships.badge_sent_to') . ' ' . $targetName . '</span></div>';
        } elseif (empty($row['_delegated_out']) && !empty($row['origin_company_name'])) {
            $originName      = htmlspecialchars((string) $row['origin_company_name']);
            $delegationBadge = '<div class="mt-1"><span class="badge" style="background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);font-size:.68rem"><i class="bi bi-inbox-fill me-1"></i>' . t('partnerships.badge_from') . ' ' . $originName . '</span></div>';
        }

        return [
            'id'                  => '#' . $row['ID'],
            'raw_id'              => $row['ID'],
            'data_hora'           => $dateTime,
            'condutor'            => $isPending
                ? '<span class="req-pending"><i class="bi bi-shop me-1"></i> ' . $partnerName . '</span>'
                : ($driverName
                    ? '<span class="badge text-bg-success">' . htmlspecialchars((string) $driverName) . '</span>'
                    : '<span class="badge bg-secondary">N.A</span>'),
            'recolha'             => htmlspecialchars((string) $row['serviceStartPoint']) . $delegationBadge,
            'entrega'             => htmlspecialchars((string) $row['serviceTargetPoint']),
            'tipo'                => '<span style="cursor:pointer;" onclick="changeTripType('
                                     . $row['ID'] . ',' . $row['serviceType'] . ')">'
                                     . ($row['serviceType'] == 1 ? 'Private' : 'Shared') . '</span>',
            'chave'               => $keyBadge,
            'status_pedido'       => $row['status_pedido'] ?? null,
            'partner_name'        => htmlspecialchars((string) ($row['partner_name'] ?? '')),
            'origin_company_name' => htmlspecialchars((string) ($row['origin_company_name'] ?? '')),
            'acoes'               => $isPending
                ? $this->actionsPending((int) $row['ID'])
                : (!empty($row['_delegated_out'])
                    ? $this->actionsDelegatedOut((int) $row['ID'])
                    : $this->actionsNormal($row)),
            'client_name'         => htmlspecialchars((string) ($row['NomeCliente']   ?? '')),
            'flight_number'       => htmlspecialchars((string) ($row['FlightNumber']  ?? '')),
            'pax_bby'             => (int) ($row['paxBBY'] ?? 0),
        ];
    }

    private function actionsDelegatedOut(int $id): string
    {
        return '<div class="d-flex gap-1 justify-content-end align-items-center">'
            . '<button class="btn btn-outline-warning rounded-circle" title="' . t('rides.recall_btn') . '" onclick="recallTrip(' . $id . ')">'
            . '<i class="bi bi-arrow-counterclockwise"></i>'
            . '</button></div>';
    }

    private function actionsPending(int $id): string
    {
        return '<div class="d-flex gap-2 justify-content-end">'
            . '<button class="btn btn-success btn-sm" onclick="handleRequest(' . $id . ',\'approve\')"><i class="bi bi-check-lg"></i></button>'
            . '<button class="btn btn-danger btn-sm" onclick="handleRequest(' . $id . ',\'reject\')"><i class="bi bi-x-lg"></i></button>'
            . '</div>';
    }

    private function actionsNormal(array $row): string
    {
        $id         = (int) $row['ID'];
        $driverName = htmlspecialchars(addslashes((string) ($row['driverName'] ?? 'N.A')), ENT_QUOTES);
        $pickup     = htmlspecialchars(addslashes((string) $row['serviceStartPoint']),     ENT_QUOTES);
        $dropoff    = htmlspecialchars(addslashes((string) $row['serviceTargetPoint']),    ENT_QUOTES);
        $flight     = htmlspecialchars(addslashes((string) ($row['FlightNumber']  ?? '')), ENT_QUOTES);
        $client     = htmlspecialchars(addslashes((string) ($row['NomeCliente']   ?? '')), ENT_QUOTES);
        $phone      = htmlspecialchars(addslashes((string) ($row['ClientNumber']  ?? '')), ENT_QUOTES);
        $price      = htmlspecialchars(addslashes((string) ($row['total_price']   ?? '0')), ENT_QUOTES);
        $deleteName = htmlspecialchars(
            addslashes($row['serviceStartPoint'] . ' — ' . $row['serviceTargetPoint']),
            ENT_QUOTES
        );
        $assignIcon = $row['driverName'] ? 'bi-person-check-fill' : 'bi-person-plus-fill';
        $assignBtn  = $row['driverName'] ? 'btn-info'             : 'btn-primary';

        $editCall = sprintf(
            "editTravel(%d,'%sT%s','%s','%s','%s',%d,%d,%d,'%s','%s','%s',%d,'%s')",
            $id,
            $row['serviceDate'],
            substr((string) $row['serviceStartTime'], 0, 5),
            $driverName, $pickup, $dropoff,
            (int) $row['paxADT'], (int) $row['paxCHD'],
            (int) ($row['paxBBY'] ?? 0),
            $flight, $client, $phone,
            (int) $row['serviceType'],
            $price
        );

        $delegateBtn = '';
        $returnBtn   = '';
        if (empty($row['original_company_id'])) {
            $delegateBtn = '<button class="btn btn-secondary rounded-circle ms-1" title="' . t('rides.delegate_btn') . '" '
                . 'onclick="openDelegateModal(' . $id . ')">'
                . '<i class="bi bi-send"></i></button>';
        } else {
            // Received trip — show Return button instead of Delegate
            $returnBtn = '<button class="btn btn-outline-warning rounded-circle ms-1" title="' . t('rides.return_btn') . '" onclick="recallTrip(' . $id . ')">'
                . '<i class="bi bi-arrow-counterclockwise"></i>'
                . '</button>';
        }

        return '<div class="d-flex gap-1 justify-content-end align-items-center">'
            . '<a href="#" class="btn ' . $assignBtn . ' rounded-circle" '
            . 'onclick="event.preventDefault();setViagemId(' . $id . ');new bootstrap.Modal(document.getElementById(\'atribuirCondutorModal\')).show();">'
            . '<i class="bi ' . $assignIcon . '"></i></a>'
            . '<a href="#" class="btn btn-warning rounded-circle text-dark ms-1" '
            . 'onclick="event.preventDefault();' . $editCall . ';new bootstrap.Modal(document.getElementById(\'editModal\')).show();">'
            . '<i class="bi bi-pencil-fill"></i></a>'
            . '<a href="#" class="btn btn-danger rounded-circle ms-1" '
            . 'onclick="event.preventDefault();setDeleteTrip(' . $id . ',\'' . $deleteName . '\');new bootstrap.Modal(document.getElementById(\'deleteTripModal\')).show();">'
            . '<i class="bi bi-trash3-fill"></i></a>'
            . '<button class="btn btn-info btn-sm rounded-circle shadow-sm ms-1" onclick="viewTripLogs(' . $id . ')">'
            . '<i class="bi bi-clock-history text-white"></i></button>'
            . $delegateBtn
            . $returnBtn
            . '</div>';
    }
}
