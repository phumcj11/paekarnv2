<?php
/**
 * @var array[] $properties
 * @var int     $propertyId
 * @var array[] $contacts
 * @var int     $total
 * @var int     $page
 * @var int     $perPage
 * @var string  $q
 * @var array   $allTags       ['tagName' => count]
 * @var string  $filterTag
 * @var array   $filterTags
 * @var string  $filterSegment
 * @var bool    $canBroadcast
 * @var bool    $canAiDraft
 */
$pages    = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
$property = null;
foreach ($properties as $p) {
    if ((int)$p['id'] === $propertyId) { $property = $p; break; }
}

function lineThaiDate(?string $ymd): string {
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    if (!$ts) return '—';
    $diff = (int)floor((time() - $ts) / 86400);
    if ($diff === 0) return 'วันนี้';
    if ($diff === 1) return 'เมื่อวาน';
    if ($diff < 7)  return $diff . ' วันที่แล้ว';
    if ($diff < 30) return (int)floor($diff / 7) . ' สัปดาห์ที่แล้ว';
    if ($diff < 365) return (int)floor($diff / 30) . ' เดือนที่แล้ว';
    return (int)floor($diff / 365) . ' ปีที่แล้ว';
}

$filterTags = $filterTags ?? ($filterTag !== '' ? [$filterTag] : []);
$tagsQuery  = !empty($filterTags) ? implode(',', $filterTags) : '';

$buildUrl = fn(array $extra): string => url('/owner/line-contacts?' . http_build_query(array_filter(
    array_merge([
        'property_id' => $propertyId,
        'q'           => $q,
        'page'        => $page,
        'tags'        => array_key_exists('tags', $extra) ? ($extra['tags'] === '' ? null : $extra['tags']) : ($tagsQuery ?: null),
        'segment'     => $filterSegment,
    ], array_diff_key($extra, ['tags' => 1])),
    fn($v) => $v !== '' && $v !== null && $v !== 0
)));

$toggleTagUrl = static function(string $tag) use ($filterTags, $buildUrl): string {
    $active = in_array($tag, $filterTags, true);
    $next   = $active
        ? array_values(array_filter($filterTags, static fn($t) => $t !== $tag))
        : array_merge($filterTags, [$tag]);
    return $buildUrl(['tags' => implode(',', $next), 'page' => 1]);
};

$tagColors = ['bg-violet-100 text-violet-800', 'bg-sky-100 text-sky-800', 'bg-amber-100 text-amber-800',
              'bg-rose-100 text-rose-800', 'bg-emerald-100 text-emerald-800', 'bg-orange-100 text-orange-800'];
$tagColorFn = function(string $tag) use ($tagColors): string {
    return $tagColors[abs(crc32($tag)) % count($tagColors)];
};

// Preset tags library — ใช้งานบ่อยในธุรกิจที่พัก
$presetTags = [
    '⭐ VIP'          => 'bg-amber-100 text-amber-800',
    '🔄 ลูกค้าประจำ'  => 'bg-emerald-100 text-emerald-800',
    '👥 กรุ๊ปทัวร์'   => 'bg-sky-100 text-sky-800',
    '🎂 วันเกิด'      => 'bg-rose-100 text-rose-800',
    '🔥 ลีดร้อน'      => 'bg-orange-100 text-orange-800',
    '💤 ยังไม่จอง'    => 'bg-slate-100 text-slate-700',
    '✅ จองแล้ว'      => 'bg-violet-100 text-violet-800',
    '📌 ติดตามต่อ'    => 'bg-indigo-100 text-indigo-800',
];
?>

