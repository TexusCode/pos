<div class="w-screen h-screen bg-slate-400 grid grid-cols-10 overflow-hidden">
    <div class="hidden" wire:loading.class.remove='hidden'
        wire:loading.class="fixed top-0 left-0 w-screen h-screen bg-black/50 flex justify-center items-center z-50 transition-opacity duration-300">
        <div class="flex justify-center flex-col items-center bg-white p-6 rounded-xl">
            <svg class="animate-spin h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <p class="mt-4 text-lg text-gray-700">Загрузка...</p>
        </div>
    </div>
    <div class="col-span-2 bg-white p-2">
        <ul class="space-y-2">
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold" wire:click='loading'>
                Закрыт смену</li>
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold">Погасить долг</li>
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold">Добавить товар</li>
            <li class="bg-red-500 hover:bg-red-400 text-white cursor-pointer p-2 text-lg font-bold">Выйти из аккаунта
            </li>
        </ul>
        <div class="mt-5 space-y-1">
            <p>Список корзины</p>
            <div class="space-y-1">
                @php
                    $cartcount = 1;
                @endphp
                @foreach ($carts as $cart)
                    <button type="button" wire:click="selectCart({{ $cart->id }})"
                        class="w-full bg-slate-400 hover:bg-slate-500 cursor-pointer p-1 font-bold text-white">Корзина
                        №{{ $cartcount }}</button>
                    @php
                        $cartcount++;
                    @endphp
                @endforeach
            </div>
        </div>

    </div>
    <div class="col-span-4 p-2 space-y-2 h-screen">
        <form wire:submit='addItemBarcode'>
            <input type="text" autofocus wire:model='barcode'
                class="bg-white w-full h-10 p-2 border-0 outline-0 focus:bg-green-400 duration-200 focus:text-white font-bold"
                placeholder="Сканируй штирх-код (ctrl+enter)" id="barcodeInput">
        </form>
        <div class="h-full bg-white space-y-2 p-2 font-bold overflow-y-scroll h-full pb-20">
            @foreach ($products as $product)
                <ul class="flex gap-2 items-center justify-between bg-slate-200 pl-2">
                    <li class="w-40 line-clamp-1">{{ $product->name }}
                    </li>
                    <li class="text-red-500">{{ $product->selling_price }} c</li>
                    <li class="">
                        <button class="bg-black text-white hover:bg-black/70 cursor-pointer px-3"
                            wire:click="addItemToCart({{ $product->id }})">
                            Добавить
                        </button>
                    </li>
                </ul>
            @endforeach
        </div>
    </div>
    <div class="col-span-4 bg-white p-2 space-y-2 h-screen flex flex-col">
        <div class="flex gap-3 justify-between text-black items-center">
            <p>Смена №{{ $shift->id }} - Корзина №{{ $selectedCart->id }}</p>
        </div>
        <div class="space-y-1 overflow-y-scroll h-full">
            @foreach ($selectedCart->items as $item)
                <ul class="flex gap-2 items-center justify-between bg-slate-200 p-1">
                    <li class="w-20 line-clamp-1">{{ $item->product->name }}
                    </li>

                    <li class="flex gap-1 w-min text-white font-black">
                        <button type="button" wire:click="decrement({{ $item->id }})"
                            class="bg-black px-1 w-5 hover:bg-black/70 cursor-pointer">-</button>
                        <p class="text-black">{{ $item->quantity }}</p>
                        <button type="button" wire:click="increment({{ $item->id }})"
                            class="bg-black px-1 w-5 hover:bg-black/70 cursor-pointer" wire:click='loading'
                            id="exitButton">+</button>
                    </li>
                    <li class="text-black font-bold">{{ $item->product->selling_price * $item->quantity }}c</li>
                    <li class="text-red-500 flex gap-2">
                        <button type="button" wire:click="deleteitem({{ $item->id }})" class="cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 7l16 0" />
                                <path d="M10 11l0 6" />
                                <path d="M14 11l0 6" />
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            </svg>
                        </button>
                        <button type="button" wire:click="discountitem({{ $item->id }})" class="cursor-pointer">
                            <svg class="text-green-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 15l6 -6" />
                                <circle cx="9.5" cy="9.5" r=".5" fill="currentColor" />
                                <circle cx="14.5" cy="14.5" r=".5" fill="currentColor" />
                                <path
                                    d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7a2.2 2.2 0 0 0 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1a2.2 2.2 0 0 0 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1" />
                            </svg>
                        </button>
                    </li>
                </ul>
            @endforeach
        </div>
        <div class="space-y-1">
            <div class="flex justify-between">
                <div class="space-y-1">
                    <p>Подытог: {{ $subtotal }}c</p>
                    <p>Скидка: {{ $discounttotal }}с</p>
                    <p>Итог: {{ $total }}с</p>
                </div>
                <div class="space-y-1 flex items-end">
                    <button type="button" wire:click='discountalltrue'
                        class="bg-red-500 text-white hover:bg-red-500/70 cursor-pointer px-3 py-1 w-full">Скидка
                        (ctrl+m)</button>
                </div>
            </div>
            @if ($discountAllModal)
                <form wire:submit='discount_all' class="flex gap-1">
                    <input required wire:model='discountmodel' type="text"
                        class="border-black border-2 outline-0 w-20 p-1" placeholder="Скидка">
                    <select required wire:model='discounttype' class="bg-white outline-0 border-2 ">
                        <option value="Фикц">Фикц</option>
                        <option value="Проценть">Проценть</option>
                    </select>
                    <button type="submit"
                        class="bg-green-500 text-white hover:bg-green-500/70 cursor-pointer px-3 py-1 w-full">Применить</button>
                </form>
            @endif
            <div class="flex gap-1">
                <button class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Оформить
                    (ctrl+c)</button>
                <button class="bg-red-500 text-white hover:bg-red-500/70 cursor-pointer px-3 py-1 w-full">Возврать
                    (ctrl+v)</button>
            </div>
            <div class="flex gap-1">
                <button type="button" wire:click='hand'
                    class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Держать
                    (ctrl+x)</button>
                <button wire:click='truncate' id="truncate" type="button"
                    class="bg-red-500 text-white hover:bg-red-500/70 cursor-pointer px-3 py-1 w-full">Сбросить
                    (ctrl+z)</button>
            </div>
        </div>
    </div>
</div>
