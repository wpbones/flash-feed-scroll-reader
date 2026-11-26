<?php
/**
 * Runs when the plugin is deleted from the Plugins screen, not when it is deactivated.
 *
 * Removes every option the plugin wrote: its settings row, the WP Bones bookkeeping beside it and
 * the 1.x `ffsr_settings` option the settings were migrated from.
 *
 * @package flash-feed-scroll-reader
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

/**
 * Deletes the options of the current site.
 */
function ffsr_uninstall_site(): void
{
  // WP Bones names the settings row after the plugin: Plugin Name, snake case, "_slug".
  $slug = 'feed_scroll_reader_slug';

  delete_option($slug);

  foreach (['_db_version', '_migrations', '_migrations_failure', '_migrations.lock'] as $suffix) {
    delete_option($slug . $suffix);
  }

  delete_option('ffsr_settings');
}

if (is_multisite()) {
  foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $blog_id) {
    switch_to_blog((int) $blog_id);
    ffsr_uninstall_site();
    restore_current_blog();
  }
} else {
  ffsr_uninstall_site();
}
