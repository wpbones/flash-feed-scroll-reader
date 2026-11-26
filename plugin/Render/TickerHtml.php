<?php

namespace NSFeedScrollReaderPlugin\Render;

use NSFeedScrollReaderPlugin\Feed\Items;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The markup of one ticker.
 *
 * ```html
 * <div class="ffsr" role="region" aria-label="…" style="--ffsr-…" data-ffsr data-speed="50">
 *   <div class="ffsr__viewport">
 *     <ul class="ffsr__track" role="list">
 *       <li class="ffsr__item"><a class="ffsr__link" href="…">Title</a><span class="ffsr__desc">…</span>
 *         <span class="ffsr__sep" aria-hidden="true">•</span></li>
 * ```
 *
 * One set of links, no copies: a keyboard or a screen reader meets every item once. The animation
 * is CSS; its duration is set here from the item count and refined by the script from the measured
 * width. Pure: it only needs WordPress's escaping functions.
 */
final class TickerHtml
{
  /** A guess of a character's width in pixels, for the duration before the script measures. */
  private const CHAR_WIDTH = 8;

  /** A guess of the viewport's width, for the same. */
  private const VIEWPORT = 1200;

  /**
   * @param array    $args  The normalized arguments (Args::normalize()), with `label` already resolved.
   * @param array[]  $items Items, from Items::merge().
   * @param int      $id    Number of this ticker on the page: ids stay unique.
   */
  public static function render(array $args, array $items, int $id): string
  {
    $rel = $args['target'] === '_blank' ? ' rel="noopener noreferrer"' : '';
    $showDescription = $args['description'];
    $chars = 0;
    $html = '';

    // Only items with a usable link: esc_url() empties a javascript: or malformed one.
    $links = [];

    foreach ($items as $item) {
      $link = esc_url((string) ($item['link'] ?? ''), ['http', 'https']);

      if ($link !== '') {
        $links[] = [$link, $item];
      }
    }

    $count = count($links);

    foreach ($links as $index => [$link, $item]) {
      $title = Items::plain((string) ($item['title'] ?? ''));
      $title = $title !== '' ? $title : $link;
      $chars += mb_strlen($title);

      $html .= '<li class="ffsr__item">';
      $html .=
        '<a class="ffsr__link" href="' . $link . '" target="' . esc_attr($args['target']) . '"' . $rel . '>' . esc_html($title) . '</a>';

      if ($showDescription) {
        $excerpt = Items::excerpt((string) ($item['description'] ?? ''), $args['description_length'], $args['suffix']);

        if ($excerpt !== '') {
          $chars += mb_strlen($excerpt);
          $html .= '<span class="ffsr__desc">' . esc_html($excerpt) . '</span>';
        }
      }

      if ($index < $count - 1 && $args['separator'] !== '') {
        $chars += mb_strlen($args['separator']);
        $html .= '<span class="ffsr__sep" aria-hidden="true">' . esc_html($args['separator']) . '</span>';
      }

      $html .= '</li>';
    }

    $duration = self::duration($chars, $args['speed']);

    $style = sprintf(
      '--ffsr-width:%s;--ffsr-height:%s;--ffsr-duration:%ss;',
      $args['width'],
      $args['height'],
      $duration
    );

    if ($args['bgcolor'] !== '') {
      $style .= '--ffsr-bg:' . $args['bgcolor'] . ';';
    }

    if ($args['color'] !== '') {
      $style .= '--ffsr-fg:' . $args['color'] . ';';
    }

    return '<div id="ffsr-' .
      (int) $id .
      '" class="ffsr" role="region" aria-label="' .
      esc_attr($args['label']) .
      '" style="' .
      esc_attr($style) .
      '" data-ffsr data-speed="' .
      (int) $args['speed'] .
      '"><div class="ffsr__viewport"><ul class="ffsr__track" role="list">' .
      $html .
      '</ul></div></div>';
  }

  /**
   * Seconds for the track to cross the viewport and its own length, at $speed pixels per second.
   */
  public static function duration(int $chars, int $speed): int
  {
    $travel = $chars * self::CHAR_WIDTH + self::VIEWPORT;

    return max(8, min(600, (int) round($travel / max(1, $speed))));
  }
}
