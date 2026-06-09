<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
  <h1 class="text-3xl font-extrabold mb-3 flex items-center gap-2"><i data-lucide="anchor" class="w-7 h-7 text-accent-600"></i> เกี่ยวกับแพกาญ.com</h1>
  <p class="text-slate-700 leading-relaxed">
    แพกาญ.com คือแพลตฟอร์มรวมแพพัก รีสอร์ท บ้านพัก และโฮมสเตย์ในจังหวัดกาญจนบุรี
    เราเชื่อว่าทุกคนควรเข้าถึงประสบการณ์การเดินทางที่ดีในราคาที่คุ้มค่า จึงสร้างระบบ
    <b class="text-accent-700">"คูปองเงินสด"</b> เพื่อเป็นช่องทางใหม่ที่ทำให้ลูกค้าได้ส่วนลด
    ในขณะที่เจ้าของแพไม่เสียค่าคอมมิชชั่นเป็นเปอร์เซ็นต์
  </p>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
    <?php $items = [
      ['heart-handshake','ยุติธรรมต่อทั้งสองฝ่าย','ลูกค้าได้ราคาที่ดี เจ้าของแพไม่ถูกหักคอม'],
      ['shield-check','รีวิวจริง โปร่งใส','รีวิวจากผู้พักจริงผ่านระบบ verified booking'],
      ['headset','ทีมงาน support','พร้อมช่วยเหลือทั้งลูกค้าและเจ้าของแพตลอด 7 วัน'],
    ]; foreach ($items as $it): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <div class="w-11 h-11 rounded-xl bg-accent-50 text-accent-600 grid place-items-center"><i data-lucide="<?= $it[0] ?>" class="w-5 h-5"></i></div>
      <h3 class="mt-3 font-bold"><?= $it[1] ?></h3>
      <p class="text-sm text-slate-600 mt-1"><?= $it[2] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>
