<div x-data="{
        toasts: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, ...detail });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
     }"
     @notify.window="push($event.detail)"
     x-init="
        @if (session('status')) push({ type: 'success', message: @js(session('status')) }); @endif
     "
     class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
     aria-live="polite"
     aria-atomic="true">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="animate-toast-in pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <span class="mt-0.5 shrink-0"
                  :class="toast.type === 'error' ? 'text-rose-500' : 'text-emerald-500'">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path x-show="toast.type !== 'error'" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path x-show="toast.type === 'error'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </span>
            <p class="flex-1 text-sm text-slate-700 dark:text-slate-200" x-text="toast.message"></p>
            <button type="button"
                    @click="dismiss(toast.id)"
                    class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                    aria-label="{{ __('app.common.close') }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
