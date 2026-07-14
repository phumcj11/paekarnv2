<?php
/** @var array<int,array<string,mixed>> $featuredRafts */
/** @var string $heroImage */
use App\Models\Property;

$featuredRafts = $featuredRafts ?? [];
$heroImage = trim((string)($heroImage ?? ''));
?>
<div class="min-h-screen overflow-hidden bg-[#f7fbfc]" x-data="paekanAiLanding()" x-init="init()">
  <section class="relative isolate min-h-[620px] overflow-hidden sm:min-h-[680px] lg:min-h-[700px]">
    <div class="absolute inset-0 -z-20 bg-gradient-to-b from-sky-100 via-cyan-50 to-white"></div>
    <?php if ($heroImage !== ''): ?>
    <img src="<?= e($heroImage) ?>"
         alt=""
         width="1280"
         height="800"
         fetchpriority="high"
         class="absolute inset-0 -z-20 h-full w-full object-cover object-center opacity-80 scale-[1.02]">
    <?php endif; ?>
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-white/35 via-sky-50/20 to-[#f7fbfc]"></div>
    <div class="absolute -left-24 top-36 -z-10 h-72 w-72 rounded-full bg-cyan-200/30 blur-3xl"></div>
    <div class="absolute -right-24 top-16 -z-10 h-80 w-80 rounded-full bg-blue-200/30 blur-3xl"></div>

    <div class="mx-auto flex w-full max-w-6xl flex-col items-center px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-20 lg:pt-24">
      <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/75 px-3.5 py-2 text-xs font-extrabold text-sky-900 shadow-sm backdrop-blur-md">
        <span class="grid h-6 w-6 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
          <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
        </span>
        แพกาญ AI · ผู้ช่วยค้นหาแพกาญจนบุรี
      </div>

      <h1 class="max-w-3xl font-heading text-[30px] font-black leading-[1.2] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
        พร้อมค้นหาแพที่ใช่
        <span class="block bg-gradient-to-r from-sky-700 via-cyan-700 to-emerald-700 bg-clip-text text-transparent">สำหรับคุณ</span>
      </h1>
      <p class="mt-4 max-w-xl text-[15px] font-medium leading-7 text-slate-600 sm:text-lg">
        บอกจำนวนคน งบ และสไตล์ที่ชอบ<br class="sm:hidden">
        ให้ AI ช่วยเลือกแพที่ตรงใจในไม่กี่วินาที
      </p>

      <div class="mt-8 w-full max-w-3xl">
        <form @submit.prevent="run()" class="relative flex min-h-[72px] items-center rounded-[2rem] border border-white/90 bg-white/95 p-2 pl-4 shadow-[0_24px_70px_-24px_rgba(14,116,144,0.45)] ring-1 ring-sky-100 backdrop-blur-xl sm:min-h-[82px] sm:rounded-[2.5rem] sm:pl-6">
          <span class="mr-2 grid h-10 w-10 shrink-0 place-items-center rounded-full bg-sky-50 text-sky-600 sm:h-11 sm:w-11">
            <i data-lucide="sparkles" class="h-5 w-5"></i>
          </span>
          <label for="ai-raft-query" class="sr-only">บอก AI ว่าต้องการแพแบบไหน</label>
          <input id="ai-raft-query"
                 x-model="query"
                 type="text"
                 maxlength="800"
                 autocomplete="off"
                 placeholder="เช่น แพ 10 คน มีปิ้งย่าง งบ 5,000"
                 class="min-w-0 flex-1 border-0 bg-transparent px-1 py-3 text-[14px] font-semibold text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0 sm:text-base">

          <button x-show="voiceSupported"
                  x-cloak
                  type="button"
                  @click="startVoice()"
                  :aria-label="listening ? 'กำลังฟังเสียง' : 'ค้นหาด้วยเสียง'"
                  :class="listening ? 'bg-rose-50 text-rose-600 ring-rose-200' : 'text-slate-500 hover:bg-slate-50 hover:text-sky-700 ring-transparent'"
                  class="mr-1 grid h-11 w-11 shrink-0 place-items-center rounded-full ring-1 transition">
            <i data-lucide="mic" class="h-5 w-5"></i>
          </button>

          <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
          <button type="submit"
                  :disabled="busy || !query.trim()"
                  class="ml-1 inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-sky-600 to-cyan-600 px-4 text-sm font-extrabold text-white shadow-lg shadow-sky-600/20 transition hover:from-sky-700 hover:to-cyan-700 disabled:cursor-not-allowed disabled:opacity-50 sm:h-14 sm:px-6">
            <i x-show="!busy" data-lucide="search" class="h-5 w-5"></i>
            <i x-show="busy" x-cloak data-lucide="loader-circle" class="h-5 w-5 animate-spin"></i>
            <span class="hidden sm:inline" x-text="busy ? 'กำลังค้นหา' : 'AI Search'"></span>
          </button>
        </form>

        <p x-show="listening" x-cloak class="mt-2 text-xs font-bold text-rose-600">กำลังฟัง… ลองพูดว่า “แพ 8 คน งบไม่เกินห้าพัน”</p>
        <p x-show="error" x-cloak x-text="error" role="alert" class="mt-3 rounded-xl border border-rose-200 bg-white/90 px-4 py-3 text-sm font-semibold text-rose-700 shadow-sm"></p>

        <div class="mt-5 flex snap-x gap-2.5 overflow-x-auto pb-2 sm:flex-wrap sm:justify-center sm:overflow-visible">
          <button type="button" @click="usePreset('แพสำหรับ 10 คน')" class="shrink-0 snap-start rounded-full border border-sky-100 bg-white/90 px-4 py-2.5 text-xs font-bold text-sky-900 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md sm:text-sm">
            <span class="mr-1.5">👥</span> แพสำหรับ 10 คน
          </button>
          <button type="button" @click="usePreset('แพลาก มีสไลเดอร์และคาราโอเกะ')" class="shrink-0 snap-start rounded-full border border-sky-100 bg-white/90 px-4 py-2.5 text-xs font-bold text-sky-900 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md sm:text-sm">
            <span class="mr-1.5">🛝</span> มีสไลเดอร์
          </button>
          <button type="button" @click="usePreset('แพสำหรับครอบครัว งบไม่เกิน 5000 บาท')" class="shrink-0 snap-start rounded-full border border-sky-100 bg-white/90 px-4 py-2.5 text-xs font-bold text-sky-900 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md sm:text-sm">
            <span class="mr-1.5">💰</span> งบไม่เกิน 5,000
          </button>
        </div>
      </div>

      <div class="mt-auto flex items-center gap-2 pt-10 text-[11px] font-semibold text-slate-500 sm:text-xs">
        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
        ค้นหาจากที่พักที่เผยแพร่จริงบนแพกาญ.com
      </div>
    </div>
  </section>

  <section x-show="busy || result" x-cloak id="ai-results" class="relative z-10 -mt-12 pb-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
      <div class="overflow-hidden rounded-[1.75rem] border border-sky-100 bg-white shadow-[0_24px_70px_-30px_rgba(15,23,42,0.35)]">
        <div class="flex items-center justify-between gap-3 bg-gradient-to-r from-sky-700 via-cyan-700 to-emerald-700 px-5 py-4 text-white sm:px-7">
          <div class="min-w-0 text-left">
            <div class="flex items-center gap-2 text-xs font-bold text-white/75">
              <i data-lucide="sparkles" class="h-4 w-4"></i> AI เลือกให้คุณ
            </div>
            <h2 class="mt-0.5 truncate text-base font-black sm:text-lg" x-text="result ? result.summary : 'กำลังวิเคราะห์แพที่เหมาะที่สุด…'"></h2>
          </div>
          <button x-show="result" type="button" @click="closeResult()" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/15 transition hover:bg-white/25" aria-label="ปิดผลการค้นหา">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>

        <div x-show="busy" class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 sm:p-6">
          <template x-for="i in 3" :key="i">
            <div class="animate-pulse rounded-2xl border border-slate-100 p-3">
              <div class="aspect-[16/10] rounded-xl bg-slate-100"></div>
              <div class="mt-3 h-4 w-2/3 rounded bg-slate-100"></div>
              <div class="mt-2 h-3 w-full rounded bg-slate-100"></div>
            </div>
          </template>
        </div>

        <div x-show="result && !busy" class="p-4 sm:p-6">
          <div x-show="result && result.top_picks && result.top_picks.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="p in (result ? result.top_picks : [])" :key="p.id">
              <a :href="p.url" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-lg">
                <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-sky-50 to-cyan-100">
                  <img x-show="p.cover" :src="p.cover" :alt="p.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                  <span x-show="p.coupon_enabled" class="absolute left-3 top-3 rounded-full bg-emerald-700 px-2.5 py-1 text-[10px] font-extrabold text-white shadow">ใช้คูปองได้</span>
                  <span class="absolute bottom-3 right-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-black text-slate-900 shadow" x-text="formatPrice(p.min_price)"></span>
                </div>
                <div class="p-4">
                  <h3 class="line-clamp-1 text-[15px] font-black text-slate-900" x-text="p.name"></h3>
                  <p class="mt-1 flex items-center gap-1 text-xs font-medium text-slate-500">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5 text-sky-600"></i>
                    <span x-text="p.zone || 'กาญจนบุรี'"></span>
                  </p>
                  <p class="mt-3 line-clamp-2 rounded-xl bg-sky-50 px-3 py-2 text-xs font-semibold leading-5 text-sky-900">
                    <span class="font-black text-sky-600">AI:</span>
                    <span x-text="p.reason"></span>
                  </p>
                </div>
              </a>
            </template>
          </div>

          <div x-show="result && (!result.top_picks || !result.top_picks.length)" class="py-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-slate-500">
              <i data-lucide="search-x" class="h-6 w-6"></i>
            </span>
            <p class="mt-3 text-sm font-bold text-slate-700">ยังไม่พบแพที่ตรงทั้งหมด ลองลดเงื่อนไขบางอย่าง</p>
          </div>

          <a x-show="result && result.redirect" :href="result ? result.redirect : '#'" class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-extrabold text-white transition hover:bg-sky-800">
            ดูผลค้นหาทั้งหมด <i data-lucide="arrow-right" class="h-4 w-4"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-[#f7fbfc] pb-16 pt-8 sm:pb-20">
    <div class="mx-auto max-w-6xl">
      <div class="flex items-end justify-between gap-4 px-4 sm:px-6">
        <div>
          <div class="flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.12em] text-sky-700">
            <i data-lucide="sparkles" class="h-4 w-4"></i> ตัวเลือกยอดนิยม
          </div>
          <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">แนะนำแพยอดนิยม</h2>
        </div>
        <a href="<?= url('/rafts') ?>" class="inline-flex shrink-0 items-center gap-1 text-sm font-bold text-slate-600 transition hover:text-sky-700">
          ดูทั้งหมด <i data-lucide="chevron-right" class="h-4 w-4"></i>
        </a>
      </div>

      <?php if ($featuredRafts !== []): ?>
      <div class="mt-5 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-5 sm:gap-4 sm:px-6 no-scrollbar">
        <?php foreach ($featuredRafts as $index => $property):
          $uid = (int)($property['listing_unit_id'] ?? 0);
          $coverPath = trim((string)($property['listing_unit_cover'] ?? ''));
          if ($coverPath === '') {
              $coverPath = trim((string)($property['cover_image'] ?? ''));
          }
          $title = $uid > 0 && !empty($property['listing_unit_name'])
              ? (string)$property['listing_unit_name']
              : (string)($property['name'] ?? '');
          $pUrl = url('/property/' . ($property['slug'] ?? '')) . ($uid > 0 ? '?unit=' . $uid : '');
          $price = (float)($property['listing_unit_price'] ?? $property['min_price'] ?? 0);
          $summary = Property::listingUnitSummaryLine($property);
        ?>
        <a href="<?= e($pUrl) ?>" class="group w-[76vw] max-w-[280px] shrink-0 snap-start overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_14px_36px_-22px_rgba(15,23,42,0.35)] transition hover:-translate-y-1 hover:shadow-xl sm:w-[280px]">
          <div class="relative aspect-[4/3] overflow-hidden bg-sky-50">
            <img src="<?= e(upload_img($coverPath, 'thumb') ?: 'https://placehold.co/560x420?text=Paekan') ?>"
                 alt="<?= e($title) ?>"
                 width="560"
                 height="420"
                 loading="<?= $index < 2 ? 'eager' : 'lazy' ?>"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-extrabold text-sky-900 shadow backdrop-blur">แนะนำ</span>
            <?php if (!empty($property['coupon_enabled'])): ?>
            <span class="absolute bottom-3 left-3 rounded-full bg-emerald-700 px-2.5 py-1 text-[10px] font-extrabold text-white shadow">ใช้คูปองได้</span>
            <?php endif; ?>
          </div>
          <div class="p-4">
            <h3 class="line-clamp-1 text-[15px] font-black text-slate-900"><?= e($title) ?></h3>
            <p class="mt-1 flex items-center gap-1 text-xs font-medium text-slate-500">
              <i data-lucide="map-pin" class="h-3.5 w-3.5 text-sky-600"></i>
              <span class="truncate"><?= e((string)($property['zone'] ?: $property['district'] ?: 'กาญจนบุรี')) ?></span>
            </p>
            <?php if ($summary !== ''): ?>
            <p class="mt-2 line-clamp-1 text-[11px] font-semibold text-slate-600"><?= e($summary) ?></p>
            <?php endif; ?>
            <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-3">
              <div>
                <div class="text-[10px] font-bold text-slate-400">เริ่มต้น</div>
                <div class="text-lg font-black text-sky-800"><?= $price > 0 ? number_format($price, 0) : 'สอบถาม' ?><?php if ($price > 0): ?><span class="ml-1 text-[10px] font-semibold text-slate-500">บาท/คืน</span><?php endif; ?></div>
              </div>
              <?php if ((int)($property['rating_count'] ?? 0) > 0): ?>
              <span class="inline-flex items-center gap-1 text-xs font-black text-amber-700"><i data-lucide="star" class="h-4 w-4 fill-amber-400 text-amber-400"></i><?= number_format((float)$property['rating_avg'], 1) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="mx-4 mt-5 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-semibold text-slate-500 sm:mx-6">
        กำลังเตรียมรายการแพแนะนำ
      </div>
      <?php endif; ?>

      <div class="mx-4 mt-7 rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-sky-950 to-cyan-950 p-6 text-white shadow-xl sm:mx-6 sm:flex sm:items-center sm:justify-between sm:gap-6 sm:p-8">
        <div>
          <div class="text-xs font-bold text-cyan-300">หาเองแล้วยังไม่เจอ?</div>
          <h2 class="mt-1 text-xl font-black">ให้ทีมแพกาญช่วยหาให้ฟรี</h2>
          <p class="mt-2 text-sm leading-6 text-slate-300">ส่งจำนวนคน วันที่ และงบ ทีมงานช่วยประสานแพที่เหมาะให้คุณ</p>
        </div>
        <a href="<?= url('/guest-seek') ?>" class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-sky-950 transition hover:bg-cyan-50 sm:mt-0 sm:w-auto">
          ให้ทีมช่วยหา <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </a>
      </div>
    </div>
  </section>

  <footer class="border-t border-slate-200 bg-white px-4 py-6 text-center text-xs font-semibold text-slate-500">
    © <?= date('Y') ?> แพกาญ.com · AI ช่วยค้นหาแพกาญจนบุรี
  </footer>
</div>

<script>
function paekanAiLanding() {
  return {
    query: '',
    busy: false,
    error: '',
    result: null,
    voiceSupported: false,
    listening: false,
    init() {
      this.voiceSupported = Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);
    },
    async run() {
      const query = this.query.trim();
      if (!query || this.busy) return;
      this.busy = true;
      this.error = '';
      this.result = null;
      this.$nextTick(() => {
        document.getElementById('ai-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
      try {
        const body = new FormData();
        body.append('query', query);
        body.append('scope', 'raft');
        const response = await fetch(<?= json_encode(url('/ai/smart-search'), JSON_UNESCAPED_SLASHES) ?>, {
          method: 'POST',
          body,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
          throw new Error(data.error || 'ค้นหาไม่สำเร็จ กรุณาลองใหม่');
        }
        this.result = data;
        this.$nextTick(() => {
          if (window.lucide) window.lucide.createIcons();
        });
      } catch (error) {
        this.error = error && error.message ? error.message : 'เชื่อมต่อ AI ไม่สำเร็จ กรุณาลองอีกครั้ง';
      } finally {
        this.busy = false;
      }
    },
    usePreset(text) {
      this.query = text;
      this.$nextTick(() => this.run());
    },
    closeResult() {
      this.result = null;
      this.error = '';
    },
    formatPrice(value) {
      const amount = Number(value || 0);
      return amount > 0 ? '฿' + amount.toLocaleString('th-TH') + '/คืน' : 'สอบถามราคา';
    },
    startVoice() {
      const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      if (!Recognition || this.listening) return;
      const recognition = new Recognition();
      recognition.lang = 'th-TH';
      recognition.interimResults = false;
      recognition.maxAlternatives = 1;
      this.listening = true;
      recognition.onresult = (event) => {
        this.query = event.results[0][0].transcript || '';
      };
      recognition.onerror = () => {
        this.error = 'รับเสียงไม่สำเร็จ ลองพิมพ์คำค้นแทนได้เลย';
      };
      recognition.onend = () => {
        this.listening = false;
        if (this.query.trim()) this.run();
      };
      recognition.start();
    },
  };
}
</script>
