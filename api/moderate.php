<?php
// POST { text: string } -> { ok: bool, reason: string }

require_once __DIR__ . '/../includes/moderation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'reason' => 'POST only']);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || !isset($body['text']) || !is_string($body['text'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'reason' => 'Missing text.']);
    exit;
}

$text = substr($body['text'], 0, 400); // hard cap defensively
$result = moderate_text($text);
echo json_encode($result);
