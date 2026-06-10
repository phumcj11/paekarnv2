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

    function typeLabel(t) {
        return TYPE_LABELS[t] || t || '';
    }

    function buildCard(p) {
        var priceStr = fmtPrice(p.min_price);
        var typeLbl  = typeLabel(p.type);
        var meta     = typeLbl + (p.zone ? ' · ' + p.zone : '');
        var coverHtml = p.cover
            ? '<img src="' + escAttr(p.cover) + '" alt="" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" decoding="async">'
            : '<div class="absolute inset-0 grid place-items-center text-2xl bg-gradient-to-br from-teal-50 to-sky-100">🏕️</div>';
        var ratingHtml = p.rating_avg > 0
            ? '<span class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-amber-700"><span aria-hidden="true">⭐</span>' + Number(p.rating_avg).toFixed(1) + '</span>'
            : '';
        var couponHtml = p.coupon_enabled
            ? '<span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-md px-1.5 py-0.5">🎫 คูปอง</span>'
            : '';

        return '<a href="' + escAttr(p.url) + '" class="group flex gap-3 p-2.5 rounded-xl border border-slate-200/90 bg-white shadow-[0_6px_22px_-10px_rgba(15,23,42,0.14)] hover:border-sky-300 hover:shadow-[0_12px_32px_-12px_rgba(14,116,144,0.22)] transition no-underline text-inherit">'
            + '<div class="relative w-[5.5rem] h-[5.5rem] shrink-0 rounded-lg overflow-hidden bg-slate-100 ring-1 ring-slate-200/80">'
            + coverHtml
            + '</div>'
            + '<div class="flex-1 min-w-0 flex flex-col gap-0.5 py-0.5">'
            + '<div class="text-[13px] font-extrabold text-slate-900 leading-tight line-clamp-1">' + escHtml(p.name) + '</div>'
            + '<div class="text-[11px] text-slate-500 line-clamp-1">' + escHtml(meta) + '</div>'
            + '<div class="text-[11px] text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-2 py-1 leading-snug line-clamp-2 mt-0.5">'
            + '<span class="font-semibold text-sky-600">AI:</span> ' + escHtml(p.reason)
            + '</div>'
            + '<div class="flex items-center gap-2 flex-wrap mt-auto pt-1">'
            + (priceStr ? '<span class="text-[13px] font-extrabold text-slate-900">' + priceStr + '<span class="text-[10px] font-medium text-slate-500">/คืน</span></span>' : '')
            + ratingHtml + couponHtml
            + '</div>'
            + '</div>'
            + '</a>';
    }

    function buildTopPicksPanel(json, root) {
        var panel = document.createElement('div');
        panel.className = 'paekan-ai-result-panel mt-3 rounded-2xl border border-slate-200/90 bg-white shadow-[0_16px_48px_-20px_rgba(15,23,42,0.18)] overflow-hidden';
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-label', 'ผลการค้นหา AI');

        var header = '<div class="flex items-center justify-between gap-3 px-3.5 py-2.5 bg-gradient-to-r from-sky-600 via-teal-600 to-emerald-600">'
            + '<div class="flex items-center gap-2 min-w-0 flex-1">'
            + '<span class="grid place-items-center w-7 h-7 rounded-lg bg-white/20 text-white shrink-0" aria-hidden="true">✨</span>'
            + '<span class="text-[12px] sm:text-[13px] font-bold text-white leading-snug line-clamp-2">' + escHtml(json.summary || '') + '</span>'
            + '</div>'
            + '<button type="button" class="pai-close shrink-0 w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 text-white text-xs font-bold grid place-items-center transition" aria-label="ปิด">✕</button>'
            + '</div>';

        var cards = '';
        if (json.top_picks && json.top_picks.length) {
            cards = '<div class="p-3 space-y-2.5">';
            json.top_picks.forEach(function (p) {
                cards += buildCard(p);
            });
            cards += '</div>';
        } else {
            cards = '<div class="px-4 py-8 text-center text-sm text-slate-500">ไม่พบที่พักที่ตรงกับเงื่อนไข ลองปรับคำค้นหา</div>';
        }

        var footer = json.redirect && json.top_picks && json.top_picks.length
            ? '<div class="px-3 pb-3 pt-0">'
            + '<a href="' + escAttr(json.redirect) + '" class="flex items-center justify-center gap-1.5 w-full py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-teal-500 hover:from-sky-600 hover:to-teal-600 text-white text-[13px] font-extrabold shadow-md transition no-underline">'
            + 'ดูผลทั้งหมด <span aria-hidden="true">→</span>'
            + '</a></div>'
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

            var old = root.parentElement && root.parentElement.querySelector('.paekan-ai-result-panel');
            if (old) old.remove();

            try {
                var fd = new FormData();
                fd.append('query', q);
                var res  = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
                var json = await res.json();

                if (json.ok) {
                    if (json.top_picks && json.top_picks.length > 0) {
                        var panel = buildTopPicksPanel(json, root);
                        root.insertAdjacentElement('afterend', panel);
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        if (window.lucide && typeof window.lucide.createIcons === 'function') {
                            window.lucide.createIcons();
                        }
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
