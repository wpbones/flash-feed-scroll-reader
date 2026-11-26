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
 */
class TextDomain extends ServiceProvider
{
  public function register()
  {
    load_plugin_textdomain('flash-feed-scroll-reader', false, basename($this->plugin->basePath) . '/languages');
  }
}
