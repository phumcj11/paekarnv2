<section class="max-w-xl mx-auto px-4 py-16 text-center">
  <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 grid place-items-center mb-4">
    <i data-lucide="sparkles" class="w-9 h-9"></i>
  </div>
  <h1 class="text-2xl font-bold text-slate-800">เราได้รับคำขอของคุณแล้ว</h1>
  <p class="text-slate-600 mt-3 leading-relaxed">
    เราประสานต่อจากนี้ — มีโอกาสที่<strong class="font-semibold text-slate-800">เจ้าของที่พักในกาญ</strong>
    ที่ตรงกับที่คุณต้องการจะ<strong class="font-semibold text-slate-800">ติดต่อกลับทางโทรหรือ LINE ที่คุณระบุ</strong>
    อย่าพลาดสายจากเบอร์ไม่คุ้นนะครับ คุณยังเข้ามาเลือกดูและจองที่พักจากหน้ารายการได้ตามใจอยู่เสมอ
  </p>

  <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
    <a href="<?= url('/properties') ?>" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700"><i data-lucide="list" class="w-4 h-4"></i>เลือกดูที่พักต่อเลย</a>
    <a href="<?= url('/guest-seek') ?>" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="search" class="w-4 h-4"></i>ปรับคำขอแล้วส่งใหม่</a>
    <a href="<?= url('/') ?>" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50">หน้าแรก</a>
  </div>

  <?php $poolVs = url('/guest-seek?type=pool_villa'); $campVs = url('/guest-seek?type=camping'); ?>
  <p class="mt-8 text-[11px] text-slate-500 leading-relaxed border-t border-slate-200 pt-6">
    อยากเริ่มจากหมวดยอดฮิต:
    <a href="<?= e($poolVs) ?>" class="underline text-primary-700 font-medium">บ้านพูลวิลล่า</a>
    ·
    <a href="<?= e($campVs) ?>" class="underline text-primary-700 font-medium">แคมป์</a>
  </p>
</section>
