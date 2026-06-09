<?php /** @var list<array<string,mixed>> $properties */
?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="bed-double" class="w-5 h-5 text-accent-600"></i> จัดการห้อง / ยูนิต</h2>
    <p class="text-sm text-slate-500 mt-1">เลือกที่พักเพื่อเพิ่ม แก้ไข หรือลบห้องและแพแต่ละลำ</p>
  </div>
  <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($properties as $p): ?>
    <a href="<?= url('/owner/properties/' . (int)$p['id'] . '/units') ?>" class="rounded-2xl border border-slate-200 hover:border-accent-300 hover:shadow-soft transition overflow-hidden flex gap-3 p-3 text-left bg-white group">
      <img src="<?= e(upload_url($p['cover_image'] ?? '') ?: 'https://placehold.co/120x90') ?>" alt="" class="w-24 h-20 rounded-xl object-cover shrink-0">
      <div class="min-w-0 flex-1 flex flex-col">
        <div class="font-bold truncate group-hover:text-accent-700"><?= e((string)($p['name'] ?? '')) ?></div>
        <div class="text-xs text-slate-500 truncate inline-flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3 shrink-0 opacity-70"></i><?= e((string)($p['zone'] ?? '')) ?></div>
        <div class="mt-auto pt-2 text-xs font-semibold text-accent-600 inline-flex items-center gap-1">
          <i data-lucide="bed-double" class="w-3.5 h-3.5"></i>
          <?= (int)($p['unit_count'] ?? 0) ?> ห้อง / ยูนิต
          <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
