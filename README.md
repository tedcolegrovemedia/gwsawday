# Governor Wolf — "Let's Build a Mobile App"

A kid-friendly, 3-step mobile app builder for Sharpen the Saw Day at Governor
Wolf Elementary. Students pick a title and a header image, write a short
description, and add buttons — all with a live phone preview on the right.
Finished builds save to a 6-character share code they can show off.

Runs on plain PHP 7.4+ / 8.x shared hosting. No database. No Node. No Composer.

## Setup

1. Upload every file in this folder to your web host.
2. Open `config.php` and fill in:
   - `OPENAI_API_KEY` — optional. If blank, we use the local wordlist.
   - `PEXELS_API_KEY` — **required**. Get a free key at https://www.pexels.com/api/
3. Make sure the `data/` directory is **writable** by PHP (often `755` or `775`).
   The app creates `data/builds/`, `data/cache/`, and `data/ratelimit/` on demand.
4. Visit `index.php` in a browser.

### Optional: force offline moderation

If you don't have an OpenAI key, or want moderation to work fully offline,
set `USE_OPENAI` to `false` in `config.php`. The local wordlist in
`includes/wordlist.php` will be the only filter.

## How it works

1. **Step 1 — Header.** Student types an app name + a picture topic (e.g. "puppy").
   Both strings are sent to `api/moderate.php`. If they pass, `api/pexels.php`
   searches Pexels and returns a kid-safe portrait image.
2. **Step 2 — Content.** A short paragraph, moderated the same way.
3. **Step 3 — Buttons.** 1–3 button labels, each moderated.
4. **Finish.** The whole build is POSTed to `api/save.php`, which
   **re-moderates every field server-side** (client checks are UX only),
   validates the image URL, writes a JSON file to `data/builds/<CODE>.json`,
   and returns a share code. The student sees the code and a copyable
   `view.php?c=CODE` link.

## Content safety

- **Server-side moderation is the hard gate.** `api/save.php` runs every
  text field through `moderate_text()` again regardless of what the client did.
- When `USE_OPENAI` is on and a key is present, text goes to the free
  [OpenAI Moderations endpoint](https://platform.openai.com/docs/guides/moderation).
  If that API fails (network, rate limit, etc.) we fall back to the wordlist
  automatically so kids are never stuck.
- The local wordlist in `includes/wordlist.php` is editable. Lowercase only,
  one entry per line. Common leetspeak variants (sh!t, 5hit, s h i t) are
  normalized before matching.
- PII checks block phone numbers, emails, and street addresses regardless of
  backend.
- The Pexels topic is moderated **before** it hits the API, so a query like
  "guns" never leaves the server.

## Security

- All student text is escaped with `htmlspecialchars()` in PHP and inserted via
  `textContent` in JS. No `innerHTML` on user input anywhere.
- Image URLs are allow-listed to `https://images.pexels.com/`.
- Share codes use a regex-validated alphabet (`[A-HJ-NP-Z2-9]{6}`), so no
  path traversal is possible when loading `data/builds/<CODE>.json`.
- `data/.htaccess` denies direct web access. Builds are only visible through
  `view.php`.
- `api/save.php` rate-limits each IP to 10 saves per 10 minutes.

## File layout

```
index.php              Main builder page
view.php               Renders a saved build by ?c=CODE
config.php             API keys + limits
api/
  moderate.php         POST { text } → { ok, reason }
  pexels.php           GET  ?q=topic → { url, credit, credit_url }
  save.php             POST build JSON → { code, url }
includes/
  moderation.php       moderate_text() — dispatches OpenAI or wordlist
  wordlist.php         Editable blocklist
  pexels.php           Pexels search + 10-min cache
  storage.php          save_build / load_build / generate_code / rate limit
  render.php           Shared phone HTML renderer used by view.php
data/
  .htaccess            Deny from all
  builds/              Saved .json builds (one per share code)
  cache/               Pexels response cache
  ratelimit/           Per-IP save counters
assets/
  css/style.css        Page + phone frame styles
  js/app.js            3-step builder state machine
  js/phone.js          Live preview renderer
```

## Testing checklist

- [ ] Happy path: build → get share code → open `view.php?c=CODE` in a new tab.
- [ ] Profanity in title is blocked (wordlist mode).
- [ ] Profanity in title is blocked (OpenAI mode, if configured).
- [ ] Weapon/drug topic is rejected before Pexels is called.
- [ ] Raw POST to `api/save.php` with a bad word returns 400.
- [ ] `<script>alert(1)</script>` in the title shows as literal text, no alert.
- [ ] `view.php?c=../../etc/passwd` returns 400, no error leak.
- [ ] Responsive: below 900px, the phone frame moves above the builder.
- [ ] 11 rapid saves from the same IP: the 11th returns 429.
- [ ] Same topic searched twice within 10 minutes: second hits the cache.
