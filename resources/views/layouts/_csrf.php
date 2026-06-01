<?php /* Shared CSRF client guard — included by every role layout (after the csrf-token meta). */ ?>
<script>
(function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.content : '';
    if (!token) return;

    function ensureToken(form) {
        if (!form || (form.method || '').toLowerCase() !== 'post') return;
        if (!form.querySelector('input[name="csrf_token"]')) {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'csrf_token'; i.value = token;
            form.appendChild(i);
        }
    }

    // 1) Forms submitted by the user (submit event fires) — capture phase, before navigation.
    document.addEventListener('submit', function (e) { ensureToken(e.target); }, true);

    // 2) Forms submitted programmatically via form.submit() — this does NOT fire the submit event,
    //    so patch the prototype to inject the token first.
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        ensureToken(this);
        return nativeSubmit.apply(this, arguments);
    };

    // 3) All fetch() POST calls get the token as a header.
    const nativeFetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        const method = (init.method || (typeof input === 'object' && input && input.method) || 'GET').toUpperCase();
        if (method === 'POST') {
            init.headers = Object.assign({ 'X-CSRF-Token': token }, init.headers || {});
        }
        return nativeFetch.call(this, input, init);
    };
})();
</script>
