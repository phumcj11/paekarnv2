<aside class="lg:col-span-3">
  <div class="bg-white border border-slate-200 rounded-2xl p-4 lg:sticky lg:top-24">
    <?php $items = [
      ['/account',           'gauge',         'แดชบอร์ด'],
      ['/account/bookings',  'calendar-check','การจองของฉัน'],
      ['/account/coupons',   'ticket',        'คูปองของฉัน'],
      ['/account/favorites', 'heart',         'บันทึกที่พัก'],
      ['/account/notifications','bell',       'การแจ้งเตือน'],
      ['/account/profile',   'user-cog',      'โปรไฟล์'],
    ]; ?>
    <nav class="flex lg:flex-col gap-1 overflow-x-auto no-scrollbar">
      <?php foreach ($items as $it):
        $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = preg_replace('#/index\.php$#','',$_SERVER['SCRIPT_NAME']);
        $base = preg_replace('#/public$#','',$base);
        $matchPath = rtrim($base,'/').$it[0];
        $active = $cur === $matchPath || str_ends_with($cur, $it[0]);
      ?>
      <a href="<?= url($it[0]) ?>" class="px-3 py-2.5 rounded-lg flex items-center gap-2 text-sm font-medium <?= $active?'bg-primary-50 text-primary-700':'hover:bg-slate-50 text-slate-700' ?>">
        <i data-lucide="<?= $it[1] ?>" class="w-4 h-4"></i> <?= e($it[2]) ?>
      </a>
      <?php endforeach; ?>
      <form action="<?= url('/logout') ?>" method="post" class="lg:mt-2 pt-2 lg:border-t border-slate-100"><?= csrf() ?>
        <button class="w-full text-left px-3 py-2.5 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center gap-2 text-sm font-medium">
          <i data-lucide="log-out" class="w-4 h-4"></i> ออกจากระบบ
        </button>
      </form>
    </nav>
  </div>
</aside>
