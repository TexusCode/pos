<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Page Title' }}</title>
    @vite('resources/css/app.css')
    @fluxAppearance
</head>

<body>
    @livewire('loading')
    {{ $slot }}
    @fluxScripts
    @vite('resources/js/app.js')
    <script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const barcodeInput = document.getElementById('barcodeInput');
            const exitButton = document.getElementById('exitButton');
            const truncate = document.getElementById('truncate');
            hotkeys('ctrl+enter', {
                preventDefault: true
            }, (event) => {
                if (document.activeElement !== barcodeInput) {
                    barcodeInput.focus();
                }
            });
            hotkeys('ctrl+m', {
                preventDefault: true
            }, (event) => {
                exitButton.click();
            });
            hotkeys('ctrl+z', {
                preventDefault: true
            }, (event) => {
                truncate.click();
            });
        });
    </script>
    <flux:toast position="top right" />
</body>

</html>
