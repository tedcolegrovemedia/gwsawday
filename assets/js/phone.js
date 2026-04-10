// Phone preview renderer. All student text is inserted via textContent so
// there's no way to XSS the preview. Image URLs must be HTTPS.

(function () {
  function safeImage(url) {
    return (typeof url === 'string' && url.indexOf('https://') === 0) ? url : '';
  }

  // build = {
  //   header:  { title, font, image_url, image_credit, image_credit_url },
  //   colors:  { primary: '#hex', accent: '#hex' },
  //   cards:   [{title, caption, image_url}, ...],
  //   buttons: [{label}, ...],
  //   footer:  { text }
  // }
  function renderPhone(build) {
    const screen  = document.getElementById('phone-screen');
    const titleEl = document.getElementById('preview-title');
    const cardsWrap = document.getElementById('preview-cards');
    const btnWrap = document.getElementById('preview-buttons');
    const footerEl = document.getElementById('preview-footer');
    const headerEl = document.querySelector('#phone-screen .app-header');

    // Font
    const font = (build.header && build.header.font) || 'fredoka';
    screen.dataset.font = font;

    // Colors → CSS variables
    const primary = (build.colors && build.colors.primary) || '#14386b';
    const accent  = (build.colors && build.colors.accent)  || '#d35400';
    screen.style.setProperty('--app-primary', primary);
    screen.style.setProperty('--app-accent',  accent);

    // Title
    const title = (build.header && build.header.title) || '';
    titleEl.textContent = title.length ? title : 'Your app name';

    // Header image
    const headerImg = safeImage(build.header && build.header.image_url);
    if (headerImg) {
      headerEl.classList.remove('empty');
      headerEl.style.backgroundImage = "url('" + headerImg.replace(/'/g, "%27") + "')";
    } else {
      headerEl.classList.add('empty');
      headerEl.style.backgroundImage = '';
    }

    // Cards
    cardsWrap.textContent = '';
    const cards = Array.isArray(build.cards) ? build.cards : [];
    cards.forEach((c) => {
      const ctitle = (c && c.title) ? String(c.title) : '';
      const ccap   = (c && c.caption) ? String(c.caption) : '';
      if (!ctitle && !ccap) return;
      const el = document.createElement('div');
      el.className = 'app-card';
      const cardImg = safeImage(c && c.image_url);
      if (cardImg) {
        el.style.backgroundImage = "url('" + cardImg.replace(/'/g, "%27") + "')";
      } else {
        el.classList.add('empty');
      }

      const overlay = document.createElement('div');
      overlay.className = 'app-card-overlay';
      el.appendChild(overlay);

      const body = document.createElement('div');
      body.className = 'app-card-body';
      if (ctitle) {
        const t = document.createElement('div');
        t.className = 'app-card-title';
        t.textContent = ctitle;
        body.appendChild(t);
      }
      if (ccap) {
        const cap = document.createElement('div');
        cap.className = 'app-card-caption';
        cap.textContent = ccap;
        body.appendChild(cap);
      }
      el.appendChild(body);
      cardsWrap.appendChild(el);
    });

    // Buttons
    btnWrap.textContent = '';
    const buttons = Array.isArray(build.buttons) ? build.buttons : [];
    buttons.forEach((b) => {
      const label = (b && b.label) ? String(b.label).trim() : '';
      if (!label) return;
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'app-button';
      el.textContent = label;
      btnWrap.appendChild(el);
    });

    // Footer
    const foot = (build.footer && build.footer.text) || '';
    footerEl.textContent = foot;
  }

  window.renderPhone = renderPhone;
})();
