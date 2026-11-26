<?php

namespace NSFeedScrollReaderPlugin\Shortcodes;

use NSFeedScrollReaderPlugin\Ticker;
use NSFeedScrollReaderPlugin\WPBones\Foundation\WordPressShortcodesServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * `[feed_scroll_reader feed="https://example.com/feed" speed="60" description="yes"]`
 *
 * Every attribute is optional and falls back to Settings > Feed Scroll Reader: feed (one or more
 * URLs, separated by spaces or commas), max_items, speed, width, height, bgcolor, color,
 * description (yes/no), description_length, suffix, separator, target, cache, label. The 1.x
 * names (feedurl, scrollspeed, stringcut, stringcutend) work too.
 */
class TickerShortcode extends WordPressShortcodesServiceProvider
{
  /**
   * @var array<string, string>
   */
  protected $shortcodes = [
    'feed_scroll_reader' => 'ticker',
  ];

  /**
   * @param array|string $atts Attributes; WordPress gives '' when there are none.
   */
  public function ticker($atts = []): string
  {
    return Ticker::html(is_array($atts) ? $atts : []);
  }
}
