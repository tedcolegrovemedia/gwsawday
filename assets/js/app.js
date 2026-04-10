// 5-step builder state machine. Talks to api/moderate.php, api/openverse.php,
// api/save.php. Live preview updates on every keystroke via renderPhone().
//
// Steps: 1=header, 2=colors, 3=fact cards, 4=buttons, 5=footer, 6=done.

(function () {
  const cfg = window.APP_CONFIG || {};
  const colorHex = cfg.colorHex || {};

  const state = {
    step: 1,
    topic: '',   // subject from step 1, reused to qualify card image searches
    build: {
      header: {
        title: '',
        font: 'fredoka',
        image_url: '',
        image_thumbnail: '',
        image_credit: '',
        image_credit_url: '',
        image_license: '',
        image_source: ''
      },
      colors: {
        primary: 'navy',    // palette key
        accent:  'orange'
      },
      cards: [
        { title: '', caption: '', image_url: '', image_thumbnail: '', image_credit: '', image_credit_url: '', image_license: '', image_source: '' }
      ],
      buttons: [{ label: '' }],
      footer: { text: '' }
    },
    imageCache: {},       // full query -> Openverse result
    cardDebounce: {},     // cardIndex -> timeout id
    // Live-moderation tracking: for each step, a Set of field keys that
    // currently contain text the moderator rejected. Any non-empty set
    // blocks that step's Next button.
    invalidFields: { 1: new Set(), 2: new Set(), 3: new Set(), 4: new Set(), 5: new Set() }
  };

  // ---------- Helpers ----------
  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  function showStep(n) {
    state.step = n;
    $$('.step-panel').forEach(el => el.classList.toggle('is-active', Number(el.dataset.step) === n));
    $$('.step-pill').forEach(el => {
      const p = Number(el.dataset.pill);
      el.classList.toggle('is-active', p === n);
      el.classList.toggle('is-done', n <= 5 && p < n);
    });
  }

  function setError(step, msg) {
    const el = document.getElementById('step-' + step + '-error');
    if (el) el.textContent = msg || '';
  }
  function clearErrors() { [1,2,3,4,5].forEach(n => setError(n, '')); }

  function lockButtons(lock) {
    $$('.step-actions .btn').forEach(b => b.disabled = lock);
  }

  // Build a shape the renderer can read — converts palette color keys to hex.
  function renderableBuild() {
    const b = state.build;
    return {
      header: b.header,
      colors: {
        primary: colorHex[b.colors.primary] || '#14386b',
        accent:  colorHex[b.colors.accent]  || '#d35400'
      },
      cards:   b.cards,
      buttons: b.buttons,
      footer:  b.footer
    };
  }
  function updatePreview() {
    if (window.renderPhone) window.renderPhone(renderableBuild());
  }

  // ---------- API calls ----------
  async function moderate(text) {
    try {
      const res = await fetch('api/moderate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text })
      });
      if (!res.ok && res.status !== 400) throw new Error('bad response');
      return await res.json();
    } catch (e) {
      return { ok: true, reason: '' }; // save.php is the hard gate
    }
  }

  async function searchImage(q) {
    if (state.imageCache[q]) return state.imageCache[q];
    const res = await fetch('api/openverse.php?q=' + encodeURIComponent(q));
    const data = await res.json();
    if (!res.ok) return { error: data.error || 'Could not load a picture.' };
    state.imageCache[q] = data;
    return data;
  }

  // For card backgrounds: try "topic + title", fall back to just title,
  // then just topic. Whichever returns results first wins. Specific
  // combined queries often have no Openverse results so a fallback
  // keeps cards from ending up blank.
  async function searchCardImage(topic, title) {
    const raw = [
      ((topic || '') + ' ' + (title || '')).trim(),
      (title || '').trim(),
      (topic || '').trim()
    ];
    const queries = raw.filter((v, i) => v && raw.indexOf(v) === i);
    for (const q of queries) {
      const r = await searchImage(q);
      if (!r.error) return r;
    }
    return { error: "No picture found." };
  }

  // ---------- Live moderation ----------
  // Attach debounced + blur-time moderation to a text input. If the
  // current value is rejected, we mark the input invalid, show the
  // step's error banner, and block Next until it clears.
  //
  // stepNum: 1..5 — which step's invalid-set this field belongs to.
  // fieldKey: unique string — used so the same field can toggle itself
  //           in and out of the invalid-set.
  function wireLiveModeration(input, stepNum, fieldKey) {
    let timer = null;

    const check = async () => {
      const val = (input.value || '').trim();
      if (val === '') {
        // Empty text is not "invalid" per the moderator — the required
        // check lives in the Next handlers.
        state.invalidFields[stepNum].delete(fieldKey);
        input.classList.remove('is-invalid');
        if (state.invalidFields[stepNum].size === 0) setError(stepNum, '');
        return;
      }
      const mod = await moderate(val);
      // If the input changed again while we were awaiting, bail — the
      // later keystroke scheduled its own check.
      if ((input.value || '').trim() !== val) return;
      if (mod.ok) {
        state.invalidFields[stepNum].delete(fieldKey);
        input.classList.remove('is-invalid');
        if (state.invalidFields[stepNum].size === 0) setError(stepNum, '');
      } else {
        state.invalidFields[stepNum].add(fieldKey);
        input.classList.add('is-invalid');
        setError(stepNum, mod.reason);
      }
    };

    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(check, 450);
    });
    input.addEventListener('blur', () => {
      clearTimeout(timer);
      check();
    });
  }

  // Remove any invalid-field entries whose keys start with the given
  // prefix. Used when we re-render dynamic lists (cards, buttons) so
  // stale entries from removed rows don't keep the step blocked.
  function clearInvalidByPrefix(stepNum, prefix) {
    const set = state.invalidFields[stepNum];
    [...set].forEach(k => { if (k.indexOf(prefix) === 0) set.delete(k); });
    if (set.size === 0) setError(stepNum, '');
  }

  async function save() {
    const res = await fetch('api/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(state.build)
    });
    const data = await res.json();
    if (!res.ok) return { error: data.error || 'Could not save your app.' };
    return data;
  }

  // ---------- Step 1: header ----------
  function wireStep1() {
    const title = $('#f-title');
    const topic = $('#f-topic');
    const titleCount = $('#f-title-count');
    const topicCount = $('#f-topic-count');

    title.addEventListener('input', () => {
      state.build.header.title = title.value;
      titleCount.textContent = title.value.length;
      updatePreview();
    });
    topic.addEventListener('input', () => {
      topicCount.textContent = topic.value.length;
    });

    // Live moderation — block bad words the moment we can detect them.
    wireLiveModeration(title, 1, 'title');
    wireLiveModeration(topic, 1, 'topic');

    $$('.font-option').forEach(btn => {
      btn.addEventListener('click', () => {
        $$('.font-option').forEach(b => b.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        state.build.header.font = btn.dataset.font;
        updatePreview();
      });
    });

    $('[data-action="next-1"]').addEventListener('click', async () => {
      clearErrors();
      const t = title.value.trim();
      const q = topic.value.trim();
      if (!t) return setError(1, 'Give your app a name first.');
      if (t.length > cfg.maxTitle) return setError(1, 'Name is too long.');
      if (!q) return setError(1, 'Tell us what your app is about.');

      lockButtons(true);
      const modT = await moderate(t);
      if (!modT.ok) {
        lockButtons(false);
        state.invalidFields[1].add('title');
        title.classList.add('is-invalid');
        return setError(1, modT.reason);
      }
      const modQ = await moderate(q);
      if (!modQ.ok) {
        lockButtons(false);
        state.invalidFields[1].add('topic');
        topic.classList.add('is-invalid');
        return setError(1, modQ.reason);
      }
      if (state.invalidFields[1].size > 0) {
        lockButtons(false);
        return setError(1, "Let's fix the words in red first.");
      }

      const pic = await searchImage(q);
      lockButtons(false);
      if (pic.error) return setError(1, pic.error);

      state.topic = q;
      state.build.header.image_url        = pic.url || '';
      state.build.header.image_thumbnail  = pic.thumbnail || '';
      state.build.header.image_credit     = pic.credit || '';
      state.build.header.image_credit_url = pic.credit_url || '';
      state.build.header.image_license    = pic.license || '';
      state.build.header.image_source     = pic.source || '';
      updatePreview();
      showStep(2);
    });
  }

  // ---------- Step 2: colors ----------
  function wireStep2() {
    $$('.color-picker').forEach(picker => {
      const role = picker.dataset.role; // 'primary' or 'accent'
      $$('.color-swatch', picker).forEach(sw => {
        sw.addEventListener('click', () => {
          $$('.color-swatch', picker).forEach(s => s.classList.remove('is-selected'));
          sw.classList.add('is-selected');
          state.build.colors[role] = sw.dataset.color;
          updatePreview();
        });
      });
    });

    $('[data-action="back-2"]').addEventListener('click', () => { clearErrors(); showStep(1); });
    $('[data-action="next-2"]').addEventListener('click', () => {
      clearErrors();
      showStep(3);
    });
  }

  // ---------- Step 3: fact cards ----------
  function fetchCardImage(idx) {
    const card = state.build.cards[idx];
    if (!card) return;
    const title = (card.title || '').trim();
    if (title.length < 3) return;

    // Update thumbnail to spinner state
    const editor = document.querySelector('.card-editor[data-index="' + idx + '"]');
    if (editor) {
      const thumb = editor.querySelector('.card-thumb');
      const spin = thumb.querySelector('.spinner');
      if (spin) spin.style.display = 'flex';
    }

    searchCardImage(state.topic, title).then(res => {
      // Guard against stale responses — only apply if this title is still the current one
      const current = state.build.cards[idx];
      if (!current || (current.title || '').trim() !== title) return;
      const editorNow = document.querySelector('.card-editor[data-index="' + idx + '"]');
      if (editorNow) {
        const spin = editorNow.querySelector('.card-thumb .spinner');
        if (spin) spin.style.display = 'none';
      }
      if (res.error) return; // silently leave card without image
      current.image_url        = res.url || '';
      current.image_thumbnail  = res.thumbnail || res.url || '';
      current.image_credit     = res.credit || '';
      current.image_credit_url = res.credit_url || '';
      current.image_license    = res.license || '';
      current.image_source     = res.source || '';

      // Update thumbnail in editor
      const editor2 = document.querySelector('.card-editor[data-index="' + idx + '"]');
      if (editor2) {
        const thumb = editor2.querySelector('.card-thumb');
        thumb.classList.add('has-image');
        thumb.style.backgroundImage = "url('" + (res.thumbnail || res.url).replace(/'/g, "%27") + "')";
        const ph = thumb.querySelector('.card-thumb-placeholder');
        if (ph) ph.style.display = 'none';
        const spin = thumb.querySelector('.spinner');
        if (spin) spin.style.display = 'none';
      }
      updatePreview();
    });
  }

  function scheduleCardImageFetch(idx) {
    clearTimeout(state.cardDebounce[idx]);
    state.cardDebounce[idx] = setTimeout(() => fetchCardImage(idx), 800);
  }

  function renderCardEditors() {
    const list = $('#cards-list');
    list.textContent = '';
    // Clear any stale invalid entries for this step; we'll re-add for
    // the current card editors as they validate.
    clearInvalidByPrefix(3, 'card-');
    state.build.cards.forEach((card, idx) => {
      const row = document.createElement('div');
      row.className = 'card-editor';
      row.dataset.index = String(idx);

      const thumb = document.createElement('div');
      thumb.className = 'card-thumb' + (card.image_url ? ' has-image' : '');
      const placeholder = document.createElement('span');
      placeholder.className = 'card-thumb-placeholder';
      placeholder.textContent = '📷';
      thumb.appendChild(placeholder);
      if (card.image_url) {
        const src = card.image_thumbnail || card.image_url;
        thumb.style.backgroundImage = "url('" + src.replace(/'/g, "%27") + "')";
        placeholder.style.display = 'none';
      }
      const spin = document.createElement('div');
      spin.className = 'spinner';
      spin.textContent = '⏳';
      spin.style.display = 'none';
      thumb.appendChild(spin);
      row.appendChild(thumb);

      const fields = document.createElement('div');
      fields.className = 'card-fields';

      const titleInput = document.createElement('input');
      titleInput.type = 'text';
      titleInput.maxLength = cfg.maxCardTitle;
      titleInput.placeholder = 'Fun fact headline';
      titleInput.value = card.title || '';
      titleInput.addEventListener('input', () => {
        state.build.cards[idx].title = titleInput.value;
        updatePreview();
        scheduleCardImageFetch(idx);
      });

      const capInput = document.createElement('textarea');
      capInput.rows = 2;
      capInput.maxLength = cfg.maxCardCaption;
      capInput.placeholder = 'The fun fact (e.g., "Dogs hear 4x better than humans!")';
      capInput.value = card.caption || '';
      capInput.addEventListener('input', () => {
        state.build.cards[idx].caption = capInput.value;
        updatePreview();
      });

      fields.appendChild(titleInput);
      fields.appendChild(capInput);
      row.appendChild(fields);

      wireLiveModeration(titleInput, 3, 'card-' + idx + '-title');
      wireLiveModeration(capInput,   3, 'card-' + idx + '-caption');

      if (state.build.cards.length > cfg.minCards) {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'card-remove';
        remove.setAttribute('aria-label', 'Remove card');
        remove.textContent = '×';
        remove.addEventListener('click', () => {
          state.build.cards.splice(idx, 1);
          renderCardEditors();
          updatePreview();
        });
        row.appendChild(remove);
      }

      list.appendChild(row);
    });
  }

  function wireStep3() {
    renderCardEditors();

    $('#add-card').addEventListener('click', () => {
      if (state.build.cards.length >= cfg.maxCards) return;
      state.build.cards.push({ title: '', caption: '', image_url: '', image_thumbnail: '', image_credit: '', image_credit_url: '', image_license: '', image_source: '' });
      renderCardEditors();
    });

    $('[data-action="back-3"]').addEventListener('click', () => { clearErrors(); showStep(2); });
    $('[data-action="next-3"]').addEventListener('click', async () => {
      clearErrors();
      if (state.invalidFields[3].size > 0) {
        return setError(3, "Let's fix the cards in red first.");
      }
      const cards = state.build.cards;
      if (cards.length < cfg.minCards) return setError(3, 'Add at least one fun fact.');
      for (let i = 0; i < cards.length; i++) {
        const c = cards[i];
        const t = (c.title || '').trim();
        const cap = (c.caption || '').trim();
        if (!t) return setError(3, 'Card ' + (i+1) + ' needs a headline.');
        if (!cap) return setError(3, 'Card ' + (i+1) + ' needs a fun fact.');
        if (t.length > cfg.maxCardTitle) return setError(3, 'Card ' + (i+1) + ' headline is too long.');
        if (cap.length > cfg.maxCardCaption) return setError(3, 'Card ' + (i+1) + ' fact is too long.');
      }
      lockButtons(true);
      for (let i = 0; i < cards.length; i++) {
        const m1 = await moderate(cards[i].title);
        if (!m1.ok) { lockButtons(false); return setError(3, m1.reason); }
        const m2 = await moderate(cards[i].caption);
        if (!m2.ok) { lockButtons(false); return setError(3, m2.reason); }
      }
      lockButtons(false);
      showStep(4);
    });
  }

  // ---------- Step 4: buttons ----------
  function collectButtons() {
    const arr = [];
    $$('#buttons-list .f-button').forEach(inp => {
      const v = inp.value.trim();
      if (v) arr.push({ label: v });
    });
    return arr;
  }

  function wireStep4() {
    const list = $('#buttons-list');

    let btnSeq = 0;
    function bindRow(row) {
      const input = row.querySelector('.f-button');
      const key = 'btn-' + (btnSeq++);
      input.addEventListener('input', () => {
        state.build.buttons = collectButtons();
        updatePreview();
      });
      wireLiveModeration(input, 4, key);
      const remove = row.querySelector('.row-remove');
      if (remove) {
        remove.addEventListener('click', () => {
          if (list.children.length <= 1) return;
          state.invalidFields[4].delete(key);
          if (state.invalidFields[4].size === 0) setError(4, '');
          row.remove();
          relabelRows();
          state.build.buttons = collectButtons();
          updatePreview();
        });
      }
    }

    function relabelRows() {
      $$('#buttons-list .button-row').forEach((row, i) => {
        const label = row.querySelector('.field-label');
        if (label) label.textContent = 'Button ' + (i + 1);
      });
    }

    $('#add-button').addEventListener('click', () => {
      if (list.children.length >= cfg.maxButtons) return;
      const row = document.createElement('label');
      row.className = 'field button-row';
      row.innerHTML =
        '<span class="field-label">Button ' + (list.children.length + 1) + '</span>' +
        '<input type="text" class="f-button" maxlength="' + cfg.maxButtonLen + '" placeholder="Learn more" autocomplete="off">' +
        '<button type="button" class="row-remove" aria-label="Remove button">×</button>';
      list.appendChild(row);
      bindRow(row);
    });

    bindRow(list.querySelector('.button-row'));

    $('[data-action="back-4"]').addEventListener('click', () => { clearErrors(); showStep(3); });
    $('[data-action="next-4"]').addEventListener('click', async () => {
      clearErrors();
      if (state.invalidFields[4].size > 0) {
        return setError(4, "Let's fix the buttons in red first.");
      }
      const btns = collectButtons();
      if (btns.length < 1) return setError(4, 'Add at least one button.');
      if (btns.length > cfg.maxButtons) return setError(4, 'Too many buttons.');
      for (const b of btns) {
        if (b.label.length > cfg.maxButtonLen) return setError(4, 'Button labels are too long.');
      }
      state.build.buttons = btns;
      lockButtons(true);
      for (const b of btns) {
        const mod = await moderate(b.label);
        if (!mod.ok) { lockButtons(false); return setError(4, mod.reason); }
      }
      lockButtons(false);
      showStep(5);
    });
  }

  // ---------- Step 5: footer ----------
  function wireStep5() {
    const footer = $('#f-footer');
    const count  = $('#f-footer-count');
    footer.addEventListener('input', () => {
      state.build.footer.text = footer.value;
      count.textContent = footer.value.length;
      updatePreview();
    });
    wireLiveModeration(footer, 5, 'footer');
    $('[data-action="back-5"]').addEventListener('click', () => { clearErrors(); showStep(4); });
    $('[data-action="finish"]').addEventListener('click', async () => {
      clearErrors();
      if (state.invalidFields[5].size > 0) {
        return setError(5, "Let's fix the footer first.");
      }
      const t = footer.value.trim();
      if (!t) return setError(5, 'Write a short footer.');
      if (t.length > cfg.maxFooter) return setError(5, 'Footer is too long.');
      lockButtons(true);
      const mod = await moderate(t);
      if (!mod.ok) {
        lockButtons(false);
        state.invalidFields[5].add('footer');
        footer.classList.add('is-invalid');
        return setError(5, mod.reason);
      }
      const result = await save();
      lockButtons(false);
      if (result.error) return setError(5, result.error);

      $('#share-code').textContent = result.code;
      $('#share-url').value = result.url;
      showStep(6);
    });
  }

  // ---------- Done panel ----------
  function wireDone() {
    $('#copy-url').addEventListener('click', () => {
      const el = $('#share-url');
      el.select();
      try { document.execCommand('copy'); } catch (e) {}
      const btn = $('#copy-url');
      const orig = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(() => { btn.textContent = orig; }, 1500);
    });
    $('#build-again').addEventListener('click', () => { location.reload(); });
  }

  // ---------- Boot ----------
  document.addEventListener('DOMContentLoaded', () => {
    wireStep1();
    wireStep2();
    wireStep3();
    wireStep4();
    wireStep5();
    wireDone();
    updatePreview();
    showStep(1);
  });
})();
