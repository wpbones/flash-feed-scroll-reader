<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use NSFeedScrollReaderPlugin\Blocks\TickerBlock;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;
use WP_Error;

/**
 * The block's server side: its attributes as the shortcode's arguments, and the markup it prints
 * through the shared path (Ticker::build), over a stubbed fetch_feed().
 */
final class TickerBlockTest extends MonkeyTestCase
{
  private array $defaults;

  /** @var \ArrayObject The URLs fetch_feed() was asked for. */
  private \ArrayObject $requested;

  protected function setUp(): void
  {
    parent::setUp();

    $this->defaults = require dirname(__DIR__, 2) . '/config/options.php';
    $this->defaults['feeds'] = 'https://default.test/feed';
    $this->requested = new \ArrayObject();

    Functions\when('NSFeedScrollReaderPlugin')->justReturn(new class {
      public string $baseUri = 'https://example.test/wp-content/plugins/flash-feed-scroll-reader';
      public string $Version = '2.1.0';
    });
    Functions\when('is_wp_error')->alias(fn($v) => $v instanceof WP_Error);
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('wp_register_style')->justReturn(true);
    Functions\when('wp_register_script')->justReturn(true);
    Functions\when('wp_enqueue_style')->justReturn(null);
    Functions\when('wp_enqueue_script')->justReturn(null);
    Functions\when('current_user_can')->justReturn(false);
    Functions\when('wp_json_encode')->alias(fn($v) => json_encode($v));
    Functions\when('get_block_wrapper_attributes')->justReturn('class="wp-block-flash-feed-scroll-reader-ticker has-background"');
  }

  /**
   * fetch_feed() with one item per working feed; a WP_Error for the URLs in $failing.
   */
  private function stubFeeds(array $failing = []): void
  {
    $requested = $this->requested;

    Functions\when('fetch_feed')->alias(function (string $url) use ($requested, $failing) {
      $requested[] = $url;

      if (in_array($url, $failing, true)) {
        return new WP_Error('down', 'boom');
      }

      $item = new class ($url) {
        public function __construct(private string $url) {}

        public function get_title()
        {
          return 'Item of ' . $this->url;
        }

        public function get_permalink()
        {
          return $this->url . '/1';
        }

        public function get_description()
        {
          return 'A description of the item';
        }

        public function get_date($f)
        {
          return '100';
        }
      };

      return new class ($item) {
        public function __construct(private object $item) {}

        public function get_items(int $s, int $m): array
        {
          return [$this->item];
        }
      };
    });
  }

  public function test_the_attributes_become_the_shortcodes_arguments(): void
  {
    $args = TickerBlock::arguments([
      'feeds' => "https://a.test/f\nhttps://b.test/f",
      'maxItems' => 5,
      'speed' => 80,
      'description' => false,
      'descriptionLength' => 40,
      'height' => '3em',
      'target' => '_self',
      'label' => 'News',
    ]);

    $this->assertSame(
      [
        'feeds' => "https://a.test/f\nhttps://b.test/f",
        'max_items' => 5,
        'speed' => 80,
        'description' => false,
        'description_length' => 40,
        'height' => '3em',
        'target' => '_self',
        'label' => 'News',
      ],
      $args
    );
  }

  public function test_what_is_not_set_stays_out_so_the_saved_default_applies(): void
  {
    $this->assertSame([], TickerBlock::arguments([]));
    $this->assertSame([], TickerBlock::arguments(['align' => 'wide', 'fontSize' => 'large', 'style' => ['spacing' => ['margin' => ['top' => '1em']]]]));
  }

  public function test_a_hand_edited_attribute_that_is_not_a_scalar_is_ignored(): void
  {
    $this->assertSame(['speed' => 30], TickerBlock::arguments(['feeds' => ['x'], 'label' => new \stdClass(), 'speed' => 30, 'height' => null]));
  }

  public function test_a_block_color_blanks_the_tickers_own_so_the_wrapper_shows(): void
  {
    $this->assertSame(['bgcolor' => ''], TickerBlock::arguments(['backgroundColor' => 'vivid-red']));
    $this->assertSame(['bgcolor' => ''], TickerBlock::arguments(['style' => ['color' => ['background' => '#112233']]]));
    $this->assertSame(['bgcolor' => ''], TickerBlock::arguments(['style' => ['color' => ['gradient' => 'linear-gradient(red, blue)']]]));
    $this->assertSame(['color' => ''], TickerBlock::arguments(['textColor' => 'white']));
    $this->assertSame(['color' => ''], TickerBlock::arguments(['style' => ['color' => ['text' => '#ffffff']]]));
    $this->assertSame(['bgcolor' => '', 'color' => ''], TickerBlock::arguments(['backgroundColor' => 'black', 'textColor' => 'white']));
  }

  public function test_the_ticker_is_inside_the_blocks_wrapper_on_the_front_end(): void
  {
    $this->stubFeeds();

    $html = TickerBlock::markup(['feeds' => 'https://mine.test/feed', 'speed' => 90, 'height' => '4em', 'description' => true], $this->defaults, false);

    $this->assertStringStartsWith('<div class="wp-block-flash-feed-scroll-reader-ticker has-background"><div id="ffsr-', $html);
    $this->assertStringEndsWith('</div></div>', $html);
    $this->assertSame(['https://mine.test/feed'], $this->requested->getArrayCopy(), 'the block replaced the default feed');
    $this->assertStringContainsString('data-speed="90"', $html);
    $this->assertStringContainsString('--ffsr-height:4em;', $html);
    $this->assertStringContainsString('class="ffsr__desc">A description of the item', $html);
  }

