<?php

namespace NSFeedScrollReaderPlugin\Feed;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Reads feeds through WordPress's own fetch_feed(): the SimplePie that ships with core, and its
 * cache in the transients.
 *
 * Each feed is fetched on its own, so one that is down does not take the others with it; what
 * failed comes back as a list of messages for the administrators.
 */
final class FeedReader
{
  /** A feed that failed is not asked again for this long. */
  public const FAILURE_TTL = 15 * MINUTE_IN_SECONDS;

  /** Prefix of the transient that remembers a failed feed (uninstall.php removes them). */
  public const FAILURE_PREFIX = 'ffsr_down_';

  /**
   * @param string[] $urls          Feed URLs.
   * @param int      $max           Items for the whole ticker.
   * @param int      $cacheSeconds  How long a fetched feed is reused.
   *
   * A feed that fails is remembered for FAILURE_TTL in a transient and skipped while it lasts.
   *
   * @return array{items: array[], errors: string[]}
   */
  public static function read(array $urls, int $max, int $cacheSeconds): array
  {
    $lists = [];
    $errors = [];

    $lifetime = static fn(): int => $cacheSeconds;

    // fetch_feed() asks wp_feed_cache_transient_lifetime for the lifetime of each feed it caches.
    add_filter('wp_feed_cache_transient_lifetime', $lifetime, 99);

    try {
      foreach ($urls as $url) {
        // core's fetch_feed() does not cache a failure, and waits up to 10 seconds for a dead host.
        $failure = get_transient(self::FAILURE_PREFIX . md5($url));

        if (is_string($failure) && $failure !== '') {
          $errors[] = $url . ': ' . $failure;
          continue;
        }

        $feed = fetch_feed($url);

        if (is_wp_error($feed)) {
          $message = $feed->get_error_message();
          set_transient(self::FAILURE_PREFIX . md5($url), $message . ' (not tried again for 15 minutes)', self::FAILURE_TTL);
          $errors[] = $url . ': ' . $message;
          continue;
        }

        $items = [];

        foreach ($feed->get_items(0, $max) as $item) {
          $timestamp = $item->get_date('U');

          $items[] = [
            'title' => (string) $item->get_title(),
            'link' => (string) $item->get_permalink(),
            'description' => (string) $item->get_description(),
            'timestamp' => is_numeric($timestamp) ? (int) $timestamp : null,
          ];
        }

        $lists[] = $items;
      }
    } finally {
      remove_filter('wp_feed_cache_transient_lifetime', $lifetime, 99);
    }

    return ['items' => Items::merge($lists, $max), 'errors' => $errors];
  }
}
