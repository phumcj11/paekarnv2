<div class="bg-white rounded-2xl border border-slate-200 shadow-soft max-w-4xl">
  <div class="p-5 border-b border-slate-100">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="megaphone" class="w-5 h-5 text-accent-600"></i> การตลาด & โปรโมชัน</h2>
  </div>
  <div class="p-5">
  <p class="text-sm text-slate-600 mb-6">
    จัดการคอนเทนต์และโปรโมชันผ่านโมดูลด้านล่าง — ข้อมูลโปรโมชันแบบ CMS เต็มรูปแบบสามารถต่อยอดจาก Banner / บทความ / คำสั่งซื้อคูปองได้ตามสเปก
  </p>
  <div class="grid sm:grid-cols-2 gap-4">
    <?php
    $cards = [
      ['/admin/blog', 'newspaper', 'บทความ & SEO', 'เขียนบทความ แท็ก และสถานะเผยแพร่'],
      ['/admin/banners', 'layout-grid', 'Banner หน้าแรก', 'ภาพและลิงก์บนหน้าแรก'],
      ['/admin/coupons', 'ticket', 'คูปอง', 'รายการคูปองและคำสั่งซื้อ'],
      ['/admin/coupon-campaigns', 'layers', 'แคมเปญคูปอง', 'หลายมูลค่า / ช่วงโปร — โครงพร้อมต่อยอด'],
      ['/admin/zone-ads', 'signpost', 'โฆษณาโซน', 'แบนเนอร์ใต้หัวข้อแพตามโซนบนหน้าแรก'],
      ['/admin/coupons/orders', 'shopping-cart', 'คำสั่งซื้อคูปอง', 'อนุมัติการชำระและติดตามยอดขาย'],
      ['/admin/reviews', 'message-circle', 'รีวิว', 'อนุมัติรีวิวจากลูกค้า'],
      ['/admin/review-videos', 'video', 'คลิปรีวิวแนวตั้ง', 'YouTube Shorts / TikTok / Reels — carousel หน้ารีวิวและหน้าแรก'],
      ['/admin/review-facebook-posts', 'facebook', 'โพสต์ Facebook', 'ลิงก์โพสต์แสดงในหน้ารีวิว (Embedded Post)'],
      ['/admin/visitor-places', 'map-pin', 'ที่เที่ยว / POI', 'จัดการหน้า /places และผูกโซนกับที่พัก'],
    ];
    foreach ($cards as $c): ?>
      <a href="<?= url($c[0]) ?>" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft hover:border-accent-400 hover:shadow-md transition flex gap-4 items-start">
        <div class="w-11 h-11 rounded-xl bg-accent-50 text-accent-700 grid place-items-center shrink-0"><i data-lucide="<?= $c[1] ?>" class="w-5 h-5"></i></div>
        <div>
          <div class="font-bold text-slate-800"><?= e($c[2]) ?></div>
          <div class="text-xs text-slate-500 mt-1"><?= e($c[3]) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  </div>
</div>
