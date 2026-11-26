<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use NSFeedScrollReaderPlugin\Ticker;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;
use WP_Error;

/**
 * Ticker::html() end to end over a stubbed fetch_feed(): the arguments of every entry point (the
 * 1.x template tag string, a shortcode's array) down to the markup, and what an administrator and a
 * visitor see when a feed fails.
 */
final class TickerTest extends MonkeyTestCase
{
  private array $defaults;

  protected function setUp(): void
  {
    parent::setUp();

    $this->defaults = require dirname(__DIR__, 2) . '/config/options.php';
    $this->defaults['feeds'] = 'https://default.test/feed';

    Functions\when('is_wp_error')->alias(fn($v) => $v instanceof WP_Error);
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('wp_enqueue_style')->justReturn(null);
    Functions\when('wp_enqueue_script')->justReturn(null);
  }

  /**
   * Stubs what Plugin::enqueueFrontEnd() needs from the WP Bones instance.
   */
  private function stubPlugin(): void
  {
    Functions\when('NSFeedScrollReaderPlugin')->justReturn(new class {
      public string $baseUri = 'https://example.test/wp-content/plugins/flash-feed-scroll-reader';
      public string $Version = '2.0.0';
    });
  }

  private function stubFeeds(array $byUrl): \ArrayObject
  {
    $requested = new \ArrayObject();

    Functions\when('fetch_feed')->alias(function (string $url) use ($byUrl, $requested) {
      $requested[] = $url;
      $feed = $byUrl[$url] ?? new WP_Error('nofeed', 'not stubbed: ' . $url);

      if ($feed instanceof WP_Error) {
        return $feed;
      }

      return new class ($feed) {
        public function __construct(private array $items) {}

        public function get_items(int $s, int $m): array
        {
          return array_map(
            fn($i) => new class ($i) {
              public function __construct(private array $i) {}

              public function get_title()
              {
                return $this->i[0];
              }

              public function get_permalink()
              {
                return $this->i[1];
              }

              public function get_description()
              {
                return $this->i[2];
              }

              public function get_date($f)
              {
                return $this->i[3];
              }
            },
            array_slice($this->items, $s, $m)
          );
        }
      };
    });

    return $requested;
  }

  public function test_the_old_template_tag_arguments_reach_the_markup(): void
  {
    $this->stubPlugin();
    Functions\when('current_user_can')->justReturn(false);
    $requested = $this->stubFeeds(['https://mine.test/feed' => [['Hello', 'https://mine.test/1', 'desc', '10']]]);

    $html = Ticker::html(
      'width=300&height=40&feedurl=https://mine.test/feed&scrollspeed=20&bgcolor=#000000&target=_self&stylesheet=http://x.test/a.css&wmode=Opaque',
      $this->defaults
    );

    $this->assertSame(['https://mine.test/feed'], $requested->getArrayCopy(), 'the tag replaced the default feed');
    $this->assertStringContainsString('--ffsr-width:300px;--ffsr-height:40px;', $html);
    $this->assertStringContainsString('--ffsr-bg:#000000;', $html);
    $this->assertStringContainsString('data-speed="50"', $html, '20 ms per step is 50 px/s');
    $this->assertStringContainsString('target="_self"', $html);
    $this->assertStringContainsString('>Hello</a>', $html);
  }

  public function test_a_shortcode_array_overrides_only_what_it_sets(): void
  {
    $this->stubPlugin();
    Functions\when('current_user_can')->justReturn(false);
    $requested = $this->stubFeeds(['https://default.test/feed' => [['T', 'https://d.test/1', '', '1']]]);

    $html = Ticker::html(['speed' => '90', 'description' => 'yes'], $this->defaults);

    $this->assertSame(['https://default.test/feed'], $requested->getArrayCopy());
    $this->assertStringContainsString('data-speed="90"', $html);
    $this->assertStringContainsString('aria-label="Latest items"', $html, 'the translated default label');
  }

