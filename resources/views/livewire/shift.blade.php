<div class="flex justify-center items-center h-screen">
    <div class="flex justify-center items-center h-screen">
        <div class="max-w-sm ">
            <x-filament::section>
                <div class="space-y-5">
                    @if($open_shift == true)
                    <div>
                        <flux:text class="text-base">Открыт смену</flux:text>
                        <flux:text>Начальная наличность в кассе</flux:text>
                    </div>
                    <form wire:submit='open_shift_date' class="space-y-5">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" required wire:model="initial_cash"
                                placeholder='Начальная в кассе' />
                        </x-filament::input.wrapper>
                        <flux:button variant="primary" type="submit"
                            class="w-full bg-black text-white hover:bg-black/70 cursor-pointer">Открыть</flux:button>
                    </form>
                    @else
                    <flux:button variant="primary" wire:click='openShiftModal'
                        class="w-full bg-black text-white hover:bg-black/70 cursor-pointer">
                        Открыт смену
                    </flux:button>
                    <flux:button variant="danger" wire:click='logout' class="w-full cursor-pointer">Выйти</flux:button>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </div>
</div>