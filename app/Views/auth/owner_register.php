<?php
use App\Core\Session;
use App\Models\Setting;

$err = Session::flash('error');
$siteName = (string) Setting::get('site_name', 'แพกาญ.com');
$lineFriendUrl = trim((string) Setting::get('line_friend_url', ''));
if ($lineFriendUrl === '') {
    $lineOa = trim((string) Setting::get('line_oa', ''));
    if ($lineOa !== '' && $lineOa[0] === '@') {
        $lineFriendUrl = 'https://line.me/R/ti/p/' . rawurlencode($lineOa);
    }
}
$benefits = [
    ['ticket', 'ระบบคูปองส่วนลด — ลูกค้าซื้อคูปองมาใช้ที่กิจการคุณ ไม่กินค่าคอมแบบ OTA'],
    ['users', 'ลูกค้าจากแพลตฟอร์มท่องเที่ยวกาญจนบุรี — ค้นหาแพ ที่พัก กิจกรรม ร้านอาหาร'],
    ['gauge', 'แดชบอร์ดจัดการที่พัก ห้อง/แพ ปฏิทินว่าง และรายงาน'],
    ['phone', 'ทีมงานโทรแนะนำการเข้าร่วมโปรคูปอง — ช่วยเพิ่มยอดขาย'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สมัครเจ้าของกิจการ — <?= e($siteName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
body { font-family: 'Sarabun', system-ui; }
.paekan-owner-register-page {
  position: relative;
  min-height: 100vh;
  background:
    radial-gradient(ellipse 140% 85% at 100% 0%, rgba(45, 212, 191, 0.34), transparent 52%),
    radial-gradient(ellipse 100% 70% at -5% 55%, rgba(20, 83, 45, 0.12), transparent 48%),
    linear-gradient(163deg, #ecfeff 0%, #d1fae5 28%, #ecfdf5 55%, #f1f5f9 100%);
}
.paekan-owner-register-page::before {
  content: '';
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(15, 118, 110, 0.09) 1px, transparent 0);
  background-size: 28px 28px;
  opacity: 0.65;
}
.paekan-owner-register-wrap { position: relative; z-index: 1; }
.paekan-owner-register-side {
  backdrop-filter: blur(8px);
  background: rgba(255, 255, 255, 0.42);
  border-radius: 1.25rem;
  border: 1px solid rgba(255, 255, 255, 0.65);
  box-shadow: 0 22px 50px -32px rgba(15, 23, 42, 0.35);
}
</style>
</head>
<body class="paekan-owner-register-page px-4 py-8 sm:py-12">
  <div class="paekan-owner-register-wrap max-w-5xl mx-auto">
    <div class="text-center mb-6 md:hidden">
      <img src="<?= asset('site-logo.png') ?>" alt="<?= e($siteName) ?>" width="72" height="72" class="mx-auto h-16 w-16 object-contain">
      <h1 class="mt-2 text-xl font-extrabold text-slate-800"><?= e($siteName) ?></h1>
    </div>

    <div class="grid md:grid-cols-2 gap-6 md:gap-8 items-start">
      <div class="text-slate-700 paekan-owner-register-side p-6 sm:p-7 order-2 md:order-1">
        <div class="hidden md:flex items-center gap-3 mb-5">
          <img src="<?= asset('site-logo.png') ?>" alt="<?= e($siteName) ?>" width="80" height="80" class="h-16 w-16 shrink-0 object-contain">
          <div>
            <div class="text-xs font-semibold text-accent-700 uppercase tracking-wide">Partner Program</div>
            <div class="text-lg font-extrabold text-slate-900 leading-tight"><?= e($siteName) ?></div>
          </div>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold leading-tight text-slate-900">
          ทำไมต้องลงกับเรา?
        </h2>
        <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed">
          แพลตฟอร์มสำหรับ <strong>เจ้าของกิจการที่พัก กิจกรรม ร้านอาหาร</strong> ในกาญจนบุรีและพื้นที่ท่องเที่ยวใกล้เคียง — ลงทะเบียนฟรี ไม่มีค่าแรกเข้า
        </p>
        <ul class="mt-5 space-y-3 text-sm">
          <?php foreach ($benefits as $it): ?>
            <li class="flex items-start gap-2.5">
              <span class="mt-0.5 w-8 h-8 rounded-lg bg-accent/15 text-accent grid place-items-center shrink-0">
                <i data-lucide="<?= e($it[0]) ?>" class="w-4 h-4"></i>
              </span>
              <span class="text-slate-700 leading-relaxed pt-0.5"><?= e($it[1]) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($lineFriendUrl !== ''): ?>
        <a href="<?= e($lineFriendUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="mt-6 w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-[#06C755] hover:bg-[#05b34c] text-white font-bold text-sm shadow-lg shadow-[#06C755]/25 transition">
          <span class="w-7 h-7 rounded-full bg-white/20 grid place-items-center text-xs font-black">L</span>
          Add LINE — สอบถามก่อนสมัคร
        </a>
        <?php endif; ?>
      </div>

      <div class="order-1 md:order-2">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl p-6 sm:p-7 border border-white/80 ring-1 ring-slate-200/70">
          <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="briefcase" class="w-5 h-5 text-accent"></i>
            สมัครเจ้าของกิจการ
          </h2>
          <p class="text-sm text-slate-500 mt-1 mb-4 leading-relaxed">ที่พัก · กิจกรรม · ร้านอาหาร — ฟรี ไม่มีค่าธรรมเนียมแรกเข้า</p>

          <?php if ($err): ?>
            <div class="mb-3 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm flex items-center gap-2">
              <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($err) ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= url('/owner/register') ?>" class="space-y-3" id="ownerRegisterForm">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-sm font-medium mb-1 block">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required value="<?= old('name') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              </div>
              <div>
                <label class="text-sm font-medium mb-1 block">เบอร์โทร <span class="text-rose-500">*</span></label>
                <input type="tel" name="phone" required value="<?= old('phone') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              </div>
            </div>
            <div>
              <label class="text-sm font-medium mb-1 block">ชื่อกิจการ / ที่พัก / ร้าน <span class="text-rose-500">*</span></label>
              <input type="text" name="business_name" required value="<?= old('business_name') ?>"
                     placeholder="เช่น รีสอร์ทริมแม่น้ำ กาญจนบุรี"
                     class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div>
              <label class="text-sm font-medium mb-1 block">LINE ID</label>
              <input type="text" name="line_id" value="<?= old('line_id') ?>" placeholder="เช่น @yourline หรือ ID ที่ลูกค้าแอดได้"
                     class="w-full px-3 py-2.5 rounded-lg border border-slate-300" autocomplete="off">
              <p class="text-[11px] text-slate-500 mt-1">ทีมงานจะติดต่อทาง LINE เพื่อแนะนำโปรคูปองและช่วยตั้งค่า</p>
            </div>

            <div class="rounded-xl border border-accent-200 bg-accent-50/80 px-4 py-3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="wants_sales_help" value="1"
                       <?= old('wants_sales_help') ? 'checked' : '' ?>
                       class="mt-1 rounded border-accent-400 text-accent focus:ring-accent">
                <span class="text-sm text-slate-800 leading-relaxed">
                  <span class="font-semibold block text-accent-900">สนใจให้ทีมงานช่วยขาย / โทรแนะนำ</span>
                  อยากให้ <?= e($siteName) ?> ช่วยขายและชวนเข้าร่วมโปรแกรมคูปองส่วนลด — ทีมงานจะโทรหาคุณหลังสมัคร
                </span>
              </label>
            </div>

            <div>
              <label class="text-sm font-medium mb-1 block">อีเมล (ใช้เข้าระบบ) <span class="text-rose-500">*</span></label>
              <input type="email" name="email" required value="<?= old('email') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-sm font-medium mb-1 block">รหัสผ่าน <span class="text-rose-500">*</span></label>
                <input type="password" name="password" required minlength="8" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              </div>
              <div>
                <label class="text-sm font-medium mb-1 block">ยืนยันรหัสผ่าน <span class="text-rose-500">*</span></label>
                <input type="password" name="password_confirm" required minlength="8" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              </div>
            </div>
            <button type="submit" id="ownerRegisterSubmit" class="w-full py-3 bg-accent text-white font-bold rounded-xl hover:opacity-95 shadow-lg shadow-accent/20">
              สมัครเลย
            </button>
            <?php if ($lineFriendUrl !== ''): ?>
            <a href="<?= e($lineFriendUrl) ?>" target="_blank" rel="noopener noreferrer"
               class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 border-[#06C755] text-[#06C755] font-semibold text-sm hover:bg-[#06C755]/5 transition md:hidden">
              <span class="w-6 h-6 rounded-full bg-[#06C755] text-white text-[10px] font-black grid place-items-center">L</span>
              สอบถามทาง LINE ก่อนสมัคร
            </a>
            <?php endif; ?>
            <div class="text-center text-sm text-slate-600 pt-1">
              มีบัญชีอยู่แล้ว? <a href="<?= url('/owner/login') ?>" class="text-accent font-semibold hover:underline">เข้าสู่ระบบ</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  const form = document.getElementById('ownerRegisterForm');
  const btn = document.getElementById('ownerRegisterSubmit');
  if (form && btn) {
    form.addEventListener('submit', () => {
      btn.disabled = true;
      btn.textContent = 'กำลังส่ง...';
    });
  }
});
</script>
</body>
</html>
