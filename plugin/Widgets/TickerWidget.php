<?php

namespace NSFeedScrollReaderPlugin\Widgets;

use NSFeedScrollReaderPlugin\Support\Args;
use NSFeedScrollReaderPlugin\Support\Plugin;
use NSFeedScrollReaderPlugin\Ticker;
use NSFeedScrollReaderPlugin\WPBones\Support\Widget;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The classic widget: a title and a ticker. Everything but the title overrides the saved defaults,
 * and an empty field uses them.
 */
class TickerWidget extends Widget
{
  public $id_base = 'feed_scroll_reader';

  public $name = 'Feed Scroll Reader';

  public $widget_options = [
    'classname' => 'widget_feed_scroll_reader',
    'customize_selective_refresh' => false,
    // The block editor's Legacy Widget block reads the instance, so a widget can become the block.
    'show_instance_in_rest' => true,
  ];

  public $control_options = [
    'width' => 350,
  ];

  /** The ticker built by widget(), for viewWidget(). */
  private string $html = '';

  public function __construct($plugin)
  {
    // Translated here: a property cannot call __().
    $this->name = __('Feed Scroll Reader', 'flash-feed-scroll-reader');
    $this->widget_options['description'] = __(
      'A horizontal scrolling ticker of items from one or more RSS/Atom feeds.',
      'flash-feed-scroll-reader'
    );

    parent::__construct($plugin);
  }

  public function defaults()
  {
    return [
      'title' => '',
      'feeds' => '',
      'max_items' => '',
      'speed' => '',
      'height' => '',
      'description' => '',
    ];
  }

  /**
   * @param array $new_instance The posted form.
   * @param array $old_instance The stored instance.
   *
   * @return array
   */
  public function update($new_instance, $old_instance)
  {
    $feeds = Args::feeds((string) ($new_instance['feeds'] ?? ''));

    return [
      'title' => sanitize_text_field((string) ($new_instance['title'] ?? '')),
      'feeds' => implode("\n", $feeds),
      'max_items' => self::optionalInt($new_instance['max_items'] ?? '', 1, 100),
      'speed' => self::optionalInt($new_instance['speed'] ?? '', 10, 400),
      'height' => ($new_instance['height'] ?? '') === '' ? '' : Args::length($new_instance['height'], '', true),
      'description' => in_array($new_instance['description'] ?? '', ['1', '0'], true) ? $new_instance['description'] : '',
    ];
  }

  public function viewForm($instance)
  {
    return Plugin::instance()
      ->view('widgets.form')
      ->with(['widget' => $this, 'instance' => wp_parse_args((array) $instance, $this->defaults())]);
  }

  /**
   * Nothing at all for a visitor when there is no ticker to show: no empty widget box.
   */
  public function widget($args, $instance)
  {
    $instance = wp_parse_args((array) $instance, $this->defaults());

    $this->html = Ticker::html([
      'feeds' => $instance['feeds'],
      'max_items' => $instance['max_items'],
      'speed' => $instance['speed'],
      'height' => $instance['height'],
      'description' => $instance['description'],
    ]);

    if ($this->html === '') {
      return;
    }

    parent::widget($args, $instance);
  }

  public function viewWidget($args, $instance)
  {
    $instance = wp_parse_args((array) $instance, $this->defaults());

    /** This filter is documented in wp-includes/widgets/class-wp-widget-text.php */
    $title = apply_filters('widget_title', (string) $instance['title'], $instance, $this->id_base);

    return Plugin::instance()
      ->view('widgets.widget')
      ->with(['title' => (string) $title, 'args' => $args, 'html' => $this->html]);
  }

  /**
   * An int inside $min..$max as a string, or '' when the field is empty or not a number: an empty
   * widget field means "use the default".
   */
  private static function optionalInt($value, int $min, int $max): string
  {
    if (!is_numeric($value)) {
      return '';
    }

    return (string) Args::int($value, $min, $max, $min);
  }
}
