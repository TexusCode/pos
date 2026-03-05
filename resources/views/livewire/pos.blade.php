<div class="pos-shell relative min-h-screen w-screen overflow-hidden p-3 text-slate-800 lg:p-4">
    <div class="pos-glow pointer-events-none absolute -left-20 top-10 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl">
    </div>
    <div class="pointer-events-none absolute right-12 top-10 h-56 w-56 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-8 right-1/3 h-56 w-56 rounded-full bg-amber-300/10 blur-3xl"></div>

    <div class="hidden" wire:loading.class.remove='hidden'
        wire:loading.class="fixed inset-0 z-50 flex items-center justify-center bg-slate-100/70 backdrop-blur-sm transition-opacity duration-300">
        <div class="rounded-2xl border border-slate-200 bg-white/95 p-6">
            <div class="flex flex-col items-center justify-center gap-3">
                <svg class="h-10 w-10 animate-spin text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="text-sm font-semibold tracking-wide text-slate-600">Загрузка данных...</p>
            </div>
        </div>
    </div>

    <div
        class="pos-grid relative z-10 grid h-[calc(100vh-1.5rem)] grid-cols-1 gap-4 lg:h-[calc(100vh-2rem)] xl:grid-cols-12">
        <aside class="pos-card flex h-full min-h-0 flex-col rounded-3xl p-4 lg:p-5 xl:col-span-3">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">TexHub POS</p>
                    <h1 class="text-xl font-semibold">Кассовая панель</h1>
                </div>
                <span
                    class="rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Онлайн</span>
            </div>

            <div class="space-y-2">
                <button type="button" wire:click='closeShiftModal'
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2.5 text-sm font-semibold text-emerald-50 transition hover:-translate-y-0.5 hover:bg-emerald-400">
                    <x-solar-icon name="archive-check-bold" class="h-4 w-4" />
                    <span>Закрыть смену</span>
                </button>
                <button type="button" wire:click='payDebtModalTrue'
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2.5 text-sm font-semibold text-emerald-50 transition hover:-translate-y-0.5 hover:bg-emerald-400">
                    <x-solar-icon name="wallet-money-bold" class="h-4 w-4" />
                    <span>Погасить долг</span>
                </button>
                <button type="button" wire:click='addProductModalTrue'
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-cyan-300/20 bg-cyan-500/80 px-3 py-2.5 text-sm font-semibold text-cyan-50 transition hover:-translate-y-0.5 hover:bg-cyan-400">
                    <x-solar-icon name="add-square-bold" class="h-4 w-4" />
                    <span>Добавить товар</span>
                </button>
                <button type="button" wire:click='addExpenceModal'
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-300/20 bg-amber-500/80 px-3 py-2.5 text-sm font-semibold text-amber-50 transition hover:-translate-y-0.5 hover:bg-amber-400">
                    <x-solar-icon name="bill-list-bold" class="h-4 w-4" />
                    <span>Добавить расход</span>
                </button>
                <button type="button" wire:click='logout'
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300/20 bg-rose-500/85 px-3 py-2.5 text-sm font-semibold text-rose-50 transition hover:-translate-y-0.5 hover:bg-rose-400">
                    <x-solar-icon name="logout-2-bold" class="h-4 w-4" />
                    <span>Выйти из аккаунта</span>
                </button>
            </div>

            <div class="mt-5 flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-200 bg-white/80 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-semibold">Список корзин</p>
                    <span
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $carts->count() }}</span>
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                    @php
                        $cartcount = 1;
                    @endphp
                    @foreach ($carts as $cart)
                        <button type="button" wire:click="selectCart({{ $cart->id }})"
                            class="inline-flex w-full items-center gap-2 rounded-xl border px-3 py-2 text-left text-sm font-semibold transition {{ $selectedCart && $selectedCart->id === $cart->id
                                ? 'border-emerald-300 bg-emerald-100 text-emerald-700'
                                : 'border-slate-200 bg-white/90 text-slate-600 hover:border-slate-300 hover:bg-slate-100' }}">
                            <x-solar-icon name="cart-2-bold" class="h-4 w-4" />
                            <span>Корзина №{{ $cartcount }}</span>
                        </button>
                        @php
                            $cartcount++;
                        @endphp
                    @endforeach
                </div>
            </div>
        </aside>

        <section class="pos-card flex h-full min-h-0 flex-col rounded-3xl p-4 lg:p-5 xl:col-span-4">
            <label for="barcodeInput"
                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Сканирование</label>
            <form wire:submit='addItemBarcode' class="mb-4">
                <input type="text" autofocus wire:model='barcode'
                    class="w-full rounded-2xl border border-slate-300 bg-white/95 px-4 py-3 text-base font-semibold text-slate-800 outline-none ring-0 transition placeholder:text-slate-500 focus:border-emerald-400 focus:bg-white"
                    placeholder="Сканируй штрих-код (ctrl+enter)" id="barcodeInput">
            </form>

            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Товары</h2>
                <span
                    class="rounded-full border border-cyan-200 bg-cyan-100/70 px-3 py-1 text-xs font-semibold text-cyan-700">{{ $products->count() }}
                    позиций</span>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                @foreach ($products as $product)
                    <div
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/90 p-3 transition hover:border-cyan-400/50 hover:bg-white">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">Остаток: {{ $product->quantity }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-semibold text-amber-700">{{ $product->selling_price }} c</p>
                            <button type="button" wire:click="addItemToCart({{ $product->id }})"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-cyan-300/30 bg-cyan-500/85 px-3 py-1.5 text-xs font-semibold text-cyan-50 transition hover:bg-cyan-400">
                                <x-solar-icon name="cart-plus-bold" class="h-4 w-4" />
                                <span>Добавить</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="pos-card flex h-full min-h-0 flex-col rounded-3xl p-4 lg:p-5 xl:col-span-5">
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Активная смена</p>
                    <p class="text-lg font-semibold">Смена №{{ $shift->id }}</p>
                </div>
                <span
                    class="rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                    Товаров: {{ $selectedCart->items->count() }}
                </span>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                @forelse ($selectedCart->items as $item)
                    <div
                        class="grid grid-cols-[1fr_auto_auto_auto] items-center gap-2 rounded-xl border border-slate-200 bg-white/90 p-2.5">
                        <p class="truncate text-sm font-semibold">{{ $item->product->name }}</p>

                        <div
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-slate-100/70 text-sm">
                            <button type="button" wire:click="decrement({{ $item->id }})"
                                class="inline-flex px-2.5 py-1.5 text-slate-600 transition hover:bg-slate-200 hover:text-slate-900">
                                <x-solar-icon name="minus-circle-bold" class="h-4 w-4" />
                            </button>
                            <p class="px-2 font-semibold text-slate-900">{{ $item->quantity }}</p>
                            <button type="button" wire:click="increment({{ $item->id }})"
                                class="inline-flex px-2.5 py-1.5 text-slate-600 transition hover:bg-slate-200 hover:text-slate-900">
                                <x-solar-icon name="add-circle-bold" class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="text-sm font-semibold text-amber-700">
                            {{ $item->product->selling_price * $item->quantity }}c</p>

                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="discountitem({{ $item->id }})"
                                class="rounded-lg border border-emerald-200 bg-emerald-100 p-1.5 text-emerald-700 transition hover:bg-emerald-200">
                                <x-solar-icon name="sale-bold" class="h-4 w-4" />
                            </button>
                            <button type="button" wire:click="deleteitem({{ $item->id }})"
                                class="rounded-lg border border-rose-200 bg-rose-100 p-1.5 text-rose-700 transition hover:bg-rose-200">
                                <x-solar-icon name="trash-bin-2-bold" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                        Корзина пока пустая. Добавьте товар из списка слева.
                    </div>
                @endforelse
            </div>

            <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50/95 p-2.5">
                <div class="grid grid-cols-2 gap-1.5 text-[13px] leading-tight">
                    <p class="text-slate-500">Подытог</p>
                    <p class="text-right font-semibold text-slate-900">{{ $subtotal }}c</p>
                    <p class="text-slate-500">Скидка</p>
                    <p class="text-right font-semibold text-emerald-700">{{ $discounttotal }}c</p>
                    <p class="text-slate-500">Итог</p>
                    <p class="text-right text-base font-bold text-amber-700">{{ $total }}c</p>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-1.5">
                    <button type="button" wire:click='discountalltrue' id="exitButton"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-300/25 bg-rose-500/85 px-3 py-1.5 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                        <x-solar-icon name="sale-bold" class="h-4 w-4" />
                        <span>Скидка</span>
                    </button>

                    @if ($returnModal)
                        <button type="button" wire:click='orderReturn'
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-300/20 bg-emerald-500/85 px-3 py-1.5 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                            <x-solar-icon name="check-circle-bold" class="h-4 w-4" />
                            <span>Подтвердить возврат</span>
                        </button>
                    @else
                        <button type="button" wire:click='returnModalTrue'
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-300/20 bg-rose-500/85 px-3 py-1.5 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                            <x-solar-icon name="undo-left-round-bold" class="h-4 w-4" />
                            <span>Возврат</span>
                        </button>
                    @endif
                </div>

                <div class="mt-2 grid grid-cols-3 gap-1.5">
                    <button type="button" wire:click='openCheckoutModal'
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-cyan-300/20 bg-cyan-500/85 px-3 py-1.5 text-sm font-semibold text-cyan-50 transition hover:bg-cyan-400">
                        <x-solar-icon name="cart-check-bold" class="h-4 w-4" />
                        <span>Оформить</span>
                    </button>
                    <button type="button" wire:click='hand'
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-200">
                        <x-solar-icon name="hand-money-bold" class="h-4 w-4" />
                        <span>Держать</span>
                    </button>
                    <button wire:click='truncate' id="truncate" type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-300/20 bg-rose-500/85 px-3 py-1.5 text-sm font-semibold text-rose-50 transition hover:bg-rose-400">
                        <x-solar-icon name="restart-bold" class="h-4 w-4" />
                        <span>Сбросить</span>
                    </button>
                </div>
            </div>
        </section>
    </div>

    @if ($discountAllModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">
                        {{ $selecteditemdiscount ? 'Скидка на товар' : 'Скидка на корзину' }}
                    </h1>
                    <button type="button" wire:click='discountalltrue'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='discount_all' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Сумма скидки</label>
                    <input required wire:model='discountmodel' type="text"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400"
                        placeholder="Введите сумму или процент">

                    <label class="text-sm text-slate-600">Тип скидки</label>
                    <select required wire:model='discounttype'
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                        <option value="Фикц">Фиксированная</option>
                        <option value="Проценть">Процент</option>
                    </select>

                    <button type="submit"
                        class="inline-flex w-full items-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="check-circle-bold" class="h-4 w-4" />
                        <span>Применить</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($checkoutModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">Оформить заказ</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='checkout' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Метод оплаты</label>
                    <select wire:model.live='paymentType' required
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                        <option value="Наличными">Наличными</option>
                        <option value="Карта">Карта</option>
                        <option value="В долг">В долг</option>
                    </select>

                    @if ($debtModal)
                        <label class="text-sm text-slate-600">Имя</label>
                        <input type="text" wire:model='name' required placeholder="Введите имя клиента"
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">

                        <label class="text-sm text-slate-600">Номер телефона</label>
                        <input type="text" wire:model='phone' required placeholder="Введите номер телефона"
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    @endif

                    <label class="text-sm text-slate-600">Полученная сумма</label>
                    <input type="text" wire:model.live='cash' required placeholder="Введите полученную сумму"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">

                    <div class="space-y-1 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <div class="flex justify-between">
                            <p class="text-slate-500">Подытог:</p>
                            <p class="font-semibold text-slate-900">{{ $subtotal }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-500">Скидка:</p>
                            <p class="font-semibold text-emerald-700">{{ $discounttotal }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-500">Итог:</p>
                            <p class="font-semibold text-amber-700">{{ $total }}c</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-slate-500">Сдача:</p>
                            <p class="font-semibold {{ $cash == null ? 'text-rose-700' : 'text-cyan-700' }}">
                                @if ($cash == null)
                                    {{ -$total }}c
                                @else
                                    {{ $cash - $total }}c
                                @endif
                            </p>
                        </div>
                    </div>

                    <label class="text-sm text-slate-600">Заметка</label>
                    <input type="text" wire:model='note' placeholder="Комментарий к заказу (необязательно)"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="cart-check-bold" class="h-4 w-4" />
                        <span>Оформить</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($debtPaymentModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">Погасить долг</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='payDebt' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Номер телефона</label>
                    <input type="text" wire:model.live='phoneDebt' required
                        placeholder="Введите номер телефона клиента"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    <label class="text-sm text-slate-600">Полученная сумма</label>
                    <input type="text" wire:model='cashDebt' required placeholder="Введите сумму оплаты долга"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="wallet-money-bold" class="h-4 w-4" />
                        <span>Погасить</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($shiftModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">Закрыть смену</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='closeShift' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Наличными в кассе</label>
                    <input type="text" wire:model='nallCassa' required
                        placeholder="Введите сумму наличных в кассе"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="archive-check-bold" class="h-4 w-4" />
                        <span>Закрыть смену</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($addProductModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">Добавить товар</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='addPRoductForm' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Штрихкод товара</label>
                    <input type="text" wire:model.live='skuPr' required
                        placeholder="Введите или сканируйте штрихкод"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">

                    @if ($addProductSection)
                        <label class="text-sm text-slate-600">Название товара</label>
                        <input type="text" wire:model='namePr' required placeholder="Введите название товара"
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">

                        <label class="text-sm text-slate-600">Цена продажи</label>
                        <input type="text" wire:model='selling_pricePr' required
                            placeholder="Введите цену продажи"
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    @endif

                    @if ($issetPr)
                        <label class="text-sm text-slate-600">Количество</label>
                        <input type="text" wire:model='quantityPr' required placeholder="Введите количество"
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    @endif

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="box-bold" class="h-4 w-4" />
                        <span>Добавить / Обновить</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($expenceModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-white/70 px-3 py-6 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white/95">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100/80 px-4 py-3">
                    <h1 class="text-lg font-semibold text-slate-900">Добавить расход</h1>
                    <button type="button" wire:click='closeCheckoutModal'
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/25 bg-rose-500/20 text-rose-700 transition hover:bg-rose-500/35"
                        aria-label="Закрыть" title="Закрыть">
                        <x-solar-icon name="close-circle-bold" class="h-5 w-5" />
                    </button>
                </div>
                <form wire:submit='addExpence' class="space-y-3 p-4">
                    <label class="text-sm text-slate-600">Сумма</label>
                    <input type="text" wire:model='expenceModel' required placeholder="Введите сумму расхода"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    <label class="text-sm text-slate-600">Описание</label>
                    <input type="text" wire:model='expenceDescModel' required
                        placeholder="Введите описание расхода"
                        class="w-full rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/20 bg-emerald-500/90 px-3 py-2 text-sm font-semibold text-emerald-50 transition hover:bg-emerald-400">
                        <x-solar-icon name="bill-list-bold" class="h-4 w-4" />
                        <span>Добавить</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
