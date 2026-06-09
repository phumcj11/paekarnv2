<?php /** @var array $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 flex items-center justify-between border-b border-slate-100">
    <div>
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="book-open" class="w-5 h-5 text-purple-600"></i> AI Knowledge Base</h3>
      <p class="text-sm text-slate-500 mt-1">FAQ ที่ AI/Chatbot จะใช้ตอบลูกค้า</p>
    </div>
    <a href="<?= url('/admin/ai/kb/form') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่ม FAQ</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">หมวด</th>
          <th class="text-left px-4 py-3">คำถาม</th>
          <th class="text-left px-4 py-3">Hit</th>
          <th class="text-left px-4 py-3">สถานะ</th>
          <th class="text-right px-4 py-3">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded"><?= e($r['category'] ?? '-') ?></span></td>
          <td class="px-4 py-3">
            <div class="font-medium"><?= e($r['question']) ?></div>
            <div class="text-xs text-slate-500 line-clamp-1 max-w-xl"><?= e(mb_substr($r['answer'],0,100)) ?>…</div>
          </td>
          <td class="px-4 py-3 text-xs"><?= number_format($r['hit_count']) ?></td>
          <td class="px-4 py-3">
            <span class="text-xs bg-<?= $r['is_active']?'emerald':'slate' ?>-100 text-<?= $r['is_active']?'emerald':'slate' ?>-700 px-2 py-0.5 rounded-full"><?= $r['is_active']?'Active':'Off' ?></span>
          </td>
          <td class="px-4 py-3 text-right whitespace-nowrap">
            <a href="<?= url('/admin/ai/kb/form?id='.$r['id']) ?>" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded text-xs">แก้ไข</a>
            <form method="post" action="<?= url('/admin/ai/kb/'.$r['id'].'/delete') ?>" class="inline" onsubmit="return confirm('ลบจริง?')">
              <?= csrf() ?>
              <button class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-xs">ลบ</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center py-10 text-slate-500">ยังไม่มีข้อมูล KB</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
