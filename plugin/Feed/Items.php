<?php

namespace NSFeedScrollReaderPlugin\Feed;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Plain feed items: the merging of several feeds and the text of one item.
 *
 * An item is an array: `title` and `link` (strings), `description` (HTML as the feed gave it) and
 * `timestamp` (Unix time, or null when the feed gives no date).
 *
 * Pure, so it is tested without SimplePie.
 */
final class Items
{
  /**
   * Several feeds in one list: newest first, the same link only once, at most $max items.
   * An item with no date goes after the dated ones, in the order the feeds gave it.
   *
   * @param array<int, array<int, array>> $lists One list of items per feed.
   *
   * @return array[]
   */
  public static function merge(array $lists, int $max): array
  {
    $all = [];
    $seen = [];
    $order = 0;

    foreach ($lists as $items) {
      foreach ($items as $item) {
        $link = (string) ($item['link'] ?? '');

        if ($link === '' || isset($seen[$link])) {
          continue;
        }

        $seen[$link] = true;
        $item['_order'] = $order++;
        $all[] = $item;
      }
    }

    usort($all, static function (array $a, array $b): int {
      $ta = $a['timestamp'] ?? null;
      $tb = $b['timestamp'] ?? null;

      if ($ta === $tb) {
        return $a['_order'] <=> $b['_order'];
      }

      if ($ta === null) {
        return 1;
      }

      if ($tb === null) {
        return -1;
      }

      return $tb <=> $ta;
    });

    $all = array_slice($all, 0, max(0, $max));

    return array_map(static function (array $item): array {
      unset($item['_order']);

      return $item;
    }, $all);
  }

  /**
   * HTML from a feed to plain text: no tags, entities decoded, white space collapsed.
   */
  public static function plain(string $html): string
  {
    $text = wp_strip_all_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim((string) preg_replace('/\s+/u', ' ', $text));
  }

  /**
   * The first $length characters of the description, with $suffix when something was cut.
   */
  public static function excerpt(string $html, int $length, string $suffix): string
  {
    $text = self::plain($html);

    if (mb_strlen($text) <= $length) {
      return $text;
    }

    return rtrim(mb_substr($text, 0, $length)) . $suffix;
  }
}
