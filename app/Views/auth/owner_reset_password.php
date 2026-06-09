<?php use App\Core\Session; $err = Session::flash('error'); $token = (string)($token ?? ''); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตั้งรหัสผ่านใหม่ — Owner Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>body{font-family:'Sarabun',system-ui}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-accent-600 via-accent-500 to-primary-600 grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-white/20 backdrop-blur grid place-items-center text-white">
        <i data-lucide="lock-keyhole" class="w-8 h-8"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-white">ตั้งรหัสผ่านใหม่</h1>
      <p class="text-sm text-white/80">กรอกรหัสผ่านใหม่สำหรับ Owner Portal</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-3 px-4 py-3 bg-rose-500/95 text-white rounded-lg text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/owner/reset-password') ?>" class="bg-white rounded-2xl shadow-xl p-7 space-y-4">
      <?= csrf() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่านใหม่</label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
        <p class="text-xs text-slate-400 mt-1">อย่างน้อย 8 ตัวอักษร</p>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">ยืนยันรหัสผ่านใหม่</label>
        <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
      </div>
      <button type="submit" class="w-full py-2.5 bg-accent-600 hover:bg-accent-700 text-white font-semibold rounded-lg transition">
        บันทึกรหัสผ่านใหม่
      </button>
      <a href="<?= url('/owner/login') ?>" class="block text-center text-sm text-slate-600 hover:text-accent-600">
        ← กลับหน้าเข้าสู่ระบบ
      </a>
    </form>
  </div>
<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons())</script>
</body>
</html>
