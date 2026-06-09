<?php
/** @var bool $webpOk @var array{originals:int,has_md:int,missing_md:int} $scan @var array|null $runResult */
$run = is_array($runResult ?? null) ? $runResult : null;
?>
<div class="max-w-4xl space-y-6">

  <?php if (!$webpOk): ?>
  <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5"></i>
    <div>
      <p class="font-semibold">เซิร์ฟเวอร์ยังไม่รองรับ WebP</p>
      <p class="mt-1 text-rose-700">ต้องเปิด PHP extension <code class="bg-white/80 px-1 rounded">gd</code> พร้อม <code class="bg-white/80 px-1 rounded">imagewebp</code> ใน DirectAdmin → PHP Settings ก่อนใช้งาน</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <div class="flex items-start gap-4">
      <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-700 grid place-items-center shrink-0">
        <i data-lucide="image-down" class="w-6 h-6"></i>
      </div>
      <div class="flex-1 min-w-0">
        <h2 class="text-lg font-bold text-slate-800">สร้าง WebP ให้รูปทั้งหมด</h2>
        <p class="mt-1 text-sm text-slate-600 leading-relaxed">
          สแกนโฟลเดอร์ <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">public/uploads/</code> แล้วสร้าง
          <strong>_md.webp</strong> (800px สำหรับ Hero/Banner) และ <strong>_thumb.webp</strong> (400px สำหรับการ์ด)
          ให้รูปเดิมและรูปใหม่ — ช่วยลด LCP และเพิ่มคะแนน PageSpeed
        </p>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-center">
        <div class="text-2xl font-bold text-slate-800"><?= (int) $scan['originals'] ?></div>
        <div class="text-xs text-slate-500 mt-1">รูปต้นฉบับทั้งหมด</div>
      </div>
      <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-center">
        <div class="text-2xl font-bold text-emerald-700"><?= (int) $scan['has_md'] ?></div>
        <div class="text-xs text-emerald-600 mt-1">มี WebP แล้ว</div>
      </div>
      <div class="rounded-xl bg-amber-50 border border-amber-100 p-4 text-center">
        <div class="text-2xl font-bold text-amber-700"><?= (int) $scan['missing_md'] ?></div>
        <div class="text-xs text-amber-600 mt-1">ยังไม่มี WebP</div>
      </div>
    </div>

    <form method="post" action="<?= url('/admin/tools/images/run') ?>" class="mt-6"
          onsubmit="return confirm('รัน Optimize รูปทั้งหมด?\n\nอาจใช้เวลา 1–3 นาที ขึ้นกับจำนวนรูป');">
      <?= csrf() ?>
      <button type="submit"
              class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm shadow-soft disabled:opacity-50 disabled:cursor-not-allowed"
              <?= $webpOk ? '' : 'disabled' ?>>
        <i data-lucide="play" class="w-4 h-4"></i>
        รัน Optimize ตอนนี้
      </button>
      <?php if ((int) $scan['missing_md'] === 0 && (int) $scan['originals'] > 0): ?>
      <p class="mt-2 text-xs text-emerald-600">รูปทั้งหมดมี WebP แล้ว — กดรันอีกครั้งได้ถ้าอัปโหลดรูปใหม่</p>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($run): ?>
  <?php $s = $run['stats']; $files = $run['processed'] ?? []; ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
      </div>
      <div>
        <h3 class="font-bold text-slate-800">รันเสร็จแล้ว</h3>
        <p class="text-sm text-slate-500">Cache หน้าแรกถูกล้างแล้ว — ลองวัด PageSpeed ใหม่ได้</p>
      </div>
    </div>
    <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-sm">
      <div><span class="block text-xl font-bold"><?= (int) $s['total'] ?></span><span class="text-slate-500">ประมวลผล</span></div>
      <div><span class="block text-xl font-bold text-emerald-600"><?= (int) $s['ok'] ?></span><span class="text-slate-500">สำเร็จ</span></div>
      <div><span class="block text-xl font-bold text-slate-400"><?= (int) $s['skip'] ?></span><span class="text-slate-500">ข้าม</span></div>
      <div><span class="block text-xl font-bold <?= (int) $s['fail'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>"><?= (int) $s['fail'] ?></span><span class="text-slate-500">ล้มเหลว</span></div>
    </div>
    <?php if ($files !== []): ?>
    <div class="border-t border-slate-100 max-h-64 overflow-y-auto">
      <ul class="divide-y divide-slate-50 text-xs font-mono">
        <?php foreach ($files as $f): ?>
        <li class="px-5 py-2 text-slate-600"><?= e($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 text-sm text-slate-600 space-y-2">
    <h3 class="font-bold text-slate-800 flex items-center gap-2">
      <i data-lucide="info" class="w-4 h-4 text-accent-600"></i> หมายเหตุ
    </h3>
    <ul class="list-disc list-inside space-y-1 text-slate-600">
      <li>รูปที่อัปโหลดใหม่จะสร้าง WebP อัตโนมัติ — หน้านี้ใช้แปลง<strong>รูปเก่า</strong>ที่มีอยู่แล้ว</li>
      <li>ไฟล์ต้นฉบับ (.jpg/.png) ยังอยู่ — หน้าเว็บจะเลือกโหลด WebP แทนเมื่อมี</li>
      <li>Hero/Banner ใช้ <code class="bg-slate-100 px-1 rounded">_md.webp</code> (~70 KB แทน PNG 2 MB)</li>
      <li>หลังรันเสร็จ แนะนำล้าง Cloudflare cache (ถ้าใช้) แล้ววัด PageSpeed ใหม่</li>
    </ul>
  </div>
</div>
