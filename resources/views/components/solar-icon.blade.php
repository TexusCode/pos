@props([
    'name',
    'class' => 'h-4 w-4',
])

@php
    $path = resource_path("views/icons/solar/{$name}.svg");
    $svg = is_file($path) ? file_get_contents($path) : '';

    if ($svg !== '') {
        $classes = trim((string) $class);

        $svg = preg_replace_callback(
            '/<svg\\b[^>]*>/i',
            static function ($matches) use ($classes) {
                $tag = $matches[0];

                if (preg_match('/\\bclass=(\"|\\\')(.*?)\\1/i', $tag, $classMatch)) {
                    $current = trim($classMatch[2]);
                    $merged = trim($current . ' ' . $classes);
                    return preg_replace('/\\bclass=(\"|\\\')(.*?)\\1/i', 'class="' . $merged . '"', $tag, 1);
                }

                return rtrim(substr($tag, 0, -1)) . ' class="' . $classes . '">';
            },
            $svg,
            1,
        );
    }
@endphp

{!! $svg !!}