  public function test_several_feeds_are_merged_and_the_limit_applies_to_the_whole(): void
  {
    $this->stubPlugin();
    Functions\when('current_user_can')->justReturn(false);
    $this->stubFeeds([
      'https://a.test/f' => [['A-old', 'https://a.test/1', '', '100'], ['A-new', 'https://a.test/2', '', '300']],
      'https://b.test/f' => [['B', 'https://b.test/1', '', '200']],
    ]);

    $html = Ticker::html(['feed' => 'https://a.test/f https://b.test/f', 'max_items' => 2], $this->defaults);

    $this->assertSame(1, preg_match('/A-new.*B/s', $html));
    $this->assertStringNotContainsString('A-old', $html);
  }

  public function test_a_visitor_sees_nothing_when_the_feed_fails(): void
  {
    Functions\when('current_user_can')->justReturn(false);
    $this->stubFeeds(['https://default.test/feed' => new WP_Error('x', 'cURL error 6: Could not resolve host')]);

    $this->assertSame('', Ticker::html([], $this->defaults));
  }

  public function test_an_administrator_gets_the_reason_in_an_html_comment(): void
  {
    Functions\when('current_user_can')->alias(fn($cap) => $cap === 'manage_options');
    $this->stubFeeds(['https://default.test/feed' => new WP_Error('x', 'cURL error 6: Could not resolve host')]);

    $html = Ticker::html([], $this->defaults);

    $this->assertStringContainsString('<!-- Feed Scroll Reader: https://default.test/feed: cURL error 6: Could not resolve host -->', $html);
    $this->assertStringNotContainsString('ffsr__track', $html);
  }

  public function test_the_comment_cannot_be_closed_from_inside(): void
  {
    Functions\when('current_user_can')->justReturn(true);

    $note = Ticker::note(['evil --> <script>alert(1)</script> ---- more']);

    $this->assertSame(1, substr_count($note, '-->'), 'only the closing one');
    $inside = substr($note, strlen("\n<!-- "), -strlen(" -->\n"));
    $this->assertStringNotContainsString('--', $inside);
    $this->assertStringNotContainsString('<script', $note);
  }

  public function test_a_feed_that_works_beside_one_that_fails_still_shows_and_tells_the_administrator(): void
  {
    $this->stubPlugin();
    Functions\when('current_user_can')->justReturn(true);
    $this->stubFeeds([
      'https://up.test/f' => [['Up', 'https://up.test/1', '', '5']],
      'https://down.test/f' => new WP_Error('x', 'boom'),
    ]);

    $html = Ticker::html(['feed' => 'https://down.test/f https://up.test/f'], $this->defaults);

    $this->assertStringContainsString('>Up</a>', $html);
    $this->assertStringContainsString('<!-- Feed Scroll Reader: https://down.test/f: boom -->', $html);
  }

  public function test_no_feed_set_says_so_to_administrators_only(): void
  {
    $defaults = array_merge($this->defaults, ['feeds' => '']);

    Functions\when('current_user_can')->justReturn(false);
    $this->assertSame('', Ticker::html([], $defaults));

    Functions\when('current_user_can')->justReturn(true);
    $this->assertStringContainsString('No feed is set', Ticker::html([], $defaults));
  }

  public function test_a_feed_with_no_items_is_not_an_error_page(): void
  {
    Functions\when('current_user_can')->justReturn(false);
    $this->stubFeeds(['https://default.test/feed' => []]);

    $this->assertSame('', Ticker::html([], $this->defaults));
  }

  public function test_build_returns_the_messages_apart_for_the_settings_page(): void
  {
    $this->stubFeeds(['https://default.test/feed' => new WP_Error('x', 'boom')]);

    $built = Ticker::build([], $this->defaults);

    $this->assertSame('', $built['html']);
    $this->assertSame(['https://default.test/feed: boom'], $built['messages']);
  }
}
