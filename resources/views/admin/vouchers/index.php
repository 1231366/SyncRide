<?php
use App\Http\View;

View::layout('layouts.admin', [
    'title'        => 'Vouchers — SyncRide OS',
    'active'       => 'vouchers',
    'extraHead'    => '
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <style>
            #tabelaVouchers { display: block; width: 100%; }
            #tabelaVouchers thead { display: none; }
            #tabelaVouchers tbody { display: grid; grid-template-columns: 1fr; gap: 10px; }
            @media (min-width: 768px)  { #tabelaVouchers tbody { grid-template-columns: repeat(2,1fr); } }
            @media (min-width: 1280px) { #tabelaVouchers tbody { grid-template-columns: repeat(3,1fr); } }
            #tabelaVouchers tbody tr {
                display: block; position: relative;
                border-radius: 22px; padding: 18px; transition: all .2s;
            }
            [data-theme="dark"]  #tabelaVouchers tbody tr { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] #tabelaVouchers tbody tr { background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); }
            #tabelaVouchers tbody td { display: block; border: none !important; padding: 0 !important; background: transparent !important; width: 100% !important; }
            [data-theme="dark"]  #tabelaVouchers tbody td { color: #f1f5f9; }
            [data-theme="light"] #tabelaVouchers tbody td { color: #0f172a; }
            #tabelaVouchers tbody td:nth-child(1) { font-size: 9px; color: #71717a; font-weight: 800; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.15em; font-family: monospace; }
            #tabelaVouchers tbody td:nth-child(1)::before { content: "ID #"; }
            #tabelaVouchers tbody td:nth-child(2) { font-size: 16px; font-weight: 800; margin-bottom: 4px; padding-right: 60px !important; }
            #tabelaVouchers tbody td:nth-child(3) { font-size: 12px; color: #71717a; margin-bottom: 4px; }
            #tabelaVouchers tbody td:nth-child(4) { font-size: 12px; display: flex !important; align-items: center; gap: 8px; margin-bottom: 4px; }
            #tabelaVouchers tbody td:nth-child(4):before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #60a5fa; flex-shrink: 0; }
            #tabelaVouchers tbody td:nth-child(5) { font-size: 12px; color: #71717a; margin-bottom: 0; }
            #tabelaVouchers tbody td:last-child { position: absolute; top: 16px; right: 16px; width: auto !important; }
            #tabelaVouchers .btn {
                width: 36px; height: 36px; padding: 0;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 999px; transition: all .15s;
            }
            [data-theme="dark"]  #tabelaVouchers .btn { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: #d4d4d8; }
            [data-theme="light"] #tabelaVouchers .btn { background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1); color: #475569; }
            #tabelaVouchers .btn-info    { color: #60a5fa !important; border-color: rgba(96,165,250,0.3) !important; }
            #tabelaVouchers .btn-success { color: #34d399 !important; border-color: rgba(52,211,153,0.3) !important; }
            .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }
            #filter-container .dataTables_filter { display: block !important; margin: 0; padding: 0; }
            #filter-container .dataTables_filter label { display: block; margin: 0; color: transparent; font-size: 0; }
            #filter-container .dataTables_filter input {
                width: 100%; padding: 10px 14px 10px 38px; border-radius: 14px;
                font-size: 13px; outline: none; font-family: inherit; transition: border-color .2s;
            }
            [data-theme="dark"]  #filter-container .dataTables_filter input { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9; }
            [data-theme="light"] #filter-container .dataTables_filter input { background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.1); color: #0f172a; }
            #filter-container .dataTables_filter input::placeholder { color: #71717a; }
            .dataTables_paginate { padding: 20px 0 4px 0; }
            .dataTables_paginate .pagination { margin: 0; padding: 0; gap: 4px; display: flex; justify-content: center; flex-wrap: wrap; list-style: none; }
            .dataTables_paginate .page-item { list-style: none; }
            .dataTables_paginate .page-link {
                padding: 0 !important; min-width: 34px; height: 34px; border-radius: 999px !important;
                font-size: 12px; font-weight: 700; text-align: center; box-shadow: none !important;
                transition: all .15s; outline: none !important;
                display: inline-flex; align-items: center; justify-content: center;
            }
            [data-theme="dark"]  .dataTables_paginate .page-link { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.08) !important; color: #d4d4d8 !important; }
            [data-theme="light"] .dataTables_paginate .page-link { background: rgba(0,0,0,0.04) !important; border: 1px solid rgba(0,0,0,0.10) !important; color: #475569 !important; }
            .dataTables_paginate .page-item.active .page-link { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
            .modal-content { backdrop-filter: blur(30px); border-radius: 28px; }
            [data-theme="dark"]  .modal-content { background: rgba(10,12,20,0.97); border: 1px solid rgba(255,255,255,0.1); color: #f1f5f9; }
            [data-theme="light"] .modal-content { background: rgba(255,255,255,0.96); border: 1px solid rgba(0,0,0,0.1); color: #0f172a; }
            [data-theme="dark"]  .modal-header { border-color: rgba(255,255,255,0.08); }
            [data-theme="light"] .modal-header { border-color: rgba(0,0,0,0.08); }
            [data-theme="dark"]  .btn-close { filter: invert(1) brightness(2); opacity: 0.6; }
            #photoModalImage { width: 100%; height: auto; border-radius: 18px; }
            .search-wrap { position: relative; flex: 1; min-width: 0; }
            .search-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #71717a; pointer-events: none; }
        </style>
    ',
    'extraScripts' => '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script>
            let tabelaVouchers, photoModalBs;

            $(document).ready(function() {
                photoModalBs = new bootstrap.Modal(document.getElementById("photoModal"));

                tabelaVouchers = $("#tabelaVouchers").DataTable({
                    processing: true, serverSide: false,
                    ajax: { url: "vouchers-data.php", type: "GET", dataSrc: "data" },
                    columns: [
                        { data: "id" },
                        { data: "client" },
                        { data: "date" },
                        { data: "route" },
                        { data: "driver" },
                        { data: "photo_url", orderable: false, render: function(url, type, row) {
                            return \'<button class="btn btn-info" onclick="openPhotoModal(\' + row.id + \', \\\'\' + url + \'\\\')" title="Ver voucher"><i class="bi bi-ticket-perforated-fill"></i></button>\'
                                 + \' <a class="btn btn-success" href="\' + url + \'" download="Voucher-\' + row.id + \'.jpg" title="Download"><i class="bi bi-download"></i></a>\';
                        }}
                    ],
                    language: { search: "", searchPlaceholder: "Pesquisar cliente, rota, condutor...", lengthMenu: "", info: "", paginate: { next: "→", previous: "←" }, zeroRecords: "Sem vouchers registados." },
                    order: [[2,"desc"]], pageLength: 12, dom: "frtp"
                });

                $("#tabelaVouchers_filter").appendTo("#filter-container");
                $("#tabelaVouchers").on("draw.dt", function() { if(window.lucide) lucide.createIcons(); });
            });

            function openPhotoModal(tripId, photoUrl) {
                document.getElementById("photoModalTitle").textContent = "Voucher #" + tripId;
                document.getElementById("photoModalImage").src = photoUrl;
                photoModalBs.show();
            }
        </script>
    ',
]);
?>

<main class="px-6 mt-8">
    <div class="mb-6">
        <h1 class="text-[24px] font-extrabold tracking-tight">Vouchers</h1>
        <p class="text-[11px] text-zinc-500 font-semibold mt-1">Fotografia dos vouchers submetidos pelos condutores</p>
    </div>

    <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2">
        <div id="filter-container" class="search-wrap">
            <i data-lucide="search" class="search-icon w-4 h-4"></i>
        </div>
    </div>

    <table id="tabelaVouchers" class="table" style="width:100%">
        <thead>
            <tr><th>ID</th><th>Cliente</th><th>Data</th><th>Rota</th><th>Condutor</th><th>Ações</th></tr>
        </thead>
        <tbody></tbody>
    </table>
</main>

<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title" id="photoModalTitle">Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" id="photoModalImage" class="img-fluid" alt="Voucher photo">
            </div>
        </div>
    </div>
</div>
