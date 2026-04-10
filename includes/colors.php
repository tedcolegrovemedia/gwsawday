<?php
// Curated color palette for the two-color picker.
// All colors are dark/saturated enough that white text stays readable on
// top of them, so students can't accidentally make an unreadable app.
//
// Teachers: feel free to edit the list. Each entry needs a stable key
// (used in the stored build), a display name, and a hex value.

function available_colors(): array {
    return [
        'red'     => ['name' => 'Red',     'hex' => '#c0392b'],
        'orange'  => ['name' => 'Orange',  'hex' => '#d35400'],
        'green'   => ['name' => 'Green',   'hex' => '#27ae60'],
        'teal'    => ['name' => 'Teal',    'hex' => '#16a085'],
        'blue'    => ['name' => 'Blue',    'hex' => '#2980b9'],
        'navy'    => ['name' => 'Navy',    'hex' => '#14386b'],
        'purple'  => ['name' => 'Purple',  'hex' => '#8e44ad'],
        'pink'    => ['name' => 'Pink',    'hex' => '#c2185b'],
        'charcoal'=> ['name' => 'Charcoal','hex' => '#2c3e50'],
        'brown'   => ['name' => 'Brown',   'hex' => '#6b3919'],
    ];
}

function is_valid_color_key(string $key): bool {
    return array_key_exists($key, available_colors());
}

function color_hex(string $key, string $fallback = '#14386b'): string {
    $c = available_colors();
    return $c[$key]['hex'] ?? $fallback;
}
