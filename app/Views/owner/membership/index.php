<?php
/** @var array|null $owner @var array $plans @var array<int,array<string,mixed>> $orders */
use App\Services\OwnerMembership;
use App\Services\OwnerTier;
use App\Services\MembershipService;

$tier          = $owner ? ($owner['membership_tier'] ?? 'none') : 'none';
$benefitsOk    = $owner ? OwnerMembership::hasActiveBenefits($owner) : false;
$salesOpen     = MembershipService::salesOpen();
$expRaw        = $owner ? ($owner['membership_expires_at'] ?? null) : null;
$graceUntilRaw = $owner ? ($owner['membership_grace_until'] ?? null) : null;
?>
<section class="max-w-4xl mx-auto space-y-6">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 sm:p-6">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="award" class="w-5 h-5 text-amber-500"></i> สถานะสมาชิก</h2>
    <div class="mt-3 flex flex-wrap gap-2 items-center text-sm">
      <?php if ($tier === 'vip' && $benefitsOk): ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-800 font-semibold text-xs"><i data-lucide="crown" class="w-3.5 h-3.5"></i> VIP ใช้งานได้</span>
      <?php elseif ($tier === 'standard' && $benefitsOk): ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-sky-100 text-sky-800 font-semibold text-xs"><i data-lucide="badge-check" class="w-3.5 h-3.5"></i> สมาชิกธรรมดา ใช้งานได้</span>
      <?php elseif ($tier !== 'none' && !$benefitsOk): ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold text-xs"><i data-lucide="clock-alert" class="w-3.5 h-3.5"></i>สิทธิ์หมดอายุ / รอต่ออายุ</span>
      <?php else: ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold text-xs"><i data-lucide="user-round" class="w-3.5 h-3.5"></i>ยังไม่เป็นสมาชิก</span>
      <?php endif; ?>
    </div>
    <?php if ($expRaw): ?>
      <p class="mt-2 text-sm text-slate-600">หมดอายุสิทธิ์แพ็กเกจ: <strong><?= e(format_date_th($expRaw)) ?></strong></p>
    <?php elseif ($tier !== 'none' && $benefitsOk): ?>
      <p class="mt-2 text-sm text-emerald-700 font-medium">แพ็กเกจตลอดชีพ — ไม่มีวันหมดอายุ</p>
    <?php endif; ?>
    <?php if ($graceUntilRaw && strtotime((string)$graceUntilRaw) > time()): ?>
      <p class="mt-1 text-xs text-amber-700">ช่วงพักสิทธิ์ (grace) ถึง <?= e(format_date_th($graceUntilRaw)) ?></p>
    <?php endif; ?>
    <p class="mt-3 text-xs text-slate-500">สมาชิก <strong>VIP</strong> ที่เป็นพาร์ทเนอร์ที่ใช้งานได้ และมีที่พักเผยแพร่ จะได้รับการแจ้งเตือนเมื่อมีลูกค้ากรอกฟอร์ม &quot;ขอให้ช่วยหาที่พัก&quot; ที่ตรงโซน / ประเภท / งบของคุณ</p>
  </div>

  <?php \App\Core\View::partial('partials/membership_tier_comparison'); ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="p-5 border-b border-slate-100">
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="package" class="w-5 h-5 text-accent-600"></i> เลือกแพ็กเกจ</h2>
      <?php if ($salesOpen): ?>
      <p class="text-sm text-slate-600 mt-1">ชำระตามข้อมูลบัญชีด้านล่าง — อัปโหลดสลิปเพื่อเปิดสิทธิ์ทันที หรือส่งคำสั่งซื้อรอแอดมินตรวจ</p>
      <?php else: ?>
      <p class="text-sm text-slate-600 mt-1">กำลังจัดเตรียมแพ็กเกจและราคา — เปิดให้บริการเร็วๆนี้</p>
      <?php endif; ?>
    </div>
    <?php if (!$salesOpen): ?>
    <div class="p-8 sm:p-12 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-100 to-sky-50 grid place-items-center mx-auto mb-4">
        <i data-lucide="clock" class="w-8 h-8 text-sky-600"></i>
      </div>
      <h3 class="font-bold text-lg text-slate-800">เปิดให้บริการเร็วๆนี้</h3>
      <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">ทีมงานกำลังสรุปแพ็กเกจและราคาให้เหมาะกับเจ้าของที่พัก — ดูสิทธิ์แต่ละระดับได้จากตารางด้านบน</p>
      <a href="<?= url('/contact') ?>" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition">
        <i data-lucide="message-circle" class="w-4 h-4"></i> ติดต่อสอบถาม
      </a>
    </div>
    <?php else: ?>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($plans as $pl):
        $isVip = ($pl['tier'] ?? '') === 'vip';
        $life  = (int)($pl['is_lifetime'] ?? 0) === 1;
        $days  = $pl['duration_days'] ?? null;
      ?>
        <div class="rounded-xl border <?= $isVip ? 'border-amber-300 bg-amber-50/40 ring-1 ring-amber-200/60' : 'border-slate-200 bg-white' ?> p-4 flex flex-col">
          <div class="flex items-start justify-between gap-2">
            <div class="font-bold text-primary-800"><?= e($pl['code']) ?></div>
            <?php if ($isVip): ?><span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">VIP</span><?php endif; ?>
          </div>
          <div class="mt-2 text-2xl font-extrabold text-accent-700"><?= format_money($pl['price']) ?></div>
          <?php
            $durationLabel = '-';
            if ($life) {
                $durationLabel = 'ตลอดชีพ';
            } elseif ($days) {
                if ($days >= 360) $durationLabel = '12 เดือน';
                elseif ($days >= 180) $durationLabel = '6 เดือน';
                elseif ($days >= 90)  $durationLabel = '3 เดือน';
                elseif ($days >= 30)  $durationLabel = '1 เดือน';
                else                  $durationLabel = "{$days} วัน";
            }
          ?>
          <div class="text-xs text-slate-600 mt-1"><?= $durationLabel ?></div>
          <?php
            $planTier = (string)($pl['tier'] ?? 'standard');
            $tierFeatures = OwnerTier::featuresForTier($planTier);
            $featureLabels = OwnerTier::featureLabels();
          ?>
          <?php if (!empty($tierFeatures)): ?>
          <ul class="mt-3 space-y-1 text-xs text-slate-600 flex-1">
            <?php foreach (array_slice($tierFeatures, 0, 6) as $feat): ?>
              <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5"></i><span><?= e($featureLabels[$feat] ?? $feat) ?></span></li>
            <?php endforeach; ?>
            <?php if (count($tierFeatures) > 6): ?>
              <li class="text-slate-400">+ อีก <?= count($tierFeatures) - 6 ?> สิทธิ์ (ดูตารางด้านบน)</li>
            <?php endif; ?>
          </ul>
          <?php endif; ?>
          <a href="<?= url('/owner/membership/buy?plan=' . (int)$pl['id']) ?>" class="mt-auto pt-4 inline-flex justify-center items-center gap-2 py-2.5 rounded-xl font-semibold text-sm <?= $isVip ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-primary-600 hover:bg-primary-700 text-white' ?>">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i> สมัครแพ็กเกจนี้
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="p-5 border-b border-slate-100">
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-slate-600"></i> ประวัติคำสั่งซื้อล่าสุด</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
          <tr>
            <th class="text-left px-4 py-3">เลขที่</th>
            <th class="text-left px-4 py-3">แพ็กเกจ</th>
            <th class="text-left px-4 py-3">ยอด</th>
            <th class="text-left px-4 py-3">สถานะ</th>
            <th class="text-left px-4 py-3">วันที่</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php if (empty($orders)): ?>
          <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">
            <span class="inline-flex flex-col items-center gap-2">
              <i data-lucide="inbox" class="w-10 h-10 text-slate-300"></i>
              <span>ยังไม่มีคำสั่งซื้อ</span>
            </span>
          </td></tr>
        <?php else:
          $colors = ['pending'=>'amber','paid'=>'emerald','cancelled'=>'slate'];
          foreach ($orders as $o): $c = $colors[$o['status']] ?? 'slate'; ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-mono text-xs"><?= e($o['order_no']) ?></td>
            <td class="px-4 py-3"><?= e($o['plan_code']) ?> <span class="text-xs text-slate-500">(<?= e($o['plan_tier']) ?>)</span></td>
            <td class="px-4 py-3 font-semibold"><?= format_money($o['amount']) ?></td>
            <td class="px-4 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($o['status']) ?></span></td>
            <td class="px-4 py-3 text-xs"><?= e(format_date_th($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
