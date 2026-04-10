<?php
// Server-side phone preview renderer for view.php. Every student field is
// escaped with htmlspecialchars() and the image URL is host-validated.

require_once __DIR__ . '/../config.php';

function render_phone_preview(array $build): string {
    $title  = (string)($build['header']['title']       ?? '');
    $img    = (string)($build['header']['image_url']   ?? '');
    $credit = (string)($build['header']['image_credit'] ?? '');
    $creditU= (string)($build['header']['image_credit_url'] ?? '');
    $body   = (string)($build['content']['body']       ?? '');
    $buttons = is_array($build['buttons'] ?? null) ? $build['buttons'] : [];

    if (strpos($img, ALLOWED_IMAGE_HOST_PREFIX) !== 0) {
        $img = '';
    }

    $h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    ob_start(); ?>
    <div class="phone">
      <div class="phone-frame">
        <div class="phone-notch"></div>
        <div class="phone-screen">
          <div class="app-header" style="<?= $img ? 'background-image:url(\'' . $h($img) . '\')' : '' ?>">
            <div class="app-header-overlay"></div>
            <h1 class="app-title"><?= $h($title) ?></h1>
          </div>
          <div class="app-content">
            <p class="app-body"><?= nl2br($h($body)) ?></p>
            <div class="app-buttons">
              <?php foreach ($buttons as $b): ?>
                <button type="button" class="app-button"><?= $h((string)($b['label'] ?? '')) ?></button>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if ($credit): ?>
            <p class="app-credit">Photo by <a href="<?= $h($creditU) ?>" target="_blank" rel="noopener"><?= $h($credit) ?></a> on Pexels</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
