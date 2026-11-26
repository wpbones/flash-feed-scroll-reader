<?php

namespace NSFeedScrollReaderPlugin\Migration;

use NSFeedScrollReaderPlugin\Support\Legacy;
use NSFeedScrollReaderPlugin\Support\Plugin;
use NSFeedScrollReaderPlugin\Support\Settings;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Moves the 1.x `ffsr_settings` option into the 2.0 defaults, once.
 *
 * Called from plugin/activation.php (a plugin activated, or reactivated) and plugin/updated.php
 * (an update: WP Bones runs it on the first request after the files change, whichever way they
 * arrived). The old option is left where it is: it is the user's backup, and uninstall.php removes it.
 */
final class LegacySettings
{
  /**
   * @return bool True when settings were moved; false when there was nothing to move or it was done.
   */
  public static function run(): bool
  {
    $old = get_option(Legacy::OPTION);
    $current = Plugin::options();

    if (!is_array($old) || !empty($current['migrated'])) {
      return false;
    }

    $partial = Legacy::migrate($old);

    $values = Settings::sanitize($partial + $current, $current, Plugin::defaults())['values'];
    $values['legacy_feeds'] = $partial['legacy_feeds'] ?? $current['legacy_feeds'];
    $values['migrated'] = 1;

    Plugin::update($values);

    return true;
  }
}
