<div class="max-w-2xl bg-amber-50 border border-amber-200 rounded-2xl p-6">
  <h2 class="font-bold text-lg text-amber-900 flex items-center gap-2"><i data-lucide="database" class="w-5 h-5"></i> ยังไม่มีตาราง <span class="font-mono">zones</span></h2>
  <p class="text-sm text-amber-950/90 mt-2 leading-relaxed">
    รันไฟล์ migration บนฐานข้อมูล MySQL ของโปรเจกต์ (เช่น phpMyAdmin → Import หรือคัดลอกคำสั่งไปรัน):
  </p>
  <pre class="mt-3 text-xs bg-white border border-amber-200 rounded-lg p-3 overflow-x-auto font-mono text-slate-800">database/migrations/20260507_zones.sql</pre>
  <p class="text-xs text-amber-900/80 mt-3">หลังรันแล้วรีเฟรชหน้านี้ — ระบบจะแสดงรายการโซนและให้จัดการได้</p>
  <a href="<?= url('/admin/zones') ?>" class="inline-flex mt-4 px-4 py-2 bg-amber-700 text-white rounded-lg text-sm font-semibold">ลองรีเฟรช</a>
</div>
