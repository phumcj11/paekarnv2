<div class="min-h-[calc(100vh-200px)] grid place-items-center px-4 py-12">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-soft border border-slate-200 p-8">
    <div class="text-center mb-6">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-primary-600 grid place-items-center text-white shadow-soft">
        <i data-lucide="log-in" class="w-7 h-7"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-ink">ยินดีต้อนรับกลับมา</h1>
      <p class="text-sm text-slate-500">เข้าสู่ระบบเพื่อจัดการการจองและคูปองของคุณ</p>
    </div>

    <form action="<?= url('/login') ?>" method="post" class="space-y-4">
      <?= csrf() ?>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
        <div class="relative">
          <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input type="email" name="email" value="<?= old('email') ?>" required
                 class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่าน</label>
        <div class="relative" x-data="{show:false}">
          <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input :type="show?'text':'password'" name="password" required
                 class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
          <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
            <i x-show="!show" data-lucide="eye" class="w-4 h-4"></i>
            <i x-show="show"  data-lucide="eye-off" class="w-4 h-4" x-cloak></i>
          </button>
        </div>
      </div>
      <button class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
        <?= __('login') ?>
      </button>
    </form>

    <div class="mt-4 text-center text-sm text-slate-600">
      ยังไม่มีบัญชี?
      <a href="<?= url('/register') ?>" class="text-accent-600 font-semibold hover:underline">สมัครฟรี</a>
    </div>
  </div>
</div>
