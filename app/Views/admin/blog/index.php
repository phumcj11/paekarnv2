<?php /** @var array $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex items-center justify-between">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="newspaper" class="w-5 h-5 text-accent-600"></i> บล็อก</h2>
    <a href="<?= url('/admin/blog/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5"><i data-lucide="plus" class="w-4 h-4"></i> เขียนใหม่</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">บทความ</th>
          <th class="text-left px-5 py-3">หมวด</th>
          <th class="text-left px-5 py-3">เข้าชม</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php $colors=['draft'=>'slate','published'=>'emerald','archived'=>'rose'];
      foreach ($rows as $b): $c=$colors[$b['status']]??'slate'; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3"><div class="flex items-center gap-3">
            <img src="<?= e(upload_url($b['cover_image'])) ?>" class="w-10 h-10 rounded-lg object-cover">
            <div><div class="font-semibold"><?= e($b['title']) ?></div>
              <div class="text-xs text-slate-500 font-mono">/<?= e($b['slug']) ?></div></div>
          </div></td>
          <td class="px-5 py-3 text-xs"><?= e($b['category'] ?? '-') ?></td>
          <td class="px-5 py-3"><?= number_format($b['view_count']) ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($b['status']) ?></span></td>
          <td class="px-5 py-3 text-right">
            <a href="<?= url('/admin/blog/' . $b['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="edit" class="w-3.5 h-3.5"></i> แก้ไข</a>
            <form method="post" action="<?= url('/admin/blog/' . $b['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ยืนยันลบ?')"><?= csrf() ?>
              <button class="px-3 py-1.5 text-xs bg-rose-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
