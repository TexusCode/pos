<div class="w-screen h-screen bg-slate-400 grid grid-cols-10 overflow-hidden">
    <div class="hidden" wire:loading.class.remove='hidden'
        wire:loading.class="fixed top-0 left-0 w-screen h-screen bg-black/50 flex justify-center items-center z-50 transition-opacity duration-300">
        <div class="flex justify-center flex-col items-center bg-white p-6 rounded-xl">
            <svg class="animate-spin h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <p class="mt-4 text-lg text-gray-700">Загрузка...</p>
        </div>
    </div>
    <div class="col-span-2 bg-white p-2">
        <ul class="space-y-2">
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold">Текушая смена</li>
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold" wire:click='loading'>
                Закрыт смену</li>
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold">Погасить долг</li>
            <li class="bg-green-400 hover:bg-green-300 cursor-pointer p-2 text-lg font-bold">Добавить товар</li>
            <li class="bg-red-500 hover:bg-red-400 text-white cursor-pointer p-2 text-lg font-bold">Выйти из аккаунта
            </li>
        </ul>
    </div>
    <div class="col-span-4 p-2 space-y-2">
        <input type="text" autofocus
            class="bg-white w-full h-10 p-2 border-0 outline-0 focus:bg-green-400 duration-200 focus:text-white font-bold"
            placeholder="Сканируй штирх-код (ctrl+enter)" id="barcodeInput">
        <div class="h-full bg-white space-y-2 p-2 font-bold">
            @for ($i = 0; $i < 10; $i++) <ul class="flex gap-2 items-center justify-between bg-slate-200 pl-2">
                <li class="w-32 line-clamp-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Illum,
                    sint!
                </li>
                <li class="text-red-500">12.2$</li>
                <li class="">
                    <button class="bg-black text-white hover:bg-black/70 cursor-pointer px-3">
                        Добавить
                    </button>
                </li>
                </ul>
                @endfor
        </div>
    </div>
    <div class="col-span-4 bg-white p-2 space-y-2 h-screen flex flex-col">
        <div class="flex gap-3 justify-between text-black items-center">
            <p>Смена №1 - Корзина №2</p>
            <div class="space-x-1">
                <button variant="danger" class="bg-black text-white hover:bg-black/70 cursor-pointer px-3">Сбросить
                    (ctrl+m)</button>
            </div>
        </div>
        <div class="space-y-1 overflow-y-scroll h-full">
            @for ($i = 0; $i < 100; $i++) <ul class="flex gap-2 items-center justify-between bg-slate-200 p-1">
                <li class="w-20 line-clamp-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Illum,
                    sint!
                </li>

                <li class="flex gap-1 w-min text-white font-black">
                    <button class="bg-black px-1 w-5 hover:bg-black/70 cursor-pointer">-</button>
                    <p class="text-black">1</p>
                    <button class="bg-black px-1 w-5 hover:bg-black/70 cursor-pointer" wire:click='loading'
                        id="exitButton">+</button>
                </li>
                <li class="text-black font-bold">12.2$</li>
                <li class="text-red-500 ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M4 7l16 0" />
                        <path d="M10 11l0 6" />
                        <path d="M14 11l0 6" />
                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                    </svg>
                </li>
                </ul>
                @endfor
        </div>
        <div class="space-y-1">
            <p>Подытог: 500с</p>
            <p>Подытог: 500с</p>
            <p>Подытог: 500с</p>
            <div class="flex gap-1">
                <input type="text" class="border-black border-2 outline-0 w-20 p-1" placeholder="Скидка">
                <button class="bg-blue-500 text-white hover:bg-blue-500/70 cursor-pointer px-3 py-1 w-full">%</button>
                <button
                    class="bg-blue-500 text-white hover:bg-blue-500/70 cursor-pointer px-3 py-1 w-full">Фикц</button>
                <button
                    class="bg-green-500 text-white hover:bg-green-500/70 cursor-pointer px-3 py-1 w-full">Применить</button>
            </div>
            <div class="flex gap-1">
                <button class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Оформить
                    (ctrl+c)</button>
                <button class="bg-red-500 text-white hover:bg-red-500/70 cursor-pointer px-3 py-1 w-full">Возврать
                    (ctrl+v)</button>
            </div>
            <div class="flex gap-1">
                <button class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Держать
                    (ctrl+x)</button>
                <button class="bg-red-500 text-white hover:bg-red-500/70 cursor-pointer px-3 py-1 w-full">Сбросить
                    (ctrl+z)</button>
            </div>
        </div>
    </div>
</div>