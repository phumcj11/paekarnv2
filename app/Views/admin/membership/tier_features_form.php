<?php
/** @var array $config @var array|null $servicePerks */
use App\Services\OwnerTier;

$config = $config ?? OwnerTier::featuresConfig();
$serviceCfg = $servicePerks ?? OwnerTier::servicePerksConfig();
$rows = OwnerTier::comparisonRows();
$tierCols = [
    'none'     => ['label' => 'ฟรี', 'head' => 'bg-slate-100 text-slate-700'],
    'standard' => ['label' => 'Standard', 'head' => 'bg-sky-100 text-sky-800'],
    'vip'      => ['label' => 'VIP', 'head' => 'bg-amber-100 text-amber-900'],
];
$csrfToken = \App\Core\Csrf::token();
?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden"
     x-data="tierFeaturesForm('<?= e($csrfToken) ?>')">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-start justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="table-2" class="w-5 h-5 text-primary-600"></i> เปรียบเทียบสิทธิ์แต่ละระดับ</h2>
      <p class="text-sm text-slate-600 mt-1">ฟีเจอร์ในระบบ: ติ๊กเพื่อเปิด/ปิดในโค้ด · สิทธิ์บริการ: แสดงในตารางและติดตามการมอบให้ manual</p>
    </div>
    <button type="button" @click="save()" :disabled="saving"
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2">
      <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="saving"></i>
      <i data-lucide="save" class="w-4 h-4" x-show="!saving"></i>
      <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกสิทธิ์'"></span>
    </button>
  </div>

  <div x-show="msg" x-cloak
       :class="ok ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
       class="mx-5 mt-4 px-3 py-2 rounded-lg border text-sm" x-text="msg"></div>

  <div class="px-5 pt-4 pb-1">
    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i data-lucide="cpu" class="w-4 h-4 text-emerald-600"></i> ฟีเจอร์ในระบบ</h3>
    <p class="text-xs text-slate-500 mt-0.5">มีผลกับเมนูและฟังก์ชันใน owner portal ทันที</p>
  </div>
  <div class="overflow-x-auto p-5 pt-3">
    <table class="w-full text-sm min-w-[560px]">
      <thead>
        <tr class="text-xs uppercase">
          <th class="text-left px-3 py-2.5 text-slate-600 font-semibold">ฟีเจอร์</th>
          <?php foreach ($tierCols as $col): ?>
            <th class="text-center px-3 py-2.5 font-semibold rounded-t-lg <?= $col['head'] ?>"><?= e($col['label']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rows as $row): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-3 py-2.5 text-slate-700"><?= e($row['label']) ?></td>
          <?php foreach (array_keys($tierCols) as $tierKey): ?>
          <td class="px-3 py-2.5 text-center">
            <?php if (!empty($row['base_property'])): ?>
              <label class="inline-flex items-center justify-center cursor-pointer">
                <input type="checkbox"
                       x-model="baseProperty['<?= $tierKey ?>']"
                       class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
              </label>
            <?php elseif (($row['feature'] ?? '') === OwnerTier::FEATURE_AVAILABLE_BOOST): ?>
              <?php if ($tierKey === 'none'): ?>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400"><i data-lucide="minus" class="w-3.5 h-3.5"></i></span>
              <?php else: ?>
                <input type="number" min="0" max="100" step="1"
                       x-model.number="boost['<?= $tierKey ?>']"
                       class="w-14 px-1 py-1 text-center rounded border border-slate-300 focus:border-primary-500 outline-none"
                       title="คะแนน boost (0 = ปิด)">
              <?php endif; ?>
            <?php elseif ($tierKey === 'none' && isset($row['feature'])): ?>
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400"><i data-lucide="minus" class="w-3.5 h-3.5"></i></span>
            <?php elseif (isset($row['feature'])): ?>
              <label class="inline-flex items-center justify-center cursor-pointer">
                <input type="checkbox"
                       x-model="features['<?= $tierKey ?>']['<?= e($row['feature']) ?>']"
                       class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
              </label>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="px-5 pb-4 text-xs text-slate-500">แถว Boost หน้าแพว่าง: ใส่ตัวเลข (+20 / +30) — ใส่ 0 เพื่อปิด · tier ฟรีไม่มีฟีเจอร์เสริม (ยกเว้นจัดการที่พักถ้าเปิด)</p>

  <div class="border-t border-slate-100 px-5 pt-4 pb-1 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i data-lucide="gift" class="w-4 h-4 text-amber-600"></i> สิทธิ์บริการ (ติดตาม manual)</h3>
      <p class="text-xs text-slate-500 mt-0.5">แสดงในตารางเปรียบเทียบและหน้าสมาชิก — แอดมินติ๊ก «ให้แล้ว» ต่อ owner</p>
    </div>
    <button type="button" @click="addServicePerk()"
            class="px-3 py-1.5 rounded-lg border border-dashed border-amber-300 text-amber-800 text-xs font-semibold hover:bg-amber-50 inline-flex items-center gap-1">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> เพิ่มสิทธิ์บริการ
    </button>
  </div>
  <div class="overflow-x-auto p-5 pt-3">
    <table class="w-full text-sm min-w-[560px]">
      <thead>
        <tr class="text-xs uppercase">
          <th class="text-left px-3 py-2.5 text-slate-600 font-semibold">สิทธิ์บริการ</th>
          <?php foreach ($tierCols as $col): ?>
            <th class="text-center px-3 py-2.5 font-semibold rounded-t-lg <?= $col['head'] ?>"><?= e($col['label']) ?></th>
          <?php endforeach; ?>
          <th class="w-10"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <template x-for="(perk, idx) in servicePerks" :key="perk.key || idx">
          <tr class="hover:bg-slate-50/80">
            <td class="px-3 py-2">
              <input type="text" x-model="perk.label" placeholder="ชื่อสิทธิ์ เช่น ปรึกษาการตลาด 1:1"
                     class="w-full min-w-[180px] px-2 py-1.5 rounded border border-slate-300 text-sm focus:border-primary-500 outline-none">
            </td>
            <template x-for="tier in ['none','standard','vip']" :key="tier">
              <td class="px-3 py-2 text-center">
                <label class="inline-flex items-center justify-center cursor-pointer">
                  <input type="checkbox" x-model="perk[tier]"
                         class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                </label>
              </td>
            </template>
            <td class="px-2 py-2 text-center">
              <button type="button" @click="removeServicePerk(idx)" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="ลบแถว">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
              </button>
            </td>
          </tr>
        </template>
        <tr x-show="servicePerks.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-slate-400 text-sm">ยังไม่มีสิทธิ์บริการ — กด «เพิ่มสิทธิ์บริการ»</td>
        </tr>
      </tbody>
    </table>
  </div>
  <p class="px-5 pb-5 text-xs text-slate-500">สิทธิ์บริการไม่ล็อกฟังก์ชันในเว็บ — ใช้สำหรับแสดงและติดตามการมอบให้ทีมงาน</p>
