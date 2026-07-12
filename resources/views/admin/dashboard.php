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
                aiThinking: "' . t('dash.syncai_thinking') . '",
                aiNewChat: "' . addslashes(t('dash.syncai_new_chat')) . '",
                aiNoConvos: "' . addslashes(t('dash.syncai_no_convos')) . '",
                aiDeleteConfirm: "' . addslashes(t('dash.syncai_delete_confirm')) . '",
                aiError: "' . addslashes(t('dash.syncai_error')) . '"
            };
            let aiConversationId = 0;
            let aiConversations  = [];

            function toggleAI() {
                const overlay = document.getElementById("ai-overlay");
                overlay.classList.toggle("active");
                document.body.style.overflow = overlay.classList.contains("active") ? "hidden" : "";
                if (overlay.classList.contains("active")) {
                    setTimeout(() => document.getElementById("ai-input").focus(), 400);
                    loadAiConversations();
                }
            }
            function toggleAiRail() {
                document.getElementById("ai-rail").classList.toggle("open");
            }
            async function loadAiConversations() {
                try {
                    const r = await fetch("/SRMT/public/api/sync-ai-engine.php?conversations=1");
                    const data = await r.json();
                    if (!data.success) return;
                    aiConversations = data.conversations;
                    renderAiRail();
                } catch (e) { /* silent */ }
            }
            function renderAiRail() {
                const list = document.getElementById("ai-rail-list");
                list.innerHTML = aiConversations.map(c => `
                    <div class="ai-rail-item ${c.id === aiConversationId ? "active" : ""}" onclick="selectAiConversation(${c.id})">
                        <span class="ai-rail-title">${escAi(c.title || SR.aiNewChat)}</span>
                        <button class="ai-rail-del" onclick="event.stopPropagation();deleteAiConversation(${c.id})">✕</button>
                    </div>
                `).join("") || `<p class="ai-rail-empty">${SR.aiNoConvos}</p>`;
            }
            function escAi(s) { return (s == null ? "" : String(s)).replace(/[&<>"]/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c])); }
            window.newAiConversation = function () {
                aiConversationId = 0;
                document.getElementById("ai-chat-content").innerHTML = `<div class="glass p-4 rounded-2xl rounded-tl-none text-zinc-300 border-indigo-500/20 max-w-[85%]">${SR.aiHello}</div>`;
                document.getElementById("ai-rail").classList.remove("open");
                renderAiRail();
            };
            window.selectAiConversation = async function (id) {
                aiConversationId = id;
                document.getElementById("ai-rail").classList.remove("open");
                renderAiRail();
                try {
                    const r = await fetch("/SRMT/public/api/sync-ai-engine.php?history=1&conversation_id=" + id);
                    const data = await r.json();
                    if (!data.success) return;
                    const content = document.getElementById("ai-chat-content");
                    content.innerHTML = data.messages.map(renderAiBubble).join("") ||
                        `<div class="glass p-4 rounded-2xl rounded-tl-none text-zinc-300 border-indigo-500/20 max-w-[85%]">${SR.aiHello}</div>`;
                    content.scrollTop = content.scrollHeight;
                } catch (e) { /* silent */ }
            };
            window.deleteAiConversation = async function (id) {
                if (!confirm(SR.aiDeleteConfirm)) return;
                try {
                    await fetch("/SRMT/public/api/sync-ai-engine.php?action=delete_conversation", {
                        method: "POST", headers: {"Content-Type":"application/json"}, body: JSON.stringify({conversation_id: id}),
                    });
                    if (id === aiConversationId) newAiConversation();
                    loadAiConversations();
                } catch (e) { /* silent */ }
            };
            function renderAiBubble(m) {
                const atts   = m.attachments || [];
                const photos = atts.filter(a => a.type === "photo")
                    .map(a => `<img src="${escAi(a.url)}" title="${escAi(a.caption)}" class="ai-bubble-photo" onclick="window.open(this.src)">`).join("");
                const rides  = atts.filter(a => a.type === "ride")
                    .map(a => `<button class="ai-ride-chip" data-ride="${escAi(JSON.stringify(a.ride))}" onclick="openRideModal(JSON.parse(this.dataset.ride))"><i data-lucide="car" class="w-3 h-3"></i> ${escAi(a.label)}</button>`).join("");
                if (m.role === "user") {
                    return `<div class="flex justify-end"><div class="bg-indigo-600 p-3 rounded-2xl rounded-tr-none max-w-[80%] border border-white/10 text-white text-xs font-medium shadow-lg">${escAi(m.content)}</div></div>`;
                }
                const html = `<div class="flex justify-start"><div class="glass p-4 rounded-2xl rounded-tl-none max-w-[90%] text-zinc-200 border-white/10 text-xs leading-relaxed shadow-xl">
                    ${escAi(m.content)}${photos ? `<div class="ai-bubble-photos">${photos}</div>` : ""}${rides ? `<div class="ai-ride-chips">${rides}</div>` : ""}
                </div></div>`;
                setTimeout(() => window.lucide?.createIcons(), 0);
                return html;
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
                content.innerHTML += `<div class="flex justify-end"><div class="bg-indigo-600 p-3 rounded-2xl rounded-tr-none max-w-[80%] border border-white/10 text-white text-xs font-medium shadow-lg">${escAi(msg)}</div></div>`;
                input.value = "";
                typing.classList.remove("hidden");
                content.scrollTop = content.scrollHeight;
                try {
                    const r = await fetch("/SRMT/public/api/sync-ai-engine.php", {
                        method: "POST", headers: {"Content-Type":"application/json"},
                        body: JSON.stringify({message: msg, conversation_id: aiConversationId}),
                    });
                    const data = await r.json();
                    typing.classList.add("hidden");
                    if (data.success) {
                        aiConversationId = data.conversation_id;
                        content.innerHTML += renderAiBubble(data.message);
                        loadAiConversations();
                    } else {
                        content.innerHTML += `<div class="text-red-500 text-[9px] font-black text-center uppercase tracking-widest">${SR.aiError}</div>`;
                    }
                } catch(e) {
                    typing.classList.add("hidden");
                    content.innerHTML += `<div class="text-red-500 text-[9px] font-black text-center uppercase tracking-widest">${SR.aiError}</div>`;
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
                backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px);
                border-radius: 28px 28px 0 0;
                z-index: 3000; transition: top 0.45s cubic-bezier(0.19, 1, 0.22, 1);
                display: flex; flex-direction: column; overflow: hidden;
            }
            [data-theme="dark"] #ai-overlay { background: rgba(10,10,14,0.97); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] #ai-overlay { background: rgba(255,255,255,0.97); border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 -24px 64px rgba(0,0,0,0.14); }
            #ai-overlay.active { top: 8vh; }
            #ai-input { font-size: 16px !important; }

            .ai-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 14px; flex-shrink: 0; }
            [data-theme="dark"] .ai-head { border-bottom: 1px solid rgba(255,255,255,0.06); }
            [data-theme="light"] .ai-head { border-bottom: 1px solid rgba(0,0,0,0.06); }
            .ai-head-icon-btn {
                width: 34px; height: 34px; border-radius: 11px; display: flex; align-items: center; justify-content: center;
                cursor: pointer; flex-shrink: 0; transition: background .15s;
            }
            [data-theme="dark"] .ai-head-icon-btn { background: rgba(255,255,255,0.06); color: #e2e8f0; }
            [data-theme="light"] .ai-head-icon-btn { background: rgba(0,0,0,0.05); color: #334155; }
            [data-theme="dark"] .ai-head-icon-btn:hover { background: rgba(255,255,255,0.12); }
            [data-theme="light"] .ai-head-icon-btn:hover { background: rgba(0,0,0,0.09); }
            .ai-title { font-size: 15px; font-weight: 800; letter-spacing: -0.01em; }
            [data-theme="dark"] .ai-title { color: #fff; }
            [data-theme="light"] .ai-title { color: #0f172a; }
            .ai-subtitle { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: #818cf8; }

            .ai-body { flex: 1; position: relative; min-height: 0; display: flex; }
            .ai-rail {
                position: absolute; inset: 0 auto 0 0; width: min(280px, 78%); z-index: 5;
                transform: translateX(-105%); transition: transform .28s ease;
                display: flex; flex-direction: column; padding: 14px 10px;
            }
            [data-theme="dark"] .ai-rail { background: rgba(18,18,24,0.98); border-right: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] .ai-rail { background: rgba(248,250,252,0.98); border-right: 1px solid rgba(0,0,0,0.08); box-shadow: 12px 0 32px rgba(0,0,0,0.12); }
            .ai-rail.open { transform: translateX(0); }
            .ai-rail-head { display: flex; align-items: center; justify-content: space-between; padding: 4px 6px 10px; }
            .ai-rail-head span { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; }
            .ai-rail-new {
                font-size: 10.5px; font-weight: 800; color: #fff; background: #4f46e5; border: none;
                border-radius: 999px; padding: 5px 11px; cursor: pointer;
            }
            #ai-rail-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
            .ai-rail-item {
                display: flex; align-items: center; justify-content: space-between; gap: 6px;
                padding: 9px 10px; border-radius: 10px; cursor: pointer; font-size: 12px; font-weight: 600;
            }
            [data-theme="dark"] .ai-rail-item { color: #cbd5e1; }
            [data-theme="light"] .ai-rail-item { color: #334155; }
            [data-theme="dark"] .ai-rail-item:hover { background: rgba(255,255,255,0.06); }
            [data-theme="light"] .ai-rail-item:hover { background: rgba(0,0,0,0.05); }
            .ai-rail-item.active { background: rgba(79,70,229,0.16); color: #818cf8; }
            .ai-rail-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .ai-rail-del { border: none; background: none; color: #94a3b8; cursor: pointer; font-size: 11px; flex-shrink: 0; opacity: .6; }
            .ai-rail-del:hover { opacity: 1; color: #f87171; }
            .ai-rail-empty { font-size: 11.5px; color: #94a3b8; text-align: center; padding: 20px 8px; }
            .ai-canvas { flex: 1; display: flex; flex-direction: column; min-width: 0; }
            .ai-bubble-photo { max-width: 200px; border-radius: 12px; margin-top: 8px; display: block; cursor: pointer; }
            .ai-bubble-photos { display: flex; flex-wrap: wrap; gap: 8px; }
            .ai-ride-chips { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
            .ai-ride-chip {
                display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700;
                color: #a5b4fc; background: rgba(99,102,241,0.14); border: 1px solid rgba(99,102,241,0.3);
                border-radius: 10px; padding: 7px 10px; cursor: pointer; text-align: left;
            }
            .ai-ride-chip:hover { background: rgba(99,102,241,0.22); }

            /* Desktop: the rail is a permanent docked column, not a mobile-style
               slide-in drawer — full width stays, only the sidebar behavior changes. */
            @media (min-width: 781px) {
                .ai-rail-toggle { display: none !important; }
                .ai-rail { position: static; width: 280px; flex-shrink: 0; transform: none !important; }
                #ai-chat-content, #ai-input-container { max-width: 760px; margin-left: auto; margin-right: auto; width: 100%; }
            }
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

<section class="px-6 mt-6 grid grid-cols-3 gap-2 text-center">
    <div class="flex flex-col items-center gap-1.5">
        <a href="/SRMT/public/admin/rides.php?new=1" class="action-circle text-blue-500"><i data-lucide="plus" class="w-5 h-5"></i></a>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.create') ?></span>
    </div>
    <div class="flex flex-col items-center gap-1.5">
        <button onclick="document.getElementById('xmlFile').click()" class="action-circle text-zinc-400"><i data-lucide="file-up" class="w-5 h-5"></i></button>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.xml') ?></span>
    </div>
    <div class="flex flex-col items-center gap-1.5">
        <a href="/SRMT/public/admin/import.php" class="action-circle text-emerald-500"><i data-lucide="file-spreadsheet" class="w-5 h-5"></i></a>
        <span class="text-[8px] font-black text-zinc-500 uppercase"><?= t('dash.excel') ?></span>
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
    <div class="w-10 h-1 bg-zinc-500/40 rounded-full mx-auto mt-3 cursor-pointer" onclick="toggleAI()"></div>
    <div class="ai-head">
        <div class="flex items-center gap-3">
            <button class="ai-head-icon-btn ai-rail-toggle" title="<?= t('dash.syncai_conversations') ?>" onclick="toggleAiRail()">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
            <div class="relative flex-shrink-0">
                <i data-lucide="bot" class="w-6 h-6 text-indigo-500"></i>
                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-emerald-500 rounded-full border-2 border-black/40 animate-pulse"></span>
            </div>
            <div>
                <h3 class="ai-title"><?= t('dash.syncai_cmd') ?></h3>
                <p class="ai-subtitle"><?= t('dash.syncai_intel') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button class="ai-head-icon-btn" title="<?= t('dash.syncai_new_chat') ?>" onclick="newAiConversation()">
                <i data-lucide="plus" class="w-4 h-4"></i>
            </button>
            <button class="ai-head-icon-btn" title="<?= t('chat.close_btn') ?>" onclick="toggleAI()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div class="ai-body">
        <div class="ai-rail" id="ai-rail">
            <div class="ai-rail-head">
                <span><?= t('dash.syncai_conversations') ?></span>
                <button class="ai-rail-new" onclick="newAiConversation()">+ <?= t('dash.syncai_new_chat') ?></button>
            </div>
            <div id="ai-rail-list"></div>
        </div>

        <div class="ai-canvas">
            <div id="ai-chat-content" class="flex-1 overflow-y-auto space-y-4 px-4 py-4">
                <div class="glass p-4 rounded-2xl rounded-tl-none text-zinc-300 border-indigo-500/20 max-w-[85%]">
                    <?= t('dash.syncai_hello') ?>
                </div>
            </div>
            <div id="ai-input-container" class="pb-6 px-4 pt-2 flex-shrink-0">
                <div id="ai-typing" class="hidden text-[9px] text-indigo-400 font-black mb-2 ml-4 uppercase italic tracking-widest"><?= t('dash.syncai_thinking') ?></div>
                <div class="glass p-2 rounded-[24px] flex items-center gap-2 border-indigo-500/30">
                    <input type="text" id="ai-input" placeholder="<?= t('dash.ask_anything') ?>" class="bg-transparent flex-1 outline-none text-white px-4 py-2" style="font-size:16px;">
                    <button onclick="sendToAI()" class="w-11 h-11 bg-indigo-600 rounded-full flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 flex-shrink-0">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="hidden">
    <input type="file" name="xmlFile" id="xmlFile" accept=".xml" onchange="this.form.submit()">
</form>
