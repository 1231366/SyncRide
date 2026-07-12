<?php
/**
 * Office chat console (admin <-> driver, topics) — ports the approved
 * prototype 1:1: driver rail + canvas (topics, thread, composer), reply,
 * media, search, convert-to-topic. Shared by every admin page via the
 * layout. Trigger from anywhere with: openChatInbox()
 */
?>
<div class="modal-overlay" id="chatOverlay" onclick="if(event.target===this) closeChatModal()"></div>
<div class="modal-os" id="chatModalOS">
    <div class="chat-console" id="chatConsole">
        <div class="chat-rail" id="chatRail">
            <div class="chat-rail-head"><?= t('chat.inbox_title') ?></div>
            <div class="chat-rail-list" id="chatRailList"></div>
        </div>

        <div class="chat-canvas">
            <div class="chat-canvas-head">
                <div class="chat-canvas-title-wrap">
                    <button class="chat-icon-btn chat-mobile-rail-toggle" title="<?= t('chat.inbox_title') ?>" onclick="toggleMobileRail()">☰</button>
                    <div class="chat-avatar" id="chatAvatar">?</div>
                    <div style="min-width:0;">
                        <div class="chat-canvas-title" id="chatCanvasTitle">—</div>
                        <div class="chat-canvas-subtitle" id="chatCanvasSubtitle" style="font-size:11.5px;color:#94a3b8;font-weight:500;"></div>
                    </div>
                </div>
                <div class="chat-canvas-actions">
                    <button class="chat-icon-btn" title="<?= t('chat.search_btn') ?>" onclick="toggleChatSearch()">🔍</button>
                    <button class="chat-icon-btn" id="chatCloseTopicBtn" title="<?= t('chat.close_topic_btn') ?>" onclick="closeActiveTopic()" style="display:none;">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </button>
                    <button class="chat-icon-btn" id="chatDeleteTopicBtn" title="<?= t('chat.delete_topic_btn') ?>" onclick="deleteActiveTopic()" style="display:none;color:#dc2626;">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <button class="chat-icon-btn" title="<?= t('chat.close_btn') ?>" onclick="closeChatModal()">✕</button>
                </div>
            </div>

            <div class="chat-topic-strip" id="chatTopicStrip"></div>
            <div class="chat-ride-banner" id="chatRideBanner" style="display:none;">
                <span>📌</span>
                <span id="chatRideBannerText"></span>
            </div>
            <div class="chat-temp-banner" id="chatTempBanner">
                <span>⏳</span>
                <span><?= t('chat.general_hint') ?></span>
                <button id="chatConvertAllBtn" onclick="convertAllGeneral()"><?= t('chat.convert_all_btn') ?></button>
            </div>

            <div style="padding:0 20px;">
                <div class="chat-search-bar" id="chatSearchBar" style="display:none;">
                    <span>🔍</span>
                    <input class="chat-search-input" id="chatSearchInput" placeholder="<?= t('chat.search_placeholder') ?>" oninput="runChatSearch()">
                    <button onclick="toggleChatSearch()">✕</button>
                </div>
            </div>

            <div class="chat-thread-wrap" id="chatThreadWrap"></div>

            <div class="chat-composer-wrap">
                <div id="chatReplyPreview" class="chat-reply-preview" style="display:none;">
                    <span class="rp-text" id="chatReplyPreviewText"></span>
                    <button onclick="cancelChatReply()">✕</button>
                </div>
                <div id="chatClosedBanner" class="chat-closed-banner" style="display:none;">
                    🔒 <?= t('chat.closed_banner') ?>
                </div>
                <div class="chat-compose-row">
                    <input type="file" id="chatFileInput" accept="image/*" style="display:none;" onchange="handleChatFileSelected(event)">
                    <input type="file" id="chatCameraInput" accept="image/*" capture="environment" style="display:none;" onchange="handleChatFileSelected(event)">
                    <button class="chat-icon-btn" title="<?= t('chat.attach_btn') ?>" onclick="document.getElementById('chatFileInput').click()">📎</button>
                    <button class="chat-icon-btn" title="<?= t('chat.camera_btn') ?>" onclick="document.getElementById('chatCameraInput').click()">📷</button>
                    <input type="text" id="chatInput" placeholder="<?= t('chat.placeholder') ?>"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();sendChatMessage();}">
                    <button class="chat-send-btn" onclick="sendChatMessage()">
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="chat-popover-backdrop" id="chatPopoverBackdrop">
    <div class="chat-popover" id="chatPopoverBody"></div>
