<?php

namespace NSFeedScrollReaderPlugin\Providers;

use NSFeedScrollReaderPlugin\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Fills the gaps of a language pack with the catalogue bundled in languages/.
 *
 * The text domain is the plugin's WordPress.org slug, and WP Bones (3.4.1 and later) and WordPress
 * itself register where its catalogues are. WordPress then loads ONE file for the locale, just in
 * time: the language pack in WP_LANG_DIR/plugins if there is one, else the bundled one. A pack from
 * translate.wordpress.org holds only the approved strings, so a string it lacks would stay English
 * although the bundled catalogue translates it. This provider loads the pack first, because it is
 * newer than the plugin and is what translators keep up to date, then the bundled catalogue, for what
 * the pack lacks or when there is no pack. The files are loaded by their path and locale.
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
