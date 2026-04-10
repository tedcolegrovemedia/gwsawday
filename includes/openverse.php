<?php
// Openverse image search wrapper with aggressive file-based caching (1 hour
// default). Openverse is a free, no-key image search across Flickr, Wikimedia,
// museum collections, etc. A whole classroom searching "puppy" only hits the
// API once per cache window — important because the anonymous rate limit is
// quite low.
//
// Register a bearer token at
//   https://api.openverse.org/v1/auth_tokens/register/
// and drop it into OPENVERSE_BEARER in config.php for higher limits.

require_once __DIR__ . '/../config.php';

// Returns ['url', 'thumbnail', 'credit', 'credit_url', 'license', 'source']
// on success, or ['error' => '...'] on failure.
function openverse_search(string $query): array {
    $query = trim($query);
    if ($query === '') {
        return ['error' => 'Please type a topic.'];
    }

    if (!is_dir(CACHE_DIR)) @mkdir(CACHE_DIR, 0775, true);
    $cache_file = CACHE_DIR . '/ov_' . md5(strtolower($query)) . '.json';
    if (is_file($cache_file) && (time() - filemtime($cache_file)) < IMAGE_CACHE_SECONDS) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached) && !empty($cached['results'])) {
            return openverse_pick($cached['results']);
        }
    }

    $url = OPENVERSE_API_BASE . 'images/?' . http_build_query([
        'q'          => $query,
        'page_size'  => 20,
        'mature'     => 'false',
        // Default sort on Openverse is relevance, which is effectively
        // "most popular match" for the query.
    ]);

    $headers = ['Accept: application/json'];
    if (OPENVERSE_BEARER !== '') {
        $headers[] = 'Authorization: Bearer ' . OPENVERSE_BEARER;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_USERAGENT      => 'GovernorWolfSawDay/1.0 (+https://governorwolf.school)',
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => "Couldn't reach the image search. Try again!"];
    }
    if ($status === 429) {
        return ['error' => "Lots of students searching right now! Wait a minute and try again."];
    }
    if ($status < 200 || $status >= 300) {
        return ['error' => "The image search is having trouble. Try again!"];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['results'])) {
        return ['error' => "We couldn't find a picture for that. Try a different word!"];
    }

    // Trim + cache only the fields we need so stored cache files stay small.
    $trimmed = ['results' => []];
    foreach ($data['results'] as $r) {
        if (empty($r['url'])) continue;
        if (strpos($r['url'], 'https://') !== 0) continue; // HTTPS only
        $trimmed['results'][] = [
            'url'        => $r['url'],
            'thumbnail'  => $r['thumbnail'] ?? $r['url'],
            'credit'     => $r['creator'] ?? 'Unknown',
            'credit_url' => $r['creator_url'] ?? ($r['foreign_landing_url'] ?? ''),
            'license'    => strtoupper((string)($r['license'] ?? '')),
            'source'     => $r['source'] ?? '',
        ];
    }
    if (!$trimmed['results']) {
        return ['error' => "We couldn't find a picture for that. Try a different word!"];
    }

    file_put_contents($cache_file, json_encode($trimmed));
    return openverse_pick($trimmed['results']);
}

function openverse_pick(array $results): array {
    // Prefer the top relevance-sorted results so the "most popular" image
    // for the query wins, but rotate through the top 5 so a classroom of
    // 30 kids doesn't all get the identical picture.
    $top = array_slice($results, 0, 5);
    return $top[array_rand($top)];
}
