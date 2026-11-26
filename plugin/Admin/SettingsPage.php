<?php

namespace NSFeedScrollReaderPlugin\Admin;

use NSFeedScrollReaderPlugin\Support\Plugin;
use NSFeedScrollReaderPlugin\Support\Settings;
use NSFeedScrollReaderPlugin\Ticker;
use NSFeedScrollReaderPlugin\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Settings > Feed Scroll Reader: the defaults every ticker starts from.
 *
 * WP Bones menus are top level only, so this page is registered here, with add_options_page(), and
 * saved through admin-post.php: a nonce, the manage_options capability, every field checked by
 * Settings::sanitize().
 */
class SettingsPage extends ServiceProvider
{
  public const SLUG = 'feed-scroll-reader';

  public const ACTION = 'ffsr_save_settings';

  private string $hook = '';

  public function register()
  {
    // Loaded on init: admin_menu and admin_post come later.
    add_action('admin_menu', [$this, 'menu']);
    add_action('admin_post_' . self::ACTION, [$this, 'save']);
    add_action('admin_enqueue_scripts', [$this, 'assets']);
    add_filter(
      'plugin_action_links_' . plugin_basename($this->plugin->basePath . '/wp-flashfeedscrollreader.php'),
      [$this, 'links']
    );
  }

  public function menu(): void
  {
    $this->hook = (string) add_options_page(
      __('Feed Scroll Reader', 'flash-feed-scroll-reader'),
      __('Feed Scroll Reader', 'flash-feed-scroll-reader'),
      'manage_options',
      self::SLUG,
      [$this, 'render']
    );
  }

  /**
   * @param string[] $links
   *
   * @return string[]
   */
  public function links(array $links): array
  {
    array_unshift(
      $links,
      '<a href="' . esc_url(self::url()) . '">' . esc_html__('Settings', 'flash-feed-scroll-reader') . '</a>'
    );

    return $links;
  }

  public static function url(): string
  {
    return admin_url('options-general.php?page=' . self::SLUG);
  }

  public function assets(string $hook): void
  {
    if ($hook !== $this->hook) {
      return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style(
      'feed-scroll-reader-admin',
      Plugin::url('public/css/feed-scroll-reader-admin.css'),
      [],
      Plugin::version()
    );
    wp_enqueue_script(
      'feed-scroll-reader-admin',
      Plugin::url('public/js/feed-scroll-reader-admin.js'),
      ['wp-color-picker'],
      Plugin::version(),
      true
    );
  }

  public function render(): void
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'flash-feed-scroll-reader'), 403);
    }

    $values = Plugin::options();

    // Read once, and gone: what the last save dropped.
    $key = 'ffsr_rejected_' . get_current_user_id();
    $rejected = get_transient($key);
    delete_transient($key);

    // phpcs:ignore WordPress.Security.NonceVerification -- a flag to show a notice, nothing is changed by it.
    $updated = isset($_GET['updated']) && $_GET['updated'] === '1';

    Plugin::instance()
      ->view('settings.index')
      ->with([
        'values' => $values,
        'updated' => $updated,
        'rejected' => is_array($rejected) ? $rejected : [],
        'preview' => Ticker::build([], $values),
        'action' => self::ACTION,
        'field' => Plugin::FIELD,
      ])
      ->render();
  }

  public function save(): void
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Sorry, you are not allowed to do that.', 'flash-feed-scroll-reader'), 403);
    }

    check_admin_referer(self::ACTION);

    // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- verified above; each field is checked by Settings::sanitize().
    $input = isset($_POST[Plugin::FIELD]) && is_array($_POST[Plugin::FIELD]) ? wp_unslash($_POST[Plugin::FIELD]) : [];

    $result = Settings::sanitize($input, Plugin::options(), Plugin::defaults());

    Plugin::update($result['values']);

    if ($result['rejected'] !== []) {
      set_transient('ffsr_rejected_' . get_current_user_id(), $result['rejected'], 2 * MINUTE_IN_SECONDS);
    }

    wp_safe_redirect(add_query_arg('updated', '1', self::url()));
    exit();
  }
}
