<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
        $builtCss = $manifest['resources/css/app.css']['file'] ?? null;
        $builtJs = $manifest['resources/js/app.js']['file'] ?? null;

        $assetPrefix = '/build';
        if ($builtCss) {
            $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
            if ($documentRoot !== '') {
                $cssInBuild = $documentRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . $builtCss;
                $cssInPublicBuild = $documentRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . $builtCss;

                if (file_exists($cssInBuild)) {
                    $assetPrefix = '/build';
                } elseif (file_exists($cssInPublicBuild)) {
                    $assetPrefix = '/public/build';
                }
            }
        }

        $fallbackAssetPrefix = $assetPrefix === '/build' ? '/public/build' : '/build';
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10b981">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Page Title' }}</title>
    @if ($builtCss)
        <link rel="stylesheet" href="{{ $assetPrefix . '/' . $builtCss }}" data-navigate-track="reload"
            onerror="if(this.href.indexOf('{{ $fallbackAssetPrefix }}/{{ $builtCss }}')===-1){this.href='{{ $fallbackAssetPrefix . '/' . $builtCss }}';}">
    @elseif (file_exists(public_path('hot')))
        @vite('resources/css/app.css')
    @endif
    @fluxAppearance
</head>

<body>
    @livewire('loading')
    {{ $slot }}
    @fluxScripts
    @if ($builtJs)
        <script type="module" src="{{ $assetPrefix . '/' . $builtJs }}" data-navigate-track="reload"
            onerror="if(this.src.indexOf('{{ $fallbackAssetPrefix }}/{{ $builtJs }}')===-1){this.src='{{ $fallbackAssetPrefix . '/' . $builtJs }}';}">
        </script>
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
