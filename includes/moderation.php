<?php
// Text moderation. Dispatches to OpenAI's free Moderations endpoint when
// configured, otherwise falls back to the local wordlist.
//
// moderate_text() always returns an associative array:
//   ['ok' => bool, 'reason' => string]
// 'reason' is a short kid-friendly message shown to the student on rejection.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/wordlist.php';

function moderate_text(string $text): array {
    $text = trim($text);
    if ($text === '') {
        return ['ok' => false, 'reason' => "Please type something first."];
    }

    // PII-ish checks run regardless of backend — kids shouldn't share these.
    $pii = moderation_pii_check($text);
    if (!$pii['ok']) return $pii;

    if (USE_OPENAI && OPENAI_API_KEY !== '') {
        $openai = moderate_openai($text);
        if ($openai !== null) return $openai;
        // On network/API failure, fall through to the wordlist so kids aren't blocked.
    }

    return moderate_wordlist($text);
}

function moderation_pii_check(string $text): array {
    // US phone number-ish
    if (preg_match('/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/', $text)) {
        return ['ok' => false, 'reason' => "Please don't share phone numbers."];
    }
    // Email-ish
    if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text)) {
        return ['ok' => false, 'reason' => "Please don't share email addresses."];
    }
    // Street address-ish (number + street word)
    if (preg_match('/\b\d{1,5}\s+\w+\s+(street|st|avenue|ave|road|rd|lane|ln|drive|dr|boulevard|blvd)\b/i', $text)) {
        return ['ok' => false, 'reason' => "Please don't share your home address."];
    }
    return ['ok' => true, 'reason' => ''];
}

function moderate_wordlist(string $text): array {
    global $BLOCKED_WORDS;

    // Normalize: lowercase, strip common leetspeak and non-letters so "sh!t",
    // "s h i t", and "5hit" all collapse to "shit".
    $normalized = strtolower($text);
    $leet = ['0'=>'o','1'=>'i','3'=>'e','4'=>'a','5'=>'s','7'=>'t','@'=>'a','$'=>'s','!'=>'i'];
    $normalized = strtr($normalized, $leet);
    $normalized = preg_replace('/[^a-z ]+/', '', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    // Also a squished version with no spaces to catch "s h i t" style evasion.
    $squished = str_replace(' ', '', $normalized);

    foreach ($BLOCKED_WORDS as $w) {
        $wn = preg_replace('/[^a-z ]+/', '', strtolower($w));
        if ($wn === '') continue;
        if (strpos($normalized, $wn) !== false) {
            return ['ok' => false, 'reason' => "Let's try different words! Keep it kind and school-friendly."];
        }
        $wsquish = str_replace(' ', '', $wn);
        if (strlen($wsquish) >= 4 && strpos($squished, $wsquish) !== false) {
            return ['ok' => false, 'reason' => "Let's try different words! Keep it kind and school-friendly."];
        }
    }
    return ['ok' => true, 'reason' => ''];
}

// Returns ['ok' => bool, 'reason' => string] on a successful API call,
// or null on network/API failure so the caller can fall back to wordlist.
function moderate_openai(string $text): ?array {
    $payload = json_encode([
        'model' => OPENAI_MOD_MODEL,
        'input' => $text,
    ]);

    $ch = curl_init('https://api.openai.com/v1/moderations');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $payload,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return null;
    }
    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['results'][0])) {
        return null;
    }

    $result = $data['results'][0];
    $blocking = ['harassment','harassment/threatening','hate','hate/threatening',
                 'sexual','sexual/minors','self-harm','self-harm/intent',
                 'self-harm/instructions','violence','violence/graphic'];
    $categories = $result['categories'] ?? [];
    foreach ($blocking as $cat) {
        if (!empty($categories[$cat])) {
            return ['ok' => false, 'reason' => "Let's try different words! Keep it kind and school-friendly."];
        }
    }
    return ['ok' => true, 'reason' => ''];
}
