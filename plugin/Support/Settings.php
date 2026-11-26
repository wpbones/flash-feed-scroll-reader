<?php

namespace NSFeedScrollReaderPlugin\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The saved defaults: the form's input in, the row that is stored out.
 *
 * Pure. Reading the row and writing it back is the job of SettingsPage and LegacySettings, through
 * `$plugin->options`.
 */
final class Settings
{
  /**
   * The fields the settings form owns.
   */
  public const FIELDS = [
    'feeds',
    'max_items',
    'speed',
    'width',
    'height',
    'bgcolor',
    'color',
    'description',
    'description_length',
    'suffix',
    'separator',
    'target',
    'cache',
    'label',
  ];

  /**
   * The stored row for a submitted form.
   *
   * Checks every field through Args::normalize() against the plugin's own defaults, so an empty
   * or invalid number, length or target falls back to its default rather than to the previous
   * value. The keys the form does not own (`legacy_feeds`, `migrated`) are kept as they are.
   *
   * @param array $input    The posted `ffsr` array, unslashed.
   * @param array $current  The stored row.
   * @param array $defaults config/options.php.
   *
   * @return array{values: array<string, mixed>, rejected: string[]}
   */
  public static function sanitize(array $input, array $current, array $defaults): array
  {
    $given = [];

    foreach (self::FIELDS as $field) {
      if (array_key_exists($field, $input)) {
        $given[$field] = $input[$field];
      }
    }

    // An unchecked box is not posted at all.
    $given['description'] = isset($input['description']) && Args::bool($input['description']) ? '1' : '0';

    $clean = Args::normalize($given, $defaults);

    $values = $current;

    foreach (self::FIELDS as $field) {
      $values[$field] = $clean[$field];
    }

    // An empty list stays empty: the defaults' feed must not come back when the user clears it.
    $values['feeds'] = implode("\n", Args::feeds($input['feeds'] ?? ''));
    $values['description'] = $clean['description'] ? 1 : 0;

    return [
      'values' => $values,
      'rejected' => Args::rejectedFeeds((string) ($input['feeds'] ?? '')),
    ];
  }

  /**
   * The stored row over the defaults: what a ticker starts from. A key the row lacks (a field
   * added by a later version) falls back to its default.
   */
  public static function merged(array $stored, array $defaults): array
  {
    return array_replace($defaults, array_intersect_key($stored, $defaults));
  }
}
