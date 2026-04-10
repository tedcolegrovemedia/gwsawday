<?php
// POST JSON build -> { code, url }
// Re-moderates EVERY text field server-side. Client moderation is UX only.

require_once __DIR__ . '/../includes/moderation.php';
require_once __DIR__ . '/../includes/storage.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit_check($ip)) {
    http_response_code(429);
    echo json_encode(['error' => 'Whoa! That\'s a lot of saves. Take a break and try again in a few minutes.']);
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
$image_url = isset($body['header']['image_url']) ? (string)$body['header']['image_url'] : '';
$credit    = isset($body['header']['image_credit'])     ? (string)$body['header']['image_credit']     : '';
$credit_u  = isset($body['header']['image_credit_url']) ? (string)$body['header']['image_credit_url'] : '';

$title = trim($title);
if ($title === '' || mb_strlen($title) > MAX_TITLE_LEN) {
    http_response_code(400);
    echo json_encode(['error' => "Title is missing or too long."]);
    exit;
}
if (strpos($image_url, ALLOWED_IMAGE_HOST_PREFIX) !== 0) {
    http_response_code(400);
    echo json_encode(['error' => "Image must come from Pexels."]);
    exit;
}
$m = moderate_text($title);
if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }

// ---- Content ----
$content_body = isset($body['content']['body']) ? (string)$body['content']['body'] : '';
$content_body = trim($content_body);
if ($content_body === '' || mb_strlen($content_body) > MAX_BODY_LEN) {
    http_response_code(400);
    echo json_encode(['error' => "Content is missing or too long."]);
    exit;
}
$m = moderate_text($content_body);
if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }

// ---- Buttons ----
$buttons_in = isset($body['buttons']) && is_array($body['buttons']) ? $body['buttons'] : [];
if (count($buttons_in) < 1 || count($buttons_in) > MAX_BUTTONS) {
    http_response_code(400);
    echo json_encode(['error' => "Add 1 to " . MAX_BUTTONS . " buttons."]);
    exit;
}
$buttons = [];
foreach ($buttons_in as $b) {
    $label = isset($b['label']) ? trim((string)$b['label']) : '';
    if ($label === '' || mb_strlen($label) > MAX_BUTTON_LEN) {
        http_response_code(400);
        echo json_encode(['error' => "Each button needs a short label."]);
        exit;
    }
    $m = moderate_text($label);
    if (!$m['ok']) { http_response_code(400); echo json_encode(['error' => $m['reason']]); exit; }
    $buttons[] = ['label' => $label];
}

$build = [
    'header' => [
        'title'            => $title,
        'image_url'        => $image_url,
        'image_credit'     => $credit,
        'image_credit_url' => $credit_u,
    ],
    'content' => [
        'body' => $content_body,
    ],
    'buttons' => $buttons,
];

try {
    $code = save_build($build);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => "Couldn't save. Please try again."]);
    exit;
}

// Build the absolute-ish share URL (same host, /view.php?c=CODE)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? '';
$dir    = rtrim(str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
$url    = $scheme . '://' . $host . $dir . '/view.php?c=' . $code;

echo json_encode(['code' => $code, 'url' => $url]);
