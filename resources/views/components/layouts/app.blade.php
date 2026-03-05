<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
        $builtCss = $manifest['resources/css/app.css']['file'] ?? null;
        $builtJs = $manifest['resources/js/app.js']['file'] ?? null;
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10b981">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">

    <title>{{ $title ?? 'Page Title' }}</title>
    @if ($builtCss)
        <link rel="stylesheet" href="{{ '/build/' . $builtCss }}" data-navigate-track="reload">
    @elseif (file_exists(public_path('hot')))
        @vite('resources/css/app.css')
    @endif
    @fluxAppearance
</head>

<body>
    <div id="offline-indicator"
        class="pointer-events-none fixed bottom-3 right-3 z-[100] hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
        Оффлайн режим: часть действий недоступна до восстановления сети
    </div>
    @livewire('loading')
    {{ $slot }}
    @fluxScripts
    @if ($builtJs)
        <script type="module" src="{{ '/build/' . $builtJs }}" data-navigate-track="reload"></script>
    @elseif (file_exists(public_path('hot')))
        @vite('resources/js/app.js')
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('keydown', (event) => {
                if (!event.ctrlKey) {
                    return;
                }

                const key = event.key.toLowerCase();
                const barcodeInput = document.getElementById('barcodeInput');
                const exitButton = document.getElementById('exitButton');
                const truncate = document.getElementById('truncate');

                if (key === 'enter') {
                    event.preventDefault();
                    if (barcodeInput && document.activeElement !== barcodeInput) {
                        barcodeInput.focus();
                    }
                    return;
                }

                if (key === 'm') {
                    event.preventDefault();
                    if (exitButton) {
                        exitButton.click();
                    }
                    return;
                }

                if (key === 'z') {
                    event.preventDefault();
                    if (truncate) {
                        truncate.click();
                    }
                }
            });
        });
    </script>
    <flux:toast position="top right" />
</body>

</html>
