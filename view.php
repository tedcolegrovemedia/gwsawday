<?php
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/render.php';
require_once __DIR__ . '/includes/fonts.php';

$code = isset($_GET['c']) ? (string)$_GET['c'] : '';
$code = strtoupper(trim($code));

if (!is_valid_code($code)) {
    http_response_code(400);
    $error = "That share code doesn't look right.";
    $build = null;
} else {
    $build = load_build($code);
    if (!$build) {
        http_response_code(404);
        $error = "We couldn't find an app with that code.";
    } else {
        $error = null;
    }
}

$font_key = 'fredoka';
if ($build && is_valid_font((string)($build['header']['font'] ?? ''))) {
    $font_key = (string)$build['header']['font'];
}

// Load only the font this build actually uses + Nunito for the page UI.
$build_font = font_or_default($font_key);
$fonts_href = 'https://fonts.googleapis.com/css2?family=' . $build_font['family']
            . '&family=Nunito:wght@400;600&display=swap';

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $build ? $h($build['header']['title'] ?? 'Student App') : 'App not found' ?> — Governor Wolf</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?= $h($fonts_href) ?>" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="view-page">
  <header class="site-header">
    <h1>Governor Wolf</h1>
    <h2>Sharpen the Saw Day</h2>
    <h3>Let's build a mobile app</h3>
  </header>

  <main class="view-main">
    <?php if ($build): ?>
      <?= render_phone_preview($build) ?>
      <p class="share-note">
        Made by a Governor Wolf student · Share code: <strong><?= $h($build['code'] ?? '') ?></strong>
      </p>
      <p class="share-actions">
        <a class="btn btn-primary" href="index.php">Build your own</a>
      </p>
    <?php else: ?>
      <div class="error-card">
        <h2>Oops!</h2>
        <p><?= $h($error ?? 'Something went wrong.') ?></p>
        <p><a class="btn btn-primary" href="index.php">Back to the builder</a></p>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
