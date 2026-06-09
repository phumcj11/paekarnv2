/**
 * AI Smart Search hero (listing + home page) — ไม่ใช้ Alpine เพื่อกัน CSP
 * ผูกกับ markup ที่มี data-paekan-ai-hero
 * Response format: {ok, filters, redirect, summary, top_picks:[{id,name,type,zone,cover,url,min_price,coupon_enabled,rating_avg,reason}]}
 */
(function () {
    var TYPE_LABELS = {
        raft: 'แพพัก', resort: 'รีสอร์ท', homestay: 'โฮมสเตย์',
        house: 'บ้านพัก', pool_villa: 'พูลวิลล่า', hotel: 'โรงแรม', camping: 'แคมป์ปิ้ง',
    };

    function fmtPrice(n) {
        return n > 0 ? '฿' + Number(n).toLocaleString('th-TH') : '';
    }

    function buildTopPicksPanel(json, root) {
        var panel = document.createElement('div');
        panel.className = 'paekan-ai-result-panel';
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-label', 'ผลการค้นหา AI');

        var header = '<div class="pai-header">'
            + '<span class="pai-summary">' + escHtml(json.summary || '') + '</span>'
            + '<button type="button" class="pai-close" aria-label="ปิด">✕</button>'
            + '</div>';

        var cards = '';
        if (json.top_picks && json.top_picks.length) {
            cards = '<div class="pai-cards">';
            json.top_picks.forEach(function (p) {
                var priceStr  = fmtPrice(p.min_price);
                var couponBadge = p.coupon_enabled ? '<span class="pai-badge-coupon">🎫 คูปอง</span>' : '';
                var typeLabel = TYPE_LABELS[p.type] || p.type || '';
                var ratingHtml = p.rating_avg > 0
                    ? '<span class="pai-rating">⭐ ' + Number(p.rating_avg).toFixed(1) + '</span>' : '';
                var coverHtml = p.cover
                    ? '<img src="' + escAttr(p.cover) + '" alt="" class="pai-card-cover" loading="lazy" decoding="async">'
                    : '<div class="pai-card-cover pai-card-cover--placeholder">🏕️</div>';

                cards += '<a href="' + escAttr(p.url) + '" class="pai-card" target="_self">'
                    + coverHtml
                    + '<div class="pai-card-body">'
                    + '<div class="pai-card-name">' + escHtml(p.name) + '</div>'
                    + '<div class="pai-card-meta">' + escHtml(typeLabel) + (p.zone ? ' · ' + escHtml(p.zone) : '') + '</div>'
                    + '<div class="pai-card-reason">"' + escHtml(p.reason) + '"</div>'
                    + '<div class="pai-card-foot">'
                    + (priceStr ? '<span class="pai-price">' + priceStr + '<small>/คืน</small></span>' : '')
                    + ratingHtml + couponBadge
                    + '</div>'
                    + '</div>'
                    + '</a>';
            });
            cards += '</div>';
        } else {
            cards = '<div class="pai-empty">ไม่พบที่พักที่ตรงกับเงื่อนไข ลองปรับคำค้นหา</div>';
        }

        var footer = json.redirect && json.top_picks && json.top_picks.length
            ? '<div class="pai-footer"><a href="' + escAttr(json.redirect) + '" class="pai-btn-all">ดูผลทั้งหมด <span class="pai-arrow">→</span></a></div>'
            : '';

        panel.innerHTML = header + cards + footer;

        panel.querySelector('.pai-close').addEventListener('click', function () {
            panel.remove();
        });

        return panel;
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(s) {
        return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function bindOnce(root) {
        if (!root || root.dataset.paekanAiHeroBound === '1') return;
        var endpoint = root.getAttribute('data-endpoint');
        var form     = root.querySelector('[data-role="paekan-ai-hero-form"]');
        var input    = root.querySelector('[data-role="paekan-ai-query"]');
        var btn      = root.querySelector('[data-role="paekan-ai-submit"]');
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

            // Remove previous result panel
            var old = root.parentElement && root.parentElement.querySelector('.paekan-ai-result-panel');
            if (old) old.remove();

            try {
                var fd = new FormData();
                fd.append('query', q);
                var res  = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
                var json = await res.json();

                if (json.ok) {
                    if (json.top_picks && json.top_picks.length > 0) {
                        // Show inline result panel after the AI search section
                        var panel = buildTopPicksPanel(json, root);
                        root.insertAdjacentElement('afterend', panel);
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else if (json.redirect) {
                        window.location.href = json.redirect;
                        return;
                    }
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
