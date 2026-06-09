<?php /** @var array $rows @var array $stats @var int $page @var int $totalPages @var int $total */ ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
  <?php
  $cards = [
      ['users', 'ลูกค้าทั้งหมด', number_format($stats['total']), 'bg-slate-50 text-slate-700'],
      ['user-check', 'ใช้งานอยู่', number_format($stats['active']), 'bg-emerald-50 text-emerald-700'],
      ['user-plus', 'ใหม่ 7 วัน', number_format($stats['new_7d']), 'bg-teal-50 text-teal-700'],
      ['calendar', 'ใหม่ 30 วัน', number_format($stats['new_30d']), 'bg-indigo-50 text-indigo-700'],
  ];
  foreach ($cards as $c): ?>
  <div class="bg-white border border-slate-200 rounded-xl p-4">
    <div class="w-9 h-9 rounded-lg <?= $c[3] ?> grid place-items-center"><i data-lucide="<?= $c[0] ?>" class="w-4 h-4"></i></div>
    <div class="mt-2 text-xl font-extrabold"><?= $c[2] ?></div>
    <div class="text-xs text-slate-500"><?= e($c[1]) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="users" class="w-5 h-5 text-accent-600"></i> ลูกค้า</h2>
      <p class="text-sm text-slate-500">ทั้งหมด <?= number_format($total) ?> รายการ</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2 items-center">
      <input type="text" name="q" placeholder="ค้นหาชื่อ/อีเมล/เบอร์..." value="<?= e($_GET['q'] ?? '') ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
      <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกสถานะ</option>
        <?php foreach (['active' => 'ใช้งาน', 'suspended' => 'ระงับ', 'pending' => 'รอยืนยัน'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($_GET['status'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="period" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกช่วงเวลา</option>
        <option value="7d" <?= ($_GET['period'] ?? '') === '7d' ? 'selected' : '' ?>>สมัคร 7 วันล่าสุด</option>
        <option value="30d" <?= ($_GET['period'] ?? '') === '30d' ? 'selected' : '' ?>>สมัคร 30 วันล่าสุด</option>
      </select>
      <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">กรอง</button>
      <a href="<?= url('/admin/customers/create') ?>" class="px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 shrink-0"><i data-lucide="user-plus" class="w-4 h-4"></i> เพิ่มลูกค้า</a>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">ชื่อ</th>
          <th class="text-left px-5 py-3">ติดต่อ</th>
          <th class="text-left px-5 py-3">จังหวัด</th>
          <th class="text-left px-5 py-3">การจอง</th>
          <th class="text-left px-5 py-3">คูปอง</th>
          <th class="text-left px-5 py-3">สมัครเมื่อ</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php
      $statusLabels = ['active' => 'ใช้งาน', 'suspended' => 'ระงับ', 'pending' => 'รอยืนยัน'];
      $statusColors = ['active' => 'emerald', 'suspended' => 'rose', 'pending' => 'amber'];
      if ($rows === []): ?>
        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">ไม่พบลูกค้า</td></tr>
      <?php else:
      foreach ($rows as $row):
          $st = (string)($row['status'] ?? 'active');
          $c = $statusColors[$st] ?? 'slate';
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-semibold"><?= e($row['name']) ?></td>
          <td class="px-5 py-3 text-xs"><?= e($row['email']) ?><div class="text-slate-500"><?= e($row['phone'] ?? '-') ?></div></td>
          <td class="px-5 py-3"><?= e($row['province'] ?? '-') ?></td>
          <td class="px-5 py-3"><?= (int)($row['booking_count'] ?? 0) ?></td>
          <td class="px-5 py-3"><?= (int)($row['coupon_count'] ?? 0) ?></td>
          <td class="px-5 py-3 text-xs text-slate-600"><?= e(format_date_th($row['created_at'] ?? '')) ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($statusLabels[$st] ?? $st) ?></span></td>
          <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
            <a href="<?= url('/admin/customers/' . (int)$row['id']) ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
            <a href="<?= url('/admin/customers/' . (int)$row['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center gap-1"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="p-4 border-t border-slate-100">
    <?php $q = $_GET; unset($q['page']); \App\Core\View::partial('partials/pagination', ['page' => $page, 'totalPages' => $totalPages, 'baseUrl' => url('/admin/customers'), 'query' => $q]); ?>
  </div>
  <?php endif; ?>
</div>
