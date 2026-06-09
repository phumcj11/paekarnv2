<?php use App\Core\Session; $err = Session::flash('error'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ลืมรหัสผ่าน — Owner Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>body{font-family:'Sarabun',system-ui}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-accent-600 via-accent-500 to-primary-600 grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-white/20 backdrop-blur grid place-items-center text-white">
        <i data-lucide="key-round" class="w-8 h-8"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-white">ลืมรหัสผ่าน</h1>
      <p class="text-sm text-white/80">กรอกอีเมลที่ใช้สมัคร Owner Portal</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-3 px-4 py-3 bg-rose-500/95 text-white rounded-lg text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/owner/forgot-password') ?>" class="bg-white rounded-2xl shadow-xl p-7 space-y-4">
      <?= csrf() ?>
      <div>
        <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
        <input type="email" name="email" required value="<?= old('email', '') ?>" autocomplete="email"
               placeholder="owner@example.com"
               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
      </div>
      <p class="text-xs text-slate-500 leading-relaxed">เราจะส่งลิงก์ตั้งรหัสผ่านใหม่ไปที่อีเมลนี้ (ใช้ได้ 1 ชั่วโมง)</p>
      <button type="submit" class="w-full py-2.5 bg-accent-600 hover:bg-accent-700 text-white font-semibold rounded-lg transition">
        ส่งลิงก์รีเซ็ตรหัสผ่าน
      </button>
      <a href="<?= url('/owner/login') ?>" class="block text-center text-sm text-slate-600 hover:text-accent-600">
        ← กลับหน้าเข้าสู่ระบบ
      </a>
    </form>
  </div>
<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons())</script>
</body>
</html>