<!-- Property selector + controls -->
<div class="flex flex-wrap items-center gap-3 mb-5">
  <?php if (count($properties) > 1): ?>
  <form method="get" action="<?= url('/owner/line-contacts') ?>" class="flex items-center gap-2">
    <select name="property_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-accent-400 bg-white">
      <?php foreach ($properties as $pr): ?>
        <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $propertyId ? 'selected' : '' ?>>
          <?= e($pr['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($q !== ''): ?>
      <input type="hidden" name="q" value="<?= e($q) ?>">
    <?php endif; ?>
    <?php if ($tagsQuery !== ''): ?>
      <input type="hidden" name="tags" value="<?= e($tagsQuery) ?>">
    <?php endif; ?>
    <?php if ($filterSegment !== ''): ?>
      <input type="hidden" name="segment" value="<?= e($filterSegment) ?>">
    <?php endif; ?>
  </form>
  <?php endif; ?>

  <!-- search -->
  <form method="get" action="<?= url('/owner/line-contacts') ?>" class="flex items-center gap-2 flex-1 min-w-[200px]">
    <input type="hidden" name="property_id" value="<?= $propertyId ?>">
    <?php if ($tagsQuery !== ''): ?>
      <input type="hidden" name="tags" value="<?= e($tagsQuery) ?>">
    <?php endif; ?>
    <?php if ($filterSegment !== ''): ?>
      <input type="hidden" name="segment" value="<?= e($filterSegment) ?>">
    <?php endif; ?>
    <div class="relative flex-1">
      <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อหรือเบอร์..."
             class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-accent-400 bg-white">
    </div>
    <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm rounded-xl transition">ค้นหา</button>
    <?php if ($q !== ''): ?>
      <a href="<?= $buildUrl(['q' => '', 'page' => 1]) ?>" class="px-3 py-2 text-slate-400 hover:text-slate-600 text-sm">✕</a>
    <?php endif; ?>
  </form>

  <!-- stat badge -->
  <?php if ($propertyId): ?>
  <span class="text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1.5 whitespace-nowrap">
    รวม <?= number_format($total) ?> คน
  </span>
  <?php endif; ?>
</div>

<?php if (!empty($allTags)): ?>
<!-- Tag filter strip (multi-select) -->
<div class="flex flex-wrap items-center gap-2 mb-4">
  <span class="text-xs text-slate-500 font-semibold shrink-0">กรองด้วย Tag:</span>
  <a href="<?= $buildUrl(['tags' => '', 'page' => 1]) ?>"
     class="px-2.5 py-1 rounded-full text-xs font-semibold transition <?= empty($filterTags) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
    ทั้งหมด
  </a>
  <?php foreach ($allTags as $tag => $cnt):
    $isActive = in_array($tag, $filterTags, true);
  ?>
  <a href="<?= e($toggleTagUrl($tag)) ?>"
     class="px-2.5 py-1 rounded-full text-xs font-semibold transition <?= $isActive ? 'bg-slate-800 text-white ring-2 ring-slate-400' : $tagColorFn($tag) . ' hover:opacity-80' ?>">
    <?= e($tag) ?> (<?= $cnt ?>)
  </a>
  <?php endforeach; ?>
  <?php if (!empty($filterTags)): ?>
  <span class="text-[10px] text-slate-400 ml-1">เลือก <?= count($filterTags) ?> tag · คลิกซ้ำเพื่อเอาออก</span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Auto-segment filter strip -->
<div class="flex flex-wrap items-center gap-2 mb-4">
  <span class="text-xs text-slate-500 font-semibold shrink-0">Segment:</span>
  <a href="<?= $buildUrl(['segment' => '', 'page' => 1]) ?>"
     class="px-2.5 py-1 rounded-full text-xs font-semibold transition <?= $filterSegment === '' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
    ทั้งหมด
  </a>
  <a href="<?= $buildUrl(['segment' => 'ทักแต่ไม่จอง', 'page' => 1]) ?>"
     class="px-2.5 py-1 rounded-full text-xs font-semibold transition <?= $filterSegment === 'ทักแต่ไม่จอง' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800 hover:opacity-80' ?>">
    ทักแต่ไม่จอง
  </a>
  <a href="<?= $buildUrl(['segment' => 'ลูกค้าเก่า 90+ วัน', 'page' => 1]) ?>"
     class="px-2.5 py-1 rounded-full text-xs font-semibold transition <?= $filterSegment === 'ลูกค้าเก่า 90+ วัน' ? 'bg-violet-700 text-white' : 'bg-violet-100 text-violet-800 hover:opacity-80' ?>">
    ลูกค้าเก่า 90+ วัน
  </a>
</div>

<!-- Preset tag library -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 mb-4"
     x-data="{ open: false }">
  <button type="button" @click="open = !open"
          class="flex items-center gap-2 w-full text-left">
    <i data-lucide="tag" class="w-4 h-4 text-violet-500 shrink-0"></i>
    <span class="text-sm font-semibold text-slate-700">Tag สำเร็จรูป (Preset Tags)</span>
    <span class="ml-auto text-xs text-slate-400" x-text="open ? '▲ ย่อ' : '▼ ดูทั้งหมด'"></span>
  </button>
  <div x-show="open" x-cloak class="mt-3 border-t border-slate-100 pt-3">
    <p class="text-xs text-slate-500 mb-2">คลิก tag เพื่อ copy ชื่อ tag ไว้พิมพ์ในช่อง "+" ของ contact แต่ละราย</p>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($presetTags as $label => $cls): ?>
      <button type="button"
              @click="navigator.clipboard?.writeText(<?= json_encode($label) ?>).then(()=>{ $dispatch('preset-tag-copied', { tag: <?= json_encode($label) ?> }) })"
              x-on:preset-tag-copied.window="$el.textContent = ($event.detail.tag === <?= json_encode($label) ?>) ? '✓ copied!' : $el.textContent; setTimeout(()=>$el.textContent=<?= json_encode($label) ?>, 1200)"
              class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $cls ?> hover:opacity-70 transition cursor-pointer select-none">
        <?= e($label) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <p class="text-[10px] text-slate-400 mt-3">
      <i data-lucide="info" class="inline w-3 h-3 mr-0.5"></i>
      เพิ่ม tag ด้วยการคลิก <strong>+</strong> บนแถว contact แล้ว paste หรือพิมพ์ชื่อ tag
    </p>
  </div>
</div>

<!-- Main content -->
<div x-data="lineContactsPage()" x-init="init()">

  <!-- Action bar: sync + broadcast -->
  <?php if ($propertyId): ?>
  <div class="flex flex-wrap items-center gap-3 mb-4">
    <!-- Sync button -->
    <button type="button" @click="syncContacts()"
            :disabled="syncing"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#06C755] hover:bg-[#04a844] text-white text-sm font-semibold rounded-xl shadow-sm transition disabled:opacity-60">
      <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
      <span x-show="!syncing">↻ ซิงค์รายชื่อจาก LINE</span>
      <span x-show="syncing" class="flex items-center gap-1.5">
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/></svg>
        กำลังซิงค์...
      </span>
    </button>

    <!-- Broadcast button -->
    <?php if ($canBroadcast): ?>
    <button type="button" @click="showBroadcast = !showBroadcast; broadcastTag = ''"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm transition">
      <i data-lucide="send" class="w-4 h-4"></i>
      ส่งทุกคน
    </button>
    <?php if (!empty($allTags)): ?>
    <button type="button" @click="showBroadcast = !showBroadcast; broadcastTag = ''"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-violet-200 hover:bg-violet-50 text-violet-700 text-sm font-semibold rounded-xl shadow-sm transition">
      <i data-lucide="tag" class="w-4 h-4"></i>
      ส่งตาม Tag
    </button>
    <?php endif; ?>
    <?php else: ?>
    <a href="<?= url('/owner/membership') ?>"
       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-violet-200 text-violet-500 text-sm font-semibold rounded-xl shadow-sm cursor-pointer opacity-75 hover:opacity-100 transition"
       title="ต้องใช้แพ็กเกจ Standard ขึ้นไป">
      <i data-lucide="lock" class="w-4 h-4"></i>
      Broadcast (Standard+)
    </a>
    <?php endif; ?>

    <!-- sync result -->
    <p x-show="syncMsg" x-text="syncMsg"
       :class="syncOk ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'"
       class="text-xs rounded-lg px-3 py-1.5 border flex-1 min-w-0 truncate"></p>
  </div>

  <!-- Broadcast panel -->
  <div x-show="showBroadcast" x-cloak
       class="mb-4 bg-white rounded-2xl border border-slate-200 shadow-soft p-4 space-y-3">
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
        <i data-lucide="megaphone" class="w-4 h-4 text-[#06C755]"></i>
        ส่งข้อความ
      </div>
      <?php if (!empty($allTags)): ?>
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500">หา:</span>
        <select x-model="broadcastTag" class="px-2 py-1 rounded-lg border border-slate-200 text-xs focus:border-violet-400 outline-none bg-white">
          <option value="">ทุกคน (<?= number_format($total) ?> คน)</option>
          <?php foreach ($allTags as $tag => $cnt): ?>
          <option value="<?= e($tag) ?>">Tag: <?= e($tag) ?> (<?= $cnt ?> คน)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <span class="text-xs text-slate-400">(<?= number_format($total) ?> คน · เฉพาะที่ยังไม่ Unfollow)</span>
      <?php endif; ?>
    </div>
    <textarea x-model="broadcastText" rows="3" maxlength="2000"
              placeholder="พิมพ์ข้อความที่ต้องการส่ง... &#10;เช่น: สวัสดีครับ มีโปรโมชั่นพิเศษเดือนนี้นะครับ 🎉"
              class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-y focus:border-[#06C755] outline-none"></textarea>

    <?php if ($canAiDraft): ?>
    <!-- AI draft helper -->
    <div class="flex items-center gap-2 flex-wrap">
      <button type="button" @click="aiBroadcastDraft()"
              :disabled="aiBroadcastLoading"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold rounded-lg transition disabled:opacity-60">
        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
        <span x-show="!aiBroadcastLoading">AI ช่วยเขียน</span>
        <span x-show="aiBroadcastLoading">กำลังร่าง…</span>
      </button>
      <input type="text" x-model="aiBroadcastContext" placeholder="หัวข้อ เช่น โปรโมชั่นเดือนหน้า / วันหยุดยาว" maxlength="100"
             class="flex-1 min-w-[180px] px-3 py-1.5 rounded-lg border border-violet-200 text-xs outline-none focus:border-violet-400 bg-violet-50/60 text-slate-700">
      <p x-show="aiBroadcastMsg" x-text="aiBroadcastMsg" class="text-xs text-violet-700 flex-1 truncate"></p>
    </div>
    <?php endif; ?>

    <div class="flex items-center gap-3">
      <button type="button" @click="sendBroadcast()"
              :disabled="broadcasting || !broadcastText.trim()"
              class="inline-flex items-center gap-2 px-5 py-2 bg-[#06C755] text-white text-sm font-semibold rounded-xl disabled:opacity-50 transition">
        <span x-show="!broadcasting">ส่งทั้งหมด</span>
        <span x-show="broadcasting">กำลังส่ง...</span>
      </button>
      <button type="button" @click="showBroadcast=false; broadcastText=''"
              class="text-sm text-slate-500 hover:text-slate-700">ยกเลิก</button>
      <p x-show="broadcastMsg" x-text="broadcastMsg"
         :class="broadcastOk ? 'text-emerald-700' : 'text-rose-600'"
         class="text-xs flex-1 truncate"></p>
    </div>
    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
      ⚠️ ระวัง — ข้อความนี้จะส่งหาลูกค้าทุกคนทันที ไม่สามารถยกเลิกได้
    </p>
  </div>
  <?php endif; ?>

  <!-- Contact list -->
  <?php if (!$propertyId): ?>
    <div class="text-center py-20 text-slate-400">
      <i data-lucide="user-check" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
      <p class="text-sm">เลือกที่พักก่อนเพื่อดูรายชื่อ</p>
    </div>
  <?php elseif (empty($contacts)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-10 text-center text-slate-400">
      <svg class="w-12 h-12 mx-auto mb-3 opacity-30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
      <?php if ($q !== ''): ?>
        <p class="text-sm">ไม่พบ "<?= e($q) ?>" — ลองค้นหาคำอื่น</p>
      <?php else: ?>
        <p class="text-sm mb-3">ยังไม่มีรายชื่อ LINE สำหรับที่พักนี้</p>
        <p class="text-xs leading-relaxed">ให้ลูกค้า <strong>Add เพื่อน OA</strong> หรือทักแชท จากนั้นกด <strong>ซิงค์รายชื่อจาก LINE</strong> ด้านบน</p>
      <?php endif; ?>
    </div>
  <?php else: ?>

    <!-- Desktop table -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600 w-10">#</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">ชื่อ / LINE</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">เบอร์โทร</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Tag</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">ทักล่าสุด</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">การจอง</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">สถานะ</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($contacts as $i => $c):
            $cTags = [];
            if (!empty($c['tags'])) {
                $decoded = json_decode((string)$c['tags'], true);
                if (is_array($decoded)) $cTags = $decoded;
            }
            $cTagsJs = json_encode($cTags, JSON_UNESCAPED_UNICODE);
          ?>
          <tr class="hover:bg-slate-50/60 transition group" x-data="contactRow(<?= (int)$c['id'] ?>, '<?= addslashes($c['phone'] ?? '') ?>', <?= htmlspecialchars($cTagsJs, ENT_QUOTES) ?>)">
            <td class="px-4 py-3 text-slate-400 text-xs"><?= ($page - 1) * $perPage + $i + 1 ?></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <?php if ($c['picture_url']): ?>
                  <img src="<?= e($c['picture_url']) ?>" alt="" class="w-9 h-9 rounded-full object-cover shrink-0">
                <?php else: ?>
                  <div class="w-9 h-9 rounded-full bg-[#06C755]/15 text-[#067a2f] grid place-items-center shrink-0 text-[10px] font-bold">LINE</div>
                <?php endif; ?>
                <div class="min-w-0">
                  <div class="font-semibold text-slate-800 truncate"><?= e($c['display_name'] ?: 'ลูกค้า LINE') ?></div>
                  <div class="text-[10px] text-slate-400 font-mono"><?= e(substr($c['line_user_id'], 0, 20)) ?>…</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <!-- view mode -->
              <div x-show="!editPhone" class="flex items-center gap-1.5">
                <span class="text-slate-700" x-text="phone || '—'"></span>
                <button type="button" @click="editPhone=true; editPhoneVal=phone"
                        class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-accent-600 transition">
                  <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>
              </div>
              <!-- edit mode -->
              <div x-show="editPhone" class="flex items-center gap-1.5">
                <input type="tel" x-model="editPhoneVal" maxlength="20" placeholder="08x-xxx-xxxx"
                       class="w-32 px-2 py-1 rounded-lg border border-accent-300 text-xs outline-none focus:border-accent-500"
                       @keyup.enter="savePhone()" @keyup.escape="editPhone=false">
                <button type="button" @click="savePhone()" class="text-emerald-600 hover:text-emerald-700">
                  <i data-lucide="check" class="w-4 h-4"></i>
                </button>
                <button type="button" @click="editPhone=false" class="text-slate-400 hover:text-slate-600">
                  <i data-lucide="x" class="w-4 h-4"></i>
                </button>
              </div>
              <p x-show="phoneSaved" class="text-[10px] text-emerald-600">บันทึกแล้ว ✓</p>
            </td>
            <!-- Tags cell -->
            <td class="px-4 py-3 max-w-[160px]">
              <div class="flex flex-wrap gap-1 items-center">
                <template x-for="tag in tags" :key="tag">
                  <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-100 text-violet-800">
                    <span x-text="tag"></span>
                    <button type="button" @click="removeTag(tag)" class="ml-0.5 opacity-60 hover:opacity-100">×</button>
                  </span>
                </template>
                <!-- add tag input -->
                <div x-show="!addingTag" class="opacity-0 group-hover:opacity-100 transition flex items-center gap-1">
                  <button type="button" @click="addingTag=true; $nextTick(()=>$el.closest('td').querySelector('input.tag-input')?.focus())"
                          class="w-5 h-5 rounded-full border border-dashed border-slate-300 text-slate-400 hover:border-violet-400 hover:text-violet-600 text-xs grid place-items-center transition">+</button>
                  <!-- preset quick-add -->
                  <div class="relative">
                    <button type="button" @click="showPresets = !showPresets"
                            class="w-5 h-5 rounded-full border border-dashed border-teal-300 text-teal-400 hover:border-teal-500 hover:text-teal-600 text-[10px] grid place-items-center transition"
                            title="Tag สำเร็จรูป">⚡</button>
                    <div x-show="showPresets" x-cloak @click.outside="showPresets=false"
                         class="absolute left-0 top-6 z-20 bg-white rounded-xl border border-slate-200 shadow-lg p-2 w-44 flex flex-wrap gap-1">
                      <?php foreach ($presetTags as $pLabel => $pCls): ?>
                      <button type="button"
                              @click="addPresetTag(<?= json_encode($pLabel) ?>)"
                              class="px-1.5 py-0.5 rounded-full text-[9px] font-semibold <?= $pCls ?> hover:opacity-70 transition">
                        <?= e($pLabel) ?>
                      </button>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div x-show="addingTag" class="flex items-center gap-1">
                  <input type="text" x-model="newTagVal" maxlength="30" placeholder="tag..."
                         class="tag-input w-20 px-1.5 py-0.5 rounded-lg border border-violet-300 text-[11px] outline-none focus:border-violet-500"
                         @keyup.enter="addTag()" @keyup.escape="addingTag=false; newTagVal=''">
                  <button type="button" @click="addTag()" class="text-violet-600 hover:text-violet-700 text-xs">✓</button>
                  <button type="button" @click="addingTag=false; newTagVal=''" class="text-slate-400 text-xs">✕</button>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs"><?= lineThaiDate($c['last_seen_at']) ?></td>
            <td class="px-4 py-3">
              <?php if ((int)$c['booking_count'] > 0): ?>
                <a href="<?= url('/owner/bookings?' . http_build_query(['line_uid' => $c['line_user_id']])) ?>"
                   class="inline-flex items-center gap-1 text-xs text-accent-700 font-semibold hover:underline">
                  <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                  <?= (int)$c['booking_count'] ?> จอง
                </a>
                <?php if ($c['last_booking_date']): ?>
                  <div class="text-[10px] text-slate-400 mt-0.5"><?= lineThaiDate($c['last_booking_date']) ?></div>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-slate-400 text-xs">—</span>
              <?php endif; ?>
              <?php if ($c['auto_segment'] ?? ''): ?>
                <div class="mt-1">
                  <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold
                    <?= $c['auto_segment'] === 'ทักแต่ไม่จอง' ? 'bg-amber-100 text-amber-800' : 'bg-violet-100 text-violet-800' ?>">
                    <?= e($c['auto_segment']) ?>
                  </span>
                </div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3">
              <?php if ($c['unfollowed_at']): ?>
                <span class="inline-flex items-center gap-1 text-xs text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Unfollow</span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">ติดตาม</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <?php if ($canAiDraft): ?>
                <button type="button" @click="fetchAiReply()" :disabled="aiLoading"
                        title="AI ช่วยร่างข้อความ"
                        class="inline-flex items-center gap-1 px-2 py-1.5 bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold rounded-lg transition disabled:opacity-60">
                  <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                  <span x-show="!aiLoading" class="hidden sm:inline">AI</span>
                  <span x-show="aiLoading">…</span>
                </button>
                <?php endif; ?>
                <button type="button" @click="openMsg()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#06C755]/10 hover:bg-[#06C755]/20 text-[#067a2f] text-xs font-semibold rounded-lg transition">
                  <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> ส่งข้อความ
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile cards -->
    <div class="md:hidden space-y-3">
      <?php foreach ($contacts as $c): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4"
           x-data="contactRow(<?= (int)$c['id'] ?>, '<?= addslashes($c['phone'] ?? '') ?>')">
        <div class="flex items-center gap-3 mb-3">
          <?php if ($c['picture_url']): ?>
            <img src="<?= e($c['picture_url']) ?>" alt="" class="w-11 h-11 rounded-full object-cover shrink-0">
          <?php else: ?>
            <div class="w-11 h-11 rounded-full bg-[#06C755]/15 text-[#067a2f] grid place-items-center shrink-0 text-xs font-bold">LINE</div>
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-slate-800"><?= e($c['display_name'] ?: 'ลูกค้า LINE') ?></div>
            <div class="flex items-center gap-2 mt-0.5">
              <?php if ($c['unfollowed_at']): ?>
                <span class="text-[10px] text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-full">Unfollow</span>
              <?php else: ?>
                <span class="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-full">ติดตาม</span>
              <?php endif; ?>
              <span class="text-[10px] text-slate-400"><?= lineThaiDate($c['last_seen_at']) ?></span>
            </div>
          </div>
          <div class="shrink-0 flex flex-col items-end gap-1">
            <?php if ((int)$c['booking_count'] > 0): ?>
            <a href="<?= url('/owner/bookings?' . http_build_query(['line_uid' => $c['line_user_id']])) ?>"
               class="text-center">
              <div class="text-base font-bold text-accent-700"><?= (int)$c['booking_count'] ?></div>
              <div class="text-[10px] text-slate-500">จอง</div>
            </a>
            <?php endif; ?>
            <?php if ($c['auto_segment'] ?? ''): ?>
            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold
              <?= $c['auto_segment'] === 'ทักแต่ไม่จอง' ? 'bg-amber-100 text-amber-800' : 'bg-violet-100 text-violet-800' ?>">
              <?= e($c['auto_segment']) ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <!-- phone -->
        <div class="flex items-center gap-2 mb-3">
          <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
          <div x-show="!editPhone" class="flex items-center gap-1.5 flex-1">
            <span class="text-sm text-slate-700" x-text="phone || 'ยังไม่มีเบอร์'"></span>
            <button type="button" @click="editPhone=true; editPhoneVal=phone" class="text-slate-400 hover:text-accent-600">
              <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
            </button>
          </div>
          <div x-show="editPhone" class="flex items-center gap-1.5 flex-1">
            <input type="tel" x-model="editPhoneVal" maxlength="20" placeholder="08x-xxx-xxxx"
                   class="flex-1 px-2 py-1 rounded-lg border border-accent-300 text-sm outline-none"
                   @keyup.enter="savePhone()" @keyup.escape="editPhone=false">
            <button type="button" @click="savePhone()" class="text-emerald-600"><i data-lucide="check" class="w-4 h-4"></i></button>
            <button type="button" @click="editPhone=false" class="text-slate-400"><i data-lucide="x" class="w-4 h-4"></i></button>
          </div>
        </div>
        <!-- send msg + AI -->
        <div class="flex gap-2">
          <?php if ($canAiDraft): ?>
          <button type="button" @click="fetchAiReply()" :disabled="aiLoading"
                  class="flex-none flex items-center gap-1.5 px-3 py-2 bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold rounded-xl transition disabled:opacity-60">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
            <span x-show="!aiLoading">AI</span><span x-show="aiLoading">…</span>
          </button>
          <?php endif; ?>
          <button type="button" @click="openMsg()"
                  class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-[#06C755]/10 hover:bg-[#06C755]/15 text-[#067a2f] text-sm font-semibold rounded-xl transition">
            <i data-lucide="message-circle" class="w-4 h-4"></i> ส่งข้อความ LINE
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-6 flex-wrap">
      <?php if ($page > 1): ?>
        <a href="<?= $buildUrl(['page' => $page - 1]) ?>"
           class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50 transition">‹ ก่อนหน้า</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <a href="<?= $buildUrl(['page' => $i]) ?>"
           class="px-3 py-1.5 rounded-lg text-sm transition <?= $i === $page ? 'bg-accent-600 text-white shadow-sm' : 'bg-white border border-slate-200 hover:bg-slate-50' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
        <a href="<?= $buildUrl(['page' => $page + 1]) ?>"
           class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50 transition">ถัดไป ›</a>
      <?php endif; ?>
      <span class="text-xs text-slate-400 ml-2">หน้า <?= $page ?>/<?= $pages ?></span>
    </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<!-- Send message modal (shared) -->
<div x-data="lineContactsPage()" style="display:none" id="line-msg-modal-host"></div>
<template id="line-msg-modal-tpl">
  <!-- อยู่ใน contactRow component แต่ละ row -->
</template>

<!-- Per-row send message sheet (mounted globally) -->
<div x-data="msgSheet()" x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-slate-900/40"
     @click.self="show=false">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 space-y-4">
    <div class="flex items-center gap-3">
      <svg class="w-6 h-6 text-[#06C755] shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
      <div>
        <div class="font-semibold text-slate-800" x-text="name"></div>
        <div class="text-xs text-slate-500">ส่งข้อความ LINE โดยตรง</div>
      </div>
    </div>
    <textarea x-model="text" rows="4" maxlength="2000"
              placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
              class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-y focus:border-[#06C755] outline-none"></textarea>
    <div class="flex items-center gap-3">
      <button type="button" @click="send()"
              :disabled="sending || !text.trim()"
              class="flex-1 py-2.5 bg-[#06C755] hover:bg-[#04a844] text-white font-semibold rounded-xl transition disabled:opacity-50">
        <span x-show="!sending">ส่งข้อความ</span>
        <span x-show="sending">กำลังส่ง...</span>
      </button>
      <button type="button" @click="show=false; text=''; result=''"
              class="px-4 py-2.5 text-slate-500 hover:text-slate-700 text-sm">ยกเลิก</button>
    </div>
    <p x-show="result" x-text="result"
       :class="resultOk ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'"
       class="text-xs rounded-lg px-3 py-2 text-center"></p>
  </div>
</div>

<script>
const __LC_PROP_ID__ = <?= (int)$propertyId ?>;
const __LC_SYNC_URL__ = '<?= url('/owner/api/line-contacts/sync') ?>';
const __LC_MSG_URL__  = '<?= url('/owner/line-contacts') ?>';
const __CSRF__ = window.__PAEKAN_CSRF__ || document.querySelector('meta[name="csrf-token"]')?.content || '';

function lineContactsPage() {
  return {
    syncing: false,
    syncMsg: '',
    syncOk: true,
    showBroadcast: false,
    broadcastText: '',
    broadcastTag: '',
    broadcasting: false,
    broadcastMsg: '',
    broadcastOk: true,
    aiBroadcastLoading: false,
    aiBroadcastContext: '',
    aiBroadcastMsg: '',

    init() {
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async syncContacts() {
      if (this.syncing || !__LC_PROP_ID__) return;
      this.syncing = true;
      this.syncMsg = '';
      try {
        const fd = new FormData();
        fd.append('_csrf', __CSRF__);
        const r = await fetch(__LC_SYNC_URL__ + '?property_id=' + __LC_PROP_ID__, { method: 'POST', body: fd });
        const d = await r.json();
        this.syncOk = !!d.ok;
        this.syncMsg = d.ok
          ? `ซิงค์สำเร็จ — นำเข้าใหม่ ${d.imported} คน, อัปเดต ${d.skipped} คน · รีเฟรชหน้าเพื่อดูผล`
          : ('ซิงค์ไม่สำเร็จ: ' + (d.error || 'ไม่ทราบสาเหตุ'));
        if (d.ok && (d.imported > 0)) {
          setTimeout(() => location.reload(), 1500);
        }
      } catch(e) { this.syncOk = false; this.syncMsg = 'เกิดข้อผิดพลาด'; }
      this.syncing = false;
    },

    async aiBroadcastDraft() {
      if (this.aiBroadcastLoading || !__LC_PROP_ID__) return;
      this.aiBroadcastLoading = true;
      this.aiBroadcastMsg = '';
      try {
        const fd = new FormData();
        fd.set('_csrf', __CSRF__);
        fd.set('property_id', String(__LC_PROP_ID__));
        fd.set('event_type', 'broadcast');
        fd.set('context', this.aiBroadcastContext || 'ข้อความทั่วไปถึงลูกค้า LINE');
        const r = await fetch('<?= url('/owner/automation/ai-draft') ?>', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok && d.text) {
          this.broadcastText = d.text;
          this.aiBroadcastMsg = '✨ ร่างแล้ว — แก้ไขก่อนส่งได้';
          setTimeout(() => this.aiBroadcastMsg = '', 3000);
        } else {
          this.aiBroadcastMsg = d.error || 'AI ไม่พร้อม';
        }
      } catch(e) { this.aiBroadcastMsg = 'เกิดข้อผิดพลาด'; }
      this.aiBroadcastLoading = false;
    },

    async sendBroadcast() {
      if (this.broadcasting || !this.broadcastText.trim()) return;
      const confirmMsg = `ยืนยันส่งข้อความหา <?= $total ?> คน ใช่ไหม?`;
      if (!confirm(confirmMsg)) return;
      this.broadcasting = true;
      this.broadcastMsg = '';
      try {
        const fd = new FormData();
        fd.set('_csrf', __CSRF__);
        fd.set('property_id', String(__LC_PROP_ID__));
        fd.set('text', this.broadcastText);
        if (this.broadcastTag) fd.set('tag', this.broadcastTag);
        const r = await fetch('<?= url('/owner/line-contacts/broadcast') ?>', { method: 'POST', body: fd });
        const d = await r.json();
        this.broadcastOk = !!d.ok;
        this.broadcastMsg = d.ok
          ? `ส่งสำเร็จ ${d.sent}/${d.total ?? d.sent} คน${d.failed > 0 ? ` · ล้มเหลว ${d.failed} คน` : ''}`
          : ('ส่งไม่สำเร็จ: ' + (d.error || 'ไม่ทราบสาเหตุ'));
      } catch(e) { this.broadcastOk = false; this.broadcastMsg = 'เกิดข้อผิดพลาด'; }
      this.broadcasting = false;
    },
  };
}

// ── Per-contact row ──────────────────────────────────
function contactRow(id, initialPhone, initialTags) {
  return {
    id,
    phone: initialPhone,
    editPhone: false,
    editPhoneVal: initialPhone,
    phoneSaved: false,
    tags: Array.isArray(initialTags) ? [...initialTags] : [],
    addingTag: false,
    newTagVal: '',
    showPresets: false,
    aiLoading: false,
    aiText: '',

    async savePhone() {
      try {
        const fd = new FormData();
        fd.set('_csrf', __CSRF__);
        fd.set('phone', this.editPhoneVal);
        const r = await fetch(`<?= url('/owner/line-contacts') ?>/${this.id}/phone`, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.phone = d.phone;
          this.editPhone = false;
          this.phoneSaved = true;
          setTimeout(() => this.phoneSaved = false, 2000);
        }
      } catch(e) {}
    },

    async addTag() {
      const tag = this.newTagVal.trim();
      if (!tag || this.tags.includes(tag)) { this.addingTag = false; this.newTagVal = ''; return; }
      const newTags = [...this.tags, tag];
      await this._saveTags(newTags);
    },

    async addPresetTag(tag) {
      if (!tag || this.tags.includes(tag)) return;
      this.showPresets = false;
      await this._saveTags([...this.tags, tag]);
    },

    async fetchAiReply() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      this.aiText = '';
      try {
        const r = await fetch(`<?= url('/owner/line-contacts') ?>/${this.id}/ai-reply`);
        const d = await r.json();
        if (d.ok && d.text) {
          this.aiText = d.text;
          // pre-populate the send message modal if available
          window._msgSheet?.openSheet(this.id,
            this.$el.querySelector('.font-semibold.text-slate-800')?.textContent?.trim() || 'ลูกค้า',
            this.aiText);
        } else {
          alert(d.error || 'AI ไม่พร้อมใช้งาน');
        }
      } catch(e) { alert('เกิดข้อผิดพลาด'); }
      this.aiLoading = false;
    },

    async removeTag(tag) {
      const newTags = this.tags.filter(t => t !== tag);
      await this._saveTags(newTags);
    },

    async _saveTags(newTags) {
      try {
        const fd = new FormData();
        fd.set('_csrf', __CSRF__);
        fd.set('tags', JSON.stringify(newTags));
        const r = await fetch(`<?= url('/owner/line-contacts') ?>/${this.id}/tags`, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.tags = d.tags;
          this.addingTag = false;
          this.newTagVal = '';
        }
      } catch(e) {}
    },

    openMsg() {
      const name = this.$el.querySelector('.font-semibold.text-slate-800')?.textContent?.trim() || 'ลูกค้า LINE';
      window._msgSheet?.openSheet(this.id, name);
    },
  };
}

// ── Global send-message sheet ────────────────────────
function msgSheet() {
  return {
    show: false,
    contactId: 0,
    name: '',
    text: '',
    sending: false,
    result: '',
    resultOk: true,

    init() {
      window._msgSheet = this;
    },

    openSheet(id, name) {
      this.contactId = id;
      this.name = name;
      this.text = '';
      this.result = '';
      this.show = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async send() {
      if (this.sending || !this.text.trim()) return;
      this.sending = true;
      this.result  = '';
      try {
        const fd = new FormData();
        fd.set('_csrf', __CSRF__);
        fd.set('text', this.text);
        const r = await fetch(`<?= url('/owner/line-contacts') ?>/${this.contactId}/message`, { method: 'POST', body: fd });
        const d = await r.json();
        this.resultOk = !!d.ok;
        this.result   = d.ok ? 'ส่งสำเร็จ ✓' : ('ส่งไม่สำเร็จ: ' + (d.error || 'ไม่ทราบ'));
        if (d.ok) { this.text = ''; setTimeout(() => { this.show = false; this.result = ''; }, 1500); }
      } catch(e) { this.resultOk = false; this.result = 'เกิดข้อผิดพลาด'; }
      this.sending = false;
    },
  };
}
</script>