</div>

<script>
document.addEventListener('alpine:init', function () {
  Alpine.data('tierFeaturesForm', function (csrfToken) {
    var cfg = <?= json_encode($config, JSON_UNESCAPED_UNICODE) ?>;
    var serviceCfg = <?= json_encode($serviceCfg, JSON_UNESCAPED_UNICODE) ?>;
    var featureKeys = <?= json_encode(array_keys(OwnerTier::featureLabels()), JSON_UNESCAPED_UNICODE) ?>;
    var tiers = ['none', 'standard', 'vip'];

    var features = { none: {}, standard: {}, vip: {} };
    featureKeys.forEach(function (k) {
      if (k === 'available_boost') return;
      tiers.forEach(function (t) {
        features[t][k] = (cfg.features[t] || []).indexOf(k) !== -1;
      });
    });

    var servicePerks = (serviceCfg.perks || []).map(function (p) {
      return {
        key: p.key || '',
        label: p.label || '',
        none: !!p.none,
        standard: !!p.standard,
        vip: !!p.vip,
      };
    });

    return {
      saving: false,
      msg: '',
      ok: true,
      baseProperty: {
        none: !!cfg.base_property.none,
        standard: !!cfg.base_property.standard,
        vip: !!cfg.base_property.vip,
      },
      features: features,
      boost: {
        standard: cfg.boost.standard || 0,
        vip: cfg.boost.vip || 0,
      },
      servicePerks: servicePerks,
      addServicePerk: function () {
        this.servicePerks.push({ key: '', label: '', none: false, standard: false, vip: false });
      },
      removeServicePerk: function (idx) {
        this.servicePerks.splice(idx, 1);
      },
      save: function () {
        var self = this;
        self.saving = true;
        self.msg = '';
        var payload = {
          _csrf: csrfToken,
          base_property: self.baseProperty,
          features: { none: [], standard: [], vip: [] },
          boost: { standard: self.boost.standard || 0, vip: self.boost.vip || 0 },
          service_perks: self.servicePerks.filter(function (p) { return (p.label || '').trim() !== ''; }),
        };
        tiers.forEach(function (t) {
          featureKeys.forEach(function (k) {
            if (k === 'available_boost') return;
            if (self.features[t][k]) payload.features[t].push(k);
          });
        });
        fetch(<?= json_encode(url('/admin/membership/tier-features'), JSON_UNESCAPED_UNICODE) ?>, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify(payload),
        })
          .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
          .then(function (res) {
            self.ok = res.ok && res.data.ok;
            self.msg = res.data.msg || (self.ok ? 'บันทึกสิทธิ์แล้ว' : 'บันทึกไม่สำเร็จ');
          })
          .catch(function () {
            self.ok = false;
            self.msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
          })
          .finally(function () { self.saving = false; });
      },
    };
  });
});
</script>
