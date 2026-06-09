<?php /** @var array $logs @var array $jobs @var array $lastByJob */
$jobLabels = [
    'expire_coupons'         => ['ทำคูปองหมดอายุ', 'ticket', 'amber'],
    'mark_no_show'           => ['ปิดการจองที่หมดเวลา', 'user-x', 'slate'],
    'send_checkin_reminders' => ['ส่งเตือนเช็คอิน', 'bell', 'accent'],
    'owner_weekly_report'    => ['รายงานสัปดาห์ Owner', 'bar-chart-3', 'blue'],
    'cleanup_drafts'         => ['ล้าง Draft เก่า', 'trash-2', 'rose'],
    'activity_featured_expire' => ['หมดอายุ Featured กิจกรรม', 'star', 'violet'],
];
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">

    <!-- Job cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <?php foreach ($jobs as $j):
      $info = $jobLabels[$j] ?? [$j, 'play', 'slate'];
      $last = $lastByJob[$j] ?? null; ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg bg-<?= $info[2] ?>-100 text-<?= $info[2] ?>-700 grid place-items-center"><i data-lucide="<?= $info[1] ?>" class="w-4 h-4"></i></div>
            <div>
              <div class="font-semibold text-sm"><?= e($info[0]) ?></div>
              <div class="text-[10px] font-mono text-slate-500"><?= $j ?></div>
            </div>
          </div>
          <form method="post" action="<?= url('/admin/automation/run') ?>" class="inline">
            <?= csrf() ?>
            <input type="hidden" name="job" value="<?= $j ?>">
            <button class="px-2.5 py-1 bg-primary-600 text-white rounded-lg text-xs hover:bg-primary-700 inline-flex items-center gap-1"><i data-lucide="play" class="w-3 h-3"></i> Run</button>
          </form>
        </div>
        <?php if ($last): ?>
          <div class="mt-2 text-xs text-slate-500">
            ล่าสุด: <?= time_ago($last['created_at']) ?> · <?= $last['affected'] ?> รายการ · <?= $last['duration_ms'] ?>ms
            <?php if ($last['status'] !== 'success'): ?>
              <span class="text-rose-600 font-semibold">FAIL</span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="mt-2 text-xs text-slate-400">ยังไม่เคยรัน</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>

    <!-- Recent logs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-accent-600"></i> ประวัติการทำงาน (100 รายการล่าสุด)</h3>
        <form method="post" action="<?= url('/admin/automation/run') ?>" class="inline">
          <?= csrf() ?>
          <button class="px-3 py-1.5 bg-accent-500 text-white rounded-lg text-xs font-semibold inline-flex items-center gap-1.5"><i data-lucide="play-circle" class="w-3.5 h-3.5"></i> Run All Jobs</button>
        </form>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-600">
            <tr>
              <th class="text-left px-4 py-3">Job</th>
              <th class="text-left px-4 py-3">Status</th>
              <th class="text-left px-4 py-3">Affected</th>
              <th class="text-left px-4 py-3">Duration</th>
              <th class="text-left px-4 py-3">Output</th>
              <th class="text-left px-4 py-3">เมื่อ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
          <?php foreach ($logs as $l): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-2 font-mono text-xs"><?= e($l['job']) ?></td>
              <td class="px-4 py-2"><span class="text-xs font-semibold bg-<?= $l['status']==='success'?'emerald':'rose' ?>-100 text-<?= $l['status']==='success'?'emerald':'rose' ?>-700 px-2 py-0.5 rounded-full"><?= e($l['status']) ?></span></td>
              <td class="px-4 py-2"><?= $l['affected'] ?></td>
              <td class="px-4 py-2 text-xs"><?= $l['duration_ms'] ?>ms</td>
              <td class="px-4 py-2 text-xs text-slate-600 max-w-md truncate"><?= e($l['output']) ?></td>
              <td class="px-4 py-2 text-xs text-slate-500"><?= time_ago($l['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="text-center py-8 text-slate-500">ยังไม่มีประวัติ</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 text-sm">
      <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="terminal" class="w-5 h-5 text-accent-600"></i> วิธีตั้ง Cron</h3>
      <div class="space-y-2">
        <div>
          <div class="text-xs text-slate-500 mb-1">Linux/Hosting (cPanel)</div>
          <code class="block bg-slate-900 text-emerald-300 p-2 rounded text-[11px] font-mono">* * * * * php <?= e(realpath(__DIR__ . '/../../../../') ?: '/path/to/paekan_v1') ?>/cli/cron.php</code>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Web Trigger</div>
          <code class="block bg-slate-900 text-emerald-300 p-2 rounded text-[11px] font-mono break-all"><?= e(rtrim((string)\App\Core\Application::$publicUrl,'/')) ?>/cron.php?key=<?= e((string)\App\Models\Setting::get('cron_secret','SECRET')) ?></code>
        </div>
        <div class="text-xs text-slate-500">เปลี่ยน secret ใน <a href="<?= url('/admin/settings') ?>" class="text-accent-700">Settings</a></div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 text-sm">
      <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="shield-check" class="w-5 h-5 text-accent-600"></i> สถานะระบบ</h3>
      <div class="flex items-center justify-between"><span>Automation</span>
        <span class="text-xs font-semibold bg-<?= \App\Models\Setting::get('automation_enabled','1')?'emerald':'slate' ?>-100 text-<?= \App\Models\Setting::get('automation_enabled','1')?'emerald':'slate' ?>-700 px-2 py-0.5 rounded-full">
          <?= \App\Models\Setting::get('automation_enabled','1')?'ON':'OFF' ?>
        </span>
      </div>
      <div class="flex items-center justify-between mt-2"><span>เตือน X วันก่อนเช็คอิน</span><strong><?= e(\App\Models\Setting::get('reminder_days_before_checkin','2')) ?> วัน</strong></div>
    </div>
  </aside>
</div>
