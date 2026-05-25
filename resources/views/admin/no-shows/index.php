<?php
use App\Http\View;

View::layout('layouts.admin', [
    'title'        => 'No-Shows — SyncRide OS',
    'active'       => 'no-shows',
    'extraHead'    => '
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <style>
            #tabelaNoShows { display: block; width: 100%; }
            #tabelaNoShows thead { display: none; }
            #tabelaNoShows tbody { display: grid; grid-template-columns: 1fr; gap: 10px; }
            @media (min-width: 768px)  { #tabelaNoShows tbody { grid-template-columns: repeat(2,1fr); } }
            @media (min-width: 1280px) { #tabelaNoShows tbody { grid-template-columns: repeat(3,1fr); } }
            #tabelaNoShows tbody tr {
                display: block; position: relative;
                background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 22px; padding: 18px; transition: all .2s; backdrop-filter: blur(20px);
            }
            #tabelaNoShows tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
            #tabelaNoShows tbody td { display: block; border: none !important; padding: 0 !important; background: transparent !important; color: #fff; width: 100% !important; }
            #tabelaNoShows tbody td:nth-child(1) { font-size: 9px; color: #71717a; font-weight: 800; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.15em; font-family: monospace; }
            #tabelaNoShows tbody td:nth-child(1)::before { content: "ID #"; }
            #tabelaNoShows tbody td:nth-child(2) { font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 14px; padding-right: 60px !important; }
            #tabelaNoShows tbody td:nth-child(3) { font-size: 12px; color: #d4d4d8; display: flex !important; align-items: center; gap: 8px; margin-bottom: 6px; }
            #tabelaNoShows tbody td:nth-child(3):before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,0.15); flex-shrink: 0; }
            #tabelaNoShows tbody td:nth-child(4) { font-size: 12px; color: #a1a1aa; display: flex !important; align-items: center; gap: 8px; margin-bottom: 4px; }
            #tabelaNoShows tbody td:nth-child(4):before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.15); flex-shrink: 0; }
            #tabelaNoShows tbody td:last-child { position: absolute; top: 16px; right: 16px; width: auto !important; }
            #tabelaNoShows .btn,
            #tabelaNoShows button:not([data-bs-dismiss]):not(.btn-close) {
                width: 36px; height: 36px; padding: 0;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 999px; background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.1); color: #d4d4d8; transition: all .15s;
            }
            #tabelaNoShows .btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
            #tabelaNoShows .btn-info    { color: #60a5fa; border-color: rgba(96,165,250,0.3); }
            #tabelaNoShows .btn-success { color: #34d399; border-color: rgba(52,211,153,0.3); }
            .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }
            #filter-container .dataTables_filter { display: block !important; margin: 0; padding: 0; }
            #filter-container .dataTables_filter label { display: block; margin: 0; color: transparent; font-size: 0; }
            #filter-container .dataTables_filter input {
                width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
                color: #fff; padding: 10px 14px 10px 38px; border-radius: 14px; font-size: 13px; outline: none; font-family: inherit;
            }
            #filter-container .dataTables_filter input::placeholder { color: #71717a; }
            #filter-container .dataTables_filter input:focus { border-color: rgba(255,255,255,0.2); }
            .dataTables_paginate { padding: 20px 0 4px 0; }
            .dataTables_paginate .pagination { margin: 0; padding: 0; gap: 4px; display: flex; justify-content: center; flex-wrap: wrap; list-style: none; }
            .dataTables_paginate .page-item { list-style: none; }
            .dataTables_paginate .page-link {
                background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.08) !important;
                color: #d4d4d8 !important; padding: 0 !important;
                min-width: 34px; height: 34px; border-radius: 999px !important;
                font-size: 12px; font-weight: 700; text-align: center; box-shadow: none !important;
                transition: all .15s; outline: none !important;
                display: inline-flex; align-items: center; justify-content: center;
            }
            .dataTables_paginate .page-link:hover { background: rgba(255,255,255,0.09) !important; color: #fff !important; }
            .dataTables_paginate .page-item.active .page-link { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
            .dataTables_paginate .page-item.disabled .page-link { opacity: 0.3; }
            table.dataTable.no-footer { border-bottom: none; }
            .modal-content { background: rgba(20,20,20,0.95); backdrop-filter: blur(30px); border: 1px solid rgba(255,255,255,0.1); border-radius: 28px; color: #fff; }
            .modal-header, .modal-footer { border-color: rgba(255,255,255,0.08); }
            .modal-title { color: #fff; font-weight: 800; }
            .btn-close { filter: invert(1) brightness(2); opacity: 0.6; }
            .btn-close:hover { opacity: 1; }
            #photoModalImage { width: 100%; height: auto; border-radius: 18px; border: 1px solid rgba(255,255,255,0.08); }
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
            let tabelaNoShows, photoModalBs;

            $(document).ready(function() {
                photoModalBs = new bootstrap.Modal(document.getElementById("photoModal"));

                tabelaNoShows = $("#tabelaNoShows").DataTable({
                    processing: true, serverSide: false,
                    ajax: { url: "no-shows-data.php", type: "GET", dataSrc: "data" },
                    columns: [
                        { data: "id" }, { data: "data_hora" },
                        { data: "condutor" }, { data: "rota" },
                        { data: "acoes", orderable: false }
                    ],
                    language: { search: "", searchPlaceholder: "Search…", lengthMenu: "", info: "", paginate: { next: "→", previous: "←" }, zeroRecords: "No records" },
                    order: [[1,"desc"]], pageLength: 12, dom: "frtp"
                });

                $("#tabelaNoShows_filter").appendTo("#filter-container");
                $("#tabelaNoShows").on("draw.dt", function() { lucide.createIcons(); });
            });

            function openPhotoModal(tripId, photoPath) {
                $("#photoModalTitle").text("No-Show #" + tripId);
                $("#photoModalImage").attr("src", photoPath);
                photoModalBs.show();
            }
        </script>
    ',
]);
?>

<main class="px-6 mt-8">
    <div class="mb-6">
        <h1 class="text-[24px] font-extrabold tracking-tight">No-Shows</h1>
        <p class="text-[11px] text-zinc-500 font-semibold mt-1">Photo record of missed pickups.</p>
    </div>

    <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2">
        <div id="filter-container" class="search-wrap">
            <i data-lucide="search" class="search-icon w-4 h-4"></i>
        </div>
    </div>

    <table id="tabelaNoShows" class="table" style="width:100%">
        <thead>
            <tr>
                <th>ID</th><th>Date &amp; Time</th>
                <th>Driver</th><th>Route</th><th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</main>

<!-- Photo modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title" id="photoModalTitle">Photo Evidence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" id="photoModalImage" class="img-fluid" alt="No-show photo">
            </div>
        </div>
    </div>
</div>
