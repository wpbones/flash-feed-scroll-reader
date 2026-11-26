<?php

namespace NSFeedScrollReaderPlugin\Providers;

use NSFeedScrollReaderPlugin\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Loads the plugin's translations under its own text domain, which is its WordPress.org slug (so a
 * language pack from translate.wordpress.org matches). WP Bones loads a text domain made from the
 * Plugin Name it was renamed with, which is not the slug.
 *
 * The bundled catalogue of the current locale is loaded by its full path: measured on WordPress
 * 7.2-RC3, load_plugin_textdomain() answered true for this folder and the strings stayed
 * untranslated, while load_textdomain() with the same file and locale translated them. When the
 * plugin has no catalogue for the locale, load_plugin_textdomain() looks in WP_LANG_DIR as usual.
 */
class TextDomain extends ServiceProvider
{
  private const DOMAIN = 'flash-feed-scroll-reader';

  public function register()
  {
    $locale = determine_locale();
    $mofile = $this->plugin->basePath . '/languages/' . self::DOMAIN . '-' . $locale . '.mo';

    if (is_readable($mofile)) {
      load_textdomain(self::DOMAIN, $mofile, $locale);

      return;
    }

    load_plugin_textdomain(self::DOMAIN, false, basename($this->plugin->basePath) . '/languages');
  }
}
