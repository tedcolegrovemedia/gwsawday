// Phone preview renderer. All student text is inserted via textContent so
// there's no way to XSS the preview. Image URLs are validated to the Pexels
// host before being used as a background.

(function () {
  const ALLOWED_HOST = 'https://images.pexels.com/';

  function safeImage(url) {
    return (typeof url === 'string' && url.indexOf(ALLOWED_HOST) === 0) ? url : '';
  }

  // Expects:
  //   build = {
  //     header:  { title, image_url, image_credit, image_credit_url },
  //     content: { body },
  //     buttons: [{label}, ...]
  //   }
  function renderPhone(build) {
    const titleEl   = document.getElementById('preview-title');
    const bodyEl    = document.getElementById('preview-body');
    const btnWrap   = document.getElementById('preview-buttons');
    const headerEl  = document.querySelector('#phone-screen .app-header');

    const title = (build.header && build.header.title) || '';
    titleEl.textContent = title.length ? title : 'Your app name';

    const img = safeImage(build.header && build.header.image_url);
    if (img) {
      headerEl.classList.remove('empty');
      headerEl.style.backgroundImage = "url('" + img.replace(/'/g, "%27") + "')";
    } else {
      headerEl.classList.add('empty');
      headerEl.style.backgroundImage = '';
    }

    const body = (build.content && build.content.body) || '';
    bodyEl.textContent = body.length ? body : 'Your text will show up here.';

    // Rebuild buttons
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
  }

  window.renderPhone = renderPhone;
})();
