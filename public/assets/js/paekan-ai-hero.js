/**
 * AI Smart Search hero (listing page) — ไม่ใช้ Alpine ในส่วนนี้ เพื่อกัน CSP ที่บล็อก inline script
 * ประกบกับ markup ใน app/Views/properties/index.php (data-paekan-ai-hero)
 */
(function () {
    function bindOnce(root) {
        if (!root || root.dataset.paekanAiHeroBound === '1') return;
        var endpoint = root.getAttribute('data-endpoint');
        var form = root.querySelector('[data-role="paekan-ai-hero-form"]');
        var input = root.querySelector('[data-role="paekan-ai-query"]');
        var btn = root.querySelector('[data-role="paekan-ai-submit"]');
        if (!endpoint || !form || !input || !btn) return;

        root.dataset.paekanAiHeroBound = '1';

        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            var q = (input.value || '').trim();
            if (!q) return;

            var idle = btn.querySelector('[data-role="idle-label"]');
            var busy = btn.querySelector('[data-role="busy-label"]');
            btn.disabled = true;
            if (idle) idle.hidden = true;
            if (busy) busy.hidden = false;
            try {
                var fd = new FormData();
                fd.append('query', q);
                var res = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
                var json = await res.json();
                if (json.ok && json.redirect) {
                    window.location.href = json.redirect;
                    return;
                }
            } catch (_) { /* noop */ }

            btn.disabled = false;
            if (idle) idle.hidden = false;
            if (busy) busy.hidden = true;
        });
    }

    function scan() {
        document.querySelectorAll('[data-paekan-ai-hero]').forEach(bindOnce);
    }

    scan();
    document.addEventListener('DOMContentLoaded', scan);
})();
