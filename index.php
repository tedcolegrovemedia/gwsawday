<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/fonts.php';
require_once __DIR__ . '/includes/colors.php';

// Preload every font option on the builder page so students see instant
// font changes. Each font is keyed to Google Fonts using the registry.
$all_fonts = available_fonts();
$font_families = [];
foreach ($all_fonts as $f) {
    $font_families[] = 'family=' . $f['family'];
}
$font_families[] = 'family=Nunito:wght@400;600';
$fonts_href = 'https://fonts.googleapis.com/css2?' . implode('&', $font_families) . '&display=swap';

$all_colors = available_colors();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Governor Wolf — Let's Build a Mobile App</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?= htmlspecialchars($fonts_href, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <h1>Governor Wolf</h1>
    <h2>Sharpen the Saw Day</h2>
    <h3>Let's build a mobile app</h3>
  </header>

  <main class="builder">
    <!-- LEFT: step-by-step builder -->
    <section class="builder-panel" aria-label="App builder">
      <ol class="step-indicator" aria-hidden="true">
        <li class="step-pill is-active" data-pill="1"><span>1</span> Header</li>
        <li class="step-pill"            data-pill="2"><span>2</span> Colors</li>
        <li class="step-pill"            data-pill="3"><span>3</span> Facts</li>
        <li class="step-pill"            data-pill="4"><span>4</span> Buttons</li>
        <li class="step-pill"            data-pill="5"><span>5</span> Footer</li>
      </ol>

      <!-- Step 1: Header (name + image topic + font) -->
      <div class="step-panel is-active" data-step="1">
        <h2>Step 1 · Design the header</h2>
        <p class="step-hint">Give your app a name, pick a subject for your pictures, and choose a font style.</p>

        <label class="field">
          <span class="field-label">App name</span>
          <input type="text" id="f-title" maxlength="<?= (int)MAX_TITLE_LEN ?>" placeholder="My Awesome App" autocomplete="off">
          <span class="field-counter"><span id="f-title-count">0</span>/<?= (int)MAX_TITLE_LEN ?></span>
        </label>

        <label class="field">
          <span class="field-label">Subject (what is your app about?)</span>
          <input type="text" id="f-topic" maxlength="<?= (int)MAX_TOPIC_LEN ?>" placeholder="puppies, ocean, space..." autocomplete="off">
          <span class="field-counter"><span id="f-topic-count">0</span>/<?= (int)MAX_TOPIC_LEN ?></span>
        </label>

        <div class="field">
          <span class="field-label">Font style</span>
          <div class="font-picker" role="radiogroup" aria-label="Font style">
            <?php foreach ($all_fonts as $key => $f): ?>
              <button type="button"
                      class="font-option<?= $key === 'fredoka' ? ' is-selected' : '' ?>"
                      data-font="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                      style="font-family: <?= htmlspecialchars($f['stack'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="font-option-name"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="font-option-sample"><?= htmlspecialchars($f['sample'], ENT_QUOTES, 'UTF-8') ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <p class="step-error" id="step-1-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-primary" data-action="next-1">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 2: Colors -->
      <div class="step-panel" data-step="2">
        <h2>Step 2 · Pick your colors</h2>
        <p class="step-hint">Pick a <strong>main color</strong> for buttons and a <strong>accent color</strong> for the footer.</p>

        <div class="field">
          <span class="field-label">Main color (buttons)</span>
          <div class="color-picker" data-role="primary" role="radiogroup" aria-label="Main color">
            <?php foreach ($all_colors as $key => $c): ?>
              <button type="button"
                      class="color-swatch<?= $key === 'navy' ? ' is-selected' : '' ?>"
                      data-color="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                      style="background: <?= htmlspecialchars($c['hex'], ENT_QUOTES, 'UTF-8') ?>"
                      aria-label="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"
                      title="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <span class="field-label">Accent color (footer)</span>
          <div class="color-picker" data-role="accent" role="radiogroup" aria-label="Accent color">
            <?php foreach ($all_colors as $key => $c): ?>
              <button type="button"
                      class="color-swatch<?= $key === 'orange' ? ' is-selected' : '' ?>"
                      data-color="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                      style="background: <?= htmlspecialchars($c['hex'], ENT_QUOTES, 'UTF-8') ?>"
                      aria-label="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"
                      title="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>

        <p class="step-error" id="step-2-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-2">&larr; Back</button>
          <button class="btn btn-primary"  data-action="next-2">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 3: Fact cards -->
      <div class="step-panel" data-step="3">
        <h2>Step 3 · Add fun fact cards</h2>
        <p class="step-hint">Write up to <?= (int)MAX_CARDS ?> fun facts about your subject. We'll find a background picture for each one as you type!</p>

        <div id="cards-list"></div>
        <button type="button" class="btn btn-ghost" id="add-card">+ Add another fact</button>

        <p class="step-error" id="step-3-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-3">&larr; Back</button>
          <button class="btn btn-primary"  data-action="next-3">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 4: Buttons -->
      <div class="step-panel" data-step="4">
        <h2>Step 4 · Add buttons</h2>
        <p class="step-hint">Add 1 to <?= (int)MAX_BUTTONS ?> buttons. Pick short, clear names.</p>

        <div id="buttons-list">
          <label class="field button-row">
            <span class="field-label">Button 1</span>
            <input type="text" class="f-button" maxlength="<?= (int)MAX_BUTTON_LEN ?>" placeholder="Learn more" autocomplete="off">
            <button type="button" class="row-remove" aria-label="Remove button">×</button>
          </label>
        </div>
        <button type="button" class="btn btn-ghost" id="add-button">+ Add another button</button>

        <p class="step-error" id="step-4-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-4">&larr; Back</button>
          <button class="btn btn-primary"  data-action="next-4">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 5: Footer -->
      <div class="step-panel" data-step="5">
        <h2>Step 5 · Write the footer</h2>
        <p class="step-hint">Add a short footer. Something like "Made by Sam" or "© Governor Wolf 2026".</p>

        <label class="field">
          <span class="field-label">Footer text</span>
          <input type="text" id="f-footer" maxlength="<?= (int)MAX_FOOTER_LEN ?>" placeholder="Made by you!" autocomplete="off">
          <span class="field-counter"><span id="f-footer-count">0</span>/<?= (int)MAX_FOOTER_LEN ?></span>
        </label>

        <p class="step-error" id="step-5-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-5">&larr; Back</button>
          <button class="btn btn-primary"  data-action="finish">Finish &amp; Save</button>
        </div>
      </div>

      <!-- Done panel -->
      <div class="step-panel" data-step="6">
        <h2>Your app is saved!</h2>
        <p class="step-hint">Share your code with your teacher or friends so they can see your app.</p>
        <div class="share-box">
          <div class="share-code" id="share-code">------</div>
          <input type="text" id="share-url" readonly>
          <button class="btn btn-primary" id="copy-url">Copy link</button>
        </div>
        <div class="step-actions">
          <button class="btn btn-secondary" id="build-again">Build another app</button>
        </div>
      </div>
    </section>

    <!-- RIGHT: phone preview -->
    <section class="preview-panel" aria-label="Live preview">
      <div class="phone">
        <div class="phone-frame">
          <div class="phone-notch"></div>
          <div class="phone-screen" id="phone-screen" data-font="fredoka" style="--app-primary:#14386b;--app-accent:#d35400;">
            <div class="app-header empty">
              <div class="app-header-overlay"></div>
              <h1 class="app-title" id="preview-title">Your app name</h1>
            </div>
            <div class="app-content">
              <div class="app-cards" id="preview-cards"></div>
              <div class="app-buttons" id="preview-buttons"></div>
            </div>
            <div class="app-footer" id="preview-footer-wrap">
              <div class="app-footer-text" id="preview-footer"></div>
            </div>
          </div>
        </div>
      </div>
      <p class="preview-hint">Live preview — watch it change as you build!</p>
    </section>
  </main>

  <script>
    window.APP_CONFIG = {
      maxTitle:        <?= (int)MAX_TITLE_LEN ?>,
      maxTopic:        <?= (int)MAX_TOPIC_LEN ?>,
      maxFooter:       <?= (int)MAX_FOOTER_LEN ?>,
      maxCards:        <?= (int)MAX_CARDS ?>,
      minCards:        <?= (int)MIN_CARDS ?>,
      maxCardTitle:    <?= (int)MAX_CARD_TITLE_LEN ?>,
      maxCardCaption:  <?= (int)MAX_CARD_CAPTION_LEN ?>,
      maxButtons:      <?= (int)MAX_BUTTONS ?>,
      maxButtonLen:    <?= (int)MAX_BUTTON_LEN ?>,
      colorHex: <?= json_encode(array_combine(array_keys($all_colors), array_column($all_colors, 'hex'))) ?>
    };
  </script>
  <script src="assets/js/phone.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