  public function test_an_empty_block_uses_the_saved_defaults(): void
  {
    $this->stubFeeds();

    $html = TickerBlock::markup([], $this->defaults, false);

    $this->assertSame(['https://default.test/feed'], $this->requested->getArrayCopy());
    $this->assertStringContainsString('data-speed="50"', $html);
    $this->assertStringNotContainsString('ffsr__desc', $html, 'descriptions are off by default');
  }

  public function test_the_settings_colors_apply_until_the_block_has_its_own(): void
  {
    $this->stubFeeds();
    $defaults = array_merge($this->defaults, ['bgcolor' => '#abcdef', 'color' => '#123456']);

    $plain = TickerBlock::markup([], $defaults, false);
    $this->assertStringContainsString('--ffsr-bg:#abcdef;--ffsr-fg:#123456;', $plain);

    $colored = TickerBlock::markup(['backgroundColor' => 'black', 'textColor' => 'white'], $defaults, false);
    $this->assertStringNotContainsString('--ffsr-bg', $colored, 'the wrapper background shows');
    $this->assertStringNotContainsString('--ffsr-fg', $colored, 'the wrapper text color is inherited');
  }

  public function test_the_feed_cap_applies_to_the_block(): void
  {
    $this->stubFeeds();
    $feeds = implode("\n", array_map(fn($n) => "https://f$n.test/feed", range(1, 8)));

    TickerBlock::markup(['feeds' => $feeds], $this->defaults, false);

    $this->assertCount(5, $this->requested, 'the first 5 of 8 are fetched');
  }

  public function test_a_feed_that_failed_is_not_fetched_again_while_the_mark_lasts(): void
  {
    $this->stubFeeds(['https://default.test/feed']);

    TickerBlock::markup([], $this->defaults, false);
    TickerBlock::markup([], $this->defaults, false);

    $this->assertCount(1, $this->requested);
  }

  public function test_the_editor_gets_the_ticker_without_a_wrapper(): void
  {
    $this->stubFeeds();

    $html = TickerBlock::markup([], $this->defaults, true);

    $this->assertStringStartsWith('<div id="ffsr-', $html);
    $this->assertStringNotContainsString('wp-block-flash-feed-scroll-reader-ticker', $html);
  }

  public function test_nothing_to_show_is_nothing_for_a_visitor(): void
  {
    $this->stubFeeds(['https://default.test/feed']);

    $this->assertSame('', TickerBlock::markup([], $this->defaults, false));
    $this->assertSame('', TickerBlock::markup([], $this->defaults, true));
  }

  public function test_an_administrator_is_told_why(): void
  {
    Functions\when('current_user_can')->justReturn(true);
    $this->stubFeeds(['https://default.test/feed']);

    $front = TickerBlock::markup([], $this->defaults, false);
    $this->assertStringContainsString('<!-- Feed Scroll Reader: https://default.test/feed: boom', $front);
    $this->assertStringNotContainsString('<div', $front, 'no empty wrapper');

    $editor = TickerBlock::markup([], $this->defaults, true);
    $this->assertSame(1, preg_match('#^<p>https://default.test/feed: boom \(not tried again for 15 minutes\)</p>$#', $editor));
  }

  public function test_a_working_feed_beside_a_failing_one_keeps_the_note_outside_the_wrapper(): void
  {
    Functions\when('current_user_can')->justReturn(true);
    $this->stubFeeds(['https://down.test/f']);

    $html = TickerBlock::markup(['feeds' => "https://down.test/f\nhttps://up.test/f"], $this->defaults, false);

    $this->assertStringContainsString('Item of https://up.test/f', $html);
    $this->assertMatchesRegularExpression('#</div></div>\n<!-- Feed Scroll Reader: https://down.test/f: boom#', $html);
  }

  public function test_the_editor_gets_the_defaults_for_its_placeholders(): void
  {
    $script = TickerBlock::defaultsScript($this->defaults);

    $this->assertStringStartsWith('window.ffsrDefaults = {', $script);
    $this->assertStringContainsString('"max_items":20', $script);
    $this->assertStringNotContainsString('migrated', $script, 'only what the editor shows');
    $this->assertStringNotContainsString('legacy_feeds', $script);
  }

  public static function routes(): array
  {
    return [
      'the editor preview' => ['/wp/v2/block-renderer/flash-feed-scroll-reader/ticker', true],
      'without the leading slash' => ['wp/v2/block-renderer/flash-feed-scroll-reader/ticker', true],
      'a post list' => ['/wp/v2/posts', false],
      'a single post' => ['/wp/v2/posts/5', false],
      'no route' => ['', false],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('routes')]
  public function test_only_the_block_renderer_route_is_the_editor_preview(string $route, bool $editor): void
  {
    $this->assertSame($editor, TickerBlock::isEditorRoute($route));
  }

  public function test_a_rest_request_for_a_posts_content_is_not_the_editor(): void
  {
    $this->stubFeeds();
    Functions\when('wp_is_serving_rest_request')->justReturn(true);
    Functions\when('get_block_wrapper_attributes')->justReturn('class="wp-block-flash-feed-scroll-reader-ticker"');

    $GLOBALS['wp'] = (object) ['query_vars' => ['rest_route' => '/wp/v2/posts/7']];
    $this->assertFalse(TickerBlock::isEditorPreview());
    $this->assertStringStartsWith('<div class="wp-block-flash-feed-scroll-reader-ticker">', TickerBlock::markup([], $this->defaults, TickerBlock::isEditorPreview()));

    $GLOBALS['wp'] = (object) ['query_vars' => ['rest_route' => '/wp/v2/block-renderer/flash-feed-scroll-reader/ticker']];
    $this->assertTrue(TickerBlock::isEditorPreview());
    $this->assertStringStartsWith('<div id="ffsr-', TickerBlock::markup([], $this->defaults, TickerBlock::isEditorPreview()));

    unset($GLOBALS['wp']);
  }
}
