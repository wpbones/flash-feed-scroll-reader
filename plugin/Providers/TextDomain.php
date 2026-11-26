<?php

namespace NSFeedScrollReaderPlugin\Providers;

use NSFeedScrollReaderPlugin\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Loads the plugin's translations under its own text domain, which is its WordPress.org slug (so a
 * language pack from translate.wordpress.org matches). WP Bones loads a text domain made from the
 * Plugin Name it was renamed with (`wp-flashfeedscrollreader`), which is not the slug: this provider
 * is what loads the slug's.
 *
 * Order: the language pack in WP_LANG_DIR/plugins first, because it is newer than the plugin and
 * is what translators keep up to date; then the catalogue bundled in languages/, for what the pack
 * lacks or when there is no pack. The files are loaded by their path and locale.
 */
class TextDomain extends ServiceProvider
{
  public const DOMAIN = 'flash-feed-scroll-reader';

  public function register()
  {
    $locale = determine_locale();

    foreach (self::files($this->plugin->basePath, $locale, WP_LANG_DIR) as $file) {
      load_textdomain(self::DOMAIN, $file, $locale);
    }
  }

  /**
   * The catalogues to load for a locale, in the order of priority: the language pack, then the
   * bundled one. Only the ones that can be read.
   *
   * @return string[]
   */
  public static function files(string $basePath, string $locale, string $langDir): array
  {
    $candidates = [
      rtrim($langDir, '/') . '/plugins/' . self::DOMAIN . '-' . $locale . '.mo',
      $basePath . '/languages/' . self::DOMAIN . '-' . $locale . '.mo',
    ];

    return array_values(array_filter($candidates, 'is_readable'));
  }
}
