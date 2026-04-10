<?php
// Governor Wolf — Sharpen the Saw Day app builder config.
// Copy this file to config.php and fill in your API keys.
// Never commit config.php with real keys to version control.

// --- Moderation ---
// If USE_OPENAI is true AND OPENAI_API_KEY is non-empty, we use the OpenAI
// Moderations endpoint (free). Otherwise we fall back to the local wordlist.
define('USE_OPENAI',      true);
define('OPENAI_API_KEY',  '');  // e.g. 'sk-...'
define('OPENAI_MOD_MODEL','omni-moderation-latest');

// --- Pexels (header images) ---
define('PEXELS_API_KEY',  '');  // get a free key at https://www.pexels.com/api/

// --- Storage / limits ---
define('DATA_DIR',        __DIR__ . '/data');
define('BUILDS_DIR',      DATA_DIR . '/builds');
define('CACHE_DIR',       DATA_DIR . '/cache');
define('RATELIMIT_DIR',   DATA_DIR . '/ratelimit');

define('MAX_TITLE_LEN',   40);
define('MAX_TOPIC_LEN',   30);
define('MAX_BODY_LEN',    300);
define('MAX_BUTTONS',     3);
define('MAX_BUTTON_LEN',  20);

define('MAX_BUILDS_PER_IP_10MIN', 150);
define('PEXELS_CACHE_SECONDS',    600);

// Only this host is allowed for image URLs stored on a build.
define('ALLOWED_IMAGE_HOST_PREFIX', 'https://images.pexels.com/');

// Shared code alphabet — no ambiguous characters (I, O, 0, 1).
define('CODE_ALPHABET', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
define('CODE_LENGTH',   6);
