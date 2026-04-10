<?php
// File-based storage for saved app builds. One JSON file per share code.

require_once __DIR__ . '/../config.php';

function storage_init(): void {
    foreach ([DATA_DIR, BUILDS_DIR, CACHE_DIR, RATELIMIT_DIR] as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
    }
}

function is_valid_code(string $code): bool {
    return (bool)preg_match('/^[A-HJ-NP-Z2-9]{' . CODE_LENGTH . '}$/', $code);
}

function generate_code(): string {
    $alpha = CODE_ALPHABET;
    $len   = strlen($alpha);
    for ($attempt = 0; $attempt < 20; $attempt++) {
        $code = '';
        for ($i = 0; $i < CODE_LENGTH; $i++) {
            $code .= $alpha[random_int(0, $len - 1)];
        }
        if (!is_file(BUILDS_DIR . '/' . $code . '.json')) {
            return $code;
        }
    }
    // Extremely unlikely; caller should surface as server error.
    throw new RuntimeException('Could not allocate a share code.');
}

function save_build(array $build): string {
    storage_init();
    $code = generate_code();
    $build['code']    = $code;
    $build['created'] = time();
    $path = BUILDS_DIR . '/' . $code . '.json';
    file_put_contents($path, json_encode($build, JSON_PRETTY_PRINT));
    return $code;
}

function load_build(string $code): ?array {
    if (!is_valid_code($code)) return null;
    $path = BUILDS_DIR . '/' . $code . '.json';
    if (!is_file($path)) return null;
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

// File-based rate limiter keyed by IP. Returns true if the request is allowed.
function rate_limit_check(string $ip): bool {
    storage_init();
    $key  = preg_replace('/[^a-zA-Z0-9._:-]/', '_', $ip);
    $file = RATELIMIT_DIR . '/' . $key . '.json';
    $now  = time();
    $window = 600; // 10 minutes

    $entries = [];
    if (is_file($file)) {
        $existing = json_decode(file_get_contents($file), true);
        if (is_array($existing)) $entries = $existing;
    }
    // Drop old entries
    $entries = array_values(array_filter($entries, fn($t) => ($now - $t) < $window));
    if (count($entries) >= MAX_BUILDS_PER_IP_10MIN) {
        file_put_contents($file, json_encode($entries));
        return false;
    }
    $entries[] = $now;
    file_put_contents($file, json_encode($entries));
    return true;
}
