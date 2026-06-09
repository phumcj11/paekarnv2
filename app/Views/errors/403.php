<div class="max-w-3xl mx-auto px-4 py-24 text-center">
  <div class="text-7xl mb-2">🚫</div>
  <div class="text-7xl font-extrabold text-rose-600">403</div>
  <h1 class="mt-2 text-2xl font-bold">ไม่มีสิทธิ์เข้าถึง</h1>
  <p class="mt-2 text-slate-600"><?= e($message ?? 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้') ?></p>
  <a href="<?= url('/') ?>" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold">
    <i data-lucide="home" class="w-4 h-4"></i> กลับหน้าแรก
  </a>
</div>
