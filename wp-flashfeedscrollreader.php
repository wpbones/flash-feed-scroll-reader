<?php

/**
 * Plugin Name: Feed Scroll Reader
 * Plugin URI: https://wordpress.org/plugins/flash-feed-scroll-reader/
 * Description: A horizontal scrolling ticker of items from one or more RSS/Atom feeds. Plain HTML and CSS, no Flash. Formerly Flash Feed Scroll Reader.
 * Version: 2.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Giovambattista Fazioli
 * Author URI: http://undolog.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flash-feed-scroll-reader
 * Domain Path: languages
 *
 */

if (!defined('ABSPATH')) {
  exit();
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels nice to relax.
|
*/

require_once __DIR__ . '/bootstrap/autoload.php';
