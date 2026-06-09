<?php /** @var string $page_title */ ?>
<div class="max-w-lg mx-auto space-y-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h1 class="font-bold flex items-center gap-2 text-lg"><i data-lucide="camera" class="w-6 h-6 text-accent-600"></i> <?= e($page_title) ?></h1>
    <p class="text-sm text-slate-500 mt-1">จัด QR ให้อยู่ในกรอบ — ระบบจะส่งไปหน้าตรวจคูปองอัตโนมัติ</p>

    <div id="qr-reader" class="mt-4 rounded-xl overflow-hidden bg-slate-900 min-h-[260px]"></div>

    <form id="scan-form" method="post" action="<?= url('/owner/coupons/scan-resolve') ?>" class="hidden">
      <?= csrf() ?>
      <input type="hidden" name="raw" id="scan-raw" value="">
    </form>

    <p id="scan-status" class="text-xs text-slate-500 mt-3 text-center">กำลังเปิดกล้อง…</p>

    <div class="mt-4 flex gap-2 justify-center">
      <a href="<?= url('/owner/coupons/verify') ?>" class="text-sm text-primary-700 font-semibold hover:underline">กลับไปกรอกรหัสเอง</a>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function(){
  const statusEl = document.getElementById('scan-status');
  const form = document.getElementById('scan-form');
  const rawInput = document.getElementById('scan-raw');
  let submitted = false;
  function submitRaw(text) {
    if (submitted) return;
    submitted = true;
    rawInput.value = text.trim();
    statusEl.textContent = 'อ่านสำเร็จ — กำลังไปหน้าตรวจคูปอง…';
    form.submit();
  }
  if (!window.Html5Qrcode) {
    statusEl.textContent = 'โหลดสคริปต์สแกนไม่ได้ — ใช้การกรอกรหัสแทน';
    return;
  }
  const scanner = new Html5Qrcode('qr-reader');
  const config = { fps: 10, qrbox: { width: 240, height: 240 } };
  scanner.start(
    { facingMode: 'environment' },
    config,
    function(decodedText) {
      submitRaw(decodedText);
      scanner.stop().catch(function(){});
    },
    function() {}
  ).catch(function() {
    scanner.start(
      { facingMode: 'user' },
      config,
      function(decodedText) {
        submitRaw(decodedText);
        scanner.stop().catch(function(){});
      },
      function() {}
    ).catch(function(err) {
      statusEl.textContent = 'เปิดกล้องไม่ได้: ' + (err && err.message ? err.message : 'ตรวจสิทธิ์เบราว์เซอร์');
    });
  }).then(function() {
    statusEl.textContent = 'สแกนได้เลย';
  });
})();
</script>
