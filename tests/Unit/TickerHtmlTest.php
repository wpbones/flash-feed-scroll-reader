<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Render\TickerHtml;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;

final class TickerHtmlTest extends MonkeyTestCase
{
  private static function args(array $over = []): array
  {
    return array_merge(
      [
        'feeds' => ['https://a.test/feed'],
        'max_items' => 20,
        'speed' => 50,
        'width' => '100%',
        'height' => '2.75em',
        'bgcolor' => '',
        'color' => '',
        'description' => false,
        'description_length' => 100,
        'suffix' => '[...]',
        'separator' => '•',
        'target' => '_blank',
        'cache' => 30,
        'label' => 'Latest items',
      ],
      $over
    );
  }

  private static function items(): array
  {
    return [
      ['title' => 'First', 'link' => 'https://a.test/1', 'description' => '<p>One two three four five</p>', 'timestamp' => 3],
      ['title' => 'Second', 'link' => 'https://a.test/2', 'description' => 'Two', 'timestamp' => 2],
      ['title' => 'Third', 'link' => 'https://a.test/3', 'description' => '', 'timestamp' => 1],
    ];
  }

  public function test_it_is_a_labelled_region_with_one_list_of_real_links(): void
  {
    $html = TickerHtml::render(self::args(), self::items(), 1);

    $this->assertStringContainsString('role="region" aria-label="Latest items"', $html);
    $this->assertStringContainsString('id="ffsr-1"', $html);
    $this->assertSame(3, substr_count($html, '<li class="ffsr__item">'));
    $this->assertSame(3, substr_count($html, '<a class="ffsr__link" href="https://a.test/'));
    $this->assertStringNotContainsString('aria-live', $html);
    $this->assertStringNotContainsString('tabindex', $html, 'links are reachable as links, with no tabindex tricks');
  }

  public function test_the_separator_goes_between_items_only_and_is_hidden_from_assistive_technology(): void
  {
    $html = TickerHtml::render(self::args(), self::items(), 1);

    $this->assertSame(2, substr_count($html, '<span class="ffsr__sep" aria-hidden="true">•</span>'));
    $this->assertStringEndsWith('Third</a></li></ul></div></div>', $html);
  }

  public function test_an_empty_separator_prints_none(): void
  {
    $html = TickerHtml::render(self::args(['separator' => '']), self::items(), 1);

    $this->assertStringNotContainsString('ffsr__sep', $html);
  }

  public function test_descriptions_are_off_by_default_and_cut_when_on(): void
  {
    $off = TickerHtml::render(self::args(), self::items(), 1);
    $this->assertStringNotContainsString('ffsr__desc', $off);

    $on = TickerHtml::render(self::args(['description' => true, 'description_length' => 7]), self::items(), 1);
    $this->assertStringContainsString('<span class="ffsr__desc">One two[...]</span>', $on);
    $this->assertStringContainsString('<span class="ffsr__desc">Two</span>', $on, 'a short one is not cut');
    $this->assertSame(2, substr_count($on, 'ffsr__desc'), 'an empty description prints no empty span');
  }

  public function test_new_tab_links_get_noopener(): void
  {
    $blank = TickerHtml::render(self::args(['target' => '_blank']), self::items(), 1);
    $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $blank);

    $self = TickerHtml::render(self::args(['target' => '_self']), self::items(), 1);
    $this->assertStringContainsString('target="_self">', $self);
    $this->assertStringNotContainsString('noopener', $self);
  }

  public function test_what_a_feed_says_is_escaped(): void
  {
    $items = [
      [
        'title' => '<script>alert(1)</script><b>Bold</b> & "quotes"',
        'link' => 'https://a.test/x?a=1&b="2"',
        'description' => '<img src=x onerror=alert(2)>text',
        'timestamp' => 1,
      ],
    ];

    $html = TickerHtml::render(self::args(['description' => true]), $items, 1);

    $this->assertStringNotContainsString('<script', $html);
    $this->assertStringNotContainsString('<img', $html);
    $this->assertStringNotContainsString('<b>', $html);
    $this->assertStringContainsString('alert(1)Bold &amp; &quot;quotes&quot;', $html);
    $this->assertStringNotContainsString('"2"', $html, 'a quote in the URL does not leave the attribute');
  }

  public function test_an_item_with_an_unusable_link_is_dropped_and_leaves_no_dangling_separator(): void
  {
    $items = self::items();
    $items[] = ['title' => 'Evil', 'link' => 'javascript:alert(1)', 'description' => '', 'timestamp' => 0];

    $html = TickerHtml::render(self::args(), $items, 1);

    $this->assertStringNotContainsString('javascript', $html);
    $this->assertStringNotContainsString('Evil', $html);
    $this->assertSame(2, substr_count($html, 'ffsr__sep'));
    $this->assertStringEndsWith('Third</a></li></ul></div></div>', $html);
  }

  public function test_an_item_with_no_title_shows_its_address(): void
  {
    $html = TickerHtml::render(self::args(), [['title' => '', 'link' => 'https://a.test/untitled', 'description' => '', 'timestamp' => 1]], 1);

    $this->assertStringContainsString('>https://a.test/untitled</a>', $html);
  }

  public function test_the_look_is_in_custom_properties(): void
  {
    $html = TickerHtml::render(
      self::args(['width' => '480px', 'height' => 'auto', 'bgcolor' => '#112233', 'color' => '#ffffff']),
      self::items(),
      2
    );

    $this->assertStringContainsString('--ffsr-width:480px;', $html);
    $this->assertStringContainsString('--ffsr-height:auto;', $html);
    $this->assertStringContainsString('--ffsr-bg:#112233;', $html);
    $this->assertStringContainsString('--ffsr-fg:#ffffff;', $html);
    $this->assertStringContainsString('id="ffsr-2"', $html);
  }

  public function test_no_color_set_means_the_stylesheet_default_is_used(): void
  {
    $html = TickerHtml::render(self::args(), self::items(), 1);

    $this->assertStringNotContainsString('--ffsr-bg', $html);
    $this->assertStringNotContainsString('--ffsr-fg', $html);
  }

  public function test_the_speed_is_given_to_the_script_and_the_duration_follows_it(): void
  {
    $slow = TickerHtml::render(self::args(['speed' => 20]), self::items(), 1);
    $fast = TickerHtml::render(self::args(['speed' => 200]), self::items(), 1);

    $this->assertStringContainsString('data-speed="20"', $slow);
    $this->assertStringContainsString('data-speed="200"', $fast);

    preg_match('/--ffsr-duration:(\d+)s/', $slow, $s);
    preg_match('/--ffsr-duration:(\d+)s/', $fast, $f);
    $this->assertGreaterThan((int) $f[1], (int) $s[1]);
  }

  public function test_duration_is_bounded(): void
  {
    $this->assertSame(8, TickerHtml::duration(0, 400));
    $this->assertSame(600, TickerHtml::duration(100000, 10));
    $this->assertSame(24, TickerHtml::duration(0, 50), '1200 px at 50 px/s');
  }
}
