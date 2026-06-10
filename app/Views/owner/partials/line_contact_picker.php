<?php
/**
 * LINE contact picker — ใช้ภายใน Alpine component ที่มี:
 * lineContacts, lineUserId, lineSearch, lineContactsLoading, showLineManual,
 * pickLineContact(c), filteredLineContacts(), clearLineContact()
 * (optional) guestName, guestPhone สำหรับ auto-fill
 */
?>
<div class="rounded-xl border border-[#06C755]/30 bg-[#06C755]/5 p-3 space-y-2.5">
  <div class="flex items-center justify-between gap-2">
    <p class="text-xs font-semibold text-[#067a2f] flex items-center gap-1.5">
      <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
      เลือกลูกค้าจากแชท LINE
    </p>
    <span class="text-[10px] text-[#067a2f]/70" x-show="lineContacts.length" x-text="lineContacts.length + ' คน'"></span>
  </div>

  <!-- แสดงคนที่เลือกแล้ว -->
  <template x-if="lineUserId && selectedLineContact()">
    <div class="flex items-center gap-2 p-2 rounded-lg bg-white border border-[#06C755]/40">
      <img x-show="selectedLineContact().picture_url" :src="selectedLineContact().picture_url"
           class="w-9 h-9 rounded-full object-cover shrink-0" alt="">
      <div x-show="!selectedLineContact().picture_url"
           class="w-9 h-9 rounded-full bg-[#06C755]/15 text-[#067a2f] grid place-items-center shrink-0 text-xs font-bold">LINE</div>
      <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-slate-800 truncate" x-text="selectedLineContact().display_name || 'ลูกค้า LINE'"></div>
        <div class="text-[10px] text-slate-400" x-show="selectedLineContact().phone" x-text="selectedLineContact().phone"></div>
      </div>
      <button type="button" @click="clearLineContact()" class="text-slate-400 hover:text-rose-500 p-1" title="ล้าง">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
  </template>

  <template x-if="!lineUserId">
    <div class="space-y-2">
      <input type="search" x-model="lineSearch" placeholder="ค้นหาชื่อหรือเบอร์..."
             class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#06C755] outline-none">

      <div x-show="lineContactsLoading" class="text-center text-xs text-slate-400 py-3">กำลังโหลดรายชื่อ...</div>

      <div x-show="!lineContactsLoading && lineContacts.length > 0"
           class="max-h-40 overflow-y-auto space-y-1 rounded-lg border border-slate-100 bg-white/60 p-1">
        <template x-for="c in filteredLineContacts()" :key="c.line_user_id">
          <button type="button" @click="pickLineContact(c)"
                  class="w-full flex items-center gap-2.5 p-2 rounded-lg text-left hover:bg-[#06C755]/10 active:bg-[#06C755]/15 transition">
            <img x-show="c.picture_url" :src="c.picture_url" class="w-9 h-9 rounded-full object-cover shrink-0" alt="">
            <div x-show="!c.picture_url"
                 class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 grid place-items-center shrink-0 text-[10px] font-bold">LINE</div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-slate-800 truncate" x-text="c.display_name || 'ลูกค้า LINE'"></div>
              <div class="text-[10px] text-slate-400 truncate"
                   x-text="c.phone ? c.phone : formatLineLastSeen(c.last_seen_at)"></div>
            </div>
            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
          </button>
        </template>
        <p x-show="filteredLineContacts().length === 0" class="text-xs text-slate-400 text-center py-3">ไม่พบชื่อที่ค้นหา</p>
      </div>

      <p x-show="!lineContactsLoading && lineContacts.length === 0"
         class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 leading-relaxed">
        ยังไม่มีรายชื่อ — ให้ลูกค้า <strong>Add เพื่อน OA</strong> หรือทักแชทสอบถามก่อน ระบบจะเก็บชื่ออัตโนมัติ
      </p>
    </div>
  </template>

  <button type="button" @click="showLineManual = !showLineManual"
          class="text-[10px] text-slate-500 underline underline-offset-2">
    <span x-text="showLineManual ? 'ซ่อนการกรอก ID เอง' : 'กรอก LINE User ID เอง'"></span>
  </button>
  <input x-show="showLineManual" type="text" x-model="lineUserId" maxlength="64" placeholder="Uxxxxxxxxxxxxxxxxxx"
         class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-mono focus:border-[#06C755] outline-none">

  <label class="flex items-center gap-2 cursor-pointer" :class="lineUserId.trim() ? '' : 'opacity-50 pointer-events-none'">
    <input type="checkbox" x-model="sendLine" class="rounded accent-[#06C755]">
    <span class="text-xs text-[#067a2f] font-medium">ส่งใบยืนยันการจองทาง LINE ทันที</span>
  </label>
</div>
