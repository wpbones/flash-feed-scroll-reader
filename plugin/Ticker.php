<?php

namespace NSFeedScrollReaderPlugin;

use NSFeedScrollReaderPlugin\Feed\FeedReader;
use NSFeedScrollReaderPlugin\Render\TickerHtml;
use NSFeedScrollReaderPlugin\Support\Args;
use NSFeedScrollReaderPlugin\Support\Legacy;
use NSFeedScrollReaderPlugin\Support\Plugin;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * A ticker, from the arguments to the HTML: the one entry point of the shortcode, the widget and
 * the template tags.
 */
final class Ticker
{
  private static int $count = 0;

  /**
   * @param string|array $given    Arguments: an array (shortcode attributes, widget instance) or a
   *                               query string ("feedurl=…&height=40", the 1.x template tag).
   * @param array|null   $defaults The saved defaults; null reads them.
   *
   * @return string The HTML. A ticker that cannot be shown gives '' (visitors) or an HTML comment
   *                with the reason (administrators).
   */
  public static function html($given = [], ?array $defaults = null): string
  {
    $built = self::build($given, $defaults);

    return $built['html'] . self::note($built['messages']);
  }

  /**
   * The ticker and what went wrong with it, apart: the settings page shows the messages as notices.
   *
   * @return array{html: string, messages: string[]} `html` is '' when there is nothing to show.
   */
  public static function build($given = [], ?array $defaults = null): array
  {
    $defaults ??= Plugin::options();

    $args = Args::normalize(Legacy::translate($given, $defaults), $defaults);

    if ($args['feeds'] === []) {
      return ['html' => '', 'messages' => [__('No feed is set: add one in Settings > Feed Scroll Reader.', 'flash-feed-scroll-reader')]];
    }

    $result = FeedReader::read($args['feeds'], $args['max_items'], $args['cache'] * MINUTE_IN_SECONDS);

    $messages = $result['errors'];

    if ($result['items'] === []) {
      if ($messages === []) {
        $messages[] = __('The feeds returned no items.', 'flash-feed-scroll-reader');
      }

      return ['html' => '', 'messages' => $messages];
    }

    if ($args['label'] === '') {
      $args['label'] = __('Latest items', 'flash-feed-scroll-reader');
    }

    Plugin::enqueueFrontEnd();

    return ['html' => TickerHtml::render($args, $result['items'], ++self::$count), 'messages' => $messages];
  }

  /**
   * What went wrong, for the people who can fix it: an HTML comment in the page for administrators,
   * nothing for visitors.
   *
   * @param string[] $messages
   */
  public static function note(array $messages): string
  {
    if ($messages === [] || !current_user_can('manage_options')) {
      return '';
    }

    $text = wp_strip_all_tags(implode('; ', $messages));
    // A comment may not hold "--" (and ends at "-->"): collapse every run of hyphens, drop the angle brackets.
    $text = (string) preg_replace('/-+/', '-', str_replace(['>', '<'], '', $text));

    return "\n<!-- Feed Scroll Reader: " . $text . " -->\n";
  }
}
