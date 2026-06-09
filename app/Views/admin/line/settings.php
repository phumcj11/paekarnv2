<?php /** @var string $enabled @var string $token @var string $secret @var string $loginId @var string $loginSec
 *  @var string $friendUrl @var string $adminUid @var string $webhookUrl @var string $callbackUrl @var array $hooks @var int $linkedUsers */ ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <form method="post" action="<?= url('/admin/line') ?>" class="lg:col-span-2 space-y-4">
    <?= csrf() ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg flex items-center gap-2"><span class="w-7 h-7 rounded-full bg-[#06C755] grid place-items-center text-white text-sm font-bold">L</span> LINE Messaging API (OA)</h3>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="line_enabled" value="1" <?= $enabled?'checked':'' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-300 peer-checked:bg-[#06C755] rounded-full relative transition">
            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full peer-checked:translate-x-5 transition"></div>
          </div>
          <span class="text-sm font-medium">เปิดใช้งาน</span>
        </label>
      </div>
      <div class="space-y-3">
        <div>
          <label class="text-sm font-medium mb-1 block">Channel Access Token (long-lived)</label>
          <input name="line_channel_access_token" value="<?= e($token) ?>" type="password" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Channel Secret</label>
          <input name="line_channel_secret" value="<?= e($secret) ?>" type="password" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">LINE Friend URL (lin.ee)</label>
          <input name="line_friend_url" value="<?= e($friendUrl) ?>" placeholder="https://lin.ee/xxxxxx" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Admin LINE User ID (สำหรับรับแจ้งเตือน)</label>
          <input name="line_admin_user_id" value="<?= e($adminUid) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs">
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs">
          <div class="font-semibold mb-1">📡 Webhook URL — ตั้งใน LINE Developers Console:</div>
          <code class="block bg-white p-2 rounded border border-slate-300 break-all"><?= e($webhookUrl) ?></code>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
      <h3 class="font-bold text-lg flex items-center gap-2 mb-4"><i data-lucide="log-in" class="w-5 h-5 text-[#06C755]"></i> LINE Login (link account)</h3>
      <div class="space-y-3">
        <div>
          <label class="text-sm font-medium mb-1 block">Login Channel ID</label>
          <input name="line_login_channel_id" value="<?= e($loginId) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Login Channel Secret</label>
          <input name="line_login_channel_secret" value="<?= e($loginSec) ?>" type="password" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs">
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs">
          <div class="font-semibold mb-1">↩ Callback URL — ตั้งใน LINE Login Channel:</div>
          <code class="block bg-white p-2 rounded border border-slate-300 break-all"><?= e($callbackUrl) ?></code>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl">บันทึก</button>
    </div>
  </form>

  <aside class="space-y-4">
    <div class="bg-gradient-to-br from-[#06C755] to-emerald-700 text-white rounded-2xl shadow-soft p-5">
      <div class="text-xs uppercase opacity-80">User ที่ผูก LINE แล้ว</div>
      <div class="text-3xl font-bold mt-1"><?= number_format($linkedUsers) ?></div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <h4 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="send" class="w-5 h-5 text-[#06C755]"></i> ทดสอบส่ง</h4>
      <form method="post" action="<?= url('/admin/line/test') ?>" class="space-y-2">
        <?= csrf() ?>
        <input name="user_id" placeholder="LINE userId (Uxxxx...)" value="<?= e($adminUid) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono">
        <textarea name="message" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">🧪 ทดสอบจากแพกาญ.com</textarea>
        <button class="w-full px-3 py-2 bg-[#06C755] hover:bg-[#05a847] text-white rounded-lg text-sm font-semibold">ทดสอบส่ง</button>
      </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <h4 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="activity" class="w-4 h-4 text-amber-500"></i> Webhook Log (30 ล่าสุด)</h4>
      <div class="space-y-1.5 max-h-72 overflow-y-auto text-xs">
        <?php foreach ($hooks as $h): ?>
          <div class="flex items-center justify-between p-2 bg-slate-50 rounded">
            <div>
              <div class="font-mono"><?= e($h['event_type'] ?? 'unknown') ?></div>
              <div class="text-[10px] text-slate-500"><?= time_ago($h['created_at']) ?></div>
            </div>
            <div class="flex gap-1">
              <?php if ($h['signature_ok']): ?><span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 rounded">SIG OK</span><?php else: ?><span class="text-[10px] bg-rose-100 text-rose-700 px-1.5 rounded">BAD SIG</span><?php endif; ?>
              <?php if ($h['processed']): ?><span class="text-[10px] bg-accent-100 text-accent-700 px-1.5 rounded">DONE</span><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($hooks)): ?><div class="text-center py-4 text-slate-400">ยังไม่มี webhook</div><?php endif; ?>
      </div>
    </div>
  </aside>
</div>
