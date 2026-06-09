<div x-data
     x-show="$store.compare && $store.compare.toast.show"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed left-1/2 top-20 z-[70] w-[min(92vw,22rem)] -translate-x-1/2 md:left-auto md:right-5 md:translate-x-0">
  <div class="flex items-center gap-2 rounded-2xl border border-white/60 bg-slate-900/92 px-4 py-3 text-sm font-bold text-white shadow-2xl backdrop-blur">
    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl"
          :class="$store.compare.toast.type === 'warn' ? 'bg-amber-400 text-amber-950' : 'bg-teal-500 text-white'">
      <i :data-lucide="$store.compare.toast.icon || 'scale'" class="h-4 w-4"></i>
    </span>
    <span x-text="$store.compare.toast.message"></span>
  </div>
</div>
