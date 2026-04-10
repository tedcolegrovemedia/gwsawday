<?php
// Pexels search wrapper with a simple file-based cache so a class of 25 kids
// searching "puppy" only calls the API once every ten minutes.

require_once __DIR__ . '/../config.php';

// Returns ['url', 'credit', 'credit_url'] on success, or ['error' => '...'] on failure.
function pexels_search(string $query): array {
    if (PEXELS_API_KEY === '') {
        return ['error' => 'Pexels API key is not configured.'];
    }

    $query = trim($query);
    if ($query === '') {
        return ['error' => 'Please type a topic.'];
    }

    // Cache key
    if (!is_dir(CACHE_DIR)) @mkdir(CACHE_DIR, 0775, true);
    $cache_file = CACHE_DIR . '/pexels_' . md5(strtolower($query)) . '.json';
    if (is_file($cache_file) && (time() - filemtime($cache_file)) < PEXELS_CACHE_SECONDS) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached) && !empty($cached['photos'])) {
            return pexels_pick_random($cached['photos']);
        }
    }

    $url = 'https://api.pexels.com/v1/search?' . http_build_query([
        'query'       => $query,
        'per_page'    => 8,
        'orientation' => 'portrait',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . PEXELS_API_KEY,
        ],
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return ['error' => 'Could not reach Pexels. Try again!'];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['photos'])) {
        return ['error' => "We couldn't find a picture for that. Try a different word!"];
    }

    // Cache trimmed result
    $trimmed = ['photos' => []];
    foreach ($data['photos'] as $p) {
        $trimmed['photos'][] = [
            'url'        => $p['src']['large'] ?? ($p['src']['medium'] ?? ''),
            'credit'     => $p['photographer'] ?? 'Pexels',
            'credit_url' => $p['photographer_url'] ?? 'https://pexels.com',
        ];
    }
    file_put_contents($cache_file, json_encode($trimmed));

    return pexels_pick_random($trimmed['photos']);
}

function pexels_pick_random(array $photos): array {
    $photos = array_values(array_filter($photos, fn($p) =>
        isset($p['url']) &&
        strpos($p['url'], ALLOWED_IMAGE_HOST_PREFIX) === 0
    ));
    if (!$photos) {
        return ['error' => "We couldn't find a picture for that. Try a different word!"];
    }
    return $photos[array_rand($photos)];
}
