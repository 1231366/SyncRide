<?php
/** @var array{all_time:int,today:int,week:int} $stats */
/** @var array<array<string,mixed>> $nextRides */
/** @var array<int> $monthlyChart */
/** @var int|null $imported */

use App\Http\View;

View::layout('layouts.admin', [
    'title'        => 'SyncRide OS — Dashboard',
    'active'       => 'dashboard',
    'extraScripts' => '
        <script>
            var SR = {
                modalTime: "' . t('dash.time') . '",
                modalFlight: "' . t('dash.flight') . '",
                modalOcc: "' . t('dash.occupancy') . '",
                aiHello: "' . addslashes(t('dash.syncai_hello')) . '",
                aiThinking: "' . t('dash.syncai_thinking') . '"
            };
            function toggleAI() {
                const overlay = document.getElementById("ai-overlay");
                overlay.classList.toggle("active");
                document.body.style.overflow = overlay.classList.contains("active") ? "hidden" : "";
                if (overlay.classList.contains("active")) setTimeout(() => document.getElementById("ai-input").focus(), 400);
            }
            function openRideModal(data) {
                document.getElementById("modalClient").innerText = data.NomeCliente || "—";
                document.getElementById("modalID").innerText = "RIDE #" + data.ID;
                document.getElementById("modalTime").innerText = (data.serviceStartTime || "").substring(0,5);
                document.getElementById("modalFlight").innerText = data.FlightNumber || "—";
                document.getElementById("modalStart").innerText = data.serviceStartPoint || "—";
                document.getElementById("modalEnd").innerText = data.serviceTargetPoint || "—";
                document.getElementById("modalPax").innerText = (data.paxADT || 0) + " / " + (data.paxCHD || 0);
                document.getElementById("modalOverlay").classList.add("active");
                document.getElementById("rideModal").classList.add("active");
            }
            function closeRideModal() {
                document.getElementById("modalOverlay").classList.remove("active");
                document.getElementById("rideModal").classList.remove("active");
            }
            async function sendToAI() {
                const input = document.getElementById("ai-input");
                const content = document.getElementById("ai-chat-content");
                const typing = document.getElementById("ai-typing");
                const msg = input.value.trim();
                if (!msg) return;
                content.innerHTML += `<div class="flex justify-end"><div class="bg-indigo-600 p-3 rounded-2xl rounded-tr-none max-w-[80%] border border-white/10 text-white text-xs font-medium shadow-lg">${msg}</div></div>`;
                input.value = "";
                typing.classList.remove("hidden");
                content.scrollTop = content.scrollHeight;
                try {
                    const r = await fetch("/SRMT/public/api/sync-ai-engine.php", { method: "POST", headers: {"Content-Type":"application/json"}, body: JSON.stringify({message: msg}) });
                    const data = await r.json();
                    typing.classList.add("hidden");
                    content.innerHTML += `<div class="flex justify-start"><div class="glass p-4 rounded-2xl rounded-tl-none max-w-[90%] text-zinc-200 border-white/10 text-xs leading-relaxed shadow-xl">${data.response || ""}</div></div>`;
                } catch(e) {
                    typing.classList.add("hidden");
                    content.innerHTML += `<div class="text-red-500 text-[9px] font-black text-center uppercase tracking-widest">SyncAI sync error</div>`;
                }
                content.scrollTop = content.scrollHeight;
            }
            document.addEventListener("DOMContentLoaded", () => {
                document.getElementById("ai-input").addEventListener("keypress", e => { if (e.key === "Enter") sendToAI(); });

                const isLight = document.documentElement.dataset.theme === "light";
                const chartColor = isLight ? "#2563eb" : "#ffffff";

                new ApexCharts(document.querySelector("#chartAnual"), {
                    series: [{ name: "Rides", data: ' . json_encode($monthlyChart) . ' }],
                    chart: { type: "area", height: 110, toolbar: { show: false }, sparkline: { enabled: true }, background: "transparent" },
                    stroke: { curve: "smooth", width: 2, colors: [chartColor] },
                    fill: { type: "gradient", gradient: { opacityFrom: 0.3, opacityTo: 0 } },
                    colors: [chartColor],
                    tooltip: { theme: isLight ? "light" : "dark", x: { show: false } }
                }).render();
            });
        </script>
        <style>
            #ai-overlay {
                position: fixed; top: 100%; left: 0; width: 100%; height: 92vh;
                background: rgba(10,10,10,0.95); backdrop-filter: blur(40px);
                border-radius: 32px 32px 0 0; border: 1px solid rgba(255,255,255,0.1);
                z-index: 3000; transition: top 0.4s cubic-bezier(0.19, 1, 0.22, 1);
                display: flex; flex-direction: column;
            }
            #ai-overlay.active { top: 8vh; }
            #ai-input { font-size: 16px !important; }
            #rideModal {
                position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) scale(0.9);
                width: 85%; max-width: 360px; visibility: hidden; opacity: 0;
                backdrop-filter: blur(30px);
                border-radius: 28px;
                z-index: 4000; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                padding: 20px;
            }
            [data-theme="dark"] #rideModal {
                background: rgba(20,20,20,0.95);
                border: 1px solid rgba(255,255,255,0.15);
            }
            [data-theme="light"] #rideModal {
                background: rgba(255,255,255,0.92);
                border: 1px solid rgba(0,0,0,0.10);
                box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            }
            #rideModal.active { visibility: visible; opacity: 1; transform: translate(-50%,-50%) scale(1); }
            .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s; }
            .modal-overlay.active { visibility: visible; opacity: 1; }
            [data-theme="light"] .modal-overlay { background: rgba(0,0,0,0.25); }
            .action-circle {
                width: 52px; height: 52px; border-radius: 999px;
                display: flex; align-items: center; justify-content: center;
                transition: all 0.2s;
            }
            [data-theme="dark"] .action-circle {
                background: rgba(255,255,255,0.08);
                border: 1px solid rgba(255,255,255,0.1);
            }
            [data-theme="light"] .action-circle {
                background: rgba(0,0,0,0.05);
                border: 1px solid rgba(0,0,0,0.08);
            }
        </style>
    ',
]);
?>

