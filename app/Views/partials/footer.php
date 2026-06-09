<?php
use App\Models\Setting;
$siteName = Setting::get('site_name', 'แพกาญ.com');
$email    = Setting::get('site_email', 'contact@paekan.com');
$phone    = Setting::get('site_phone', '034-000-000');
$line     = Setting::get('line_oa', '@paekan');
?>
<footer class="bg-primary-700 text-slate-100 mt-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-10">
    <div class="md:col-span-1">
      <div class="flex items-center gap-3 mb-4">
        <span class="block h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-white/95 shadow-lg ring-2 ring-white/20">
          <img src="<?= asset('site-logo.png') ?>" alt="<?= e($siteName) ?>" width="96" height="96" decoding="async" class="h-full w-full object-contain p-0.5">
        </span>
        <div class="min-w-0">
          <div class="font-bold text-lg"><?= e($siteName) ?></div>
          <div class="text-xs text-slate-300">ที่พักครบทุกประเภทในกาญจนบุรี</div>
        </div>
      </div>
      <p class="text-sm text-slate-300 leading-relaxed">
        แพลตฟอร์มจองที่พักในกาญจนบุรี — แพ รีสอร์ท โรงแรม โฮมสเตย์ พูลวิลล่า พร้อมระบบคูปองเงินสด เพื่อให้คุณได้ที่พักที่ใช่ในราคาที่คุ้ม
      </p>
    </div>

    <div>
      <h3 class="font-semibold mb-3 text-white">ค้นหา</h3>
      <ul class="space-y-2 text-sm text-slate-300">
        <li><a href="<?= url('/rafts?zone=เขื่อนศรีนครินทร์') ?>" class="hover:text-accent-300">แพเขื่อนศรีนครินทร์</a></li>
        <li><a href="<?= url('/rafts?zone=สังขละบุรี') ?>" class="hover:text-accent-300">แพสังขละบุรี</a></li>
        <li><a href="<?= url('/resorts') ?>" class="hover:text-accent-300">รีสอร์ท</a></li>
        <li><a href="<?= url('/hotels') ?>" class="hover:text-accent-300">โรงแรม</a></li>
        <li><a href="<?= url('/stays') ?>" class="hover:text-accent-300">โฮมสเตย์ & บ้านพัก</a></li>
        <li><a href="<?= url('/pool-villas') ?>" class="hover:text-accent-300">บ้านพูลวิลล่า</a></li>
        <li><a href="<?= url('/properties') ?>" class="hover:text-accent-300">ค้นหาทั้งหมด</a></li>
      </ul>
    </div>

    <div>
      <h3 class="font-semibold mb-3 text-white">บริการ</h3>
      <ul class="space-y-2 text-sm text-slate-300">
        <li><a href="<?= url('/guest-seek') ?>" class="hover:text-accent-300">ให้ทีมช่วยหาที่พัก</a></li>
        <li><a href="<?= url('/coupons') ?>" class="hover:text-accent-300">คูปองเงินสด</a></li>
        <li><a href="<?= url('/blog') ?>" class="hover:text-accent-300">บล็อกท่องเที่ยว</a></li>
        <li><a href="<?= url('/track-order') ?>" class="hover:text-accent-300">ติดตามคำสั่งซื้อ</a></li>
        <li><a href="<?= url('/about') ?>" class="hover:text-accent-300">เกี่ยวกับเรา</a></li>
        <li><a href="<?= url('/contact') ?>" class="hover:text-accent-300">ติดต่อเรา</a></li>
        <li><a href="<?= url('/owner/register') ?>" class="text-accent-300 hover:text-white inline-flex items-center gap-1"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i> ลงทะเบียนเป็นเจ้าของแพ</a></li>
        <li><a href="<?= url('/provider/register') ?>" class="text-accent-300 hover:text-white inline-flex items-center gap-1"><i data-lucide="handshake" class="w-3.5 h-3.5"></i> สมัครเป็นผู้ให้บริการ</a></li>
      </ul>
    </div>

    <div>
      <h3 class="font-semibold mb-3 text-white">ติดต่อ</h3>
      <ul class="space-y-2 text-sm text-slate-300">
        <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4"></i><?= e($phone) ?></li>
        <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i><?= e($email) ?></li>
        <li class="flex items-center gap-2"><i data-lucide="message-circle" class="w-4 h-4"></i>LINE: <?= e($line) ?></li>
      </ul>
      <div class="flex items-center gap-3 mt-4">
        <a href="#" aria-label="Facebook" class="w-9 h-9 rounded-full bg-white/10 grid place-items-center hover:bg-white/20"><?php \App\Core\View::partial('partials/brand-icon', ['name' => 'facebook']); ?></a>
        <a href="#" aria-label="Instagram" class="w-9 h-9 rounded-full bg-white/10 grid place-items-center hover:bg-white/20"><?php \App\Core\View::partial('partials/brand-icon', ['name' => 'instagram']); ?></a>
        <a href="#" aria-label="YouTube" class="w-9 h-9 rounded-full bg-white/10 grid place-items-center hover:bg-white/20"><?php \App\Core\View::partial('partials/brand-icon', ['name' => 'youtube']); ?></a>
      </div>
    </div>
  </div>

  <div class="border-t border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between text-xs text-slate-300">
      <div>© <?= date('Y') ?> <?= e($siteName) ?>. All Rights Reserved.</div>
      <div class="hidden sm:block">Made with <i data-lucide="heart" class="w-3 h-3 inline text-rose-400"></i> in Kanchanaburi</div>
    </div>
  </div>
</footer>
