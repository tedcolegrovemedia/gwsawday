<?php require_once __DIR__ . '/config.php'; ?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Governor Wolf — Let's Build a Mobile App</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
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
        <li class="step-pill"            data-pill="2"><span>2</span> Content</li>
        <li class="step-pill"            data-pill="3"><span>3</span> Buttons</li>
      </ol>

      <!-- Step 1: Header -->
      <div class="step-panel is-active" data-step="1">
        <h2>Step 1 · Design the header</h2>
        <p class="step-hint">Give your app a name and choose a picture for the top.</p>

        <label class="field">
          <span class="field-label">App name</span>
          <input type="text" id="f-title" maxlength="<?= (int)MAX_TITLE_LEN ?>" placeholder="My Awesome App" autocomplete="off">
          <span class="field-counter"><span id="f-title-count">0</span>/<?= (int)MAX_TITLE_LEN ?></span>
        </label>

        <label class="field">
          <span class="field-label">Picture topic</span>
          <input type="text" id="f-topic" maxlength="<?= (int)MAX_TOPIC_LEN ?>" placeholder="puppies, ocean, space..." autocomplete="off">
          <span class="field-counter"><span id="f-topic-count">0</span>/<?= (int)MAX_TOPIC_LEN ?></span>
        </label>

        <p class="step-error" id="step-1-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-primary" data-action="next-1">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 2: Content -->
      <div class="step-panel" data-step="2">
        <h2>Step 2 · Write the content</h2>
        <p class="step-hint">Tell people what your app is about. Keep it friendly!</p>

        <label class="field">
          <span class="field-label">Main text</span>
          <textarea id="f-body" rows="5" maxlength="<?= (int)MAX_BODY_LEN ?>" placeholder="Welcome! This app is about..."></textarea>
          <span class="field-counter"><span id="f-body-count">0</span>/<?= (int)MAX_BODY_LEN ?></span>
        </label>

        <p class="step-error" id="step-2-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-2">&larr; Back</button>
          <button class="btn btn-primary"  data-action="next-2">Next &rarr;</button>
        </div>
      </div>

      <!-- Step 3: Buttons -->
      <div class="step-panel" data-step="3">
        <h2>Step 3 · Add buttons</h2>
        <p class="step-hint">Add 1 to <?= (int)MAX_BUTTONS ?> buttons. Pick short, clear names.</p>

        <div id="buttons-list">
          <label class="field button-row">
            <span class="field-label">Button 1</span>
            <input type="text" class="f-button" maxlength="<?= (int)MAX_BUTTON_LEN ?>" placeholder="Learn more" autocomplete="off">
            <button type="button" class="row-remove" aria-label="Remove button">×</button>
          </label>
        </div>
        <button type="button" class="btn btn-ghost" id="add-button">+ Add another button</button>

        <p class="step-error" id="step-3-error" aria-live="polite"></p>

        <div class="step-actions">
          <button class="btn btn-secondary" data-action="back-3">&larr; Back</button>
          <button class="btn btn-primary"  data-action="finish">Finish &amp; Save</button>
        </div>
      </div>

      <!-- Done panel -->
      <div class="step-panel" data-step="4">
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
          <div class="phone-screen" id="phone-screen">
            <div class="app-header empty">
              <div class="app-header-overlay"></div>
              <h1 class="app-title" id="preview-title">Your app name</h1>
            </div>
            <div class="app-content">
              <p class="app-body" id="preview-body">Your text will show up here.</p>
              <div class="app-buttons" id="preview-buttons"></div>
            </div>
          </div>
        </div>
      </div>
      <p class="preview-hint">Live preview — watch it change as you build!</p>
    </section>
  </main>

  <script>
    window.APP_CONFIG = {
      maxTitle:  <?= (int)MAX_TITLE_LEN ?>,
      maxTopic:  <?= (int)MAX_TOPIC_LEN ?>,
      maxBody:   <?= (int)MAX_BODY_LEN ?>,
      maxButtons:<?= (int)MAX_BUTTONS ?>,
      maxButtonLen:<?= (int)MAX_BUTTON_LEN ?>
    };
  </script>
  <script src="assets/js/phone.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
