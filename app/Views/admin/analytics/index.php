<?php
use App\Services\AnalyticsEventContext;
/** @var bool $v2_ready @var string|null $v2_started_at @var array $human_counts @var array $legacy_view_counts */
/** @var array $click_unique_today @var array $click_unique_7 @var array $click_unique_30 @var array $click_audit_today @var array $click_legacy_today */
/** @var array $bookings_today @var array $bookings_7 @var array $bookings_30 @var array $recent_audit */
$table_ok = $table_ok ?? true;
$clicks_table_ok = $clicks_table_ok ?? false;
$v2_ready = $v2_ready ?? false;
$human_counts = $human_counts ?? ['views_today' => 0, 'views_7d' => 0, 'views_30d' => 0, 'unique_today' => 0, 'unique_7d' => 0, 'unique_30d' => 0];
$legacy_view_counts = $legacy_view_counts ?? ['views_today' => 0, 'views_7d' => 0, 'views_30d' => 0];
$click_unique_today = $click_unique_today ?? ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'map' => 0, 'total' => 0];
$click_unique_7 = $click_unique_7 ?? $click_unique_today;
$click_unique_30 = $click_unique_30 ?? $click_unique_today;
$click_audit_today = $click_audit_today ?? ['raw' => 0, 'duplicate' => 0, 'bot' => 0, 'internal' => 0];
$click_legacy_today = $click_legacy_today ?? ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'total' => 0];
$top_paths_today = $top_paths_today ?? [];
$top_properties_today = $top_properties_today ?? [];
$recent_audit = $recent_audit ?? [];
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
      <strong>ยังไม่มีตาราง analytics_page_views</strong> — รัน migration:
      <code class="bg-white/80 px-2 py-0.5 rounded text-xs font-mono">scripts/migrate_analytics.sh</code>
      และ <code class="bg-white/80 px-2 py-0.5 rounded text-xs font-mono">scripts/migrate_analytics_v2.sh</code>
    </div>
  <?php endif; ?>
  <?php if (!$clicks_table_ok): ?>
    <div class="rounded-2xl border border-amber-300 bg-amber-50 text-amber-900 px-5 py-4 text-sm">
      <strong>ยังไม่มีตาราง property_lead_clicks</strong> — รัน migration เดียวกันด้านบน
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="flex flex-wrap items-center gap-2 mb-1">
        <h2 class="text-xl font-bold text-slate-800">Analytics — Google &amp; ภายในระบบ</h2>
        <?php if ($v2_ready): ?>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
            Analytics V2<?= $v2_started_at ? ' — ตั้งแต่ ' . e(date('d/m/Y H:i', strtotime($v2_started_at))) : '' ?>
          </span>
        <?php else: ?>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold border border-amber-200">
            รอ migrate V2
          </span>
        <?php endif; ?>
      </div>
      <p class="text-sm text-slate-500 mt-1">
        KPI หลักนับเฉพาะคนจริง · ไม่นับทีมงาน/บอท · CTA ไม่ซ้ำภายใน 30 นาที ·
        <strong>คลิกปุ่มโทร ≠ สายโทรสำเร็จ</strong>
      </p>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft p-5 ring-1 ring-emerald-100">
        <div class="text-xs text-emerald-700 uppercase tracking-wide font-semibold">Human page views วันนี้</div>
        <div class="mt-1 text-3xl font-extrabold text-emerald-800"><?= number_format((int)($human_counts['views_today'] ?? 0)) ?></div>
        <div class="text-sm text-slate-600">Unique visitors <?= number_format((int)($human_counts['unique_today'] ?? 0)) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wide">Human page views 7 วัน</div>
        <div class="mt-1 text-3xl font-extrabold text-primary-700"><?= number_format((int)($human_counts['views_7d'] ?? 0)) ?></div>
        <div class="text-sm text-slate-500">Unique <?= number_format((int)($human_counts['unique_7d'] ?? 0)) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wide">Human page views 30 วัน</div>
        <div class="mt-1 text-3xl font-extrabold text-accent-700"><?= number_format((int)($human_counts['views_30d'] ?? 0)) ?></div>
        <div class="text-sm text-slate-500">Unique <?= number_format((int)($human_counts['unique_30d'] ?? 0)) ?></div>
      </div>
    </div>

    <?php if ($clicks_table_ok): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <div class="font-bold text-slate-800">CTA ที่ไม่ซ้ำ (Analytics V2)</div>
        <p class="text-xs text-slate-500 mt-1">คลิกปุ่มโทร · LINE · คูปอง · จอง · แผนที่ — นับเฉพาะคนจริง ไม่ซ้ำภายใน 30 นาที</p>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 p-5 border-b border-slate-100">
        <?php foreach (['phone' => 'คลิกปุ่มโทร', 'line' => 'LINE', 'coupon' => 'คูปอง', 'book' => 'จอง', 'map' => 'แผนที่'] as $k => $label): ?>
        <div>
          <div class="text-xs text-slate-500"><?= e($label) ?></div>
          <div class="text-2xl font-extrabold text-slate-800"><?= number_format((int)($click_unique_today[$k] ?? 0)) ?></div>
          <div class="text-[10px] text-slate-400">7d: <?= number_format((int)($click_unique_7[$k] ?? 0)) ?> · 30d: <?= number_format((int)($click_unique_30[$k] ?? 0)) ?></div>
        </div>
        <?php endforeach; ?>
        <div>
          <div class="text-xs text-slate-500">รวมวันนี้</div>
          <div class="text-2xl font-extrabold text-primary-700"><?= number_format((int)($click_unique_today['total'] ?? 0)) ?></div>
        </div>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 bg-slate-50/80 text-sm">
        <div><span class="text-slate-500">Raw hits วันนี้</span><div class="font-bold"><?= number_format((int)($click_audit_today['raw'] ?? 0)) ?></div></div>
        <div><span class="text-slate-500">ซ้ำ 30 นาที</span><div class="font-bold text-amber-700"><?= number_format((int)($click_audit_today['duplicate'] ?? 0)) ?></div></div>
        <div><span class="text-slate-500">Bot</span><div class="font-bold text-rose-600"><?= number_format((int)($click_audit_today['bot'] ?? 0)) ?></div></div>
        <div><span class="text-slate-500">ทีมงาน</span><div class="font-bold text-violet-600"><?= number_format((int)($click_audit_today['internal'] ?? 0)) ?></div></div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-blue-200 shadow-soft p-5">
        <div class="text-xs text-blue-700 uppercase font-semibold">การจองวันนี้</div>
        <div class="mt-1 text-2xl font-extrabold"><?= number_format((int)($bookings_today['total'] ?? 0)) ?></div>
        <div class="text-sm text-slate-500">ยืนยัน <?= number_format((int)($bookings_today['confirmed'] ?? 0)) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase">การจอง 7 วัน</div>
        <div class="mt-1 text-2xl font-extrabold"><?= number_format((int)($bookings_7['total'] ?? 0)) ?></div>
        <div class="text-sm text-slate-500">ยืนยัน <?= number_format((int)($bookings_7['confirmed'] ?? 0)) ?></div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <div class="text-xs text-slate-500 uppercase">การจอง 30 วัน</div>
        <div class="mt-1 text-2xl font-extrabold"><?= number_format((int)($bookings_30['total'] ?? 0)) ?></div>
        <div class="text-sm text-slate-500">ยืนยัน <?= number_format((int)($bookings_30['confirmed'] ?? 0)) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-emerald-100 font-bold text-emerald-900">หน้าที่ถูกเปิดบ่อย — วันนี้ (V2)</div>
        <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-emerald-50 text-emerald-800 text-xs uppercase sticky top-0">
              <tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">Views</th><th class="text-right px-4 py-2">Unique</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_today as $row): ?>
              <tr>
                <td class="px-4 py-2 font-mono text-xs break-all"><?= e((string)($row['path'] ?? '')) ?></td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)($row['views'] ?? $row['cnt'] ?? 0)) ?></td>
                <td class="px-4 py-2 text-right text-emerald-700"><?= number_format((int)($row['unique_visitors'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_today)): ?><tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูลวันนี้</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-emerald-100 font-bold text-emerald-900">ที่พักที่ถูกเปิดบ่อย — วันนี้ (V2)</div>
        <div class="overflow-x-auto max-h-[320px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-emerald-50 text-emerald-800 text-xs uppercase sticky top-0">
              <tr><th class="text-left px-4 py-2">ที่พัก</th><th class="text-right px-4 py-2">Views</th><th class="text-right px-4 py-2">Unique</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_properties_today as $row): ?>
              <tr>
                <td class="px-4 py-2">
                  <div class="font-medium"><?= e((string)$row['name']) ?></div>
                  <div class="text-[11px] text-slate-400 font-mono"><?= e((string)$row['slug']) ?></div>
                </td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)($row['views'] ?? $row['cnt'] ?? 0)) ?></td>
                <td class="px-4 py-2 text-right text-emerald-700"><?= number_format((int)($row['unique_visitors'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($top_properties_today)): ?><tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูลวันนี้</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($clicks_table_ok && !empty($recent_audit)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <div class="font-bold text-slate-800">เหตุการณ์ CTA ล่าสุด</div>
        <p class="text-xs text-slate-500 mt-1">ตรวจสอบ raw / duplicate / bot / internal</p>
      </div>
      <div class="overflow-x-auto max-h-[360px] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0">
            <tr>
              <th class="text-left px-4 py-2">เวลา</th>
              <th class="text-left px-4 py-2">ที่พัก</th>
              <th class="text-left px-4 py-2">CTA</th>
              <th class="text-left px-4 py-2">Device</th>
              <th class="text-left px-4 py-2">Referrer</th>
              <th class="text-left px-4 py-2">Visitor</th>
              <th class="text-left px-4 py-2">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($recent_audit as $ev): ?>
            <?php
              $counted = (int)($ev['is_counted'] ?? 1);
              $reason = (string)($ev['dedupe_reason'] ?? '');
              if ($counted) {
                  $status = 'นับแล้ว';
                  $statusClass = 'text-emerald-700';
              } elseif ($reason === 'duplicate_30m') {
                  $status = 'ซ้ำ 30 นาที';
                  $statusClass = 'text-amber-700';
              } elseif ((int)($ev['is_bot'] ?? 0)) {
                  $status = 'bot';
                  $statusClass = 'text-rose-600';
              } elseif ((int)($ev['is_internal'] ?? 0)) {
                  $status = 'ทีมงาน';
                  $statusClass = 'text-violet-600';
              } else {
                  $status = 'ไม่นับ';
                  $statusClass = 'text-slate-500';
              }
            ?>
            <tr class="text-xs">
              <td class="px-4 py-2 whitespace-nowrap"><?= e(substr((string)($ev['created_at'] ?? ''), 0, 16)) ?></td>
              <td class="px-4 py-2"><?= e((string)($ev['property_name'] ?? '')) ?></td>
              <td class="px-4 py-2 font-mono"><?= e((string)($ev['click_type'] ?? '')) ?></td>
              <td class="px-4 py-2"><?= e((string)($ev['device_type'] ?? '—')) ?></td>
              <td class="px-4 py-2 truncate max-w-[120px]"><?= e((string)($ev['referrer_host'] ?? '(direct)')) ?></td>
              <td class="px-4 py-2 font-mono"><?= e(AnalyticsEventContext::shortHash($ev['visitor_hash'] ?? null)) ?></td>
              <td class="px-4 py-2 font-semibold <?= $statusClass ?>"><?= e($status) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($clicks_table_ok): ?>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-0 xl:divide-x divide-slate-100 bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="p-5 border-b xl:border-b-0 border-slate-100">
        <div class="text-sm font-bold text-slate-700 mb-3">แพที่คลิกปุ่มโทร (ไม่ซ้ำ) — วันนี้</div>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($top_click_phone_today as $row): ?>
            <tr><td class="py-2 pr-2"><?= e((string)$row['name']) ?></td><td class="py-2 text-right font-semibold"><?= number_format((int)$row['cnt']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($top_click_phone_today)): ?><tr><td colspan="2" class="py-4 text-center text-slate-500">ยังไม่มี</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="p-5 border-b xl:border-b-0 border-slate-100">
        <div class="text-sm font-bold text-slate-700 mb-3">แพที่คลิก LINE (ไม่ซ้ำ) — วันนี้</div>
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
        <div class="text-sm font-bold text-slate-700 mb-3">แพที่คลิกคูปอง (ไม่ซ้ำ) — วันนี้</div>
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
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">หน้าที่ถูกเปิดบ่อย — 7 วัน</div>
        <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0">
              <tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">Views</th><th class="text-right px-4 py-2">Unique</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_7 as $row): ?>
              <tr>
                <td class="px-4 py-2 font-mono text-xs break-all"><?= e((string)($row['path'] ?? '')) ?></td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)($row['views'] ?? $row['cnt'] ?? 0)) ?></td>
                <td class="px-4 py-2 text-right"><?= number_format((int)($row['unique_visitors'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_7)): ?><tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">หน้าที่ถูกเปิดบ่อย — 30 วัน</div>
        <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0">
              <tr><th class="text-left px-4 py-2">Path</th><th class="text-right px-4 py-2">Views</th><th class="text-right px-4 py-2">Unique</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($top_paths_30 as $row): ?>
              <tr>
                <td class="px-4 py-2 font-mono text-xs break-all"><?= e((string)($row['path'] ?? '')) ?></td>
                <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)($row['views'] ?? $row['cnt'] ?? 0)) ?></td>
                <td class="px-4 py-2 text-right"><?= number_format((int)($row['unique_visitors'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($top_paths_30)): ?><tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">ที่พักที่ถูกเปิดบ่อย — 30 วัน (V2)</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr><th class="text-left px-4 py-2">ที่พัก</th><th class="text-left px-4 py-2">Slug</th><th class="text-right px-4 py-2">Views</th><th class="text-right px-4 py-2">Unique</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($top_properties_30 as $row): ?>
            <tr>
              <td class="px-4 py-2 font-medium"><?= e((string)$row['name']) ?></td>
              <td class="px-4 py-2 font-mono text-xs"><?= e((string)$row['slug']) ?></td>
              <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)($row['views'] ?? $row['cnt'] ?? 0)) ?></td>
              <td class="px-4 py-2 text-right text-emerald-700"><?= number_format((int)($row['unique_visitors'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($top_properties_30)): ?><tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <div class="font-bold text-slate-800">ที่พักเด่น (30 วัน) — V2 human views</div>
        <p class="text-xs text-slate-500 mt-1">Human page views + unique visitors + จองจริง</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
              <th class="text-left px-4 py-2">ที่พัก</th>
              <th class="text-right px-4 py-2">Views 30d</th>
              <th class="text-right px-4 py-2">Unique</th>
              <th class="text-right px-4 py-2">จอง 30d</th>
              <th class="text-right px-4 py-2">⭐</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($leaderboard_30 as $row): ?>
            <tr>
              <td class="px-4 py-2">
                <a href="<?= url('/admin/properties/' . (int)$row['id'] . '/edit') ?>" class="font-medium text-primary-700 hover:underline"><?= e((string)$row['name']) ?></a>
                <div class="text-[11px] text-slate-400 font-mono"><?= e((string)$row['slug']) ?></div>
              </td>
              <td class="px-4 py-2 text-right font-semibold"><?= number_format((int)$row['views_30d']) ?></td>
              <td class="px-4 py-2 text-right text-emerald-700"><?= number_format((int)($row['unique_visitors_30d'] ?? 0)) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format((int)$row['bookings_30d']) ?></td>
              <td class="px-4 py-2 text-right"><?= number_format((float)$row['rating_avg'], 1) ?> <span class="text-slate-400">(<?= (int)$row['rating_count'] ?>)</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($leaderboard_30)): ?><tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">ไม่มีที่พักที่เผยแพร่</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-bold uppercase tracking-wide text-slate-500 bg-slate-200 px-2 py-0.5 rounded">Legacy — ก่อน Analytics V2</span>
      </div>
      <p class="text-xs text-slate-500 mb-4">ข้อมูลเดิมนับทุก HTTP hit ไม่กรอง bot/ทีมงาน/ซ้ำ — ไม่ควรเทียบตรงกับ V2</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div><div class="text-slate-500">Page views legacy วันนี้</div><div class="font-bold"><?= number_format((int)($legacy_view_counts['views_today'] ?? 0)) ?></div></div>
        <div><div class="text-slate-500">CTA legacy วันนี้</div><div class="font-bold"><?= number_format((int)($click_legacy_today['total'] ?? 0)) ?></div></div>
        <div><div class="text-slate-500">โทร legacy</div><div class="font-bold"><?= number_format((int)($click_legacy_today['phone'] ?? 0)) ?></div></div>
        <div><div class="text-slate-500">LINE legacy</div><div class="font-bold"><?= number_format((int)($click_legacy_today['line'] ?? 0)) ?></div></div>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <div class="font-bold text-slate-800">Snapshot จาก Google API (cache)</div>
      <p class="text-xs text-slate-500 mt-1">เก็บผลดึงจาก GA4 Data API / Search Console API ผ่าน cron</p>
    </div>
    <?php if (!$external_snapshots_ok): ?>
      <div class="px-5 py-4 text-sm text-amber-900 bg-amber-50 border-t border-amber-100">
        <strong>ยังไม่มีตาราง analytics_external_snapshots</strong>
      </div>
    <?php elseif (empty($external_snapshots)): ?>
      <div class="px-5 py-10 text-center text-sm text-slate-500">ยังไม่มีแถวใน cache</div>
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
              $pj = (string)($erow['payload_json'] ?? '');
              $preview = mb_strlen($pj) > 240 ? mb_substr($pj, 0, 240) . '…' : $pj;
              ?>
              <tr class="align-top">
                <td class="px-4 py-2 whitespace-nowrap text-xs text-slate-600"><?= e((string)($erow['fetched_at'] ?? '')) ?></td>
                <td class="px-4 py-2 font-mono text-xs"><?= e((string)($erow['source'] ?? '')) ?></td>
                <td class="px-4 py-2 font-mono text-xs break-all"><?= e((string)($erow['snapshot_key'] ?? '')) ?></td>
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
