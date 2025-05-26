<div class="flex justify-center items-center h-screen">
    <div class="max-w-sm">
        <x-filament::section>
            <x-slot name="heading">
                Войдите в систему
            </x-slot>

            <x-slot name="description">
                Введите свой номер телефон и пароль.
            </x-slot>
            <form wire:submit='login' class="space-y-5">
                <x-filament::input.wrapper>
                    <x-filament::input type="number" required wire:model="phone" placeholder='Номер телефон' />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="password" required wire:model="password" placeholder='Пароль' />
                </x-filament::input.wrapper>
                <flux:button variant="danger" type="submit" class="w-full">Войти</flux:button>
                @if($message)
                <div class="bg-orange-200 px-6 py-4 rounded-md text-lg flex items-center mx-auto max-w-lg">
                    <svg viewBox="0 0 24 24" class="text-yellow-600 w-5 h-5 sm:w-5 sm:h-5 mr-3">
                        <path fill="currentColor"
                            d="M23.119,20,13.772,2.15h0a2,2,0,0,0-3.543,0L.881,20a2,2,0,0,0,1.772,2.928H21.347A2,2,0,0,0,23.119,20ZM11,8.423a1,1,0,0,1,2,0v6a1,1,0,1,1-2,0Zm1.05,11.51h-.028a1.528,1.528,0,0,1-1.522-1.47,1.476,1.476,0,0,1,1.448-1.53h.028A1.527,1.527,0,0,1,13.5,18.4,1.475,1.475,0,0,1,12.05,19.933Z">
                        </path>
                    </svg>
                    <span class="text-yellow-800 text-sm">
                        {{ $message }}
                    </span>
                </div>
                @endif
            </form>
            {{-- Content --}}
        </x-filament::section>

    </div>
</div>