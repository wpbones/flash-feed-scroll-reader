<?php

namespace NSFeedScrollReaderPlugin\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Turns whatever a caller hands in (a shortcode's attributes, a widget instance, the 1.x template
 * tag's query string, the settings form) into one clean set of arguments.
 *
 * Pure: no WordPress state. Every value that reaches the page comes out of normalize(), so a CSS
 * length, a color or a number is checked here once and the renderer can trust it.
 */
final class Args
{
  public const TARGETS = ['_blank', '_self'];

  /**
   * Keys where an empty value is a real choice (no separator, no suffix, transparent, inherited
   * color) and so must not fall back to the default.
   */
  private const BLANKABLE = ['separator', 'suffix', 'bgcolor', 'color'];

  /**
   * The settings, or the arguments of a ticker, as the page can use them.
   *
   * @param array $given    Raw arguments, already in the 2.0 names (see Legacy::translate()).
   * @param array $defaults The saved defaults: raw, as stored.
   *
   * @return array{feeds: string[], max_items: int, speed: int, width: string, height: string, bgcolor: string, color: string, description: bool, description_length: int, suffix: string, separator: string, target: string, cache: int, label: string}
   */
  public static function normalize(array $given, array $defaults): array
  {
    $merged = $defaults;

    foreach ($given as $key => $value) {
      if (!is_string($key)) {
        continue;
      }

      if (self::isBlank($value) && !in_array($key, self::BLANKABLE, true)) {
        continue;
      }

      $merged[$key] = $value;
    }

    return [
      'feeds' => self::feeds($merged['feeds'] ?? ''),
      'max_items' => self::int($merged['max_items'] ?? 20, 1, 100, 20),
      'speed' => self::int($merged['speed'] ?? 50, 10, 400, 50),
      'width' => self::length($merged['width'] ?? '100%', '100%', false),
      'height' => self::length($merged['height'] ?? '2.75em', '2.75em', true),
      'bgcolor' => self::color($merged['bgcolor'] ?? ''),
      'color' => self::color($merged['color'] ?? ''),
      'description' => self::bool($merged['description'] ?? false),
      'description_length' => self::int($merged['description_length'] ?? 100, 1, 1000, 100),
      'suffix' => self::text($merged['suffix'] ?? '[...]', 20),
      'separator' => self::text($merged['separator'] ?? '•', 20),
      'target' => self::target($merged['target'] ?? '_blank'),
      'cache' => self::int($merged['cache'] ?? 30, 1, 1440, 30),
      'label' => self::text($merged['label'] ?? '', 100),
    ];
  }

  /**
   * One URL per entry. Takes an array, or a string split on new lines, commas and spaces; keeps the
   * http(s) ones, once each.
   *
   * @param string|array $value
   *
   * @return string[]
   */
  public static function feeds($value): array
  {
    if (!is_array($value)) {
      $value = preg_split('/[\s,]+/', (string) $value) ?: [];
    }

    $urls = [];

    foreach ($value as $url) {
      if (!is_string($url)) {
        continue;
      }

      $url = trim($url);

      if ($url !== '' && self::isFeedUrl($url)) {
        $urls[$url] = $url;
      }
    }

    return array_values($urls);
  }

  /**
   * The entries of a feed list that are not usable URLs: what the settings page tells the user it
   * dropped.
   *
   * @param string|array $value
   *
   * @return string[]
   */
  public static function rejectedFeeds($value): array
  {
    if (!is_array($value)) {
      $value = preg_split('/[\s,]+/', (string) $value) ?: [];
    }

    $rejected = [];

    foreach ($value as $url) {
      $url = is_string($url) ? trim($url) : '';

      if ($url !== '' && !self::isFeedUrl($url)) {
        $rejected[] = $url;
      }
    }

    return $rejected;
  }

  public static function isFeedUrl(string $url): bool
  {
    return preg_match('#^https?://[^\s/?\#]+#i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  /**
   * A CSS length that is safe to print inside a style attribute: a number with an optional unit.
   * A bare number is pixels (what 1.x did: `height=40`). `auto` is allowed where $allowAuto.
   */
  public static function length($value, string $default, bool $allowAuto): string
  {
    $value = strtolower(trim((string) $value));

    if ($allowAuto && $value === 'auto') {
      return 'auto';
    }

    if (preg_match('/^(\d{1,5}(?:\.\d{1,3})?)(px|em|rem|%|vw|vh)?$/', $value, $m) !== 1 || (float) $m[1] <= 0) {
      return $default;
    }

    return $m[1] . ($m[2] ?? 'px');
  }

  /**
   * #rgb, #rrggbb or #rrggbbaa, lower case; anything else is "no color".
   */
  public static function color($value): string
  {
    $value = strtolower(trim((string) $value));

    return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value) === 1 ? $value : '';
  }

  public static function int($value, int $min, int $max, int $default): int
  {
    if (!is_numeric($value)) {
      return $default;
    }

    return max($min, min($max, (int) round((float) $value)));
  }

  public static function bool($value): bool
  {
    if (is_string($value)) {
      return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on', 'y'], true);
    }

    return (bool) $value;
  }

  /**
   * Plain text, trimmed and cut to $max characters. No markup: it is printed with esc_html().
   */
  public static function text($value, int $max): string
  {
    $value = trim(strip_tags((string) $value));
    $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);

    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
  }

  /**
   * Only _blank and _self; _parent and _top mean nothing in a ticker, so they are _self.
   */
  public static function target($value): string
  {
    $value = strtolower(trim((string) $value));

    if (in_array($value, ['_parent', '_top'], true)) {
      return '_self';
    }

    return in_array($value, self::TARGETS, true) ? $value : '_blank';
  }

  private static function isBlank($value): bool
  {
    return $value === null || (is_string($value) && trim($value) === '') || $value === [];
  }
}
