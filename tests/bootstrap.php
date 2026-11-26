<?php

/**
 * Unit-suite bootstrap: no WordPress, no database.
 *
 * The plugin's logic is plain PHP over arrays; the few WordPress functions it calls (escaping,
 * fetch_feed, get_option...) are stubbed per test with Brain Monkey, so one that was not stubbed
 * fails as an undefined function instead of silently passing.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}

if (!defined('MINUTE_IN_SECONDS')) {
  define('MINUTE_IN_SECONDS', 60);
}

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/Support/WP_Error.php';
