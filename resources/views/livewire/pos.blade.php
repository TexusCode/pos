<style>
    .pos-shell {
        --pos-ink: #e2e8f0;
        --pos-muted: #94a3b8;
        --pos-surface: rgba(15, 23, 42, 0.86);
        --pos-border: rgba(148, 163, 184, 0.22);
        --pos-accent: #34d399;
        --pos-accent-soft: rgba(52, 211, 153, 0.2);
        background:
            radial-gradient(circle at 14% 14%, rgba(16, 185, 129, 0.24), transparent 40%),
            radial-gradient(circle at 85% 12%, rgba(14, 165, 233, 0.2), transparent 38%),
            radial-gradient(circle at 70% 86%, rgba(250, 204, 21, 0.1), transparent 35%),
            #020617;
    }

    .pos-card {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        box-shadow: 0 20px 45px rgba(2, 6, 23, 0.42);
        backdrop-filter: blur(12px);
        animation: pos-fade 0.45s ease both;
    }

    .pos-grid > *:nth-child(2) {
        animation-delay: 0.06s;
    }

    .pos-grid > *:nth-child(3) {
        animation-delay: 0.12s;
    }

    .pos-glow {
        animation: pos-drift 8s ease-in-out infinite;
    }

    @keyframes pos-fade {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pos-drift {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(8px);
        }
    }
</style>

