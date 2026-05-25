<?php
use App\Http\View;

/**
 * @var array{total:int,pending:int,approved:int} $counts
 * @var string $userName
 */
$firstName    = explode(' ', trim($userName))[0];
$userPhotoSrc = isset($_SESSION['profile_photo_path']) && $_SESSION['profile_photo_path'] !== ''
    ? '/SRMT/' . ltrim((string) $_SESSION['profile_photo_path'], '/')
    : '/SRMT/public/assets/images/icons/SyncRide.png';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <title>Partner Portal — SyncRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <style>
        :root {
            --font-primary: 'Inter', sans-serif; --font-display: 'Poppins', sans-serif;
            --bg-body: #f3f4f6; --bg-card: #ffffff; --text-main: #111827; --text-muted: #6b7280;
            --primary-accent: #4f46e5; --primary-hover: #4338ca;
            --border-color: #e5e7eb; --radius-md: 16px; --radius-sm: 10px;
            --card-shadow: 0 2px 12px rgba(0,0,0,.04);
            --safe-top: env(safe-area-inset-top, 0px); --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        [data-bs-theme="dark"] {
            --bg-body: #0f172a; --bg-card: #1e293b; --text-main: #f9fafb; --text-muted: #94a3b8;
            --primary-accent: #6366f1; --primary-hover: #818cf8; --border-color: #334155;
            --card-shadow: 0 4px 20px rgba(0,0,0,.2);
        }
        body { font-family: var(--font-primary); background-color: var(--bg-body); color: var(--text-main); padding-bottom: calc(90px + var(--safe-bottom)); margin: 0; min-height: 100vh; }
        @media (min-width: 992px) { body { padding-bottom: 0; } }
        .app-header { background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); padding-top: var(--safe-top); height: calc(70px + var(--safe-top)); display: flex; align-items: center; position: fixed; top: 0; width: 100%; z-index: 1040; }
        [data-bs-theme="dark"] .app-header { background: rgba(30,41,59,.95); }
        .navbar-brand img { height: 35px; margin-right: 10px; }
        .navbar-brand span { font-family: var(--font-display); font-weight: 700; }
        .app-main { padding-top: calc(80px + var(--safe-top)) !important; }
        .stat-card { background: var(--bg-card); border: none; border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; box-shadow: var(--card-shadow); }
        .stat-value { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; margin-bottom: 0; line-height: 1.2; }
        .stat-label { font-size: .75rem; opacity: .7; font-weight: 500; }
        .stat-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 8px; font-size: 1rem; }
        .stat-blue   { color: #3b82f6; } .stat-blue   .stat-icon { color: #3b82f6; background: rgba(59,130,246,.1); }
        .stat-orange { color: #f97316; } .stat-orange .stat-icon { color: #f97316; background: rgba(249,115,22,.1); }
        .stat-green  { color: #10b981; } .stat-green  .stat-icon { color: #10b981; background: rgba(16,185,129,.1); }
        .modern-search { position: relative; width: 100%; max-width: 400px; }
        .modern-search input { width: 100%; padding: 10px 15px 10px 45px; border-radius: 50px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); box-shadow: var(--card-shadow); }
        .modern-search i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .nav-pills { gap: 8px; flex-wrap: nowrap; padding: 0; margin: 0; border: none; }
        .nav-pills .nav-link { color: var(--text-muted); background: var(--bg-card); border-radius: 50px; padding: 8px 20px; font-size: .85rem; font-weight: 600; white-space: nowrap; box-shadow: 0 2px 5px rgba(0,0,0,.02); transition: all .2s; }
        .nav-pills .nav-link.active { background-color: var(--primary-accent); color: #fff; box-shadow: 0 4px 10px rgba(79,70,229,.3); }
        @media (max-width: 767.98px) {
            .nav-pills { display: flex; width: 100%; justify-content: space-between; gap: 5px; }
            .nav-pills .nav-item { flex: 1; min-width: 0; }
            .nav-pills .nav-link { width: 100%; text-align: center; padding: 10px 0; font-size: .75rem; border-radius: 12px; overflow: hidden; text-overflow: ellipsis; }
        }
        .card-table { background: transparent; border-radius: var(--radius-md); overflow: hidden; }
        @media (min-width: 768px) { .card-table { background: var(--bg-card); border: 1px solid var(--border-color); } .table td { padding: 1rem; vertical-align: middle; } }
        @media (max-width: 767.98px) {
            #tabelaPartner thead { display: none; }
            #tabelaPartner tbody tr { display: flex; flex-direction: column; background-color: var(--bg-card); border: none; border-radius: 14px; margin-bottom: 12px; padding: 12px; position: relative; box-shadow: var(--card-shadow); }
            #tabelaPartner tbody td { display: block; width: 100%; padding: 0 !important; margin-bottom: 3px; background: transparent !important; border: none !important; box-shadow: none !important; }
            #tabelaPartner tbody td:nth-child(1) { font-size: .7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
            #tabelaPartner tbody td:nth-child(2) { font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 5px; line-height: 1.2; padding-right: 60px !important; }
            #tabelaPartner tbody td:nth-child(3), #tabelaPartner tbody td:nth-child(4), #tabelaPartner tbody td:nth-child(5) { font-size: .85rem; color: var(--text-main); display: flex; align-items: center; min-height: 22px; }
            #tabelaPartner tbody td:nth-child(8) { position: absolute; top: 12px; right: 12px; width: auto !important; margin: 0; }
            #tabelaPartner tbody td:nth-child(7) { position: absolute; top: 44px; right: 12px; width: auto !important; margin: 0; text-align: right; }
            #tabelaPartner tbody td:nth-child(6) { position: absolute; top: 12px; right: 120px; width: auto !important; margin: 0; text-align: right; }
        }
        .bottom-navbar { background-color: var(--bg-card); border-top: 1px solid var(--border-color); z-index: 1040; position: fixed; bottom: 0; left: 0; width: 100%; height: calc(70px + var(--safe-bottom)); padding-bottom: var(--safe-bottom); display: flex; justify-content: space-around; align-items: center; box-shadow: 0 -5px 20px rgba(0,0,0,.05); }
        .nav-item-bottom { color: var(--text-muted); flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; border: none; background: transparent; font-size: .75rem; height: 100%; transition: color .2s; cursor: pointer; }
        .nav-item-bottom i { font-size: 1.5rem; margin-bottom: 2px; }
        .nav-item-bottom.active { color: var(--primary-accent); }
        .btn-center-add { position: absolute; top: -25px; left: 50%; transform: translateX(-50%); width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-accent), var(--primary-hover)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border: 5px solid var(--bg-body); box-shadow: 0 5px 15px rgba(79,70,229,.4); cursor: pointer; z-index: 1050; transition: transform .2s; }
        .btn-center-add:active { transform: translateX(-50%) scale(.95); }
    </style>
</head>
<body>
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand fixed-top">
        <div class="container-fluid">
            <a href="#" class="navbar-brand d-flex align-items-center">
                <img id="app-logo" src="/SRMT/public/assets/images/icons/SyncRide.png" alt="SyncRide">
            </a>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2">
                    <button class="btn btn-link text-muted p-0 border-0" id="theme-toggle"><i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i></button>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= View::e($userPhotoSrc) ?>" class="rounded-circle shadow-sm border" style="width:36px;height:36px;object-fit:cover;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 overflow-hidden mt-2">
                        <li class="user-header bg-primary text-white p-4 text-center">
                            <p class="mb-0 fw-bold"><?= View::e($userName) ?></p>
                            <a href="/SRMT/public/auth/logout.php" class="btn btn-danger btn-sm rounded-pill mt-3 fw-bold">Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <main class="app-main">
        <div class="app-content">
            <div class="container-xl">
                <div class="d-flex justify-content-between align-items-end mb-4 px-1">
                    <div>
                        <h3 class="fw-bold mb-0">Hello, <?= View::e($firstName) ?></h3>
                        <p class="text-muted mb-0 small">Welcome to the partner portal.</p>
                    </div>
                    <button class="btn btn-primary rounded-pill d-none d-md-block shadow-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalNewBooking">
                        <i class="bi bi-plus-lg me-1"></i> New Booking
                    </button>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="stat-card stat-blue">
                            <div class="stat-icon"><i class="bi bi-airplane-fill"></i></div>
                            <div class="stat-value"><?= $counts['total'] ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card stat-orange">
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                            <div class="stat-value"><?= $counts['pending'] ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card stat-green">
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="stat-value"><?= $counts['approved'] ?></div>
                            <div class="stat-label">Confirmed</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                    <div class="modern-search order-2 order-md-1">
                        <i class="bi bi-search"></i>
                        <input type="text" id="customSearch" placeholder="Search…">
                    </div>
                    <ul class="nav nav-pills order-1 order-md-2" id="partner-tabs">
                        <li class="nav-item"><a class="nav-link active" data-status="pendente" href="#">Pending</a></li>
                        <li class="nav-item"><a class="nav-link" data-status="aprovado"  href="#">Confirmed</a></li>
                        <li class="nav-item"><a class="nav-link" data-status="rejeitado" href="#">Rejected</a></li>
                    </ul>
                </div>

                <div class="card-table">
                    <table id="tabelaPartner" class="table mb-0 w-100">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Passenger</th>
                                <th>Flight</th>
                                <th>Route</th>
                                <th>Pax</th>
                                <th class="text-center">Key</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="bottom-navbar d-md-none">
    <a href="#" class="nav-item-bottom active"><i class="bi bi-house-door-fill"></i><span>Home</span></a>
    <div style="width:60px;height:60px;"></div>
    <div class="btn-center-add" data-bs-toggle="modal" data-bs-target="#modalNewBooking"><i class="bi bi-plus-lg"></i></div>
    <button onclick="confirmLogout()" class="nav-item-bottom text-danger"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
</div>

<!-- Logout confirm modal -->
<div class="modal fade" id="modalConfirmLogout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body text-center p-4">
                <h5 class="fw-bold mb-2">Sign out?</h5>
                <p class="text-muted small mb-4">You will need to log in again.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">No</button>
                    <a href="/SRMT/public/auth/logout.php" class="btn btn-danger rounded-pill px-4 fw-bold">Yes</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New booking modal -->
<div class="modal fade" id="modalNewBooking" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">New Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <form id="formNewBooking">
                    <div class="row g-3">
                        <div class="col-6"><label class="small text-muted fw-bold">Date</label><input type="date" name="date" class="form-control" required></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Time</label><input type="time" name="time" class="form-control" required></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Name</label><input type="text" name="client_name" class="form-control" required></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Phone</label><input type="text" name="client_phone" class="form-control"></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Price</label><input type="number" step="0.01" name="price" class="form-control"></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Adults</label><input type="number" name="pax_adt" value="1" class="form-control" required></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Crianças</label><input type="number" name="pax_chd" value="0" class="form-control"></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Bebés</label><input type="number" name="pax_bby" value="0" class="form-control"></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Pickup</label><input type="text" name="pickup" class="form-control" required></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Destination</label><input type="text" name="dropoff" class="form-control" required></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Flight</label><input type="text" name="flight" class="form-control"></div>
                        <div class="col-6">
                            <label class="small text-muted fw-bold">Has Key?</label>
                            <select name="has_key" class="form-select">
                                <option value="0" selected>No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="col-12 mt-4"><button type="submit" class="btn btn-primary w-100 rounded-pill">Submit</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit booking modal -->
<div class="modal fade" id="modalEditBooking" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Edit Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <form id="formEditBooking">
                    <input type="hidden" name="ride_id" id="edit_ride_id">
                    <div class="row g-3">
                        <div class="col-6"><label class="small text-muted fw-bold">Date</label><input type="date" name="date" id="edit_date" class="form-control" required></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Time</label><input type="time" name="time" id="edit_time" class="form-control" required></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Name</label><input type="text" name="client_name" id="edit_client_name" class="form-control" required></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Phone</label><input type="text" name="client_phone" id="edit_client_phone" class="form-control"></div>
                        <div class="col-6"><label class="small text-muted fw-bold">Flight</label><input type="text" name="flight" id="edit_flight" class="form-control"></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Adults</label><input type="number" name="pax_adt" id="edit_pax_adt" value="1" class="form-control" required></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Crianças</label><input type="number" name="pax_chd" id="edit_pax_chd" value="0" class="form-control"></div>
                        <div class="col-4"><label class="small text-muted fw-bold">Bebés</label><input type="number" name="pax_bby" id="edit_pax_bby" value="0" class="form-control"></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Pickup</label><input type="text" name="pickup" id="edit_pickup" class="form-control" required></div>
                        <div class="col-12"><label class="small text-muted fw-bold">Destination</label><input type="text" name="dropoff" id="edit_dropoff" class="form-control" required></div>
                        <div class="col-12 mt-2"><button type="submit" class="btn btn-primary w-100 rounded-pill">Save Changes</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme
(function () {
    const html   = document.documentElement;
    const toggle = document.getElementById('theme-toggle');
    const icon   = document.getElementById('theme-icon');
    const logo   = document.getElementById('app-logo');
    function applyTheme(t) {
        html.setAttribute('data-bs-theme', t);
        icon.className = t === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
        if (logo) logo.src = t === 'dark' ? '/SRMT/public/assets/images/icons/Syncridewhite.png' : '/SRMT/public/assets/images/icons/SyncRide.png';
    }
    applyTheme(localStorage.getItem('theme') || 'light');
    toggle.addEventListener('click', () => {
        const next = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', next); applyTheme(next);
    });
})();

function confirmLogout() { new bootstrap.Modal(document.getElementById('modalConfirmLogout')).show(); }

let table, currentStatus = 'pendente';
$(document).ready(function () {
    table = $('#tabelaPartner').DataTable({
        language: { emptyTable: 'No bookings in this status', zeroRecords: 'No results found' },
        ajax: { url: '/SRMT/public/partner/api-rides.php', data: d => { d.status = currentStatus; } },
        columns: [
            { data: 'data_hora', className: 'ps-4' },
            { data: 'cliente', className: 'fw-bold' },
            { data: 'voo', render: (d) => window.innerWidth < 768 ? '<i class="bi bi-airplane-fill text-primary me-2"></i> ' + (d || '--') : d },
            { data: 'rota', render: (d) => window.innerWidth < 768 ? '<i class="bi bi-geo-alt-fill text-muted me-2"></i> ' + d : d },
            { data: 'pax',  render: (d) => window.innerWidth < 768 ? '<i class="bi bi-people-fill text-muted me-2"></i> ' + d : d },
            { data: 'has_key', className: 'text-center', render: d => d == 1
                ? '<span class="badge bg-success rounded-pill"><i class="bi bi-key-fill"></i> Yes</span>'
                : '<span class="badge bg-danger rounded-pill"><i class="bi bi-x-lg"></i> No</span>' },
            { data: 'status', className: 'text-center', render: d => {
                const s = (d||'').toLowerCase();
                const [cls, txt] = s === 'pendente' ? ['bg-warning text-warning','Pending'] : s === 'aprovado' ? ['bg-success text-success','Confirmed'] : s === 'rejeitado' ? ['bg-danger text-danger','Rejected'] : ['bg-secondary text-secondary', d];
                return `<span class="badge rounded-pill ${cls} bg-opacity-10 px-3 py-2 border border-opacity-10">${txt}</span>`;
            }},
            { data: 'acoes', className: 'text-end pe-3', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        dom: 'rt<"d-flex justify-content-center mt-3"p>',
        pageLength: 10,
        autoWidth: false
    });

    $('#customSearch').on('keyup', function () { table.search(this.value).draw(); });

    // Tab switching
    document.querySelectorAll('#partner-tabs .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('#partner-tabs .nav-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            currentStatus = link.dataset.status;
            table.ajax.reload();
        });
    });

    // Open edit modal — delegate on tbody because rows are re-rendered by DataTables
    $('#tabelaPartner tbody').on('click', '.btn-edit-ride', function () {
        const row = table.row($(this).closest('tr')).data();
        if (!row) return;
        const m = document.getElementById('modalEditBooking');
        m.querySelector('#edit_ride_id').value    = row.raw_id;
        m.querySelector('#edit_date').value       = row.raw_date;
        m.querySelector('#edit_time').value       = row.raw_time;
        m.querySelector('#edit_client_name').value= row.raw_client;
        m.querySelector('#edit_client_phone').value= row.raw_phone;
        m.querySelector('#edit_pax_adt').value    = row.raw_pax_adt;
        m.querySelector('#edit_pax_chd').value    = row.raw_pax_chd;
        m.querySelector('#edit_pax_bby').value    = row.raw_pax_bby;
        m.querySelector('#edit_pickup').value     = row.raw_pickup;
        m.querySelector('#edit_dropoff').value    = row.raw_dropoff;
        m.querySelector('#edit_flight').value     = row.raw_flight;
        new bootstrap.Modal(m).show();
    });

    $('#formEditBooking').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Saving…');
        fetch('/SRMT/public/partner/api-update-ride.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(res => {
                if (res.success) { toastr.success('Booking updated!'); bootstrap.Modal.getInstance(document.getElementById('modalEditBooking')).hide(); table.ajax.reload(); }
                else toastr.error(res.error || 'Update not allowed');
            })
            .catch(() => toastr.error('Network error'))
            .finally(() => btn.prop('disabled', false).html('Save Changes'));
    });

    $('#formNewBooking').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Sending…');
        fetch('/SRMT/public/partner/api-create-ride.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(res => {
                if (res.success) { toastr.success('Booking submitted!'); $('#modalNewBooking').modal('hide'); $('#formNewBooking')[0].reset(); table.ajax.reload(); }
                else toastr.error(res.error || 'Error');
            })
            .catch(() => toastr.error('Network error'))
            .finally(() => btn.prop('disabled', false).html('Submit'));
    });
});
</script>
</body>
</html>
