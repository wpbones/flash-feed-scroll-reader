<?php

if (!defined('ABSPATH')) {
  exit();
}

/*
|--------------------------------------------------------------------------
| Plugin options: the defaults of every ticker
|--------------------------------------------------------------------------
|
| Stored by WP Bones as one JSON row in wp_options, under this plugin's slug. Every
| ticker (shortcode, widget, template tag) starts from these values and overrides
| only what it sets itself. `feeds` is a string, one URL per line, never an array:
| WP Bones merges an update recursively, so a shorter list would keep the old tail.
|
| `legacy_feeds` and `migrated` belong to the migration of the 1.x settings
| (NSFeedScrollReaderPlugin\Migration\LegacySettings).
|
*/

return [
  'feeds' => 'https://wordpress.org/news/feed/',
  'max_items' => 20,
  'speed' => 50,
  'width' => '100%',
  'height' => '2.75em',
  'bgcolor' => '',
  'color' => '',
  'description' => 0,
  'description_length' => 100,
  'suffix' => '[...]',
  'separator' => '•',
  'target' => '_blank',
  'cache' => 30,
  'label' => '',
  'legacy_feeds' => '',
  'migrated' => 0,
];
