<?php /** @var array<int,array<string,mixed>> $rows @var bool $table_ok */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="scroll-text" class="w-5 h-5 text-accent-600"></i> Audit log</h2>
    <p class="text-xs text-slate-500 mt-1">เหตุการณ์ล่าสุดจากระบบ — กรองตาม action หรือชนิดเอนทิตี</p>
    <?php if (!$table_ok): ?>
    <p class="mt-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">ยังไม่มีตาราง audit_logs — รัน <code class="text-xs">database/migration_audit_logs.sql</code></p>
    <?php endif; ?>
  </div>
  <?php if ($table_ok): ?>
  <form method="get" action="<?= url('/admin/audit-logs') ?>" class="px-5 py-3 flex flex-wrap gap-2 border-b border-slate-100 bg-slate-50/80">
    <input type="text" name="action" value="<?= e($_GET['action'] ?? '') ?>" placeholder="action เช่น property_" class="px-3 py-2 rounded-lg border border-slate-300 text-sm min-w-[160px]">
    <input type="text" name="entity" value="<?= e($_GET['entity'] ?? '') ?>" placeholder="entity_type เช่น property" class="px-3 py-2 rounded-lg border border-slate-300 text-sm min-w-[160px]">
    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold">กรอง</button>
    <a href="<?= url('/admin/audit-logs') ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-white">ล้าง</a>
  </form>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">เวลา</th>
          <th class="text-left px-5 py-3">Action</th>
          <th class="text-left px-5 py-3">Entity</th>
          <th class="text-left px-5 py-3">Actor</th>
          <th class="text-left px-5 py-3">Payload</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50 align-top">
          <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap"><?= e((string)($r['created_at'] ?? '')) ?></td>
          <td class="px-5 py-3 font-mono text-xs"><?= e((string)($r['action'] ?? '')) ?></td>
          <td class="px-5 py-3 text-xs"><?= e((string)($r['entity_type'] ?? '')) ?> <?= isset($r['entity_id']) && $r['entity_id'] !== null ? '#' . (int)$r['entity_id'] : '' ?></td>
          <td class="px-5 py-3 text-xs"><?= e((string)($r['actor_email'] ?? '')) ?></td>
          <td class="px-5 py-3 text-xs text-slate-600 max-w-md break-all"><?= e((string)($r['payload'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($rows === []): ?>
        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">ไม่มีข้อมูล</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