</div>

<script>
(function () {
    let pollTimer = null;
    let badgeTimer = null;
    let drivers = [];
    let topics = [];
    let currentMessages = [];
    let activeDriverId = null;
    let activeDriverName = '';
    let activeTopicId = null;
    let replyTo = null;
    const esc = s => (s == null ? '' : String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])));

    window.openChatInbox = function () {
        document.getElementById('chatOverlay').classList.add('active');
        document.getElementById('chatModalOS').classList.add('active');
        document.getElementById('chatConsole').classList.add('rail-mode');
        loadChatInbox();
        clearInterval(pollTimer);
        pollTimer = setInterval(() => {
            loadChatInbox();
            if (activeTopicId) { loadChatTopics(); loadChatThread(activeTopicId); }
        }, 5000);
    };
    window.closeChatModal = function () {
        document.getElementById('chatOverlay').classList.remove('active');
        document.getElementById('chatModalOS').classList.remove('active');
        clearInterval(pollTimer); pollTimer = null;
        refreshChatBadge();
    };
    window.toggleMobileRail = function () {
        document.getElementById('chatConsole').classList.toggle('rail-open');
    };

    async function loadChatInbox() {
        try {
            const res  = await fetch('/SRMT/public/api/driver-chat.php?inbox=1');
            const data = await res.json();
            if (!data.success) return;
            drivers = data.drivers;
            if (activeDriverId === null && drivers.length) {
                await selectDriver(drivers[0].driver_id, drivers[0].driver_name);
            }
            renderRail();
        } catch (e) { /* silent, next poll retries */ }
    }
    function renderRail() {
        const list = document.getElementById('chatRailList');
        list.innerHTML = drivers.map(d => {
            const initial = esc(d.driver_name || '?').charAt(0).toUpperCase();
            const active  = d.driver_id === activeDriverId;
            const unread  = d.unread > 0 ? `<span class="chat-inbox-unread" style="min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:#dc2626;color:#fff;font-size:10px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;">${d.unread > 9 ? '9+' : d.unread}</span>` : '';
            const preview = d.last_message ? esc(d.last_message) : '';
            return `
                <div class="chat-rail-item ${active ? 'active' : ''}" onclick="selectDriver(${d.driver_id}, '${esc(d.driver_name).replace(/'/g, "\\'")}')">
                    <div class="chat-avatar">${initial}</div>
                    <div class="chat-rail-info">
                        <div class="chat-rail-name">${esc(d.driver_name)} ${unread}</div>
                        <div class="chat-rail-preview">${preview}</div>
                    </div>
                </div>`;
        }).join('');
    }

    window.selectDriver = async function (driverId, driverName) {
        activeDriverId  = driverId;
        activeDriverName = driverName;
        activeTopicId   = null;
        document.getElementById('chatAvatar').textContent = esc(driverName).charAt(0).toUpperCase();
        document.getElementById('chatCanvasTitle').textContent = driverName;
        document.getElementById('chatConsole').classList.remove('rail-open');
        renderRail();
        cancelChatReply();
        closeChatSearchSilently();
        await loadChatTopics();
    };

    // ── Topics ───────────────────────────────────────────────────────────
    async function loadChatTopics() {
        if (!activeDriverId) return;
        try {
            const res  = await fetch('/SRMT/public/api/driver-chat.php?topics=1&driver_id=' + activeDriverId);
            const data = await res.json();
            if (!data.success) return;
            topics = data.topics;
            if (activeTopicId === null || !topics.find(t => t.id === activeTopicId)) {
                activeTopicId = (topics.find(t => t.is_general) || topics[0]).id;
            }
            renderTopicStrip();
            await loadChatThread(activeTopicId);
        } catch (e) { /* silent, next poll retries */ }
    }
    function renderTopicStrip() {
        const strip = document.getElementById('chatTopicStrip');
        const pills = topics.map(t => {
            const label = t.is_general ? <?= json_encode(t('chat.general_label')) ?> : (t.title || <?= json_encode(t('chat.new_topic_label')) ?>);
            const cls = ['chat-topic-pill'];
            if (t.is_general) cls.push('general');
            if (t.id === activeTopicId) cls.push('active');
            if (t.status === 'closed') cls.push('is-closed');
            const dot  = t.is_general ? '' : '<span class="dot"></span>';
            const star = t.pinned ? '★ ' : '';
            return `<div class="${cls.join(' ')}" onclick="selectChatTopic(${t.id})">${dot}${star}${esc(label)}</div>`;
        }).join('');
        strip.innerHTML = pills + `<div class="chat-topic-pill new-topic" onclick="openNewTopicPopover()">+ <?= t('chat.new_topic_btn') ?></div>`;
    }
    window.selectChatTopic = async function (id) {
        activeTopicId = id;
        renderTopicStrip();
        cancelChatReply();
        closeChatSearchSilently();
        await loadChatThread(id);
    };

    let lastThreadSignature = '';
    let lastRenderedConversationId = null;
    async function loadChatThread(conversationId, forceScroll) {
        try {
            const res  = await fetch('/SRMT/public/api/driver-chat.php?conversation_id=' + conversationId);
            const data = await res.json();
            if (!data.success || conversationId !== activeTopicId) return;
            currentMessages = data.messages;
            renderCanvasSubtitle(data.topic);
            // Polling every few seconds shouldn't repaint (and yank scroll to the
            // bottom) when nothing actually changed — only re-render on a real diff,
            // except when switching conversation (always show the new one from the bottom).
            const switchedConversation = conversationId !== lastRenderedConversationId;
            const shouldForceScroll = !!forceScroll || switchedConversation;
            const signature = JSON.stringify(currentMessages.map(m => [m.id, m.message, m.attachment_path, m.is_read_by_admin, m.is_read_by_driver]));
            if (shouldForceScroll || signature !== lastThreadSignature) {
                lastThreadSignature = signature;
                lastRenderedConversationId = conversationId;
                renderThread(currentMessages, data.topic, null, shouldForceScroll);
            }
        } catch (e) { /* silent, next poll retries */ }
    }
    function renderCanvasSubtitle(topic) {
        document.getElementById('chatCanvasSubtitle').textContent = topic.is_general
            ? <?= json_encode(t('chat.general_label')) ?>
            : (topic.title || <?= json_encode(t('chat.new_topic_label')) ?>);
        document.getElementById('chatCloseTopicBtn').style.display  = (!topic.is_general && topic.status === 'open') ? 'flex' : 'none';
        document.getElementById('chatDeleteTopicBtn').style.display = (!topic.is_general && topic.status === 'closed') ? 'flex' : 'none';
        document.getElementById('chatClosedBanner').style.display   = (!topic.is_general && topic.status === 'closed') ? 'flex' : 'none';
        document.getElementById('chatTempBanner').classList.toggle('show', !!topic.is_general);
        document.getElementById('chatConvertAllBtn').style.display  = (topic.is_general && currentMessages.length > 0) ? 'inline-block' : 'none';

        const rideBanner = document.getElementById('chatRideBanner');
        if (topic.linked_ride_label) {
            document.getElementById('chatRideBannerText').textContent = topic.linked_ride_label;
            rideBanner.style.display = 'flex';
        } else {
            rideBanner.style.display = 'none';
        }
    }
    function renderThread(list, topic, filterTerm, forceScroll) {
        const wrap = document.getElementById('chatThreadWrap');
        const wasNearBottom = forceScroll || (wrap.scrollHeight - wrap.scrollTop - wrap.clientHeight) < 80;
        const term = (filterTerm || '').trim().toLowerCase();
        const highlight = t => term ? esc(t).replace(new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig'), '<mark>$1</mark>') : esc(t);

        wrap.innerHTML = list.map(m => {
            if (m.sender === 'system') {
                if (term && !m.message.toLowerCase().includes(term)) return '';
                return `<div class="chat-system-note">${highlight(m.message)}</div>`;
            }
            if (term && !(m.message || '').toLowerCase().includes(term) &&
                !(m.attachment_path && 'foto photo'.includes(term))) return '';

            const isMe = m.sender === 'admin';
            const nameTag = (!isMe && m.sender_name) ? `<div class="chat-sender-name">${esc(m.sender_name)}</div>` : '';
            const quoted  = m.reply_to_id ? renderQuote(m.reply_to_id, list) : '';
            const photo   = m.attachment_path ? `<img class="chat-bubble-photo" src="/SRMT/public/${esc(m.attachment_path)}" onclick="window.open(this.src)">` : '';
            const canConvert = topic.is_general;

            return `<div class="chat-bubble-row ${isMe ? 'me' : 'them'}">
                <div class="chat-bubble-actions">
                    <button onclick="startChatReply(${m.id}, '${esc(m.message || (m.attachment_path ? 'Foto' : '')).replace(/'/g, "\\'")}')">↩ <?= t('chat.reply_btn') ?></button>
                    ${canConvert ? `<button onclick="convertFromMessage(${m.id})">✂ <?= t('chat.convert_from_here_btn') ?></button>` : ''}
                </div>
                ${nameTag}
                <div class="chat-bubble ${isMe ? 'chat-bubble-me' : 'chat-bubble-them'}">
                    ${quoted}${photo}${m.message ? highlight(m.message) : ''}
                </div>
                <div class="chat-bubble-time">${m.timestamp.substring(11, 16)}</div>
            </div>`;
        }).join('') || `<p style="text-align:center;color:#94a3b8;font-size:13px;padding:30px 0;">${term ? <?= json_encode(t('chat.search_empty')) ?> : <?= json_encode(t('chat.empty')) ?>}</p>`;
        // Only snap to the bottom if the admin was already reading the latest
        // messages — don't yank them down while they've scrolled up into history.
        if (wasNearBottom) wrap.scrollTop = wrap.scrollHeight;
    }
    function renderQuote(replyToId, list) {
        const original = list.find(m => m.id === replyToId);
        const text = original ? (original.message || (original.attachment_path ? '📷 Foto' : '')) : '';
        return text ? `<div class="chat-quote-block">${esc(text)}</div>` : '';
    }

    window.startChatReply = function (msgId, text) {
        replyTo = { id: msgId, text };
        document.getElementById('chatReplyPreview').style.display = 'flex';
        document.getElementById('chatReplyPreviewText').textContent = '↩ ' + text;
        document.getElementById('chatInput').focus();
    };
    window.cancelChatReply = function () {
        replyTo = null;
        document.getElementById('chatReplyPreview').style.display = 'none';
    };

    // ── Search (client-side filter over the currently open topic, like the prototype) ──
    window.toggleChatSearch = function () {
        const bar = document.getElementById('chatSearchBar');
        const opening = bar.style.display === 'none';
        bar.style.display = opening ? 'flex' : 'none';
        if (opening) document.getElementById('chatSearchInput').focus();
        else { document.getElementById('chatSearchInput').value = ''; runChatSearch(); }
    };
    function closeChatSearchSilently() {
        document.getElementById('chatSearchBar').style.display = 'none';
        document.getElementById('chatSearchInput').value = '';
    }
    window.runChatSearch = function () {
        const term  = document.getElementById('chatSearchInput').value;
        const topic = topics.find(t => t.id === activeTopicId);
        if (topic) renderThread(currentMessages, topic, term);
    };

    // ── Send / attach ────────────────────────────────────────────────────
    window.sendChatMessage = async function () {
        const input = document.getElementById('chatInput');
        const msg   = input.value.trim();
        if (!msg || !activeTopicId) return;
        input.value = '';
        await postSend({ conversation_id: activeTopicId, message: msg, reply_to_id: replyTo ? replyTo.id : undefined });
        cancelChatReply();
    };
    // Resize before sending — a raw phone photo (several MB) double-base64'd for
    // the WAF shield can silently blow past post_max_size, dropping the whole
    // request (surfaces as "Incomplete data").
    function resizeAndEncodeChatPhoto(img, maxDim, quality) {
        const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
        const dw = Math.round(img.width * scale), dh = Math.round(img.height * scale);
        const tmp = document.createElement('canvas');
        tmp.width = dw; tmp.height = dh;
        tmp.getContext('2d').drawImage(img, 0, 0, dw, dh);
        return tmp.toDataURL('image/jpeg', quality);
    }
    window.handleChatFileSelected = function (event) {
        const file = event.target.files[0];
        event.target.value = '';
        if (!file || !activeTopicId) return;
        const img = new Image();
        img.onload = () => postSend({ conversation_id: activeTopicId, message: '', image_data: resizeAndEncodeChatPhoto(img, 1000, 0.75) });
        img.src = URL.createObjectURL(file);
    };
    async function postSend(payload) {
        try {
            const res = await fetch('/SRMT/public/api/driver-chat.php', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'p=' + encodeURIComponent(btoa(unescape(encodeURIComponent(JSON.stringify(payload))))),
            });
            const data = await res.json();
            if (data.success) { await loadChatThread(activeTopicId, true); loadChatTopics(); loadChatInbox(); }
            else window.toastr?.error(data.error || 'Failed to send.');
        } catch (e) { window.toastr?.error('Network error.'); }
    }

    // ── Topic actions ────────────────────────────────────────────────────
    window.closeActiveTopic = function () {
        const topic = topics.find(t => t.id === activeTopicId);
        openPopover({
            title: <?= json_encode(t('chat.close_topic_popover_title')) ?>,
            hint: <?= json_encode(t('chat.close_topic_popover_hint')) ?>,
            fields: [{ key: 'title', label: <?= json_encode(t('chat.topic_title_label')) ?>, placeholder: <?= json_encode(t('chat.close_topic_placeholder')) ?>, value: (topic && topic.title) || '' }],
            confirmLabel: <?= json_encode(t('chat.close_topic_confirm')) ?>,
            onConfirm: async (vals) => {
                if (!vals.title) return;
                await postTopicAction('close_topic', { conversation_id: activeTopicId, title: vals.title });
                await loadChatThread(activeTopicId);
                window.toastr?.success(<?= json_encode(t('chat.toast_closed')) ?>);
            }
        });
    };

    window.openNewTopicPopover = async function () {
        openPopover({
            title: <?= json_encode(t('chat.new_topic_popover_title')) ?>,
            hint: <?= json_encode(t('chat.new_topic_popover_hint')) ?>,
            fields: [
                { key: 'title', label: <?= json_encode(t('chat.topic_title_label')) ?>, placeholder: <?= json_encode(t('chat.new_topic_placeholder')) ?> },
                { key: 'ride', label: <?= json_encode(t('chat.link_ride_label')) ?>, type: 'ride-picker', driverId: activeDriverId },
            ],
            confirmLabel: <?= json_encode(t('chat.new_topic_confirm')) ?>,
            onConfirm: async (vals) => {
                const data = await postTopicAction('create_topic', { driver_id: activeDriverId, title: vals.title, linked_ride_id: vals.ride });
                if (data && data.success) { await loadChatTopics(); await window.selectChatTopic(data.topic.id); }
            }
        });
    };

    window.deleteActiveTopic = function () {
        const topic = topics.find(t => t.id === activeTopicId);
        if (!topic) return;
        openPopover({
            title: <?= json_encode(t('chat.delete_topic_popover_title')) ?>,
            hint: <?= json_encode(t('chat.delete_topic_popover_hint')) ?>,
            fields: [],
            confirmLabel: <?= json_encode(t('chat.delete_topic_confirm')) ?>,
            confirmDanger: true,
            onConfirm: async () => {
                const data = await postTopicAction('delete_topic', { conversation_id: activeTopicId });
                if (data && data.success) {
                    activeTopicId = null;
                    await loadChatTopics();
                    window.toastr?.success(<?= json_encode(t('chat.toast_deleted')) ?>);
                }
            }
        });
    };

    window.convertFromMessage = function (messageId) {
        openPopover({
            title: <?= json_encode(t('chat.convert_popover_title')) ?>,
            hint: <?= json_encode(t('chat.convert_from_here_hint')) ?>,
            fields: [
                { key: 'title', label: <?= json_encode(t('chat.topic_title_label')) ?>, placeholder: <?= json_encode(t('chat.convert_placeholder')) ?> },
                { key: 'ride', label: <?= json_encode(t('chat.link_ride_label')) ?>, type: 'ride-picker', driverId: activeDriverId },
            ],
            confirmLabel: <?= json_encode(t('chat.convert_confirm')) ?>,
            onConfirm: async (vals) => {
                if (!vals.title) return;
                const data = await postTopicAction('convert_topic', { conversation_id: activeTopicId, from_message_id: messageId, title: vals.title, linked_ride_id: vals.ride });
                if (data && data.success) { await loadChatTopics(); await window.selectChatTopic(data.topic.id); window.toastr?.success(<?= json_encode(t('chat.toast_converted')) ?>); }
            }
        });
    };

    window.convertAllGeneral = function () {
        if (!currentMessages.length) return;
        openPopover({
            title: <?= json_encode(t('chat.convert_all_popover_title')) ?>,
            hint: <?= json_encode(t('chat.convert_all_hint')) ?>,
            fields: [
                { key: 'title', label: <?= json_encode(t('chat.topic_title_label')) ?>, placeholder: <?= json_encode(t('chat.convert_placeholder')) ?> },
                { key: 'ride', label: <?= json_encode(t('chat.link_ride_label')) ?>, type: 'ride-picker', driverId: activeDriverId },
            ],
            confirmLabel: <?= json_encode(t('chat.convert_all_confirm')) ?>,
            onConfirm: async (vals) => {
                if (!vals.title) return;
                const data = await postTopicAction('convert_topic', { conversation_id: activeTopicId, title: vals.title, linked_ride_id: vals.ride });
                if (data && data.success) {
                    await loadChatTopics(); await window.selectChatTopic(data.topic.id);
                    window.toastr?.success(<?= json_encode(t('chat.toast_converted_all')) ?>);
                }
            }
        });
    };

    async function postTopicAction(action, payload) {
        try {
            const res = await fetch('/SRMT/public/api/driver-chat.php?action=' + action, {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'p=' + encodeURIComponent(btoa(unescape(encodeURIComponent(JSON.stringify(payload))))),
            });
            const data = await res.json();
            if (!data.success) { window.toastr?.error(data.error || 'Failed.'); return data; }
            return data;
        } catch (e) { window.toastr?.error('Network error.'); return null; }
    }

    // Generic popover engine (title/hint/fields/confirm). Field type 'ride-picker'
    // renders a search box + scrollable card list instead of a plain <select>.
    function openPopover({ title, hint, fields, confirmLabel, confirmDanger, onConfirm }) {
        const body = document.getElementById('chatPopoverBody');
        body.innerHTML = `
            <h3>${esc(title)}</h3>
            <p class="hint">${esc(hint)}</p>
            ${fields.map(f => {
                if (f.type === 'ride-picker') return renderRidePickerField(f);
                return `
                    <label>${esc(f.label)}</label>
                    <input type="text" data-key="${f.key}" placeholder="${esc(f.placeholder || '')}" value="${esc(f.value || '')}">`;
            }).join('')}
            <div class="chat-popover-actions">
                <button class="chat-btn-ghost" onclick="closeChatPopover()"><?= t('chat.cancel_btn') ?></button>
                <button class="chat-btn-primary${confirmDanger ? ' danger' : ''}" id="chatPopoverConfirm">${esc(confirmLabel)}</button>
            </div>`;
        document.getElementById('chatPopoverBackdrop').classList.add('open');
        fields.filter(f => f.type === 'ride-picker').forEach(f => wireRidePickerField(f));
        document.getElementById('chatPopoverConfirm').onclick = () => {
            const vals = {};
            body.querySelectorAll('[data-key]').forEach(el => vals[el.dataset.key] = el.value);
            closeChatPopover();
            onConfirm(vals);
        };
    }
    function renderRidePickerField(f) {
        return `
            <label>${esc(f.label)}</label>
            <input type="hidden" data-key="${f.key}" value="">
            <div class="chat-ride-picker" id="ridePicker_${f.key}">
                <input type="text" class="chat-ride-picker-search" placeholder="<?= t('chat.link_ride_search') ?>">
                <div class="chat-ride-picker-selected" style="display:none;"></div>
                <div class="chat-ride-picker-list"></div>
            </div>`;
    }
    // Backend-searched, not a pre-fetched list — a driver can rack up thousands
    // of rides over time, so the picker only ever holds the current search's
    // small result set (15 most-recent by default, 20 matches once you type).
    function wireRidePickerField(f) {
        const root       = document.getElementById('ridePicker_' + f.key);
        const hidden     = document.querySelector(`[data-key="${f.key}"]`);
        const search     = root.querySelector('.chat-ride-picker-search');
        const list       = root.querySelector('.chat-ride-picker-list');
        const selectedEl = root.querySelector('.chat-ride-picker-selected');
        let currentRides = [];
        let debounceTimer = null;

        function renderList(rides) {
            currentRides = rides;
            list.innerHTML = rides.map(r => `<div class="chat-ride-picker-item" data-ride-id="${r.id}">${esc(r.label)}</div>`).join('')
                || `<p style="font-size:11.5px;color:#94a3b8;padding:8px 2px;"><?= t('chat.link_ride_none_found') ?></p>`;
            list.querySelectorAll('.chat-ride-picker-item').forEach(el => {
                el.onclick = () => {
                    const ride = currentRides.find(r => String(r.id) === el.dataset.rideId);
                    hidden.value = ride.id;
                    selectedEl.textContent = '✓ ' + ride.label;
                    selectedEl.style.display = 'block';
                };
            });
        }
        async function search_(term) {
            try {
                const res  = await fetch('/SRMT/public/api/driver-chat.php?recent_rides=1&driver_id=' + f.driverId + '&term=' + encodeURIComponent(term));
                const data = await res.json();
                if (data.success) renderList(data.rides);
            } catch (e) { /* keep showing the last successful list */ }
        }
        search.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => search_(search.value), 300);
        });
        search_(''); // initial: 15 most recent
    }
    window.closeChatPopover = function () { document.getElementById('chatPopoverBackdrop').classList.remove('open'); };

    // ── Unread badge ─────────────────────────────────────────────────────
    function setChatBadge(n) {
        const dot = document.getElementById('chatHeaderDot');
        if (!dot) return;
        if (n > 0) { dot.textContent = n > 9 ? '9+' : n; dot.style.display = 'flex'; }
        else { dot.style.display = 'none'; }
    }
    async function refreshChatBadge() {
        try {
            const res  = await fetch('/SRMT/public/api/driver-chat.php?count=1');
            const data = await res.json();
            if (data.success) setChatBadge(data.unread);
        } catch (e) { /* silent */ }
    }
    refreshChatBadge();
    clearInterval(badgeTimer);
    badgeTimer = setInterval(refreshChatBadge, 20000);
})();
</script>
