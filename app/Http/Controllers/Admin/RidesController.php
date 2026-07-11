<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\CompanyPartnershipRepository;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Services\FCMSender;
use App\Services\PricingEngine;
use App\Support\Database;
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
    private bool                         $hasActivePartners = false;

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
        $length   = min(500, max(1, (int) ($_GET['length'] ?? 25)));
        $search   = trim((string) ($_GET['search']['value'] ?? ''));
        $orderCol = (int) ($_GET['order'][0]['column'] ?? 2);
        $orderDir = strtoupper(trim((string) ($_GET['order'][0]['dir'] ?? 'asc'))) === 'DESC' ? 'DESC' : 'ASC';
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo   = trim((string) ($_GET['date_to']   ?? ''));

        // Only offer "Delegate" when this company actually has a partner to delegate to.
        $companyId = Session::companyId() ?? 0;
        $this->hasActivePartners = $companyId > 0 && $this->partnerships->activePartnersFor($companyId) !== [];

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

        $rawDriverPay = str_replace(',', '.', (string) $this->input('valorMotorista', ''));
        $driverPay    = is_numeric($rawDriverPay) ? (float) $rawDriverPay : null;

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
            'valor_motorista'    => $driverPay,
            'admin_note'         => $this->input('adminNote'),
        ]);

        $driver = $this->input('driver', '');
        if ($driver !== '' && $driver !== 'later' && ctype_digit((string) $driver)) {
            $driverId = (int) $driver;
            $this->services->assignDriver($ride->id, $driverId);
            FCMSender::sendToUser($driverId, 'Nova viagem atribuída', $this->rideNotifBody($date, $time), ['ride_id' => (string) $ride->id]);
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

        $rawDriverPay = str_replace(',', '.', (string) $this->input('edit_valorMotorista', ''));
        $driverPay    = is_numeric($rawDriverPay) ? (float) $rawDriverPay : null;

        $this->services->update($id, [
            'serviceDate'        => $date,
            'serviceStartTime'   => $time,
            'serviceStartPoint'  => $this->input('edit_origin'),
            'serviceTargetPoint' => $this->input('edit_destination'),
            'leg_code'           => $this->input('edit_leg_code') ?: null,
            'paxADT'             => (int) $this->input('edit_paxADT', 0),
            'paxCHD'             => (int) $this->input('edit_paxCHD', 0),
            'paxBBY'             => (int) $this->input('edit_paxBBY', 0),
            'FlightNumber'       => $this->input('edit_flightNumber') ?: null,
            'NomeCliente'        => $this->input('edit_clientName')   ?: null,
            'ClientNumber'       => $this->input('edit_clientNumber') ?: null,
            'total_price'        => $price,
            'valor_motorista'    => $driverPay,
            'admin_note'         => $this->input('edit_adminNote'),
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
        $payBasis = (string) $this->input('payBasis', '');

        if ($rideId <= 0 || $driverId <= 0) {
            $this->redirect('/SRMT/public/admin/rides.php?error=dadosInvalidos');
        }

        $this->services->assignDriver($rideId, $driverId);
        $this->applyDriverPayout($rideId, $driverId, $payBasis);
        $this->logs->record("Admin assigned driver #{$driverId} to ride #{$rideId}");

        $stmt = Database::connection()->prepare('SELECT serviceDate, serviceStartTime FROM Services WHERE ID = ? LIMIT 1');
        $stmt->execute([$rideId]);
        $info = $stmt->fetch(\PDO::FETCH_ASSOC);
        $notifBody = $info
            ? $this->rideNotifBody((string) $info['serviceDate'], (string) $info['serviceStartTime'])
            : 'Tens uma nova viagem atribuída. Vê mais detalhes na app!';

        FCMSender::sendToUser($driverId, 'Nova viagem atribuída 🚗', $notifBody, ['ride_id' => (string) $rideId]);
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

    /** POST /admin/ride-aggregate.php — cria viagem multi-paragem a partir de 2+ serviços. */
    public function aggregate(): never
    {
        $this->requirePost();

        $decoded = json_decode((string) $this->input('ids_bulk', '[]'), true);
        $ids     = is_array($decoded) ? array_map('intval', $decoded) : [];

        $masterId = $this->services->aggregate($ids);
        if ($masterId === null) {
            $this->json(['success' => false, 'error' => 'Selecione pelo menos 2 serviços independentes válidos.'], 422);
        }

        $this->logs->record('Admin aggregated rides ' . implode(',', $ids) . " → master #{$masterId}");
        $this->json(['success' => true, 'master_id' => $masterId]);
    }

    /** POST /admin/ride-disaggregate.php — desagrega a viagem mestre inteira. */
    public function disaggregate(): never
    {
        $this->requirePost();

        $id = (int) $this->input('ride_id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'Missing ride id.'], 422);
        }

        $this->services->disaggregate($id);
        $this->logs->record("Admin disaggregated master ride #{$id}");
        $this->json(['success' => true]);
    }

    /** GET /admin/ride-stops.php?ride_id=N — devolve paragens de uma viagem mestre. */
    public function getStops(): never
    {
        $id = (int) ($_GET['ride_id'] ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'Missing ride id.'], 422);
        }
        $stops = $this->services->getStops($id);
        $this->json(['success' => true, 'stops' => $stops]);
    }

    /** POST /admin/ride-stops-save.php — guarda ordem + campos de todas as paragens. */
    public function saveStops(): never
    {
        $this->requirePost();

        $masterId = (int) $this->input('master_id', 0);
        $decoded  = json_decode((string) $this->input('stops', '[]'), true);

        if ($masterId <= 0 || !is_array($decoded) || $decoded === []) {
            $this->json(['success' => false, 'error' => 'Invalid data.'], 422);
        }

        // Verificar ownership antes de gravar.
        $companyId = \App\Support\Session::companyId();
        if ($companyId !== null && !$this->services->ownedBy($masterId)) {
            $this->json(['success' => false, 'error' => 'Forbidden.'], 403);
        }

        $this->services->saveStops($masterId, $decoded);
        $this->json(['success' => true]);
    }

    /** POST /admin/ride-stops-reorder.php — reordena paragens de uma viagem mestre. */
    public function reorderStops(): never
    {
        $this->requirePost();

        $masterId   = (int) $this->input('master_id', 0);
        $decoded    = json_decode((string) $this->input('ordered_ids', '[]'), true);
        $orderedIds = is_array($decoded) ? array_map('intval', $decoded) : [];

        if ($masterId <= 0 || $orderedIds === []) {
            $this->json(['success' => false, 'error' => 'Invalid data.'], 422);
        }

        $this->services->reorderStops($masterId, $orderedIds);
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

        if ($ride->partnerId !== null) {
            $d = \DateTime::createFromFormat('Y-m-d', $ride->date);
            $t = \DateTime::createFromFormat('H:i:s', $ride->startTime)
              ?: \DateTime::createFromFormat('H:i', $ride->startTime);
            $dateStr = $d ? $d->format('d/m/Y') : $ride->date;
            $timeStr = $t ? $t->format('H:i')   : $ride->startTime;
            $client  = $ride->clientName ?? 'Cliente';

            if ($action === 'approve') {
                FCMSender::sendToUser(
                    $ride->partnerId,
                    '✅ Pedido confirmado',
                    "{$client} · {$dateStr} às {$timeStr}\nO vosso pedido foi aprovado.",
                    ['ride_id' => (string) $id]
                );
            } else {
                FCMSender::sendToUser(
                    $ride->partnerId,
                    '❌ Pedido recusado',
                    "{$client} · {$dateStr} às {$timeStr}\nO vosso pedido foi recusado. Contacte o operador.",
                    ['ride_id' => (string) $id]
                );
            }
        }

        $this->json(['success' => true]);
    }

    /**
     * Calcula e grava o valor a pagar ao motorista pelo preçário, no momento
     * da atribuição. A base de pagamento vem do pedido ou, em falta, do default
     * do motorista. Se o preçário não tiver tarifa, mantém o valor existente.
     */
    private function applyDriverPayout(int $rideId, int $driverId, string $payBasisInput): void
    {
        $ride = $this->services->find($rideId);
        if ($ride === null) {
            return;
        }
        $basis = in_array($payBasisInput, ['company_vehicle', 'own_vehicle'], true)
            ? $payBasisInput
            : $this->users->defaultPayBasis($driverId);

        $payout = PricingEngine::default()->driverPayout(
            $ride->resort,
            $ride->vehicleLabel,
            $ride->type,
            $ride->totalPax(),
            $ride->hotelExtra,
            $basis
        );
        $this->services->setDriverPricing($rideId, $basis, $payout);
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

        // Grouping ID / Reference — visível na lista e pesquisável (importante para
        // tratamento de reclamações). Mostra-se um badge discreto quando existe.
        $groupingBadge = '';
        $groupingRef   = trim((string) ($row['grouping_ref'] ?? ''));
        if ($groupingRef !== '') {
            $groupingBadge = '<div class="mt-1"><span class="badge" style="background:rgba(100,116,139,.12);color:#64748b;border:1px solid rgba(100,116,139,.2);font-size:.62rem" title="Grouping ID"><i class="bi bi-hash"></i>'
                . htmlspecialchars($groupingRef) . '</span></div>';
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
            'recolha'             => $this->ioBadge($row) . htmlspecialchars((string) $row['serviceStartPoint']) . $delegationBadge . $groupingBadge,
            'entrega'             => htmlspecialchars((string) $row['serviceTargetPoint']),
            'tipo'                => '<span style="cursor:pointer;" onclick="changeTripType('
                                     . $row['ID'] . ',' . $row['serviceType'] . ')">'
                                     . ($row['serviceType'] == 1 ? 'Private' : 'Shared') . '</span>'
                                     . ((int) ($row['is_aggregate_master'] ?? 0) === 1
                                        ? ' <span class="badge" style="background:rgba(6,182,212,.12);color:#06b6d4;border:1px solid rgba(6,182,212,.25);font-size:.62rem" title="' . t('rides.stops_badge_title') . '">'
                                          . '<i class="bi bi-signpost-2"></i> ' . (int) ($row['stop_count'] ?? 0) . ' ' . t('rides.stops_badge') . '</span>'
                                        : ''),
            'grouping_ref'        => $row['grouping_ref'] ?? null,
            'is_aggregate_master' => (int) ($row['is_aggregate_master'] ?? 0),
            'stop_count'          => (int) ($row['stop_count'] ?? 0),
            'raw_type'            => (int) $row['serviceType'],
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
            'is_completed'        => (int) ($row['status_id'] ?? 0) === 4,
            'raw_status'          => (int) ($row['status_id'] ?? 0),
        ];
    }

    /**
     * IN / OUT airport badge — mirrors the driver dashboard's serviceIO().
     * Source of truth is the imported `leg_code` (IN/OT from the Excel "Service Base
     * Code"); the pickup/dropoff text match is only a fallback for rides that have
     * no leg_code (manually created, or legacy imports) so edits to Origem/Destino
     * can't desync the badge from what the operator actually entered.
     */
    private function ioBadge(array $row): string
    {
        $leg = strtoupper(trim((string) ($row['leg_code'] ?? '')));

        if ($leg === 'IN') {
            $pickIsAir = true;
        } elseif ($leg === 'OT') {
            $pickIsAir = false;
        } else {
            $isAirport = static fn(string $s): bool =>
                (bool) preg_match('/aeroport|airport|\bLIS\b|\bOPO\b|\bFAO\b|\bFNC\b|\bPDL\b/i', $s);

            $pickIsAir = $isAirport((string) ($row['serviceStartPoint']  ?? ''));
            $dropIsAir = $isAirport((string) ($row['serviceTargetPoint'] ?? ''));

            if ($pickIsAir === $dropIsAir) {
                return ''; // both or neither → not a clear arrival/departure
            }
        }

        [$label, $style] = $pickIsAir
            ? ['IN',  'background:rgba(37,99,235,.12);color:#2563eb;border:1px solid rgba(37,99,235,.25)']
            : ['OUT', 'background:rgba(220,38,38,.1);color:#dc2626;border:1px solid rgba(220,38,38,.25)'];

        return '<span class="badge rounded-pill me-1" style="font-size:.6rem;font-weight:800;letter-spacing:.04em;' . $style . '">'
            . '<i class="bi bi-airplane-fill"></i> ' . $label . '</span>';
    }

    private function actionsDelegatedOut(int $id): string
    {
        return '<div class="d-flex gap-1 justify-content-end align-items-center">'
            . '<button class="btn btn-outline-warning rounded-circle" title="' . t('rides.recall_btn') . '" onclick="recallTrip(' . $id . ', true)">'
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
        $driverPay  = $row['valor_motorista'] !== null && $row['valor_motorista'] !== ''
            ? htmlspecialchars(addslashes((string) $row['valor_motorista']), ENT_QUOTES)
            : '';
        $deleteName = htmlspecialchars(
            addslashes($row['serviceStartPoint'] . ' — ' . $row['serviceTargetPoint']),
            ENT_QUOTES
        );
        $legCode    = htmlspecialchars(strtoupper(trim((string) ($row['leg_code'] ?? ''))), ENT_QUOTES);
        $assignIcon = $row['driverName'] ? 'bi-person-check-fill' : 'bi-person-plus-fill';
        $assignBtn  = $row['driverName'] ? 'btn-info'             : 'btn-primary';

        // Notes are free text — pass base64 to avoid breaking the inline JS call.
        $driverNoteB64 = base64_encode((string) ($row['driver_note'] ?? ''));
        $adminNoteB64  = base64_encode((string) ($row['admin_note']  ?? ''));

        $editCall = sprintf(
            "editTravel(%d,'%sT%s','%s','%s','%s',%d,%d,%d,'%s','%s','%s',%d,'%s','%s','%s','%s','%s')",
            $id,
            $row['serviceDate'],
            substr((string) $row['serviceStartTime'], 0, 5),
            $driverName, $pickup, $dropoff,
            (int) $row['paxADT'], (int) $row['paxCHD'],
            (int) ($row['paxBBY'] ?? 0),
            $flight, $client, $phone,
            (int) $row['serviceType'],
            $price, $driverPay,
            $driverNoteB64, $adminNoteB64, $legCode
        );

        $disaggregateBtn = '';
        if ((int) ($row['is_aggregate_master'] ?? 0) === 1) {
            $stopCount       = (int) ($row['stop_count'] ?? 0);
            $disaggregateBtn = '<button class="btn btn-outline-info rounded-circle ms-1" title="' . t('rides.stops_open_btn') . '" '
                . 'onclick="openStopsModal(' . $id . ',' . $stopCount . ')">'
                . '<i class="bi bi-signpost-2"></i></button>';
        }

        $delegateBtn = '';
        $returnBtn   = '';
        if (empty($row['original_company_id'])) {
            // Delegate only makes sense if there's at least one active partnership.
            if ($this->hasActivePartners) {
                $delegateBtn = '<button class="btn btn-secondary rounded-circle ms-1" title="' . t('rides.delegate_btn') . '" '
                    . 'onclick="openDelegateModal(' . $id . ')">'
                    . '<i class="bi bi-send"></i></button>';
            }
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
            . $disaggregateBtn
            . $delegateBtn
            . $returnBtn
            . '</div>';
    }

    private function rideNotifBody(string $date, string $time): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        $t = \DateTime::createFromFormat('H:i:s', $time) ?: \DateTime::createFromFormat('H:i', $time);
        $dayStr  = $d ? $d->format('d/m/Y') : $date;
        $timeStr = $t ? $t->format('H:i')   : $time;
        return "Serviço atribuído para dia {$dayStr} às {$timeStr}. Vê mais detalhes na app!";
    }
}
