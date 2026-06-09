<?php /** @var array $user */ ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>
  <div class="lg:col-span-9">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-2"><i data-lucide="user-cog" class="w-6 h-6 text-accent-600"></i> โปรไฟล์</h1>
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-primary-600 text-white grid place-items-center font-bold text-2xl"><?= mb_substr($user['name'],0,1) ?></div>
        <div>
          <div class="font-bold text-lg"><?= e($user['name']) ?></div>
          <div class="text-sm text-slate-500"><?= e($user['email']) ?></div>
          <div class="text-sm text-slate-500"><?= e($user['phone'] ?? '-') ?></div>
        </div>
      </div>
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><div class="text-xs text-slate-500">บทบาท</div><div class="font-medium"><?= e($user['role']) ?></div></div>
        <div><div class="text-xs text-slate-500">สถานะ</div><div class="font-medium"><?= e($user['status']) ?></div></div>
        <div><div class="text-xs text-slate-500">เข้าสู่ระบบล่าสุด</div><div class="font-medium"><?= format_date_th($user['last_login_at'] ?? null) ?></div></div>
        <div><div class="text-xs text-slate-500">สมัครเมื่อ</div><div class="font-medium"><?= format_date_th($user['created_at']) ?></div></div>
      </div>
    </div>

    <!-- LINE OA + Notification settings (Phase 3) -->
    <div class="mt-6 bg-white border border-slate-200 rounded-2xl p-6">
      <h2 class="font-bold flex items-center gap-2 mb-4"><i data-lucide="message-square" class="w-5 h-5 text-emerald-600"></i> ช่องทางการแจ้งเตือน</h2>

      <?php if (!empty($user['line_user_id'])): ?>
        <div class="flex items-center justify-between p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#06C755] grid place-items-center text-white font-bold">L</div>
            <div>
              <div class="font-semibold text-emerald-700">LINE เชื่อมต่อแล้ว</div>
              <div class="text-xs text-slate-500">รหัส: <code class="font-mono"><?= e(substr((string)$user['line_user_id'], 0, 12)) ?>…</code></div>
            </div>
          </div>
          <a href="<?= url('/line/unlink') ?>" onclick="return confirm('ยกเลิกการผูกบัญชี LINE?')" class="px-3 py-1.5 text-xs bg-rose-50 text-rose-600 border border-rose-200 rounded-lg hover:bg-rose-100">ยกเลิก</a>
        </div>
      <?php else: ?>
        <a href="<?= url('/line/login') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#06C755] hover:bg-[#05a847] text-white font-semibold rounded-lg">
          <span class="font-bold">L</span> เชื่อมต่อ LINE เพื่อรับการแจ้งเตือน
        </a>
        <p class="text-xs text-slate-500 mt-2">เมื่อเชื่อมต่อแล้ว จะได้รับ การยืนยันการจอง คูปอง และข่าวโปรโมชั่นทาง LINE</p>
      <?php endif; ?>

      <div class="mt-4 text-xs text-slate-500">
        <a href="<?= e((string)\App\Models\Setting::get('line_friend_url', 'https://line.me')) ?>" target="_blank" class="text-accent-700 hover:underline">+ Add LINE OA @paekan</a>
      </div>
    </div>
  </div>
</section>
