<?php
// POST JSON build -> { code, url }
// Re-moderates EVERY text field server-side. Client moderation is UX only.
//
// Expected build shape:
// {
//   header:  { title, font, image_url, image_thumbnail, image_credit, image_credit_url, image_license, image_source },
//   cards:   [{ emoji, title, caption }, ...],
//   buttons: [{ label }, ...],
//   footer:  { text }
// }

require_once __DIR__ . '/../includes/moderation.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/fonts.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit_check($ip)) {
    http_response_code(429);
    echo json_encode(['error' => "Whoa! That's a lot of saves. Take a break and try again in a few minutes."]);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request.']);
    exit;
}

// ---- Header ----
$title     = isset($body['header']['title'])     ? (string)$body['header']['title']     : '';
$font      = isset($body['header']['font'])      ? (string)$body['header']['font']      : 'fredoka';
$image_url = isset($body['header']['image_url']) ? (string)$body['header']['image_url'] : '';
$image_thumb = isset($body['header']['image_thumbnail']) ? (string)$body['header']['image_thumbnail'] : '';
$credit    = isset($body['header']['image_credit'])     ? (string)$body['header']['image_credit']     : '';
$credit_u  = isset($body['header']['image_credit_url']) ? (string)$body['header']['image_credit_url'] : '';
$license   = isset($body['header']['image_license'])    ? (string)$body['header']['image_license']    : '';
$img_source= isset($body['header']['image_source'])     ? (string)$body['header']['image_source']     : '';

$title = trim($title);
if ($title === '' || mb_strlen($title) > MAX_TITLE_LEN) {
    http_response_code(400);
    echo json_encode(['error' => 'Title is missing or too long.']);
    exit;
}
if (strpos($image_url, 'https://') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Image must be a secure (https) URL.']);
    exit;
}
if (!is_valid_font($font)) {
    $font = 'fredoka';
}
$m = moderate_text($title);
if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }

// ---- Cards ----
$cards_in = isset($body['cards']) && is_array($body['cards']) ? $body['cards'] : [];
if (count($cards_in) < MIN_CARDS || count($cards_in) > MAX_CARDS) {
    http_response_code(400);
    echo json_encode(['error' => 'Add ' . MIN_CARDS . ' to ' . MAX_CARDS . ' fun cards.']);
    exit;
}
$cards = [];
foreach ($cards_in as $c) {
    $emoji   = isset($c['emoji'])   ? trim((string)$c['emoji'])   : '';
    $c_title = isset($c['title'])   ? trim((string)$c['title'])   : '';
    $c_cap   = isset($c['caption']) ? trim((string)$c['caption']) : '';

    if ($emoji === '' || mb_strlen($emoji) > 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Each card needs an emoji.']);
        exit;
    }
    if ($c_title === '' || mb_strlen($c_title) > MAX_CARD_TITLE_LEN) {
        http_response_code(400);
        echo json_encode(['error' => "Each card needs a short title (under " . MAX_CARD_TITLE_LEN . " letters)."]);
        exit;
    }
    if (mb_strlen($c_cap) > MAX_CARD_CAPTION_LEN) {
        http_response_code(400);
        echo json_encode(['error' => "Card captions are too long (max " . MAX_CARD_CAPTION_LEN . " letters)."]);
        exit;
    }
    $m = moderate_text($c_title);
    if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }
    if ($c_cap !== '') {
        $m = moderate_text($c_cap);
        if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }
    }
    $cards[] = ['emoji' => $emoji, 'title' => $c_title, 'caption' => $c_cap];
}

// ---- Buttons ----
$buttons_in = isset($body['buttons']) && is_array($body['buttons']) ? $body['buttons'] : [];
if (count($buttons_in) < 1 || count($buttons_in) > MAX_BUTTONS) {
    http_response_code(400);
    echo json_encode(['error' => 'Add 1 to ' . MAX_BUTTONS . ' buttons.']);
    exit;
}
$buttons = [];
foreach ($buttons_in as $b) {
    $label = isset($b['label']) ? trim((string)$b['label']) : '';
    if ($label === '' || mb_strlen($label) > MAX_BUTTON_LEN) {
        http_response_code(400);
        echo json_encode(['error' => 'Each button needs a short label.']);
        exit;
    }
    $m = moderate_text($label);
    if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }
    $buttons[] = ['label' => $label];
}

// ---- Footer ----
$footer_text = isset($body['footer']['text']) ? trim((string)$body['footer']['text']) : '';
if ($footer_text === '' || mb_strlen($footer_text) > MAX_FOOTER_LEN) {
    http_response_code(400);
    echo json_encode(['error' => "Write a short footer (under " . MAX_FOOTER_LEN . " letters)."]);
    exit;
}
$m = moderate_text($footer_text);
if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }

$build = [
    'header' => [
        'title'            => $title,
        'font'             => $font,
        'image_url'        => $image_url,
        'image_thumbnail'  => $image_thumb,
        'image_credit'     => $credit,
        'image_credit_url' => $credit_u,
        'image_license'    => $license,
        'image_source'     => $img_source,
    ],
    'cards'   => $cards,
    'buttons' => $buttons,
    'footer'  => ['text' => $footer_text],
];

try {
    $code = save_build($build);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => "Couldn't save. Please try again."]);
    exit;
}

// Build the absolute share URL (same host, /view.php?c=CODE)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? '';
$dir    = rtrim(str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
$url    = $scheme . '://' . $host . $dir . '/view.php?c=' . $code;

echo json_encode(['code' => $code, 'url' => $url]);
