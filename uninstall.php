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

  // The plugin's own transients: the failed-feed marks and the settings page's one-time notice.
  // Not core's shared `feed_*` transients: other plugins read feeds through them too.
  global $wpdb;

  foreach (['ffsr_down_', 'ffsr_rejected_'] as $prefix) {
    $names = $wpdb->get_col(
      $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_transient_' . $prefix) . '%')
    );

    foreach ($names as $name) {
      // Also removes its timeout row.
      delete_transient(substr($name, strlen('_transient_')));
    }
  }
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
