<div class="relative min-h-screen overflow-hidden bg-slate-100 p-4">
    <div class="pointer-events-none absolute -left-28 top-0 h-80 w-80 rounded-full bg-emerald-200/70 blur-3xl"></div>
    <div class="pointer-events-none absolute right-0 top-10 h-72 w-72 rounded-full bg-cyan-200/60 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-amber-100/80 blur-3xl"></div>

    <div class="relative z-10 flex min-h-[calc(100vh-2rem)] items-center justify-center">
        <section class="w-full max-w-md rounded-3xl border border-slate-200 bg-white/95 p-6 sm:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">TexHub POS</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Создание смены</h1>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-100 text-emerald-700">
                    <x-solar-icon name="archive-check-bold" class="h-5 w-5" />
                </span>
            </div>

            @if ($open_shift)
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-800">Открытие новой смены</p>
                    <p class="mt-1 text-xs text-emerald-700">Укажите стартовую сумму наличных в кассе</p>
                </div>

                <form wire:submit='open_shift_date' class="space-y-4">
                    <div class="space-y-1.5">
                        <label for="initial-cash" class="text-sm font-medium text-slate-700">Начальная наличность</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <x-solar-icon name="wallet-money-bold" class="h-4 w-4" />
                            </span>
                            <input id="initial-cash" type="text" required wire:model="initial_cash"
                                placeholder="Введите сумму в кассе"
                                class="w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-400" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-300/30 bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-400">
                            <x-solar-icon name="check-circle-bold" class="h-4 w-4" />
                            <span>Открыть</span>
                        </button>
                        <button type="button" wire:click="$set('open_shift', false)"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                            <x-solar-icon name="close-circle-bold" class="h-4 w-4" />
                            <span>Отмена</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="mb-4 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                    <p class="text-sm font-semibold text-cyan-800">Смена ещё не открыта</p>
                    <p class="mt-1 text-xs text-cyan-700">Нажмите кнопку ниже, чтобы начать работу</p>
                </div>

                <div class="space-y-2">
                    <button type="button" wire:click='openShiftModal'
                        class="inline-flex w-full items-center gap-2 rounded-xl border border-emerald-300/30 bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-400">
                        <x-solar-icon name="archive-check-bold" class="h-4 w-4" />
                        <span>Открыть смену</span>
                    </button>
                    <button type="button" wire:click='logout'
                        class="inline-flex w-full items-center gap-2 rounded-xl border border-rose-300/25 bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-400">
                        <x-solar-icon name="logout-2-bold" class="h-4 w-4" />
                        <span>Выйти</span>
                    </button>
                </div>
            @endif
        </section>
    </div>
</div>
