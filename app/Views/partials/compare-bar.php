<div x-data
     x-show="$store.compare && $store.compare.items.length > 0"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="paekan-compare-dock hidden md:block fixed inset-x-3 bottom-[4.75rem] z-[55] md:inset-x-auto md:bottom-5 md:right-5 md:w-[36rem]">
  <div class="rounded-3xl border border-teal-100/90 bg-white/96 p-3 shadow-[0_18px_60px_-18px_rgba(15,23,42,0.38)] ring-1 ring-white/70 backdrop-blur-xl">
    <div class="flex items-center gap-3">
      <div class="flex min-w-0 flex-1 items-center gap-2">
        <template x-for="item in $store.compare.items" :key="item.property_id + ':' + item.unit_id">
          <div class="relative h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
            <img :src="item.image || 'https://placehold.co/120x120?text=PK'" alt="" class="h-full w-full object-cover">
            <button type="button"
                    @click="$store.compare.remove(item.property_id, item.unit_id)"
                    class="absolute -right-1 -top-1 grid h-5 w-5 place-items-center rounded-full bg-slate-900 text-white shadow"
                    aria-label="ลบออกจากรายการเทียบ">
              <i data-lucide="x" class="h-3 w-3"></i>
            </button>
          </div>
        </template>
        <template x-for="slot in Math.max(0, $store.compare.max - $store.compare.items.length)" :key="'slot'+slot">
          <a href="<?= url('/rafts') ?>" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-dashed border-teal-200 bg-teal-50 text-teal-700" aria-label="เพิ่มแพเพื่อเทียบ">
            <i data-lucide="plus" class="h-4 w-4"></i>
          </a>
        </template>
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 text-sm font-extrabold text-slate-900">
            <i data-lucide="git-compare-arrows" class="h-4 w-4 text-teal-600"></i>
            <span>เทียบ <span x-text="$store.compare.items.length"></span> หลัง</span>
          </div>
          <p class="truncate text-[11px] font-semibold text-slate-500" x-text="$store.compare.items.length < $store.compare.max ? 'เพิ่มได้อีก ' + ($store.compare.max - $store.compare.items.length) + ' หลัง' : 'เลือกครบ ' + $store.compare.max + ' หลังแล้ว'"></p>
        </div>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <button type="button" @click="$store.compare.clear()" class="hidden rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 sm:inline-flex sm:items-center sm:gap-1.5" aria-label="ล้างรายการเทียบ">
          <i data-lucide="eraser" class="h-3.5 w-3.5"></i> ล้าง
        </button>
        <a :href="$store.compare.compareUrl()" class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-teal-900/15 transition hover:bg-teal-700">
          เทียบเลย <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </a>
      </div>
    </div>
  </div>
</div>
