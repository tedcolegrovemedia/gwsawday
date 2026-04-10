<?php
// GET ?q=<topic> -> { url, credit, credit_url } or { error }
// The topic is moderated before it's sent to Pexels, so the API never sees
// queries like "guns" or "drugs".

require_once __DIR__ . '/../includes/moderation.php';
require_once __DIR__ . '/../includes/pexels.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? (string)$_GET['q'] : '';
$q = trim($q);
if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please type a topic.']);
    exit;
}
if (strlen($q) > MAX_TOPIC_LEN) {
    $q = substr($q, 0, MAX_TOPIC_LEN);
}

$mod = moderate_text($q);
if (!$mod['ok']) {
    http_response_code(400);
    echo json_encode(['error' => $mod['reason']]);
    exit;
}

$result = pexels_search($q);
if (isset($result['error'])) {
    http_response_code(502);
    echo json_encode($result);
    exit;
}
echo json_encode($result);
