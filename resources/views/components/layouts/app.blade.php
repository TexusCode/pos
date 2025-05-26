<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Page Title' }}</title>
    @vite('resources/css/app.css')
    @filamentStyles
    @fluxAppearance
</head>

<body>
    @livewire('loading')
    {{ $slot }}
    @filamentScripts
    @fluxScripts
    @vite('resources/js/app.js')
    <script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
                    const barcodeInput = document.getElementById('barcodeInput');
                    const exitButton = document.getElementById('exitButton');
                    hotkeys('ctrl+enter', { preventDefault: true }, (event) => {
                        if (document.activeElement !== barcodeInput) {
                            barcodeInput.focus();
                        }
                    });
                    hotkeys('ctrl+z', { preventDefault: true }, (event) => {
                        console.log('Нажата комбинация Ctrl + S! Имитируем клик по кнопке "Выход".');
                        exitButton.click();
                    });
                });
    </script>
</body>

</html>