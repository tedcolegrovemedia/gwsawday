<?php
// Kid-friendly Google Font options the student can pick for their app title.
// To add another font: pick a Google Font family, set `family` to the URL
// segment (use `+` for spaces, pin weights if you want), and set `stack`
// to the CSS font-family fallback string.

function available_fonts(): array {
    return [
        'fredoka' => [
            'name'   => 'Fredoka',
            'family' => 'Fredoka:wght@500;600;700',
            'stack'  => "'Fredoka', sans-serif",
            'sample' => 'Friendly & round',
        ],
        'luckiest' => [
            'name'   => 'Luckiest Guy',
            'family' => 'Luckiest+Guy',
            'stack'  => "'Luckiest Guy', cursive",
            'sample' => 'Bold & cartoony',
        ],
        'press-start' => [
            'name'   => 'Press Start 2P',
            'family' => 'Press+Start+2P',
            'stack'  => "'Press Start 2P', monospace",
            'sample' => 'Retro video game',
        ],
        'bungee' => [
            'name'   => 'Bungee',
            'family' => 'Bungee',
            'stack'  => "'Bungee', cursive",
            'sample' => 'Chunky blocks',
        ],
        'pacifico' => [
            'name'   => 'Pacifico',
            'family' => 'Pacifico',
            'stack'  => "'Pacifico', cursive",
            'sample' => 'Smooth & stylish',
        ],
    ];
}

function font_or_default(string $key): array {
    $fonts = available_fonts();
    return $fonts[$key] ?? $fonts['fredoka'];
}

function is_valid_font(string $key): bool {
    return array_key_exists($key, available_fonts());
}

function google_fonts_link_for(string $key): string {
    $f = font_or_default($key);
    return 'https://fonts.googleapis.com/css2?family=' . $f['family'] . '&display=swap';
}
