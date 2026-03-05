<div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <section class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">Вход в систему</h1>
            <p class="mt-1 text-sm text-slate-600">Введите номер телефона и пароль</p>
        </div>

        <form wire:submit='login' class="space-y-4">
            <div class="space-y-1.5">
                <label for="login-phone" class="text-sm font-medium text-slate-700">Номер телефона</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <x-solar-icon name="phone-bold" class="h-4 w-4" />
                    </span>
                    <input id="login-phone" type="tel" required wire:model="phone" autocomplete="tel"
                        placeholder="Введите номер телефона"
                        class="w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-400">
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="login-password" class="text-sm font-medium text-slate-700">Пароль</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <x-solar-icon name="lock-password-bold" class="h-4 w-4" />
                    </span>
                    <input id="login-password" type="password" required wire:model="password" autocomplete="current-password"
                        placeholder="Введите пароль"
                        class="w-full rounded-xl border border-slate-300 bg-white px-10 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-400">
                </div>
            </div>

            <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300/30 bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-400">
                <x-solar-icon name="login-2-bold" class="h-4 w-4" />
                <span>Войти</span>
            </button>

            @if ($message)
                <div class="inline-flex w-full items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    <x-solar-icon name="danger-triangle-bold" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @endif
        </form>
    </section>
</div>
