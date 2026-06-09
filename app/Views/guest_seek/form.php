<?php
/** @var list<string> $zones @var array<string,string> $types */
$initial_property_type ??= '';
$selectedType = (string)(old('preferred_property_type', $initial_property_type));

$presetZones = [];
foreach ($zones as $zv) {
    $zv = trim((string)$zv);
    if ($zv !== '') {
        $presetZones[] = $zv;
    }
}
$presetZones = array_values(array_slice(array_unique($presetZones), 0, 20));
?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="search" class="w-8 h-8 text-accent-600"></i> ขอให้ช่วยหาที่พัก</h1>
  <p class="text-slate-600 mt-2 text-sm leading-relaxed">
    บอกเราสั้น ๆ ว่าอยากพักแบบไหนในกาญ — โซนไหน ประเภทที่พัก และงบคร่าว ๆ
    เราจะช่วยประสานให้<strong class="text-amber-800">เจ้าของที่พักที่น่าจะตรงกับคำขอของคุณ</strong>ได้รู้
    เพื่อโอกาสได้รับข้อเสนอหรือการติดต่อกลับตามข้อมูลที่คุณให้ไว้ในฟอร์มนี้
  </p>
  <details class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3">
    <summary class="cursor-pointer text-sm font-bold text-emerald-900 inline-flex items-center gap-2 list-none [&::-webkit-details-marker]:hidden">
      <i data-lucide="compass" class="w-4 h-4 shrink-0"></i>
      เคล็ดลับกรอกให้ได้โปรไว ตรงใจ
    </summary>
    <ul class="mt-2 ml-7 text-xs text-emerald-900/90 space-y-1.5 leading-relaxed list-disc marker:text-emerald-600">
      <li>ใส่<strong class="text-emerald-950">วันเข้าพักและจำนวนคน</strong>ให้ชัด จะช่วยให้เขาเสนอห้องและราคาได้ตรงสุด</li>
      <li><strong class="text-emerald-950">งบสูงสุดต่อคืน</strong>หรือประเภทที่พักที่ไม่ยืดหยุ่น ช่วยกรองตัวเลือกก่อนคุณต้องถามเองทุกที่</li>
      <li>ช่องรายละเอียดเพิ่มเติม — อยากริมน้ำ รับสัตว์ ต้องครัว เปลเด็กเล็ก พิมพ์ไว้เลยจะได้ตรงโจทย์เร็วขึ้น</li>
      <li><strong class="text-emerald-950">เบอร์โทรและ LINE</strong> ควรเป็นช่องทางที่รับได้จริง จะได้ไม่พลาดคำตอบหรือดีลดี ๆ</li>
    </ul>
  </details>

  <form method="post" action="<?= url('/guest-seek') ?>" class="mt-8 space-y-5 bg-white border border-slate-200 rounded-2xl p-6 shadow-soft">
    <?= csrf() ?>

    <label class="sr-only" aria-hidden="true">Website</label>
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="absolute opacity-0 h-0 w-0 pointer-events-none" aria-hidden="true">

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ <span class="text-rose-500">*</span></label>
        <input type="text" name="name" required maxlength="120" value="<?= old('name') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทร <span class="text-rose-500">*</span></label>
        <input type="tel" name="phone" required value="<?= old('phone') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none" placeholder="08x-xxx-xxxx">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
        <input type="email" name="email" maxlength="160" value="<?= old('email') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">LINE ID / ชื่อ LINE <span class="text-slate-400 font-normal">(ถ้าสะดวกให้เขาทักมา)</span></label>
        <input type="text" name="line_contact" maxlength="120" value="<?= old('line_contact') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-accent-600"></i>โซนที่สนใจ <span class="text-rose-500">*</span></label>
        <select id="guest_seek_zone" name="preferred_zone" required class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none bg-white">
          <option value="" disabled <?= old('preferred_zone') === '' ? 'selected' : '' ?>>— เลือกโซน —</option>
          <?php foreach ($zones as $zv): $zv = trim((string)$zv); if ($zv === '') continue; ?>
            <option value="<?= e($zv) ?>" <?= old('preferred_zone') === $zv ? 'selected' : '' ?>><?= e($zv) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($presetZones !== []): ?>
        <div class="mt-2 flex flex-wrap gap-1.5">
          <?php foreach ($presetZones as $pz): ?>
            <button type="button" data-zone="<?= e($pz) ?>" class="guest_seek_zone_btn inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-accent-50 hover:text-accent-800 hover:ring-accent-200 transition">
              <i data-lucide="map-pin" class="w-3 h-3 opacity-70"></i><?= e($pz) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <p class="text-[11px] text-slate-500 mt-1.5">แตะเลือกโซนที่ใกล้เคียงได้ — ถ้าครอบคลุมหลายโซน ให้อธิบายในช่องรายละเอียดด้านล่าง</p>
        <?php endif; ?>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block flex items-center gap-1.5"><i data-lucide="hotel" class="w-3.5 h-3.5 text-accent-600"></i>ประเภทที่พัก</label>
        <select name="preferred_property_type" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none bg-white">
          <?php foreach ($types as $code => $label): ?>
            <option value="<?= e((string)$code) ?>" <?= $selectedType === (string)$code ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($initial_property_type !== ''): ?>
        <p class="text-[11px] text-sky-700 bg-sky-50 border border-sky-100 rounded-lg px-2.5 py-1.5 mt-2 inline-flex items-center gap-1.5">
          <i data-lucide="link-2" class="w-3.5 h-3.5"></i>เข้ามาพร้อมประเภทจากลิงก์แล้ว — เปลี่ยนเป็น «ทุกประเภท» ได้เมื่ออยากครอบคลุมกว้าง</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">เข้าพัก (ถ้ามี)</label>
        <input type="date" name="check_in" value="<?= old('check_in') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">ออก (ถ้ามี)</label>
        <input type="date" name="check_out" value="<?= old('check_out') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">จำนวนคนโดยประมาณ</label>
        <input type="number" name="guest_count" min="0" max="99" value="<?= old('guest_count') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
      </div>
    </div>

    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">งบโดยประมาณสูงสุด (บาท)</label>
      <input type="number" name="budget_max" min="0" step="1" value="<?= old('budget_max') ?>"
             class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none" placeholder="เช่น 5000">
      <p class="text-xs text-slate-500 mt-1">ใช้เทียบกับราคาต่ำสุดของที่พักที่เผยแพร่ในพื้นที่ที่เลือก</p>
    </div>

    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">รายละเอียดเพิ่มเติม</label>
      <textarea name="message" rows="4" maxlength="2000" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none"><?= old('message') ?></textarea>
    </div>

    <label class="flex items-start gap-3 cursor-pointer">
      <input type="checkbox" name="consent" value="1" class="mt-1 rounded border-slate-300 text-accent-600 focus:ring-accent-500" <?= old('consent') === '1' ? 'checked' : '' ?> required>
      <span class="text-sm text-slate-600">ข้อยินยอมให้ติดต่อกลับตามข้อมูลที่ให้ไว้ เพื่อช่วยหาที่พักและบริการที่เกี่ยวข้อง</span>
    </label>

    <button type="submit" class="w-full py-3 rounded-xl bg-accent-500 hover:bg-accent-600 text-white font-bold inline-flex items-center justify-center gap-2">
      <i data-lucide="send" class="w-4 h-4"></i> ส่งคำขอ
    </button>
  </form>
</section>
<script>
document.querySelectorAll('.guest_seek_zone_btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var z = btn.getAttribute('data-zone');
    var sel = document.getElementById('guest_seek_zone');
    if (sel && z) {
      sel.value = z;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
});
</script>
