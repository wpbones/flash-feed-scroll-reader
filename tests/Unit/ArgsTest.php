<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Support\Args;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArgsTest extends TestCase
{
  private const DEFAULTS = [
    'feeds' => "https://example.com/feed\nhttps://example.org/atom",
    'max_items' => 20,
    'speed' => 50,
    'width' => '100%',
    'height' => '2.75em',
    'bgcolor' => '',
    'color' => '',
    'description' => 0,
    'description_length' => 100,
    'suffix' => '[...]',
    'separator' => '•',
    'target' => '_blank',
    'cache' => 30,
    'label' => '',
  ];

  public function test_nothing_given_gives_the_defaults(): void
  {
    $args = Args::normalize([], self::DEFAULTS);

    $this->assertSame(['https://example.com/feed', 'https://example.org/atom'], $args['feeds']);
    $this->assertSame(20, $args['max_items']);
    $this->assertSame(50, $args['speed']);
    $this->assertSame('100%', $args['width']);
    $this->assertSame('2.75em', $args['height']);
    $this->assertFalse($args['description']);
    $this->assertSame('•', $args['separator']);
    $this->assertSame('_blank', $args['target']);
  }

  public function test_a_given_value_overrides_the_default_and_a_blank_one_does_not(): void
  {
    $args = Args::normalize(['speed' => '120', 'height' => '', 'max_items' => '   '], self::DEFAULTS);

    $this->assertSame(120, $args['speed']);
    $this->assertSame('2.75em', $args['height']);
    $this->assertSame(20, $args['max_items']);
  }

  public function test_blank_separator_suffix_and_colors_are_a_choice_not_a_missing_value(): void
  {
    $defaults = array_merge(self::DEFAULTS, ['bgcolor' => '#112233', 'color' => '#ffffff']);

    $args = Args::normalize(['separator' => '', 'suffix' => '', 'bgcolor' => '', 'color' => ''], $defaults);

    $this->assertSame('', $args['separator']);
    $this->assertSame('', $args['suffix']);
    $this->assertSame('', $args['bgcolor']);
    $this->assertSame('', $args['color']);
  }

  public function test_numbers_are_clamped_and_garbage_falls_back(): void
  {
    $args = Args::normalize(
      ['speed' => '99999', 'max_items' => '-4', 'cache' => 'soon', 'description_length' => '0'],
      self::DEFAULTS
    );

    $this->assertSame(400, $args['speed']);
    $this->assertSame(1, $args['max_items']);
    $this->assertSame(30, $args['cache']);
    $this->assertSame(1, $args['description_length']);
  }

  #[DataProvider('lengths')]
  public function test_length(string $in, string $expected): void
  {
    $this->assertSame($expected, Args::length($in, 'DEFAULT', true));
  }

  public static function lengths(): array
  {
    return [
      'percent' => ['100%', '100%'],
      'bare number is pixels (1.x: height=40)' => ['40', '40px'],
      'decimal em' => ['2.5em', '2.5em'],
      'upper case' => ['300PX', '300px'],
      'auto allowed here' => ['auto', 'auto'],
      'zero is no size' => ['0', 'DEFAULT'],
      'a CSS injection' => ['10px;background:url(//evil)', 'DEFAULT'],
      'a function' => ['calc(100% - 4px)', 'DEFAULT'],
      'a word' => ['big', 'DEFAULT'],
    ];
  }

  public function test_width_does_not_take_auto(): void
  {
    $this->assertSame('100%', Args::length('auto', '100%', false));
  }

  #[DataProvider('colors')]
  public function test_color(string $in, string $expected): void
  {
    $this->assertSame($expected, Args::color($in));
  }

  public static function colors(): array
  {
    return [
      'six digits' => ['#FFAA00', '#ffaa00'],
      'three digits' => ['#fa0', '#fa0'],
      'with alpha' => ['#11223344', '#11223344'],
      'a name is not accepted' => ['red', ''],
      'no hash' => ['ffaa00', ''],
      'injection' => ['#fff;position:fixed', ''],
      'empty' => ['', ''],
    ];
  }

  #[DataProvider('booleans')]
  public function test_bool($in, bool $expected): void
  {
    $this->assertSame($expected, Args::bool($in));
  }

  public static function booleans(): array
  {
    return [
      ['1', true],
      ['yes', true],
      ['TRUE', true],
      [1, true],
      ['0', false],
      ['no', false],
      ['', false],
      [0, false],
      ['maybe', false],
    ];
  }

  #[DataProvider('targets')]
  public function test_target(string $in, string $expected): void
  {
    $this->assertSame($expected, Args::target($in));
  }

  public static function targets(): array
  {
    return [
      ['_blank', '_blank'],
      ['_SELF', '_self'],
      ['_top', '_self'],
      ['_parent', '_self'],
      ['myframe', '_blank'],
      ['', '_blank'],
    ];
  }

  public function test_text_is_plain_and_short(): void
  {
    $this->assertSame('bold move', Args::text(' <b>bold</b> move ', 20));
    $this->assertSame('abcde', Args::text('abcdefghij', 5));
    $this->assertSame('è√ü', Args::text('è√üxyz', 3));
  }

  public function test_feeds_split_on_lines_commas_and_spaces_and_keep_each_once(): void
  {
    $feeds = Args::feeds("https://a.test/feed, https://b.test/feed\nhttps://a.test/feed   https://c.test/rss");

    $this->assertSame(['https://a.test/feed', 'https://b.test/feed', 'https://c.test/rss'], $feeds);
  }

  public function test_feeds_keep_only_web_addresses(): void
  {
    $value = "javascript:alert(1)\nftp://a.test/feed\nfile:///etc/passwd\nnot a url\nhttps://ok.test/feed\nhttp://";

    $this->assertSame(['https://ok.test/feed'], Args::feeds($value));
    $this->assertSame(['javascript:alert(1)', 'ftp://a.test/feed', 'file:///etc/passwd', 'not', 'a', 'url', 'http://'], Args::rejectedFeeds($value));
  }

  public function test_a_ticker_reads_at_most_five_feeds(): void
  {
    $urls = array_map(fn($i) => "https://f$i.test/feed", range(1, 50));

    $this->assertCount(5, Args::feeds($urls));
    $this->assertSame(['https://f1.test/feed', 'https://f2.test/feed', 'https://f3.test/feed', 'https://f4.test/feed', 'https://f5.test/feed'], Args::feeds(implode("\n", $urls)));
    $this->assertCount(5, Args::normalize(['feeds' => implode(' ', $urls)], self::DEFAULTS)['feeds']);
  }

  public function test_the_cap_counts_valid_feeds_not_lines(): void
  {
    $value = "nope\njavascript:x\n" . implode("\n", array_map(fn($i) => "https://f$i.test/feed", range(1, 8)));

    $this->assertSame('https://f5.test/feed', Args::feeds($value)[4]);
  }

  public function test_feeds_take_an_array_too(): void
  {
    $this->assertSame(['https://a.test/f'], Args::feeds(['https://a.test/f', 'https://a.test/f', 12, '']));
  }

  public function test_the_arguments_a_shortcode_cannot_break_the_page_with(): void
  {
    $args = Args::normalize(
      ['width' => '1px"onmouseover="x', 'bgcolor' => 'red;x', 'separator' => '<script>alert(1)</script>', 'label' => '"><img src=x>'],
      self::DEFAULTS
    );

    $this->assertSame('100%', $args['width']);
    $this->assertSame('', $args['bgcolor']);
    $this->assertSame('alert(1)', $args['separator']);
    $this->assertStringNotContainsString('<', $args['label']);
  }
}
