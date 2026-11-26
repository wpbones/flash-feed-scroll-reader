<?php

namespace NSFeedScrollReaderPlugin\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The only place that talks to the WP Bones plugin instance: its options and its URLs. Everything
 * else takes arrays, so it can be tested without the framework.
 */
final class Plugin
{
  /** Handle of the front end style and script. */
  public const HANDLE = 'feed-scroll-reader';

  /** Name of the form's fields: `ffsr[feeds]`, `ffsr[speed]`... */
  public const FIELD = 'ffsr';

  private static ?array $defaults = null;

  /**
   * The WP Bones instance.
   */
  public static function instance()
  {
    return \NSFeedScrollReaderPlugin();
  }

  /**
   * config/options.php.
   */
  public static function defaults(): array
  {
    if (self::$defaults === null) {
      self::$defaults = (array) require self::instance()->basePath . '/config/options.php';
    }

    return self::$defaults;
  }

  /**
   * The saved defaults of every ticker: the stored row over config/options.php.
   */
  public static function options(): array
  {
    return Settings::merged(self::instance()->options->toArray(), self::defaults());
  }

  /**
   * Write some options. WP Bones merges them over the stored row.
   */
  public static function update(array $values): void
  {
    self::instance()->options->update($values);
  }

  public static function url(string $path): string
  {
    return self::instance()->baseUri . '/' . ltrim($path, '/');
  }

  public static function version(): string
  {
    return (string) self::instance()->Version;
  }

  /**
   * Registers the ticker's style and script under HANDLE, so the block can name them in block.json
   * and enqueueFrontEnd() can enqueue them. Safe to call twice: a handle registers once.
   */
  public static function registerFrontEnd(): void
  {
    wp_register_style(self::HANDLE, self::url('public/css/feed-scroll-reader.css'), [], self::version());
    wp_register_script(self::HANDLE, self::url('public/js/feed-scroll-reader.js'), [], self::version(), [
      'in_footer' => true,
      'strategy' => 'defer',
    ]);
  }

  /**
   * Style and script of the ticker, once per page however many tickers it holds.
   */
  public static function enqueueFrontEnd(): void
  {
    self::registerFrontEnd();
    wp_enqueue_style(self::HANDLE);
    wp_enqueue_script(self::HANDLE);
  }
}
