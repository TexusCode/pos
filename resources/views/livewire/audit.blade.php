<div class="flex justify-center items-center h-screen w-screen bg-slate-200">
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
    <div class="max-w-sm bg-white p-4">
        @if ($audit)
            <form wire:submit='addAudit' class="w-full space-y-3 p-2">
                <label>Названия ревизии</label>
                <input type="text" wire:model='auditName' required class="border-2 w-full">
                <label>Заметка</label>
                <input type="text" wire:model='auditNote' class="border-2 w-full">
                <button type="submit"
                    class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Создать
                    ревизия</button>
            </form>
        @else
            <div>
                {{ $selectedAudit->name }}
                @if ($auditModal)
                    <form wire:submit='skuCheck' class="w-full space-y-3 p-2">
                        <label>Штрихкод</label>
                        <input type="text" wire:model='skuPr' required class="border-2 w-full">
                        <button type="submit"
                            class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Найти</button>
                    </form>
                @else
                    <form wire:submit='addAuditItem' class="w-full space-y-3 p-2">
                        <label>Штрихкод</label>
                        <input type="text" wire:model='skuPr' required class="border-2 w-full">
                        @if ($notPrSc)
                            <label>Названия</label>
                            <input type="text" wire:model='namePr' required class="border-2 w-full">
                            <label>Цена</label>
                            <input type="text" wire:model='pricePr' required class="border-2 w-full">
                        @endif
                        <label>Количество</label>
                        <input type="text" wire:model='quantityPr' required class="border-2 w-full">
                        <button type="submit"
                            class="bg-black text-white hover:bg-black/70 cursor-pointer px-3 py-1 w-full">Обновить /
                            Создать</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
    <div class="fixed bottom-0 left-0 w-full flex justify-center items-center p-4 gap-2">
        <button type="button" wire:click='logout' class="bg-red-500 p-2 hover:bg-red-400 text-xl text-white">Выйти из
            аккаунта</button>
        @if ($auditModal)
            <button type="button" wire:click='closeAudit'
                wire:confirm.prompt="Точно хотите закончивать ревизию? Для подтверждения напишите Закончить |Закончить"
                class="bg-red-500 p-2 hover:bg-red-400 text-xl text-white">Закончить
                ревизию</button>
        @else
            <a href="{{ route('audit') }}" class="bg-red-500 p-2 hover:bg-red-400 text-xl text-white">
                Назад</a>
        @endif
    </div>
</div>
