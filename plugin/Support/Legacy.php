<?php

namespace NSFeedScrollReaderPlugin\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Everything 1.x handed us: the template tag's arguments and the `ffsr_settings` option.
 *
 * Pure: a function from the old shapes to the new ones.
 */
final class Legacy
{
  /** The 1.x option that held the settings. */
  public const OPTION = 'ffsr_settings';

  /**
   * Arguments of the 1.x tag that only made sense for the Flash movie: they are accepted and
   * dropped without a word.
   */
  private const IGNORED = ['stylesheet', 'wmode', 'gateway', 'sample'];

  /**
   * The arguments of the template tags, `get_flashfeedscrollreader()` and `flashfeedscrollreader()`,
   * as a query string ("width=100%&height=20") or an array, in the 2.0 names.
   *
   * - feedurl -> feeds; useaggregator=1 with no feedurl -> the feeds of the old aggregator list
   * - scrollspeed (milliseconds per step in Flash) -> speed (pixels per second)
   * - stringcut -> description_length, stringcutend -> suffix
   * - stylesheet, wmode and the other Flash-only arguments are dropped
   *
   * New names pass through unchanged, so a tag can already use `speed`, `color` or `max_items`.
   *
   * @param string|array $args    What the caller passed.
   * @param array        $options The saved options: where the old aggregator list lives.
   */
  public static function translate($args, array $options = []): array
  {
    if (is_string($args)) {
      parse_str($args, $parsed);
      $args = $parsed;
    }

    if (!is_array($args)) {
      return [];
    }

    $out = [];

    foreach ($args as $key => $value) {
      if (!is_string($key)) {
        continue;
      }

      $key = strtolower($key);

      if (in_array($key, self::IGNORED, true)) {
        continue;
      }

      switch ($key) {
        case 'feedurl':
        case 'feed':
        case 'url':
          $out['feeds'] = $value;
          break;

        case 'scrollspeed':
          $speed = self::speedFromMilliseconds($value);

          if ($speed !== null) {
            $out['speed'] = $speed;
          }
          break;

        case 'stringcut':
          $out['description_length'] = $value;
          break;

        case 'stringcutend':
          $out['suffix'] = $value;
          break;

        case 'useaggregator':
          $useAggregator = Args::bool($value);
          break;

        default:
          $out[$key] = $value;
      }
    }

    if (!empty($useAggregator) && !isset($out['feeds']) && !empty($options['legacy_feeds'])) {
      $out['feeds'] = $options['legacy_feeds'];
    }

    return $out;
  }

  /**
   * Flash's scroll speed was a delay in milliseconds between two steps. Taking one step as one
   * pixel, 25 ms is 40 px/s. Null when the value is not a positive number.
   *
   * @param mixed $milliseconds
   */
  public static function speedFromMilliseconds($milliseconds): ?int
  {
    if (!is_numeric($milliseconds) || (float) $milliseconds <= 0) {
      return null;
    }

    return Args::int(1000 / (float) $milliseconds, 10, 400, 50);
  }

  /**
   * The 1.x `ffsr_settings` array in the new options. Only the keys the old plugin knew; what it
   * does not say stays at the new default, so the result is a partial array to merge over them.
   *
   * @param array $old The value of the `ffsr_settings` option.
   *
   * @return array<string, mixed>
   */
  public static function migrate(array $old): array
  {
    $new = [];

    $aggregator = [];

    if (isset($old['ffsr_aggregator']) && is_array($old['ffsr_aggregator'])) {
      $aggregator = Args::feeds($old['ffsr_aggregator']);
    }

    $single = Args::feeds((string) ($old['ffsr_feedurl'] ?? ''));
    $useAggregator = Args::bool($old['ffsr_useaggregator'] ?? false) && $aggregator !== [];

    $feeds = $useAggregator ? $aggregator : $single;

    if ($feeds !== []) {
      $new['feeds'] = implode("\n", $feeds);
    }

    if ($aggregator !== []) {
      $new['legacy_feeds'] = implode("\n", $aggregator);
    }

    // The old limit was per feed; the new one is for the whole ticker.
    if ($useAggregator && isset($old['ffsr_maxitems']) && is_numeric($old['ffsr_maxitems'])) {
      $new['max_items'] = Args::int((float) $old['ffsr_maxitems'] * count($feeds), 1, 100, 20);
    }

    if (isset($old['ffsr_scrollspeed'])) {
      $speed = self::speedFromMilliseconds($old['ffsr_scrollspeed']);

      if ($speed !== null) {
        $new['speed'] = $speed;
      }
    }

    // Plain strings: 'width', 'height', 'bgcolor', 'separator', 'stringcutend', 'target'.
    $copy = [
      'ffsr_width' => 'width',
      'ffsr_height' => 'height',
      'ffsr_bgcolor' => 'bgcolor',
      'ffsr_separator' => 'separator',
      'ffsr_stringcutend' => 'suffix',
      'ffsr_target' => 'target',
      'ffsr_stringcut' => 'description_length',
    ];

    foreach ($copy as $from => $to) {
      if (isset($old[$from]) && is_scalar($old[$from])) {
        $new[$to] = $old[$from];
      }
    }

    if (isset($old['ffsr_description'])) {
      $new['description'] = Args::bool($old['ffsr_description']) ? 1 : 0;
    }

    return $new;
  }
}
