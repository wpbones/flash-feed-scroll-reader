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
  /**
   * @param string[] $urls          Feed URLs.
   * @param int      $max           Items for the whole ticker.
   * @param int      $cacheSeconds  How long a fetched feed is reused.
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
        $feed = fetch_feed($url);

        if (is_wp_error($feed)) {
          $errors[] = $url . ': ' . $feed->get_error_message();
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