<div class="pos-shell relative min-h-screen w-screen overflow-hidden p-3 text-slate-100 lg:p-4">
    <div class="pos-glow pointer-events-none absolute -left-20 top-10 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute right-12 top-10 h-56 w-56 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-8 right-1/3 h-56 w-56 rounded-full bg-amber-300/10 blur-3xl"></div>

    <div class="hidden" wire:loading.class.remove='hidden'
        wire:loading.class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-opacity duration-300">
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/95 p-6 shadow-2xl">
            <div class="flex flex-col items-center justify-center gap-3">
                <svg class="h-10 w-10 animate-spin text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="text-sm font-semibold tracking-wide text-slate-300">Загрузка данных...</p>
            </div>
        </div>
    </div>

    <div class="pos-grid relative z-10 grid h-[calc(100vh-1.5rem)] grid-cols-1 gap-4 lg:h-[calc(100vh-2rem)] xl:grid-cols-12">
        <aside class="pos-card min-h-0 rounded-3xl p-4 lg:p-5 xl:col-span-3">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">TexHub POS</p>
                    <h1 class="text-xl font-semibold">Кассовая панель</h1>
                </div>
                <span class="rounded-full border border-emerald-300/30 bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">Онлайн</span>
            </div>

            <div class="space-y-2">
                <button type="button" wire:click='closeShiftModal'
                    class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2.5 text-sm font-semibold text-emerald-50 transition hover:-translate-y-0.5 hover:bg-emerald-400">
                    Закрыть смену
                </button>
                <button type="button" wire:click='payDebtModalTrue'
                    class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2.5 text-sm font-semibold text-emerald-50 transition hover:-translate-y-0.5 hover:bg-emerald-400">
                    Погасить долг
                </button>
                <button type="button" wire:click='addProductModalTrue'
                    class="w-full rounded-xl border border-cyan-300/20 bg-cyan-500/80 px-3 py-2.5 text-sm font-semibold text-cyan-50 transition hover:-translate-y-0.5 hover:bg-cyan-400">
                    Добавить товар
                </button>
                <button type="button" wire:click='addExpenceModal'
                    class="w-full rounded-xl border border-amber-300/20 bg-amber-500/80 px-3 py-2.5 text-sm font-semibold text-amber-50 transition hover:-translate-y-0.5 hover:bg-amber-400">
                    Добавить расход
                </button>
                <button type="button" wire:click='logout'
                    class="w-full rounded-xl border border-rose-300/20 bg-rose-500/85 px-3 py-2.5 text-sm font-semibold text-rose-50 transition hover:-translate-y-0.5 hover:bg-rose-400">
                    Выйти из аккаунта
                </button>
            </div>

            <div class="mt-5 flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-700/80 bg-slate-950/50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-semibold">Список корзин</p>
                    <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{{ $carts->count() }}</span>
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                    @php
                        $cartcount = 1;
                    @endphp
                    @foreach ($carts as $cart)
                        <button type="button" wire:click="selectCart({{ $cart->id }})"
                            class="w-full rounded-xl border px-3 py-2 text-left text-sm font-semibold transition {{ $selectedCart && $selectedCart->id === $cart->id
                                ? 'border-emerald-400/30 bg-emerald-500/20 text-emerald-200'
                                : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-slate-500 hover:bg-slate-800' }}">
                            Корзина №{{ $cartcount }}
                        </button>
                        @php
                            $cartcount++;
                        @endphp
                    @endforeach
                </div>
            </div>
        </aside>

        <section class="min-h-0 space-y-4 xl:col-span-4">
            <div class="pos-card rounded-3xl p-4 lg:p-5">
                <label for="barcodeInput" class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Сканирование</label>
                <form wire:submit='addItemBarcode'>
                    <input type="text" autofocus wire:model='barcode'
                        class="w-full rounded-2xl border border-slate-600 bg-slate-900/80 px-4 py-3 text-base font-semibold text-slate-100 outline-none ring-0 transition placeholder:text-slate-500 focus:border-emerald-400 focus:bg-slate-900 focus:shadow-[0_0_0_3px_rgba(52,211,153,0.15)]"
                        placeholder="Сканируй штрих-код (ctrl+enter)" id="barcodeInput">
                </form>
            </div>

            <div class="pos-card flex min-h-0 flex-1 flex-col rounded-3xl p-4 lg:p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Товары</h2>
                    <span class="rounded-full border border-cyan-300/25 bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-300">{{ $products->count() }} позиций</span>
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                    @foreach ($products as $product)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-700 bg-slate-900/70 p-3 transition hover:border-cyan-400/50 hover:bg-slate-900">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-100">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400">Остаток: {{ $product->quantity }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-semibold text-amber-300">{{ $product->selling_price }} c</p>
                                <button type="button" wire:click="addItemToCart({{ $product->id }})"
                                    class="rounded-lg border border-cyan-300/30 bg-cyan-500/85 px-3 py-1.5 text-xs font-semibold text-cyan-50 transition hover:bg-cyan-400">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="pos-card flex min-h-0 flex-col rounded-3xl p-4 lg:p-5 xl:col-span-5">
            <div class="mb-4 flex items-center justify-between border-b border-slate-700/80 pb-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Активная смена</p>
                    <p class="text-lg font-semibold">Смена №{{ $shift->id }}</p>
                </div>
                <span class="rounded-full border border-emerald-300/30 bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">
                    Товаров: {{ $selectedCart->items->count() }}
                </span>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                @forelse ($selectedCart->items as $item)
                    <div class="grid grid-cols-[1fr_auto_auto_auto] items-center gap-2 rounded-xl border border-slate-700 bg-slate-900/70 p-2.5">
                        <p class="truncate text-sm font-semibold">{{ $item->product->name }}</p>

                        <div class="inline-flex items-center rounded-lg border border-slate-600 bg-slate-800/70 text-sm">
                            <button type="button" wire:click="decrement({{ $item->id }})"
                                class="px-2.5 py-1.5 text-slate-300 transition hover:bg-slate-700 hover:text-white">-</button>
                            <p class="px-2 font-semibold text-white">{{ $item->quantity }}</p>
                            <button type="button" wire:click="increment({{ $item->id }})"
                                class="px-2.5 py-1.5 text-slate-300 transition hover:bg-slate-700 hover:text-white">+</button>
                        </div>

                        <p class="text-sm font-semibold text-amber-300">{{ $item->product->selling_price * $item->quantity }}c</p>

                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="discountitem({{ $item->id }})"
                                class="rounded-lg border border-emerald-400/30 bg-emerald-500/15 p-1.5 text-emerald-300 transition hover:bg-emerald-500/30">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 15l6 -6" />
                                    <circle cx="9.5" cy="9.5" r=".5" fill="currentColor" />
                                    <circle cx="14.5" cy="14.5" r=".5" fill="currentColor" />
                                    <path
                                        d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7a2.2 2.2 0 0 0 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1a2.2 2.2 0 0 0 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1" />
                                </svg>
                            </button>
                            <button type="button" wire:click="deleteitem({{ $item->id }})"
                                class="rounded-lg border border-rose-400/30 bg-rose-500/15 p-1.5 text-rose-300 transition hover:bg-rose-500/30">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M4 7l16 0" />
                                    <path d="M10 11l0 6" />
                                    <path d="M14 11l0 6" />
                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-600 bg-slate-900/50 p-8 text-center text-sm text-slate-400">
                        Корзина пока пустая. Добавьте товар из списка слева.
                    </div>
                @endforelse
            </div>

            <div class="mt-4 rounded-2xl border border-slate-700/80 bg-slate-950/60 p-3">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p class="text-slate-400">Подытог</p>
                    <p class="text-right font-semibold text-white">{{ $subtotal }}c</p>
                    <p class="text-slate-400">Скидка</p>
                    <p class="text-right font-semibold text-emerald-300">{{ $discounttotal }}c</p>
                    <p class="text-slate-400">Итог</p>
                    <p class="text-right text-lg font-bold text-amber-300">{{ $total }}c</p>
                </div>

                <button type="button" wire:click='discountalltrue' id="exitButton"
                    class="mt-3 w-full rounded-xl border border-rose-300/25 bg-rose-500/85 px-3 py-2 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                    Скидка (ctrl+m)
                </button>

                @if ($discountAllModal)
                    <form wire:submit='discount_all' class="mt-3 grid grid-cols-[1fr_1fr_auto] gap-2">
                        <input required wire:model='discountmodel' type="text"
                            class="rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400"
                            placeholder="Скидка">
                        <select required wire:model='discounttype'
                            class="rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            <option value="Фикц">Фикц</option>
                            <option value="Проценть">Проценть</option>
                        </select>
                        <button type="submit"
                            class="rounded-xl border border-emerald-300/20 bg-emerald-500/85 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                            Применить
                        </button>
                    </form>
                @endif

                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button type="button" wire:click='openCheckoutModal'
                        class="rounded-xl border border-cyan-300/20 bg-cyan-500/85 px-3 py-2 text-sm font-semibold text-cyan-50 transition hover:bg-cyan-400">
                        Оформить (ctrl+c)
                    </button>

                    @if ($returnModal)
                        <button type="button" wire:click='orderReturn'
                            class="rounded-xl border border-emerald-300/20 bg-emerald-500/85 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                            Подтвердить возврат
                        </button>
                    @else
                        <button type="button" wire:click='returnModalTrue'
                            class="rounded-xl border border-rose-300/20 bg-rose-500/85 px-3 py-2 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                            Возврат
                        </button>
                    @endif
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button type="button" wire:click='hand'
                        class="rounded-xl border border-slate-500/50 bg-slate-800 px-3 py-2 text-sm font-semibold text-slate-100 transition hover:bg-slate-700">
                        Держать (ctrl+x)
                    </button>
                    <button wire:click='truncate' id="truncate" type="button"
                        class="rounded-xl border border-rose-300/20 bg-rose-500/85 px-3 py-2 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                        Сбросить (ctrl+z)
                    </button>
                </div>
            </div>
        </section>
    </div>

    @if ($checkoutModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/75 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700/70 bg-slate-900/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-white">Оформить заказ</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="rounded-lg border border-rose-300/25 bg-rose-500/20 px-3 py-1 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/35">
                        Закрыть
                    </button>
                </div>
                <form wire:submit='checkout' class="space-y-3 p-4">
                    <label class="text-sm text-slate-300">Метод оплаты</label>
                    <select wire:model.live='paymentType' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                        <option value="Наличными">Наличными</option>
                        <option value="Карта">Карта</option>
                        <option value="В долг">В долг</option>
                    </select>

                    @if ($debtModal)
                        <label class="text-sm text-slate-300">Имя</label>
                        <input type="text" wire:model='name' required
                            class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">

                        <label class="text-sm text-slate-300">Номер телефона</label>
                        <input type="text" wire:model='phone' required
                            class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    @endif

                    <label class="text-sm text-slate-300">Полученная сумма</label>
                    <input type="text" wire:model.live='cash' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">

                    <div class="space-y-1 rounded-xl border border-slate-700 bg-slate-950/70 p-3 text-sm">
                        <div class="flex justify-between">
                            <p class="text-slate-400">Подытог:</p>
                            <p class="font-semibold text-white">{{ $subtotal }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-400">Скидка:</p>
                            <p class="font-semibold text-emerald-300">{{ $discounttotal }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-400">Итог:</p>
                            <p class="font-semibold text-amber-300">{{ $total }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-400">Сдача:</p>
                            <p class="font-semibold {{ $cash == null ? 'text-rose-300' : 'text-cyan-300' }}">
                                @if ($cash == null)
                                    {{ -$total }}c
                                @else
                                    {{ $cash - $total }}c
                                @endif
                            </p>
                        </div>
                    </div>

                    <label class="text-sm text-slate-300">Заметка</label>
                    <input type="text" wire:model='note'
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">

                    <button type="submit"
                        class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        Оформить
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($debtPaymentModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/75 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700/70 bg-slate-900/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-white">Погасить долг</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="rounded-lg border border-rose-300/25 bg-rose-500/20 px-3 py-1 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/35">
                        Закрыть
                    </button>
                </div>
                <form wire:submit='payDebt' class="space-y-3 p-4">
                    <label class="text-sm text-slate-300">Номер телефона</label>
                    <input type="text" wire:model.live='phoneDebt' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    <label class="text-sm text-slate-300">Полученная сумма</label>
                    <input type="text" wire:model='cashDebt' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        Погасить
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($shiftModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/75 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700/70 bg-slate-900/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-white">Закрыть смену</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="rounded-lg border border-rose-300/25 bg-rose-500/20 px-3 py-1 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/35">
                        Закрыть
                    </button>
                </div>
                <form wire:submit='closeShift' class="space-y-3 p-4">
                    <label class="text-sm text-slate-300">Наличными в кассе</label>
                    <input type="text" wire:model='nallCassa' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        Закрыть смену
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($addProductModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/75 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700/70 bg-slate-900/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-white">Добавить товар</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="rounded-lg border border-rose-300/25 bg-rose-500/20 px-3 py-1 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/35">
                        Закрыть
                    </button>
                </div>
                <form wire:submit='addPRoductForm' class="space-y-3 p-4">
                    <label class="text-sm text-slate-300">Штрихкод товара</label>
                    <input type="text" wire:model.live='skuPr' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">

                    @if ($addProductSection)
                        <label class="text-sm text-slate-300">Название товара</label>
                        <input type="text" wire:model='namePr' required
                            class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">

                        <label class="text-sm text-slate-300">Цена продажи</label>
                        <input type="text" wire:model='selling_pricePr' required
                            class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    @endif

                    @if ($issetPr)
                        <label class="text-sm text-slate-300">Количество</label>
                        <input type="text" wire:model='quantityPr' required
                            class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    @endif

                    <button type="submit"
                        class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        Добавить / Обновить
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($expenceModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/75 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-700/70 bg-slate-900/95 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-white">Добавить расход</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="rounded-lg border border-rose-300/25 bg-rose-500/20 px-3 py-1 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/35">
                        Закрыть
                    </button>
                </div>
                <form wire:submit='addExpence' class="space-y-3 p-4">
                    <label class="text-sm text-slate-300">Сумма</label>
                    <input type="text" wire:model='expenceModel' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    <label class="text-sm text-slate-300">Описание</label>
                    <input type="text" wire:model='expenceDescModel' required
                        class="w-full rounded-xl border border-slate-600 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="w-full rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        Добавить
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