<?php if ($imported !== null): ?>
<section class="px-6 mt-4">
    <div class="p-3 rounded-[18px] flex items-center gap-3 border <?= $imported > 0 ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-amber-500/30 bg-amber-500/10' ?>">
        <i data-lucide="<?= $imported > 0 ? 'check-circle' : 'info' ?>" class="w-4 h-4 <?= $imported > 0 ? 'text-emerald-400' : 'text-amber-400' ?>"></i>
        <p class="text-[11px] font-bold <?= $imported > 0 ? 'text-emerald-100' : 'text-amber-100' ?>">
            <?= $imported > 0 ? "{$imported} " . t('dash.xml_imported') : t('dash.xml_skipped') ?>
        </p>
    </div>
</section>
<?php endif; ?>

<section class="px-6 mt-4">
    <div onclick="toggleAI()" class="p-3 rounded-[18px] flex items-center justify-between cursor-pointer border border-indigo-500/30 bg-indigo-500/10">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 bg-indigo-500/20 rounded-full flex items-center justify-center"><i data-lucide="sparkles" class="w-4 h-4 text-indigo-400"></i></div>
            <p class="text-[10px] font-bold text-indigo-400 italic"><?= t('dash.syncai_label') ?></p>
        </div>
        <i data-lucide="chevron-right" class="w-4 h-4 text-indigo-500/50"></i>
    </div>
</section>

<section class="px-6 mt-4 grid grid-cols-2 gap-3">
    <div class="glass p-4 rounded-[22px]">
        <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest"><?= t('dash.rides_today') ?></p>
        <h3 class="text-2xl font-black"><?= (int) $stats['today'] ?></h3>
    </div>
    <div class="glass p-4 rounded-[22px]">
        <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest"><?= t('dash.this_week') ?></p>
        <h3 class="text-2xl font-black"><?= (int) $stats['week'] ?></h3>
    </div>
</section>

<section class="px-6 mt-4">
    <div class="glass rounded-[28px] p-5 relative">
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-[10px] font-black uppercase tracking-widest"><?= t('dash.performance') ?></h3>
            <div class="text-right">
                <span class="text-sm font-black text-blue-500 leading-none"><?= (int) $stats['all_time'] ?></span>
                <span class="text-[7px] font-bold text-zinc-500 block uppercase"><?= t('dash.all_time') ?></span>
            </div>
        </div>
        <div id="chartAnual" class="mt-0"></div>
    </div>
</section>

<section class="px-6 mt-6 grid grid-cols-4 gap-2 text-center">
    <div class="flex flex-col items-center gap-1.5">
        <a href="/SRMT/public/admin/rides.php" class="action-circle text-blue-500"><i data-lucide="plus" class="w-5 h-5"></i></a>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.create') ?></span>
    </div>
    <div class="flex flex-col items-center gap-1.5">
        <button onclick="document.getElementById('xmlFile').click()" class="action-circle text-zinc-400"><i data-lucide="file-up" class="w-5 h-5"></i></button>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.xml') ?></span>
    </div>
    <div class="flex flex-col items-center gap-1.5">
        <a href="/SRMT/public/admin/driver-stats.php" class="action-circle text-zinc-400"><i data-lucide="bar-chart-3" class="w-5 h-5"></i></a>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.stats') ?></span>
    </div>
    <div class="flex flex-col items-center gap-1.5">
        <a href="/SRMT/public/admin/users.php" class="action-circle text-zinc-400"><i data-lucide="users" class="w-5 h-5"></i></a>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.team') ?></span>
    </div>
</section>

