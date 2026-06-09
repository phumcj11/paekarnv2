<?php /** @var array $property @var array $units @var array $images */
$unitStatusLabels = ['pending' => 'รออนุมัติ', 'published' => 'เผยแพร่แล้ว', 'rejected' => 'ถูกปฏิเสธ'];
$unitStatusClasses = [
  'pending' => 'bg-amber-100 text-amber-700',
  'published' => 'bg-emerald-100 text-emerald-700',
  'rejected' => 'bg-rose-100 text-rose-700',
];
?>
<a href="<?= url('/admin/properties') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="aspect-[16/5] overflow-hidden bg-slate-100">
    <img src="<?= e(upload_url($property['cover_image'])) ?>" class="w-full h-full object-cover">
  </div>
  <div class="p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-semibold bg-slate-100 px-2 py-0.5 rounded"><?= e($property['type']) ?></span>
          <span class="text-xs font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded"><?= e($property['status']) ?></span>
          <?php if ($property['is_verified']): ?><span class="text-xs font-semibold bg-accent-100 text-accent-700 px-2 py-0.5 rounded">✓ Verified</span><?php endif; ?>
          <?php if ($property['is_featured']): ?><span class="text-xs font-semibold bg-rose-100 text-rose-700 px-2 py-0.5 rounded">⭐ Featured</span><?php endif; ?>
        </div>
        <h1 class="text-2xl font-extrabold mt-1"><?= e($property['name']) ?></h1>
        <p class="text-sm text-slate-500 mt-1"><?= e($property['address']) ?></p>
      </div>
      <div class="flex flex-wrap gap-2">
        <?php if ($property['status'] !== 'published'): ?>
        <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/approve') ?>"><?= csrf() ?>
          <button class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4"></i> Approve</button>
        </form>
        <?php endif; ?>
        <?php if ($property['status'] !== 'rejected'): ?>
        <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/reject') ?>"><?= csrf() ?>
          <button class="px-4 py-2 bg-rose-500 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="x" class="w-4 h-4"></i> Reject</button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/feature') ?>"><?= csrf() ?>
          <button class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="star" class="w-4 h-4"></i> Toggle Feature</button>
        </form>
        <a href="<?= url('/admin/properties/' . $property['id'] . '/units') ?>" class="px-4 py-2 bg-accent-600 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="bed-double" class="w-4 h-4"></i> จัดการหลังพัก</a>
        <a href="<?= url('/admin/properties/' . $property['id'] . '/edit') ?>" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-4 h-4"></i> แก้ไข</a>
        <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ลบที่พักนี้และข้อมูลที่เกี่ยวข้องตามที่ระบบรองรับ — ยืนยัน?');"><?= csrf() ?>
          <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="trash-2" class="w-4 h-4"></i> ลบที่พัก</button>
        </form>
        <a target="_blank" href="<?= url('/property/' . $property['slug']) ?>" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="external-link" class="w-4 h-4"></i> View Public</a>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5 text-sm">
      <?php
      $adminCaps = \App\Support\PropertyBookingCapabilities::fromProperty($property);
      ?>
      <div><div class="text-xs text-slate-500">ปุ่มโทร</div><div class="font-semibold"><?= !empty($adminCaps['allow_contact']) ? 'เปิด' : 'ปิด' ?></div></div>
      <?php if (\App\Core\Database::tableHasColumn('properties', 'show_line_contact')): ?>
      <div><div class="text-xs text-slate-500">ปุ่ม LINE</div><div class="font-semibold"><?= !empty($adminCaps['show_line_contact']) ? 'เปิด' : 'ปิด' ?></div></div>
      <?php endif; ?>
      <div><div class="text-xs text-slate-500">ใช้คูปอง</div><div class="font-semibold"><?= !empty($adminCaps['coupon_enabled']) ? 'เปิด' : 'ปิด' ?></div></div>
      <div><div class="text-xs text-slate-500">จองออนไลน์</div><div class="font-semibold"><?= !empty($adminCaps['allow_online_booking']) ? 'เปิด' : 'ปิด' ?></div></div>
      <div><div class="text-xs text-slate-500">บังคับสลิป</div><div class="font-semibold"><?= !empty($adminCaps['booking_requires_payment']) ? 'เปิด' : 'ปิด' ?></div></div>
      <div><div class="text-xs text-slate-500">Booking Mode (sync)</div><div class="font-semibold"><?= e($property['booking_mode']) ?></div></div>
      <div><div class="text-xs text-slate-500">Priority หน้าแรก</div><div class="font-semibold"><?= (int)($property['priority'] ?? 0) ?></div></div>
      <?php if (array_key_exists('coupon_contract_signed_at', $property) && $property['coupon_contract_signed_at']): ?>
      <div><div class="text-xs text-slate-500">สัญญาคูปองลงนาม</div><div class="font-semibold text-xs"><?= e((string)$property['coupon_contract_signed_at']) ?></div></div>
      <?php endif; ?>
      <div><div class="text-xs text-slate-500">รีวิว</div><div class="font-semibold"><?= $property['rating_count'] ?> · ⭐ <?= number_format($property['rating_avg'],1) ?></div></div>
      <div><div class="text-xs text-slate-500">เข้าชม</div><div class="font-semibold"><?= number_format($property['view_count']) ?></div></div>
      <?php if (!empty($property['raft_variant'])): ?>
      <div><div class="text-xs text-slate-500">ประเภทแพ</div><div class="font-semibold"><?= ($property['raft_variant']==='shore'?'แพริมน้ำ':'แพลาก') ?></div></div>
      <?php endif; ?>
      <?php if (!empty($property['contact_email'])): ?>
      <div class="col-span-2"><div class="text-xs text-slate-500">อีเมล</div><div class="font-semibold break-all"><?= e($property['contact_email']) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($property['website_url'])): ?>
      <div class="col-span-2"><div class="text-xs text-slate-500">เว็บไซต์</div><div class="font-semibold break-all"><?= e($property['website_url']) ?></div></div>
      <?php endif; ?>
    </div>

    <div class="mt-6">
      <h3 class="font-bold mb-2 flex items-center gap-2"><i data-lucide="bed-double" class="w-5 h-5 text-accent-600"></i> ห้องพัก (<?= count($units) ?>)</h3>
      <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-600"><tr>
          <th class="text-left px-3 py-2">ชื่อ</th><th class="text-left px-3 py-2">สถานะ</th><th class="text-left px-3 py-2">รับ</th><th class="text-left px-3 py-2">ราคา</th><th class="text-left px-3 py-2">เสาร์-อา</th><th class="text-left px-3 py-2">วันหยุด</th><th class="text-left px-3 py-2">ตรวจ</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($units as $u): ?>
          <?php $unitStatus = (string)($u['moderation_status'] ?? 'pending'); ?>
          <tr><td class="px-3 py-2 font-medium"><?= e($u['name']) ?></td>
            <td class="px-3 py-2"><span class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?= e($unitStatusClasses[$unitStatus] ?? 'bg-slate-100 text-slate-600') ?>"><?= e($unitStatusLabels[$unitStatus] ?? $unitStatus) ?></span></td>
            <td class="px-3 py-2"><?= $u['capacity_min'] ?>-<?= $u['capacity_max'] ?></td>
            <td class="px-3 py-2"><?= format_money($u['price']) ?></td>
            <td class="px-3 py-2"><?= format_money($u['price_weekend']) ?></td>
            <td class="px-3 py-2"><?= format_money($u['price_holiday']) ?></td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-1">
                <?php if ($unitStatus !== 'published'): ?>
                <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/units/' . $u['id'] . '/approve') ?>"><?= csrf() ?>
                  <button class="px-2.5 py-1 bg-emerald-500 text-white rounded-lg text-xs inline-flex items-center gap-1"><i data-lucide="check" class="w-3 h-3"></i>อนุมัติ</button>
                </form>
                <?php endif; ?>
                <?php if ($unitStatus !== 'rejected'): ?>
                <form method="post" action="<?= url('/admin/properties/' . $property['id'] . '/units/' . $u['id'] . '/reject') ?>"><?= csrf() ?>
                  <button class="px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-xs inline-flex items-center gap-1"><i data-lucide="x" class="w-3 h-3"></i>ปฏิเสธ</button>
                </form>
                <?php endif; ?>
              </div>
            </td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>

    <div class="mt-6">
      <h3 class="font-bold mb-2 flex items-center gap-2"><i data-lucide="image" class="w-5 h-5 text-accent-600"></i> รูปภาพ (<?= count($images) ?>)</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <?php foreach ($images as $img): ?>
          <img src="<?= e(upload_url($img['path'])) ?>" class="aspect-[4/3] object-cover rounded-lg">
        <?php endforeach; ?>
      </div>
    </div>

    <div class="mt-6">
      <h3 class="font-bold mb-2 flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-accent-600"></i> ข้อมูลจากแบบฟอร์มเจ้าของแพ</h3>
      <?php if (\App\Models\Property::decodeOwnerIntake($property['owner_intake'] ?? null) !== []): ?>
        <?php \App\Core\View::partial('partials/property-owner-intake-section', ['property' => $property, 'standalone' => false]); ?>
      <?php else: ?>
        <p class="text-sm text-slate-500">ยังไม่มีข้อมูลในฟิลด์ FAQ โครงสร้าง (owner_intake)</p>
      <?php endif; ?>
    </div>

    <div class="mt-6">
      <h3 class="font-bold mb-2 flex items-center gap-2"><i data-lucide="file-text" class="w-5 h-5 text-accent-600"></i> รายละเอียด</h3>
      <div class="text-sm text-slate-700 whitespace-pre-line bg-slate-50 p-4 rounded-xl"><?= e($property['description']) ?></div>
    </div>
  </div>
</div>
