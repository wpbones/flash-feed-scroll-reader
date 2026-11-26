<?php
/**
 * The ticker widget on the front end.
 *
 * @var string $title The widget title, filtered.
 * @var array  $args  before_title, after_title (the theme's markup).
 * @var string $html  The ticker, already escaped by TickerHtml.
 */

if (!defined('ABSPATH')) {
  exit();
}

if ($title !== '') {
  echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput -- the theme's markup.
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in TickerHtml.