<section class="px-6 mt-8">
    <div class="flex justify-between items-center mb-4 px-1">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic"><?= t('dash.next_rides') ?></h3>
        <a href="/SRMT/public/admin/rides.php" class="text-[9px] font-black text-blue-500 uppercase"><?= t('dash.view_all') ?></a>
    </div>
    <div class="space-y-2">
        <?php foreach ($nextRides as $ride): ?>
            <div onclick='openRideModal(<?= json_encode($ride, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                 class="glass p-3.5 rounded-2xl flex items-center justify-between cursor-pointer active:scale-95 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 bg-blue-600/10 rounded-full flex items-center justify-center border border-blue-500/20"><i data-lucide="car" class="w-4 h-4 text-blue-500"></i></div>
                    <div>
                        <h4 class="text-xs font-bold"><?= View::e($ride['NomeCliente'] ?? '—') ?></h4>
                        <p class="text-[9px] text-zinc-500 font-bold"><?= View::e($ride['serviceStartTime'] ?? '') ?> • <?= View::e($ride['FlightNumber'] ?? '') ?></p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </div>
        <?php endforeach; ?>
        <?php if ($nextRides === []): ?>
            <div class="glass p-6 rounded-2xl text-center text-zinc-500 text-xs"><?= t('dash.no_upcoming') ?></div>
        <?php endif; ?>
    </div>
</section>

<!-- Ride detail modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeRideModal()"></div>
<div id="rideModal">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h3 id="modalClient" class="text-lg font-black">—</h3>
            <p id="modalID" class="text-[9px] text-blue-500 font-bold uppercase">RIDE #—</p>
        </div>
        <button onclick="closeRideModal()" class="text-zinc-400"><i data-lucide="x-circle"></i></button>
    </div>
    <div class="space-y-4 text-[10px]">
        <div class="flex gap-2">
            <div class="flex-1 bg-white/5 p-3 rounded-2xl"><p class="text-zinc-500 font-bold uppercase mb-1"><?= t('dash.time') ?></p><p id="modalTime" class="font-black">—</p></div>
            <div class="flex-1 bg-white/5 p-3 rounded-2xl"><p class="text-zinc-500 font-bold uppercase mb-1"><?= t('dash.flight') ?></p><p id="modalFlight" class="font-black">—</p></div>
        </div>
        <div class="bg-white/5 p-4 rounded-2xl space-y-3">
            <div class="flex items-center gap-3"><i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i><p id="modalStart" class="truncate text-zinc-400 font-medium">—</p></div>
            <div class="flex items-center gap-3"><i data-lucide="flag" class="w-3 h-3 text-red-500"></i><p id="modalEnd" class="truncate text-zinc-400 font-medium">—</p></div>
        </div>
        <div class="flex justify-between px-2 text-zinc-400 font-bold uppercase"><p><?= t('dash.occupancy') ?></p><p id="modalPax" class="font-black">0 / 0</p></div>
    </div>
</div>

<!-- SyncAI overlay -->
<div id="ai-overlay">
    <div class="flex-1 flex flex-col p-6 relative min-h-0">
        <div class="w-12 h-1 bg-zinc-800 rounded-full mx-auto mb-8 cursor-pointer" onclick="toggleAI()"></div>
        <div class="flex justify-between items-center mb-6 px-2">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i data-lucide="bot" class="w-7 h-7 text-indigo-500"></i>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-black animate-pulse"></span>
                </div>
                <div>
                    <h3 class="text-xl font-black text-white italic tracking-tighter"><?= t('dash.syncai_cmd') ?></h3>
                    <p class="text-[8px] text-zinc-500 font-bold uppercase tracking-widest"><?= t('dash.syncai_intel') ?></p>
                </div>
            </div>
            <button onclick="toggleAI()" class="w-9 h-9 glass rounded-full flex items-center justify-center"><i data-lucide="x" class="w-4 h-4 text-white"></i></button>
        </div>
        <div id="ai-chat-content" class="flex-1 overflow-y-auto space-y-4 px-2 pb-4">
            <div class="glass p-4 rounded-2xl rounded-tl-none text-zinc-300 border-indigo-500/20 max-w-[85%]">
                <?= t('dash.syncai_hello') ?>
            </div>
        </div>
        <div id="ai-input-container" class="pb-6 px-2 mt-2">
            <div id="ai-typing" class="hidden text-[9px] text-indigo-400 font-black mb-2 ml-4 uppercase italic tracking-widest"><?= t('dash.syncai_thinking') ?></div>
            <div class="glass p-2 rounded-[24px] flex items-center gap-2 border-indigo-500/30">
                <input type="text" id="ai-input" placeholder="<?= t('dash.ask_anything') ?>" class="bg-transparent flex-1 outline-none text-white px-4 py-2" style="font-size:16px;">
                <button onclick="sendToAI()" class="w-11 h-11 bg-indigo-600 rounded-full flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="hidden">
    <input type="file" name="xmlFile" id="xmlFile" accept=".xml" onchange="this.form.submit()">
</form>
