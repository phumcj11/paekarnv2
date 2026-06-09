<?php use App\Core\Session; $err = Session::flash('error'); $ok = Session::flash('success'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Owner Login — แพกาญ.com</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>body{font-family:'Sarabun',system-ui}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-accent-600 via-accent-500 to-primary-600 grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-white/20 backdrop-blur grid place-items-center text-white">
        <i data-lucide="briefcase" class="w-8 h-8"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-white">Owner Portal</h1>
      <p class="text-sm text-white/80">หลังบ้านสำหรับเจ้าของแพ / รีสอร์ท</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-3 px-4 py-3 bg-rose-500/95 text-white rounded-lg text-sm flex items-center gap-2"><i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="mb-3 px-4 py-3 bg-emerald-500/95 text-white rounded-lg text-sm flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><?= e($ok) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/owner/login') ?>" class="bg-white rounded-2xl shadow-xl p-7 space-y-4">
      <?= csrf() ?>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
        <input type="email" name="email" required value="<?= old('email', '') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่าน</label>
        <input type="password" name="password" required
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
      </div>
      <button class="w-full py-2.5 bg-accent-600 hover:bg-accent-700 text-white font-semibold rounded-lg">
        เข้าสู่ระบบ
      </button>
      <div class="text-center text-sm text-slate-600">
        ยังไม่มีบัญชี?
        <a href="<?= url('/owner/register') ?>" class="text-accent-600 font-semibold hover:underline">สมัครเป็นพาร์ทเนอร์ฟรี</a>
      </div>
      <a href="<?= url('/') ?>" class="block text-center text-xs text-slate-500 hover:text-primary mt-2">← กลับเว็บไซต์</a>
    </form>
  </div>
<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons())</script>
</body>
</html>
