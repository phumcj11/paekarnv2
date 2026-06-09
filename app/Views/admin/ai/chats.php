<?php /** @var array $sessions */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="message-square" class="w-5 h-5 text-purple-600"></i> AI Chat Sessions</h3>
    <p class="text-sm text-slate-500 mt-1">ประวัติบทสนทนากับน้องแพ — ใช้ดู insight ลูกค้า</p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">Session</th>
          <th class="text-left px-4 py-3">คำถามแรก</th>
          <th class="text-left px-4 py-3">จำนวนข้อความ</th>
          <th class="text-left px-4 py-3">ล่าสุด</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($sessions as $s): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-2 font-mono text-xs text-slate-600"><?= e(substr($s['session_id'],0,18)) ?>…</td>
          <td class="px-4 py-2 max-w-md truncate"><?= e($s['first_msg'] ?? '-') ?></td>
          <td class="px-4 py-2 text-xs"><?= $s['msgs'] ?></td>
          <td class="px-4 py-2 text-xs text-slate-500"><?= time_ago($s['last_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($sessions)): ?><tr><td colspan="4" class="text-center py-10 text-slate-500">ยังไม่มีบทสนทนา</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
