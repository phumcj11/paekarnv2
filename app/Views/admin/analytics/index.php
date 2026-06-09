<?php
/** @var array $counts @var array $top_paths_today @var array $top_paths_7 @var array $top_paths_30 @var array $top_properties_today @var array $top_properties_30 @var array $leaderboard_30 */
$table_ok = $table_ok ?? true;
$clicks_table_ok = $clicks_table_ok ?? false;
$click_counts_today = $click_counts_today ?? ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'total' => 0];
$top_click_phone_today = $top_click_phone_today ?? [];
$top_click_line_today = $top_click_line_today ?? [];
$top_click_coupon_today = $top_click_coupon_today ?? [];
$top_paths_today = $top_paths_today ?? [];
$top_properties_today = $top_properties_today ?? [];
$embed_url = $embed_url ?? '';
$ga_report_url = $ga_report_url ?? '';
$gsc_url = $gsc_url ?? '';
$ga4_id = $ga4_id ?? '';
$external_snapshots_ok = $external_snapshots_ok ?? true;
$external_snapshots = $external_snapshots ?? [];
?>

<div class="space-y-6">
  <?php if (!$table_ok): ?>
    <div class="rounded-2xl border border-amber-300 bg-amber-50 text-amber-900 px-5 py-4 text-sm">
      <strong>ยังไม่มีตาราง analytics_page_views</strong> — รันไฟล์ SQL:
      <code class="bg-white/80 px-2 py-0.5 rounded text-xs font-mono">database/patches/20260524_admin_analytics_mysql57.sql</code>
      บนฐานข้อมูล Production แล้วโหลดหน้านี้อีกครั้ง
    </div>
  <?php endif; ?>
  <?php if (!$clicks_table_ok): ?>
    <div class="rounded-2xl border border-amber-300 bg-amber-50 text-amber-900 px-5 py-4 text-sm">
      <strong>ยังไม่มีตาราง property_lead_clicks</strong> — รันไฟล์เดียวกัน:
      <code class="bg-white/80 px-2 py-0.5 rounded text-xs font-mono">database/patches/20260524_admin_analytics_mysql57.sql</code>
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-slate-800">Analytics — Google &amp; ภายในระบบ</h2>
      <p class="text-sm text-slate-500 mt-1">ยอดเข้าชมจากแพกาญ (หลังมีตาราง) · ลัดไป GA4 / Search Console · รายงานฝังได้</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($ga_report_url !== ''): ?>
        <a href="<?= e($ga_report_url) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
          <i data-lucide="external-link" class="w-4 h-4"></i> GA4
        </a>
      <?php endif; ?>
      <?php if ($gsc_url !== ''): ?>
        <a href="<?= e($gsc_url) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
          <i data-lucide="search" class="w-4 h-4"></i> Search Console
        </a>
      <?php endif; ?>
      <?php if ($ga4_id !== ''): ?>
        <span class="inline-flex items-center px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-mono border border-slate-200"><?= e($ga4_id) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($embed_url !== ''): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-100 font-semibold text-slate-700 flex items-center gap-2">
        <i data-lucide="layout-dashboard" class="w-5 h-5 text-accent-600"></i> รายงานฝัง (Looker / GA)
      </div>
      <div class="aspect-[16/10] min-h-[420px]">
        <iframe src="<?= e($embed_url) ?>" class="w-full h-full border-0" title="Embedded analytics" loading="lazy"></iframe>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($table_ok): ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft p-5 ring-1 ring-emerald-100">
        <div class="text-xs text-emerald-700 uppercase tracking-wide font-semibold">Page views วันนี้</div>
        <div class="mt-1 text-3xl font-extrabold text-emerald-800"><?= number_format((int) ($counts['views_today'] ?? 0)) ?></div>
        <div class="text-sm text-slate-600"><?= e(date('d/m/Y')) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wide">Page views 7 วัน</div>
        <div class="mt-1 text-3xl font-extrabold text-primary-700"><?= number_format((int) ($counts['views_7d'] ?? 0)) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wide">Page views 30 วัน</div>
        <div class="mt-1 text-3xl font-extrabold text-accent-700"><?= number_format((int) ($counts['views_30d'] ?? 0)) ?></div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-emerald-100 font-bold text-emerald-900">หน้าที่ถูกเปิดบ่อย — วันนี้</div>
        <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-emerald-50 text-emerald-800 text-xs uppercase sticky top-0"><tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">ครั้ง</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_today as $row): ?>
              <tr><td class="px-4 py-2 font-mono text-xs break-all"><?= e((string) $row['path']) ?></td><td class="px-4 py-2 text-right font-semibold"><?= number_format((int) $row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_today)): ?><tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูลวันนี้</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-emerald-100 font-bold text-emerald-900">ที่พักที่ถูกเปิดบ่อย — วันนี้</div>
        <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-emerald-50 text-emerald-800 text-xs uppercase sticky top-0"><tr><th class="text-left px-4 py-2">ที่พัก</th><th class="text-right px-4 py-2">เข้าชม</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_properties_today as $row): ?>
              <tr>
                <td class="px-4 py-2">
                  <div class="font-medium"><?= e((string) $row['name']) ?></div>
                  <div class="text-[11px] text-slate-400 font-mono"><?= e((string) $row['slug']) ?></div>
                </td>
                <td class="px-4 py-2 text-right font-semibold text-emerald-700"><?= number_format((int) $row['cnt']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($top_properties_today)): ?><tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูลวันนี้</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($clicks_table_ok): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <div class="font-bold text-slate-800">คลิก CTA วันนี้</div>
        <p class="text-xs text-slate-500 mt-1">โทร · LINE · ซื้อคูปอง · จองออนไลน์ — นับเมื่อลูกค้ากดปุ่มบนหน้ารายละเอียดที่พัก</p>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 p-5 border-b border-slate-100">
        <div><div class="text-xs text-slate-500">โทร</div><div class="text-2xl font-extrabold text-primary-700"><?= number_format((int)($click_counts_today['phone'] ?? 0)) ?></div></div>
        <div><div class="text-xs text-slate-500">LINE</div><div class="text-2xl font-extrabold text-[#06C755]"><?= number_format((int)($click_counts_today['line'] ?? 0)) ?></div></div>
        <div><div class="text-xs text-slate-500">ซื้อคูปอง</div><div class="text-2xl font-extrabold text-rose-700"><?= number_format((int)($click_counts_today['coupon'] ?? 0)) ?></div></div>
        <div><div class="text-xs text-slate-500">จอง</div><div class="text-2xl font-extrabold text-accent-700"><?= number_format((int)($click_counts_today['book'] ?? 0)) ?></div></div>
        <div><div class="text-xs text-slate-500">รวม</div><div class="text-2xl font-extrabold text-slate-800"><?= number_format((int)($click_counts_today['total'] ?? 0)) ?></div></div>
      </div>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-0 xl:divide-x divide-slate-100">
        <div class="p-5">
          <div class="text-sm font-bold text-slate-700 mb-3">แพที่ถูกคลิกโทรมากสุด — วันนี้</div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_click_phone_today as $row): ?>
              <tr><td class="py-2 pr-2"><?= e((string)$row['name']) ?></td><td class="py-2 text-right font-semibold"><?= number_format((int)$row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_click_phone_today)): ?><tr><td colspan="2" class="py-4 text-center text-slate-500">ยังไม่มี</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="p-5">
          <div class="text-sm font-bold text-slate-700 mb-3">แพที่ถูกคลิก LINE มากสุด — วันนี้</div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_click_line_today as $row): ?>
              <tr><td class="py-2 pr-2"><?= e((string)$row['name']) ?></td><td class="py-2 text-right font-semibold"><?= number_format((int)$row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_click_line_today)): ?><tr><td colspan="2" class="py-4 text-center text-slate-500">ยังไม่มี</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="p-5">
          <div class="text-sm font-bold text-slate-700 mb-3">แพที่ถูกคลิกซื้อคูปองมากสุด — วันนี้</div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_click_coupon_today as $row): ?>
              <tr><td class="py-2 pr-2"><?= e((string)$row['name']) ?></td><td class="py-2 text-right font-semibold"><?= number_format((int)$row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_click_coupon_today)): ?><tr><td colspan="2" class="py-4 text-center text-slate-500">ยังไม่มี</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">หน้าที่ถูกเปิดบ่อย — 7 วัน</div>
        <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0"><tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">ครั้ง</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_7 as $row): ?>
              <tr><td class="px-4 py-2 font-mono text-xs break-all"><?= e((string) $row['path']) ?></td><td class="px-4 py-2 text-right font-semibold"><?= number_format((int) $row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_7)): ?><tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">หน้าที่ถูกเปิดบ่อย — 30 วัน</div>
        <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0"><tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">ครั้ง</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_30 as $row): ?>
              <tr><td class="px-4 py-2 font-mono text-xs break-all"><?= e((string) $row['path']) ?></td><td class="px-4 py-2 text-right font-semibold"><?= number_format((int) $row['cnt']) ?></td></tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_30)): ?><tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">ที่พักที่ถูกเปิดบ่อย — 30 วัน</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase"><tr><th class="text-left px-4 py-2">ที่พัก</th><th class="text-left px-4 py-2">Slug</th><th class="text-right px-4 py-2">เข้าชม</th></tr></thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($top_properties_30 as $row): ?>
            <tr>
              <td class="px-4 py-2 font-medium"><?= e((string) $row['name']) ?></td>
              <td class="px-4 py-2 font-mono text-xs"><?= e((string) $row['slug']) ?></td>
              <td class="px-4 py-2 text-right font-semibold text-accent-700"><?= number_format((int) $row['cnt']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($top_properties_30)): ?><tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <div class="font-bold text-slate-800">ที่พักเด่น (30 วัน) — เรียงจากเข้าชมในบ้าน</div>
        <p class="text-xs text-slate-500 mt-1">เปิดหน้ารายละเอียดที่พัก + จำนวนจองในช่วงเดียวกัน + คะแนน</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr><th class="text-left px-4 py-2">ที่พัก</th><th class="text-right px-4 py-2">เข้าชม 30d</th><th class="text-right px-4 py-2">จอง 30d</th><th class="text-right px-4 py-2">⭐</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($leaderboard_30 as $row): ?>
            <tr>
              <td class="px-4 py-2">
                <a href="<?= url('/admin/properties/' . (int) $row['id'] . '/edit') ?>" class="font-medium text-primary-700 hover:underline"><?= e((string) $row['name']) ?></a>
                <div class="text-[11px] text-slate-400 font-mono"><?= e((string) $row['slug']) ?></div>
              </td>
              <td class="px-4 py-2 text-right font-semibold"><?= number_format((int) $row['views_30d']) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format((int) $row['bookings_30d']) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format((float) $row['rating_avg'], 1) ?> <span class="text-slate-400">(<?= (int) $row['rating_count'] ?>)</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($leaderboard_30)): ?><tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">ไม่มีที่พักที่เผยแพร่</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <div class="font-bold text-slate-800">Snapshot จาก Google API (cache)</div>
      <p class="text-xs text-slate-500 mt-1">เก็บผลดึงจาก GA4 Data API / Search Console API ผ่าน cron — ลดโควตาและความช้าของหน้าแอดมิน</p>
    </div>
    <?php if (!$external_snapshots_ok): ?>
      <div class="px-5 py-4 text-sm text-amber-900 bg-amber-50 border-t border-amber-100">
        <strong>ยังไม่มีตาราง analytics_external_snapshots</strong> — รัน:
        <code class="bg-white/80 px-2 py-0.5 rounded text-xs font-mono">database/migrations/20260507_analytics_external_snapshots.sql</code>
      </div>
    <?php elseif (empty($external_snapshots)): ?>
      <div class="px-5 py-10 text-center text-sm text-slate-500">
        ยังไม่มีแถวใน cache — เมื่อมีสคริปต์ cron ให้เรียก <code class="text-xs font-mono bg-slate-100 px-1 rounded">AnalyticsExternalSnapshotService::save()</code>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
              <th class="text-left px-4 py-2">เวลาดึง</th>
              <th class="text-left px-4 py-2">แหล่ง</th>
              <th class="text-left px-4 py-2">คีย์</th>
              <th class="text-left px-4 py-2">Payload (ตัด)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($external_snapshots as $erow): ?>
              <?php
              $pj = (string) ($erow['payload_json'] ?? '');
              $preview = mb_strlen($pj) > 240 ? mb_substr($pj, 0, 240) . '…' : $pj;
              ?>
              <tr class="align-top">
                <td class="px-4 py-2 whitespace-nowrap text-xs text-slate-600"><?= e((string) ($erow['fetched_at'] ?? '')) ?></td>
                <td class="px-4 py-2 font-mono text-xs"><?= e((string) ($erow['source'] ?? '')) ?></td>
                <td class="px-4 py-2 font-mono text-xs break-all"><?= e((string) ($erow['snapshot_key'] ?? '')) ?></td>
                <td class="px-4 py-2"><pre class="text-[11px] font-mono text-slate-700 whitespace-pre-wrap break-all m-0"><?= e($preview) ?></pre></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="text-xs text-slate-500 px-1">
    ตั้งค่า GA4 / ลิงก์ / iframe ได้ที่ <a href="<?= url('/admin/settings') ?>" class="text-primary-700 font-semibold hover:underline">การตั้งค่า</a>
  </div>
</div>
