// 4-step builder state machine. Talks to api/moderate.php, api/openverse.php,
// api/save.php. Live preview updates on every keystroke via renderPhone().

(function () {
  const cfg = window.APP_CONFIG || {};

  const state = {
    step: 1,
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
      cards: [
        { emoji: '', title: '', caption: '' }
      ],
      buttons: [{ label: '' }],
      footer: { text: '' }
    },
    imageCache: {}, // topic -> Openverse result
    emojiTargetCardIndex: null
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
      el.classList.toggle('is-done', n <= 4 && p < n);
    });
  }

  function setError(step, msg) {
    const el = document.getElementById('step-' + step + '-error');
    if (el) el.textContent = msg || '';
  }
  function clearErrors() { [1,2,3,4].forEach(n => setError(n, '')); }

  function lockButtons(lock) {
    $$('.step-actions .btn').forEach(b => b.disabled = lock);
  }

  function updatePreview() {
    if (window.renderPhone) window.renderPhone(state.build);
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
      // Network issue — don't block the kid; save.php is the hard gate.
      return { ok: true, reason: '' };
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

  // ---------- Step 1: header (name + topic + font) ----------
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

    // Font picker
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
      if (!q) return setError(1, 'Pick a topic for your picture.');

      lockButtons(true);
      const modT = await moderate(t);
      if (!modT.ok) { lockButtons(false); return setError(1, modT.reason); }
      const modQ = await moderate(q);
      if (!modQ.ok) { lockButtons(false); return setError(1, modQ.reason); }

      const pic = await searchImage(q);
      lockButtons(false);
      if (pic.error) return setError(1, pic.error);

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

  // ---------- Step 2: cards ----------
  function renderCardEditors() {
    const list = $('#cards-list');
    list.textContent = '';
    state.build.cards.forEach((card, idx) => {
      const row = document.createElement('div');
      row.className = 'card-editor';
      row.dataset.index = String(idx);

      const emojiBtn = document.createElement('button');
      emojiBtn.type = 'button';
      emojiBtn.className = 'card-emoji-btn' + (card.emoji ? '' : ' is-empty');
      emojiBtn.textContent = card.emoji || 'Pick';
      emojiBtn.addEventListener('click', () => openEmojiPicker(idx));
      row.appendChild(emojiBtn);

      const fields = document.createElement('div');
      fields.className = 'card-fields';

      const titleInput = document.createElement('input');
      titleInput.type = 'text';
      titleInput.maxLength = cfg.maxCardTitle;
      titleInput.placeholder = 'Card title';
      titleInput.value = card.title || '';
      titleInput.addEventListener('input', () => {
        state.build.cards[idx].title = titleInput.value;
        updatePreview();
      });

      const capInput = document.createElement('input');
      capInput.type = 'text';
      capInput.maxLength = cfg.maxCardCaption;
      capInput.placeholder = 'Short caption (optional)';
      capInput.value = card.caption || '';
      capInput.addEventListener('input', () => {
        state.build.cards[idx].caption = capInput.value;
        updatePreview();
      });

      fields.appendChild(titleInput);
      fields.appendChild(capInput);
      row.appendChild(fields);

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

  function wireStep2() {
    renderCardEditors();

    $('#add-card').addEventListener('click', () => {
      if (state.build.cards.length >= cfg.maxCards) return;
      state.build.cards.push({ emoji: '', title: '', caption: '' });
      renderCardEditors();
    });

    $('[data-action="back-2"]').addEventListener('click', () => { clearErrors(); showStep(1); });
    $('[data-action="next-2"]').addEventListener('click', async () => {
      clearErrors();
      const cards = state.build.cards;
      if (cards.length < cfg.minCards) return setError(2, 'Add at least one card.');
      for (let i = 0; i < cards.length; i++) {
        const c = cards[i];
        if (!c.emoji) return setError(2, 'Card ' + (i+1) + ' needs an emoji.');
        const t = (c.title || '').trim();
        if (!t) return setError(2, 'Card ' + (i+1) + ' needs a title.');
        if (t.length > cfg.maxCardTitle) return setError(2, 'Card ' + (i+1) + ' title is too long.');
        const cap = (c.caption || '').trim();
        if (cap.length > cfg.maxCardCaption) return setError(2, 'Card ' + (i+1) + ' caption is too long.');
      }
      lockButtons(true);
      for (let i = 0; i < cards.length; i++) {
        const m1 = await moderate(cards[i].title);
        if (!m1.ok) { lockButtons(false); return setError(2, m1.reason); }
        if (cards[i].caption) {
          const m2 = await moderate(cards[i].caption);
          if (!m2.ok) { lockButtons(false); return setError(2, m2.reason); }
        }
      }
      lockButtons(false);
      showStep(3);
    });
  }

  // ---------- Emoji picker ----------
  function openEmojiPicker(cardIndex) {
    state.emojiTargetCardIndex = cardIndex;
    $('#emoji-modal').hidden = false;
  }
  function closeEmojiPicker() {
    state.emojiTargetCardIndex = null;
    $('#emoji-modal').hidden = true;
  }
  function wireEmojiPicker() {
    $$('#emoji-modal [data-close]').forEach(el => el.addEventListener('click', closeEmojiPicker));
    $$('.emoji-cell').forEach(cell => {
      cell.addEventListener('click', () => {
        const i = state.emojiTargetCardIndex;
        if (i === null || i === undefined) return;
        state.build.cards[i].emoji = cell.dataset.emoji;
        closeEmojiPicker();
        renderCardEditors();
        updatePreview();
      });
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeEmojiPicker();
    });
  }

  // ---------- Step 3: buttons ----------
  function collectButtons() {
    const arr = [];
    $$('#buttons-list .f-button').forEach(inp => {
      const v = inp.value.trim();
      if (v) arr.push({ label: v });
    });
    return arr;
  }

  function wireStep3() {
    const list = $('#buttons-list');

    function bindRow(row) {
      const input = row.querySelector('.f-button');
      input.addEventListener('input', () => {
        state.build.buttons = collectButtons();
        updatePreview();
      });
      const remove = row.querySelector('.row-remove');
      if (remove) {
        remove.addEventListener('click', () => {
          if (list.children.length <= 1) return;
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

    $('[data-action="back-3"]').addEventListener('click', () => { clearErrors(); showStep(2); });
    $('[data-action="next-3"]').addEventListener('click', async () => {
      clearErrors();
      const btns = collectButtons();
      if (btns.length < 1) return setError(3, 'Add at least one button.');
      if (btns.length > cfg.maxButtons) return setError(3, 'Too many buttons.');
      for (const b of btns) {
        if (b.label.length > cfg.maxButtonLen) return setError(3, 'Button labels are too long.');
      }
      state.build.buttons = btns;
      lockButtons(true);
      for (const b of btns) {
        const mod = await moderate(b.label);
        if (!mod.ok) { lockButtons(false); return setError(3, mod.reason); }
      }
      lockButtons(false);
      showStep(4);
    });
  }

  // ---------- Step 4: footer ----------
  function wireStep4() {
    const footer = $('#f-footer');
    const count  = $('#f-footer-count');
    footer.addEventListener('input', () => {
      state.build.footer.text = footer.value;
      count.textContent = footer.value.length;
      updatePreview();
    });
    $('[data-action="back-4"]').addEventListener('click', () => { clearErrors(); showStep(3); });
    $('[data-action="finish"]').addEventListener('click', async () => {
      clearErrors();
      const t = footer.value.trim();
      if (!t) return setError(4, 'Write a short footer.');
      if (t.length > cfg.maxFooter) return setError(4, 'Footer is too long.');
      lockButtons(true);
      const mod = await moderate(t);
      if (!mod.ok) { lockButtons(false); return setError(4, mod.reason); }
      const result = await save();
      lockButtons(false);
      if (result.error) return setError(4, result.error);

      $('#share-code').textContent = result.code;
      $('#share-url').value = result.url;
      showStep(5);
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
    wireEmojiPicker();
    wireDone();
    updatePreview();
    showStep(1);
  });
})();
