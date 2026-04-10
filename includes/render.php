<?php
// Server-side phone preview renderer for view.php. Every student field is
// escaped with htmlspecialchars() and the image URL is validated.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/fonts.php';

function render_phone_preview(array $build): string {
    $title    = (string)($build['header']['title']            ?? '');
    $font_key = (string)($build['header']['font']             ?? 'fredoka');
    $img      = (string)($build['header']['image_url']        ?? '');
    $credit   = (string)($build['header']['image_credit']     ?? '');
    $creditU  = (string)($build['header']['image_credit_url'] ?? '');
    $license  = (string)($build['header']['image_license']    ?? '');
    $source   = (string)($build['header']['image_source']     ?? '');

    $cards    = is_array($build['cards']   ?? null) ? $build['cards']   : [];
    $buttons  = is_array($build['buttons'] ?? null) ? $build['buttons'] : [];
    $footer   = (string)($build['footer']['text'] ?? '');

    if (strpos($img, 'https://') !== 0) $img = '';
    if (!is_valid_font($font_key)) $font_key = 'fredoka';

    $h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    ob_start(); ?>
    <div class="phone">
      <div class="phone-frame">
        <div class="phone-notch"></div>
        <div class="phone-screen" data-font="<?= $h($font_key) ?>">
          <div class="app-header<?= $img ? '' : ' empty' ?>" style="<?= $img ? 'background-image:url(\'' . $h($img) . '\')' : '' ?>">
            <div class="app-header-overlay"></div>
            <h1 class="app-title"><?= $h($title) ?></h1>
          </div>
          <div class="app-content">
            <div class="app-cards">
              <?php foreach ($cards as $i => $c): ?>
                <div class="app-card card-<?= (int)($i % 3) ?>">
                  <div class="app-card-emoji"><?= $h((string)($c['emoji'] ?? '')) ?></div>
                  <div class="app-card-body">
                    <div class="app-card-title"><?= $h((string)($c['title'] ?? '')) ?></div>
                    <?php if (!empty($c['caption'])): ?>
                      <div class="app-card-caption"><?= $h((string)$c['caption']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="app-buttons">
              <?php foreach ($buttons as $b): ?>
                <button type="button" class="app-button"><?= $h((string)($b['label'] ?? '')) ?></button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="app-footer">
            <div class="app-footer-text"><?= $h($footer) ?></div>
            <?php if ($credit): ?>
              <div class="app-credit">
                Image by <?php if ($creditU): ?><a href="<?= $h($creditU) ?>" target="_blank" rel="noopener"><?= $h($credit) ?></a><?php else: ?><?= $h($credit) ?><?php endif; ?>
                <?php if ($source): ?> · <?= $h($source) ?><?php endif; ?>
                <?php if ($license): ?> · <?= $h($license) ?><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
