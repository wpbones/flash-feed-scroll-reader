<?php

namespace NSFeedScrollReaderPlugin\Blocks;

use NSFeedScrollReaderPlugin\Providers\TextDomain;
use NSFeedScrollReaderPlugin\Support\Plugin;
use NSFeedScrollReaderPlugin\Ticker;
use NSFeedScrollReaderPlugin\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The "Feed Scroll Reader" block: the shortcode's ticker, with a live preview in the editor.
 *
 * It is the shortcode by another way in: the attributes become the shortcode's arguments and go
 * through Ticker::build() like every other entry point, so the normalisation, the escaping, the
 * 5-feed cap and the cache of failed feeds are the same. What the block adds is the wrapper, for the
 * supports the editor offers (colors, font size, margin, alignment).
 *
 * The block's files are hand-written, there is no JavaScript build: public/blocks/ticker/.
 */
class TickerBlock extends ServiceProvider
{
  /** The block's attributes that become the ticker's arguments (attribute => argument). */
  private const ARGUMENTS = [
    'feeds' => 'feeds',
    'maxItems' => 'max_items',
    'speed' => 'speed',
    'description' => 'description',
    'descriptionLength' => 'description_length',
    'height' => 'height',
    'target' => 'target',
    'label' => 'label',
  ];

  public function register()
  {
    // Loaded on init, which is where blocks are registered.
    Plugin::registerFrontEnd();

    $block = register_block_type($this->plugin->basePath . '/public/blocks/ticker', [
      'render_callback' => [self::class, 'render'],
    ]);

    if (!$block) {
      return;
    }

    // The editor's strings, from the bundled catalogue: a handle that comes from block.json is only
    // looked up in WP_LANG_DIR otherwise.
    foreach ($block->editor_script_handles as $handle) {
      wp_set_script_translations($handle, TextDomain::DOMAIN, $this->plugin->basePath . '/languages');
      wp_add_inline_script($handle, self::defaultsScript(Plugin::options()), 'before');
    }
  }

  /**
   * The saved defaults, for the editor to show as placeholders: what an empty field falls back to.
   */
  public static function defaultsScript(array $options): string
  {
    $defaults = array_intersect_key($options, array_flip([
      'feeds', 'max_items', 'speed', 'description', 'description_length', 'height', 'target',
    ]));

    return 'window.ffsrDefaults = ' . wp_json_encode($defaults) . ';';
  }

  /**
   * The attributes as the arguments of the shortcode.
   *
   * An attribute that is not set stays out, so the saved default applies. A color set in the block
   * (a palette slug, a custom value, a gradient) belongs to the wrapper: the ticker's own color is
   * blanked, or the default from Settings would hide it. Pure.
   *
   * @param array $attributes What the editor saved.
   *
   * @return array<string, mixed>
   */
  public static function arguments(array $attributes): array
  {
    $args = [];

    foreach (self::ARGUMENTS as $attribute => $argument) {
      // A hand-edited post may hold an array or an object here: only a scalar is a value.
      if (isset($attributes[$attribute]) && is_scalar($attributes[$attribute])) {
        $args[$argument] = $attributes[$attribute];
      }
    }

    $style = is_array($attributes['style']['color'] ?? null) ? $attributes['style']['color'] : [];

    if (!empty($attributes['backgroundColor']) || !empty($attributes['gradient']) || !empty($style['background']) || !empty($style['gradient'])) {
      $args['bgcolor'] = '';
    }

    if (!empty($attributes['textColor']) || !empty($style['text'])) {
      $args['color'] = '';
    }

    return $args;
  }

  /**
   * The block's render callback.
   *
   * @param array $attributes The block's attributes.
   */
  public static function render(array $attributes = []): string
  {
    return self::markup($attributes, Plugin::options(), wp_is_serving_rest_request());
  }

  /**
   * @param array $attributes The block's attributes.
   * @param array $defaults   The saved defaults.
   * @param bool  $editor     Whether this is the editor's preview request (ServerSideRender).
   *
   * @return string The ticker in the wrapper of the block; '' when there is nothing to show. For the
   *                editor no wrapper: it draws its own, with the same classes, and two would double
   *                the color and the margin. An administrator is told why there is no ticker.
   */
  public static function markup(array $attributes, array $defaults, bool $editor): string
  {
    $built = Ticker::build(self::arguments($attributes), $defaults);

    if ($built['html'] === '') {
      if ($editor && $built['messages'] !== [] && current_user_can('manage_options')) {
        return '<p>' . esc_html(wp_strip_all_tags(implode('; ', $built['messages']))) . '</p>';
      }

      return $editor ? '' : Ticker::note($built['messages']);
    }

    if ($editor) {
      return $built['html'];
    }

    return '<div ' . get_block_wrapper_attributes() . '>' . $built['html'] . '</div>' . Ticker::note($built['messages']);
  }
}
