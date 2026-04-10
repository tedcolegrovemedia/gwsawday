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

// --- Openverse (header + card images) ---
// Openverse is an open-content image search with no API key required for
// light use. Anonymous limits are low though — for a classroom you SHOULD
// register a free token at:
//   https://api.openverse.org/v1/auth_tokens/register/
// and paste it below.
define('OPENVERSE_API_BASE',  'https://api.openverse.org/v1/');
define('OPENVERSE_BEARER',    '');  // optional but strongly recommended for class use

// --- Storage / limits ---
define('DATA_DIR',        __DIR__ . '/data');
define('BUILDS_DIR',      DATA_DIR . '/builds');
define('CACHE_DIR',       DATA_DIR . '/cache');
define('RATELIMIT_DIR',   DATA_DIR . '/ratelimit');

define('MAX_TITLE_LEN',         40);
define('MAX_TOPIC_LEN',         30);
define('MAX_FOOTER_LEN',        60);
define('MAX_CARDS',             3);
define('MIN_CARDS',             1);
define('MAX_CARD_TITLE_LEN',    28);
define('MAX_CARD_CAPTION_LEN',  80);
define('MAX_BUTTONS',           3);
define('MAX_BUTTON_LEN',        20);

define('MAX_BUILDS_PER_IP_10MIN', 150);
define('IMAGE_CACHE_SECONDS',    3600);  // 1 hour — helps stay under Openverse anon limits

// Shared code alphabet — no ambiguous characters (I, O, 0, 1).
define('CODE_ALPHABET', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
define('CODE_LENGTH',   6);
