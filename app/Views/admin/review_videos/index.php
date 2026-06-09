<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $categories */
/** @var array<string,string> $platforms */
use App\Models\ReviewVideo;
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h1 class="text-xl font-bold text-primary-800">วิดีโอแนะนำ</h1>
    <p class="text-sm text-slate-600 mt-0.5">YouTube / Shorts / TikTok / Reels — แสดง carousel แนวตั้งบนหน้ารีวิวและหน้าแรก</p>
  </div>
  <a href="<?= url('/admin/review-videos/create') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold text-sm shrink-0">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มวิดีโอ
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold text-slate-700">ลำดับ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">คลิป</th>
          <th class="px-4 py-3 font-semibold text-slate-700">Platform</th>
          <th class="px-4 py-3 font-semibold text-slate-700">หมวด</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ที่พัก</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ใช้งาน</th>
          <th class="px-4 py-3 font-semibold text-slate-700 w-44"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">ยังไม่มีรายการ — เพิ่มจากปุ่มด้านบน</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r):
            $thumb = ReviewVideo::thumbnailUrlFor($r);
            $plat = ReviewVideo::platformLabel($r);
          ?>
            <tr class="hover:bg-slate-50/80">
              <td class="px-4 py-3 align-top font-mono text-xs"><?= (int)$r['sort_order'] ?></td>
              <td class="px-4 py-3 align-top">
                <div class="flex gap-3">
                  <?php if ($thumb): ?>
                    <img src="<?= e($thumb) ?>" alt="" width="72" height="96" class="rounded-lg bg-slate-100 shrink-0 w-[54px] h-[72px] object-cover">
                  <?php else: ?>
                    <div class="rounded-lg bg-slate-200 shrink-0 w-[54px] h-[72px] grid place-items-center text-[10px] text-slate-500 font-semibold"><?= e($plat) ?></div>
                  <?php endif; ?>
                  <div class="min-w-0">
                    <div class="font-semibold text-slate-900"><?= e($r['title']) ?></div>
                    <div class="text-xs text-slate-500 mt-1 truncate max-w-xs"><?= e(ReviewVideo::sourceUrlOf($r)) ?></div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 align-top"><span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100"><?= e($plat) ?></span></td>
              <td class="px-4 py-3 align-top text-slate-700"><?= e($categories[$r['category']] ?? $r['category']) ?></td>
              <td class="px-4 py-3 align-top text-slate-600"><?= !empty($r['property_name']) ? e($r['property_name']) : '—' ?></td>
              <td class="px-4 py-3 align-top"><?= !empty($r['is_active']) ? '<span class="text-emerald-600 font-medium">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?></td>
              <td class="px-4 py-3 align-top">
                <div class="flex flex-col gap-1.5">
                  <a href="<?= url('/admin/review-videos/' . $r['id'] . '/edit') ?>" class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-medium">แก้ไข</a>
                  <form method="post" action="<?= url('/admin/review-videos/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('ลบวิดีโอนี้?')"><?= csrf() ?>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-medium">ลบ</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
