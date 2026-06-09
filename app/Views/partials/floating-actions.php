<?php
use App\Models\Setting;

$chatEnabled = Setting::get('ai_chatbot_enabled', '1');
$greeting = (string)Setting::get('ai_chatbot_greeting', 'สวัสดีค่ะ');
$raftsUrl = url('/rafts');
$compareBaseUrl = url('/compare');
$isComparePage = ($page ?? '') === 'compare/index';
$isPropertyShowPage = ($page ?? '') === 'properties/show';
?>
<div class="fixed bottom-[5.25rem] right-4 z-50 font-sans md:bottom-5 md:right-5">
  <div class="relative flex flex-col items-end gap-3">

    <?php if (!$isComparePage && !$isPropertyShowPage): ?>
    <!-- Compare FAB — มือถือเท่านั้น (desktop ใช้ dock + nav) -->
    <a x-data
       :href="$store.compare && $store.compare.items.length ? $store.compare.compareUrl() : '<?= e($raftsUrl) ?>'"
       class="group md:hidden relative grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-2xl ring-2 ring-white transition active:scale-95"
       :aria-label="$store.compare && $store.compare.items.length ? 'ดูรายการเทียบ ' + $store.compare.items.length + ' หลัง' : 'เลือกแพเพื่อเทียบ'">
      <i data-lucide="scale" class="h-6 w-6"></i>
      <span x-show="$store.compare && $store.compare.items.length > 0"
            x-cloak
            x-text="$store.compare.items.length > 9 ? '9+' : $store.compare.items.length"
            class="absolute -right-0.5 -top-0.5 grid min-h-[1.25rem] min-w-[1.25rem] place-items-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-extrabold text-white"></span>
      <span class="pointer-events-none absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100">
        เทียบแพ
      </span>
    </a>
    <?php endif; ?>

    <?php if ($chatEnabled): ?>
    <div x-data="paekanChat()" class="relative flex flex-col items-end">
      <!-- Chat panel -->
      <div x-show="open"
           x-transition.opacity
           x-cloak
           class="absolute bottom-full right-0 mb-3 flex w-[min(360px,calc(100vw-2rem))] max-h-[min(560px,calc(100vh-8rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-[400px]">
        <div class="flex items-center gap-3 bg-gradient-to-r from-accent-500 to-accent-700 px-4 py-3 text-white">
          <div class="grid h-10 w-10 place-items-center rounded-full bg-white/20 text-xl">🌊</div>
          <div class="flex-1 min-w-0">
            <div class="font-bold">น้องแพ — AI ผู้ช่วย</div>
            <div class="flex items-center gap-1 text-xs text-white/80"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> ออนไลน์</div>
          </div>
          <button type="button" @click="open=false" class="rounded-lg p-1.5 hover:bg-white/10" aria-label="ปิดแชท">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>

        <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
          <template x-for="(m, i) in messages" :key="i">
            <div :class="m.role==='user' ? 'flex justify-end' : 'flex justify-start gap-2'">
              <template x-if="m.role==='assistant'">
                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-accent-100">🌊</div>
              </template>
              <div :class="m.role==='user' ? 'bg-accent-500 text-white' : 'border border-slate-200 bg-white text-slate-800'"
                   class="max-w-[80%] whitespace-pre-wrap break-words rounded-2xl px-3.5 py-2.5 text-sm shadow-sm" x-text="m.content"></div>
            </div>
          </template>
          <div x-show="loading" class="flex gap-2">
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-accent-100">🌊</div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
              <div class="flex gap-1">
                <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></div>
                <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400" style="animation-delay:.15s"></div>
                <div class="h-2 w-2 animate-bounce rounded-full bg-slate-400" style="animation-delay:.3s"></div>
              </div>
            </div>
          </div>
        </div>

        <div x-show="messages.length<=1" class="flex gap-1.5 overflow-x-auto border-t border-slate-100 bg-white px-3 py-2">
          <template x-for="s in suggestions" :key="s">
            <button type="button" @click="send(s)" x-text="s" class="whitespace-nowrap rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-700 transition hover:bg-accent-100"></button>
          </template>
        </div>

        <form @submit.prevent="send($refs.input.value); $refs.input.value=''" class="flex gap-2 border-t border-slate-100 bg-white p-3">
          <input x-ref="input" type="text" placeholder="ถามอะไรก็ได้..." class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-accent-500 focus:ring-2 focus:ring-accent-100">
          <button type="submit" :disabled="loading" class="rounded-lg bg-accent-500 px-4 py-2 text-white hover:bg-accent-600 disabled:opacity-50">
            <i data-lucide="send" class="h-4 w-4"></i>
          </button>
        </form>
      </div>

      <!-- Chat bubble -->
      <button type="button"
              x-show="!open"
              @click="open=true; setTimeout(()=>$refs.input?.focus(),100)"
              class="group relative grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-accent-500 to-accent-700 text-white shadow-2xl transition hover:scale-110"
              aria-label="เปิดแชทถามน้องแพ">
        <i data-lucide="message-circle" class="h-6 w-6"></i>
        <span class="absolute right-0 top-0 h-3 w-3 animate-pulse rounded-full border-2 border-white bg-rose-500"></span>
        <span class="pointer-events-none absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs text-white opacity-0 transition group-hover:opacity-100">
          💬 ถามน้องแพได้เลย
        </span>
      </button>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($chatEnabled): ?>
<script>
function paekanChat() {
  return {
    open: false,
    loading: false,
    messages: [{role:'assistant', content: <?= json_encode($greeting, JSON_UNESCAPED_UNICODE) ?>}],
    suggestions: ['คูปองเงินสดคืออะไร?','พาสัตว์เลี้ยงเข้าได้มั้ย?','จองอย่างไร?','รับชำระแบบไหน?','ติดต่อทีมงาน'],
    async send(msg) {
      msg = (msg || '').trim();
      if (!msg || this.loading) return;
      this.messages.push({role:'user', content: msg});
      this.loading = true;
      this.$nextTick(() => { if (this.$refs.messages) this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; });
      try {
        const fd = new FormData(); fd.append('message', msg);
        const r = await fetch('<?= url('/ai/chat') ?>', {method:'POST', body: fd});
        const j = await r.json();
        if (j.ok) this.messages.push({role:'assistant', content: j.reply});
        else this.messages.push({role:'assistant', content:'ขออภัย ระบบขัดข้องชั่วคราว'});
      } catch (e) {
        this.messages.push({role:'assistant', content:'เชื่อมต่อไม่สำเร็จ ลองอีกครั้งค่ะ'});
      } finally {
        this.loading = false;
        this.$nextTick(() => { if (this.$refs.messages) this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; if (window.lucide) lucide.createIcons(); });
      }
    }
  }
}
</script>
<?php endif; ?>
