<?php /** @var int $face @var int $sale @var int $valid @var array $properties */ ?>
<section class="bg-gradient-to-br from-accent-500 via-accent-600 to-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-16 grid md:grid-cols-2 gap-8 items-center">
    <div>
      <span class="inline-flex items-center gap-1 bg-white/20 px-3 py-1 rounded-full text-xs font-semibold mb-3">
        <i data-lucide="zap" class="w-3.5 h-3.5"></i> Cash Voucher
      </span>
      <h1 class="text-3xl md:text-5xl font-extrabold leading-tight">ซื้อ ฿<?= $sale ?> ใช้ได้ ฿<?= $face ?></h1>
      <p class="mt-3 text-white/90 text-lg">คูปองเงินสดของแพกาญ — ใช้แทนเงินสดลดค่าที่พักได้กับแพร่วมโครงการทุกแห่ง</p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="<?= url('/coupons/buy') ?>" class="inline-flex items-center gap-2 bg-white text-primary-700 font-semibold px-5 py-3 rounded-xl shadow-soft hover:bg-amber-50">
          <i data-lucide="gift" class="w-5 h-5"></i> ซื้อคูปองเลย
        </a>
        <a href="#how" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 px-5 py-3 rounded-xl">
          <i data-lucide="play-circle" class="w-5 h-5"></i> วิธีใช้งาน
        </a>
      </div>
    </div>
    <div class="flex justify-center">
      <div class="relative">
        <div class="bg-white text-ink rounded-2xl p-6 w-80 shadow-2xl rotate-3">
          <div class="flex items-center justify-between mb-2">
            <div class="text-xs text-slate-500">PAEKAN VOUCHER</div>
            <i data-lucide="ticket" class="w-5 h-5 text-accent-600"></i>
          </div>
          <div class="text-5xl font-black text-primary-700">฿<?= $face ?></div>
          <div class="text-xs text-slate-500 mt-1">มูลค่าใช้จริง</div>
          <hr class="my-3 border-dashed">
          <div class="text-xs text-slate-500">CODE</div>
          <div class="font-mono font-bold text-lg">PKAN-XXXX-XXXX</div>
          <div class="mt-2 text-[10px] text-slate-400">หมดอายุภายใน <?= $valid ?> วัน</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- How to use -->
<section id="how" class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
  <div class="text-center mb-8">
    <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">How it works</span>
    <h2 class="text-2xl md:text-3xl font-bold">ใช้งานง่ายแค่ 4 ขั้นตอน</h2>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $steps = [
      ['gift','1) ซื้อคูปอง','เลือกจำนวนคูปอง ชำระผ่าน PromptPay/โอนเงิน'],
      ['mail','2) รับรหัส','รับรหัสคูปอง PKAN-XXXX-XXXX ทันทีหลังชำระเงิน'],
      ['hotel','3) เลือกที่พัก','เลือกแพ/รีสอร์ทที่ร่วมโครงการ และจองผ่านระบบ'],
      ['check-circle','4) แจ้งใช้คูปอง','กรอกรหัสตอนจอง — เจ้าของแพ verify ตอน check-in'],
    ];
    foreach ($steps as $s): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <div class="w-12 h-12 rounded-xl bg-accent-50 text-accent-600 grid place-items-center"><i data-lucide="<?= $s[0] ?>" class="w-6 h-6"></i></div>
      <h3 class="mt-3 font-bold"><?= $s[1] ?></h3>
      <p class="text-sm text-slate-600 mt-1"><?= $s[2] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Properties supporting -->
<?php if (!empty($properties)): ?>
<section class="bg-white border-y border-slate-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
    <div class="text-center mb-8">
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">Partners</span>
      <h2 class="text-2xl md:text-3xl font-bold">ที่พักที่ใช้คูปองได้</h2>
    </div>
    <div class="md:hidden">
    <?php
    \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
      'properties' => $properties,
      'wrapperClass' => 'max-w-2xl mx-auto w-full',
    ]);
    ?>
    </div>
    <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($properties as $property): \App\Core\View::partial('partials/property-card', ['property' => $property]); endforeach; ?>
    </div>
    <div class="text-center mt-8">
      <a href="<?= url('/properties?coupon=1') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700">
        <i data-lucide="search" class="w-4 h-4"></i> ดูทั้งหมด
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-14">
  <h2 class="text-2xl font-bold text-center mb-6">คำถามที่พบบ่อย</h2>
  <div class="space-y-2" x-data="{open:0}">
    <?php
    $faqs = [
      ['คูปอง 1 ใบใช้กับการจองได้กี่ครั้ง?','ใช้ได้ครั้งเดียว แต่หากซื้อหลายใบสามารถใช้รวมกันในครั้งเดียวกันได้ตามนโยบายของที่พัก'],
      ['คูปองหมดอายุเมื่อไหร่?','หมดอายุภายใน '.$valid.' วันนับจากวันที่ออก'],
      ['สามารถขอคืนเงินได้ไหม?','คูปองไม่สามารถขอคืนเงินได้ แต่สามารถโอนสิทธิ์ได้ภายในระยะเวลาที่กำหนด'],
      ['ผูกกับเบอร์โทรศัพท์ทำไม?','เพื่อตรวจสอบสิทธิ์ตอนใช้งานและกันการนำคูปองไปขายต่อ'],
    ];
    foreach ($faqs as $i=>$q): ?>
    <div class="bg-white border border-slate-200 rounded-xl">
      <button @click="open===<?= $i ?>?open=null:open=<?= $i ?>" class="w-full text-left px-4 py-3 flex items-center justify-between font-semibold">
        <?= e($q[0]) ?>
        <i data-lucide="chevron-down" class="w-4 h-4" :class="open===<?= $i ?>?'rotate-180':''"></i>
      </button>
      <div x-show="open===<?= $i ?>" x-collapse class="px-4 pb-4 text-sm text-slate-600"><?= e($q[1]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
