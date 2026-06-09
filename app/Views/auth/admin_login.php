<?php use App\Core\Session; $err = Session::flash('error'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin Login — แพกาญ.com</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>body{font-family:'Sarabun',system-ui}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary to-slate-900 grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-white/10 backdrop-blur grid place-items-center text-white">
        <i data-lucide="shield-check" class="w-8 h-8"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-white">Admin Backoffice</h1>
      <p class="text-sm text-white/70">แพกาญ.com — Control Panel</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-3 px-4 py-3 bg-rose-500/90 text-white rounded-lg text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/login') ?>" class="bg-white rounded-2xl shadow-xl p-7 space-y-4">
      <?= csrf() ?>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">Admin Email</label>
        <input type="email" name="email" required value="<?= old('email','admin@paekan.com') ?>"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-slate-100 outline-none">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">Password</label>
        <input type="password" name="password" required
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-slate-100 outline-none">
      </div>
      <button class="w-full py-2.5 bg-primary text-white font-semibold rounded-lg hover:opacity-95">
        เข้าสู่ระบบ Admin
      </button>
      <a href="<?= url('/') ?>" class="block text-center text-xs text-slate-500 hover:text-primary mt-2">← กลับเว็บไซต์</a>
    </form>
  </div>
<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons())</script>
</body>
</html>
