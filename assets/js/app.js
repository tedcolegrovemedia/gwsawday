// 3-step builder state machine. Talks to api/moderate.php, api/pexels.php,
// api/save.php. Live preview updates on every keystroke via renderPhone().

(function () {
  const cfg = window.APP_CONFIG || {};

  const state = {
    step: 1,
    build: {
      header:  { title: '', image_url: '', image_credit: '', image_credit_url: '' },
      content: { body: '' },
      buttons: [{ label: '' }]
    },
    pexelsCache: {} // topic -> result, to avoid refetching on Back/Next
  };

  // ---------- Helpers ----------
  const $  = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  function showStep(n) {
    state.step = n;
    $$('.step-panel').forEach(el => el.classList.toggle('is-active', Number(el.dataset.step) === n));
    $$('.step-pill').forEach(el => {
      const p = Number(el.dataset.pill);
      el.classList.toggle('is-active', p === n);
      el.classList.toggle('is-done', p < n && n <= 3);
    });
  }

  function setError(step, msg) {
    const el = document.getElementById('step-' + step + '-error');
    if (el) el.textContent = msg || '';
  }
  function clearErrors() { [1,2,3].forEach(n => setError(n, '')); }

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
      // Network problem — don't block the kid; let save.php be the hard gate.
      return { ok: true, reason: '' };
    }
  }

  async function pexels(q) {
    if (state.pexelsCache[q]) return state.pexelsCache[q];
    const res = await fetch('api/pexels.php?q=' + encodeURIComponent(q));
    const data = await res.json();
    if (!res.ok) return { error: data.error || 'Could not load a picture.' };
    state.pexelsCache[q] = data;
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

      const pic = await pexels(q);
      lockButtons(false);
      if (pic.error) return setError(1, pic.error);

      state.build.header.image_url        = pic.url || '';
      state.build.header.image_credit     = pic.credit || '';
      state.build.header.image_credit_url = pic.credit_url || '';
      updatePreview();
      showStep(2);
    });
  }

  // ---------- Step 2: content ----------
  function wireStep2() {
    const body = $('#f-body');
    const count = $('#f-body-count');
    body.addEventListener('input', () => {
      state.build.content.body = body.value;
      count.textContent = body.value.length;
      updatePreview();
    });
    $('[data-action="back-2"]').addEventListener('click', () => { clearErrors(); showStep(1); });
    $('[data-action="next-2"]').addEventListener('click', async () => {
      clearErrors();
      const v = body.value.trim();
      if (!v) return setError(2, 'Write a little bit about your app.');
      if (v.length > cfg.maxBody) return setError(2, 'That is too long.');
      lockButtons(true);
      const mod = await moderate(v);
      lockButtons(false);
      if (!mod.ok) return setError(2, mod.reason);
      showStep(3);
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
    $('[data-action="finish"]').addEventListener('click', async () => {
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
      const result = await save();
      lockButtons(false);
      if (result.error) return setError(3, result.error);

      $('#share-code').textContent = result.code;
      $('#share-url').value = result.url;
      showStep(4);
    });
  }

  // ---------- Step 4: done ----------
  function wireStep4() {
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
    updatePreview();
    showStep(1);
  });
})();
