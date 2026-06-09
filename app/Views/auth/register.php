<div class="min-h-[calc(100vh-200px)] grid place-items-center px-4 py-12">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-soft border border-slate-200 p-8">
    <div class="text-center mb-6">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-accent-500 grid place-items-center text-white shadow-soft">
        <i data-lucide="user-plus" class="w-7 h-7"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-ink">สมัครสมาชิกฟรี</h1>
      <p class="text-sm text-slate-500">รับสิทธิประโยชน์ คูปองส่วนลด และการจองที่ง่ายขึ้น</p>
    </div>
    <form action="<?= url('/register') ?>" method="post" class="space-y-3">
      <?= csrf() ?>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ-นามสกุล</label>
        <input type="text" name="name" value="<?= old('name') ?>" required
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
        <input type="email" name="email" value="<?= old('email') ?>" required
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทรศัพท์</label>
        <input type="tel" name="phone" value="<?= old('phone') ?>" required
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none"
               placeholder="08x-xxx-xxxx">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่าน</label>
          <input type="password" name="password" required minlength="8"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">ยืนยันรหัสผ่าน</label>
          <input type="password" name="password_confirm" required minlength="8"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
        </div>
      </div>
      <button class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg transition">
        สมัครสมาชิก
      </button>
    </form>
    <div class="mt-4 text-center text-sm text-slate-600">
      มีบัญชีอยู่แล้ว?
      <a href="<?= url('/login') ?>" class="text-primary-600 font-semibold hover:underline">เข้าสู่ระบบ</a>
    </div>
  </div>
</div>
