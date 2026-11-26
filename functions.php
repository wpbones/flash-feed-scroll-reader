<?php

// return, not exit(): Composer loads this file before PHPUnit's bootstrap defines ABSPATH, and an
// exit() here ends the test run with no output and status 0.
if (!defined('ABSPATH')) {
  return;
}

/*
|--------------------------------------------------------------------------
| Template tags
|--------------------------------------------------------------------------
|
| The two functions of Flash Feed Scroll Reader 1.x, kept for themes that call them. Loaded by
| Composer on every request, so a theme can call them wherever it likes.
|
*/

if (!function_exists('get_flashfeedscrollreader')) {
  /**
   * The ticker's HTML.
   *
   * @param string|array $args A query string ("width=100%&height=40&feedurl=https://example.com/feed")
   *                           or an array. The 1.x names work: width, height, feedurl, scrollspeed,
   *                           separator, description, stringcut, stringcutend, target, bgcolor,
   *                           useaggregator. stylesheet, wmode and the other Flash-only ones are
   *                           ignored. The 2.0 names work too: see the shortcode.
   *
   * @return string
   */
  function get_flashfeedscrollreader($args = null): string
  {
    return \NSFeedScrollReaderPlugin\Ticker::html($args ?? []);
  }
}

if (!function_exists('flashfeedscrollreader')) {
  /**
   * Echoes the ticker. Same arguments as get_flashfeedscrollreader().
   *
   * @param string|array $args
   *
   * @return void
   */
  function flashfeedscrollreader($args = null): void
  {
    // The HTML is escaped piece by piece in TickerHtml.
    echo get_flashfeedscrollreader($args); // phpcs:ignore WordPress.Security.EscapeOutput
  }
}
