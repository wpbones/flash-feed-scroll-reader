<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Support\Legacy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The 1.x template tag and the 1.x `ffsr_settings` option, in the shapes the real plugin produced:
 * its own defaults, its own sample string.
 */
final class LegacyTest extends TestCase
{
  /** What 1.2.0 stored on a site that never touched the settings (wp-ffsr_admin.php init_options). */
  private const OLD_DEFAULTS = [
    'ffsr_width' => '100%',
    'ffsr_height' => '40',
    'ffsr_description' => '0',
    'ffsr_feedurl' => 'http://www.saidmade.com/feed',
    'ffsr_scrollspeed' => '25',
    'ffsr_separator' => ' * ',
    'ffsr_stringcut' => '50',
    'ffsr_stylesheet' => 'http://example.com/wp-content/plugins/flash-feed-scroll-reader/css/style.css',
    'ffsr_bgcolor' => '#ffffff',
    'ffsr_wmode' => 'Window',
    'ffsr_gateway' => 'http://example.com/gateway.php',
    'ffsr_sample' => 'width=500&height=50&feedurl=http://labs.saidmade.com/feed&bgcolor=#000000&useaggregator=0',
    'ffsr_useaggregator' => '0',
    'ffsr_aggregator' => ['http://www.saidmade.com/feed', 'http://www.appleside.it/feed', 'http://www.undolog.com/feed'],
    'ffsr_maxitems' => '5',
    'ffsr_stringcutend' => '[...]',
    'ffsr_target' => '_blank',
  ];

  public function test_the_old_readme_example_translates(): void
  {
    $out = Legacy::translate('width=100%&height=20&target=_self');

    $this->assertSame(['width' => '100%', 'height' => '20', 'target' => '_self'], $out);
  }

  public function test_the_old_sample_string_translates(): void
  {
    $out = Legacy::translate(self::OLD_DEFAULTS['ffsr_sample']);

    $this->assertSame(
      ['width' => '500', 'height' => '50', 'feeds' => 'http://labs.saidmade.com/feed', 'bgcolor' => '#000000'],
      $out
    );
  }

  public function test_old_names_map_to_new_ones(): void
  {
    $out = Legacy::translate([
      'feedurl' => 'https://a.test/feed',
      'scrollspeed' => '20',
      'stringcut' => '80',
      'stringcutend' => '…',
      'separator' => ' | ',
      'description' => '1',
    ]);

    $this->assertSame(
      [
        'feeds' => 'https://a.test/feed',
        'speed' => 50,
        'description_length' => '80',
        'suffix' => '…',
        'separator' => ' | ',
        'description' => '1',
      ],
      $out
    );
  }

  public function test_flash_only_arguments_are_dropped_quietly(): void
  {
    $out = Legacy::translate('stylesheet=http://x.test/a.css&wmode=Transparent&width=300');

    $this->assertSame(['width' => '300'], $out);
  }

  public function test_new_names_pass_through(): void
  {
    $out = Legacy::translate(['speed' => 90, 'color' => '#000', 'max_items' => 7]);

    $this->assertSame(['speed' => 90, 'color' => '#000', 'max_items' => 7], $out);
  }

  public function test_use_aggregator_takes_the_old_aggregator_list(): void
  {
    $options = ['legacy_feeds' => "http://a.test/f\nhttp://b.test/f"];

    $this->assertSame(
      ['feeds' => "http://a.test/f\nhttp://b.test/f"],
      Legacy::translate('useaggregator=1', $options)
    );
  }

  public function test_use_aggregator_never_overrides_a_feed_the_tag_names(): void
  {
    $out = Legacy::translate('useaggregator=1&feedurl=http://mine.test/f', ['legacy_feeds' => 'http://a.test/f']);

    $this->assertSame(['feeds' => 'http://mine.test/f'], $out);
  }

  public function test_use_aggregator_off_changes_nothing(): void
  {
    $this->assertSame([], Legacy::translate('useaggregator=0', ['legacy_feeds' => 'http://a.test/f']));
  }

  public function test_garbage_in_is_an_empty_array(): void
  {
    $this->assertSame([], Legacy::translate(null));
    $this->assertSame([], Legacy::translate(''));
    $this->assertSame([], Legacy::translate(42));
  }

  #[DataProvider('speeds')]
  public function test_scroll_speed_in_milliseconds_becomes_pixels_per_second(string $ms, ?int $speed): void
  {
    $this->assertSame($speed, Legacy::speedFromMilliseconds($ms));
  }

  public static function speeds(): array
  {
    return [
      'the old default, 25 ms' => ['25', 40],
      '10 ms' => ['10', 100],
      'too fast is clamped' => ['1', 400],
      'too slow is clamped' => ['500', 10],
      'zero is no speed' => ['0', null],
      'garbage' => ['fast', null],
    ];
  }

  public function test_untouched_1x_settings_migrate(): void
  {
    $new = Legacy::migrate(self::OLD_DEFAULTS);

    $this->assertArrayNotHasKey('feeds', $new, 'the only feed was the dead default: the new default stays');
    $this->assertSame(40, $new['speed']);
    $this->assertSame('100%', $new['width']);
    $this->assertSame('40', $new['height']);
    $this->assertSame(0, $new['description']);
    $this->assertSame('50', $new['description_length']);
    $this->assertSame('[...]', $new['suffix']);
    $this->assertSame(' * ', $new['separator']);
    $this->assertSame('#ffffff', $new['bgcolor']);
    $this->assertSame('_blank', $new['target']);
    $this->assertArrayNotHasKey('max_items', $new, 'the per-feed limit only counts with the aggregator');
    $this->assertSame("http://www.appleside.it/feed\nhttp://www.undolog.com/feed", $new['legacy_feeds']);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function deadDefaults(): array
  {
    return [
      'the 1.x default' => ['http://www.saidmade.com/feed'],
      'https' => ['https://www.saidmade.com/feed'],
      'trailing slash' => ['http://www.saidmade.com/feed/'],
      'no www, upper case' => ['HTTPS://SAIDMADE.COM/Feed/'],
    ];
  }

  #[DataProvider('deadDefaults')]
  public function test_the_dead_default_feed_as_the_only_feed_is_left_to_the_new_default(string $url): void
  {
    $old = self::OLD_DEFAULTS;
    $old['ffsr_feedurl'] = $url;
    $old['ffsr_useaggregator'] = '0';

    $this->assertArrayNotHasKey('feeds', Legacy::migrate($old));
  }

  #[DataProvider('deadDefaults')]
  public function test_the_dead_default_feed_among_others_is_dropped(string $url): void
  {
    $old = self::OLD_DEFAULTS;
    $old['ffsr_useaggregator'] = '1';
    $old['ffsr_aggregator'] = [$url, 'https://mine.test/feed'];
    $old['ffsr_maxitems'] = '5';

    $new = Legacy::migrate($old);

    $this->assertSame('https://mine.test/feed', $new['feeds']);
    $this->assertSame('https://mine.test/feed', $new['legacy_feeds']);
    $this->assertSame(5, $new['max_items'], 'the limit counts the feeds that are left');
  }

  public function test_other_saidmade_addresses_are_kept(): void
  {
    $old = ['ffsr_feedurl' => 'http://www.saidmade.com/feed/rss2/'];

    $this->assertSame('http://www.saidmade.com/feed/rss2/', Legacy::migrate($old)['feeds']);
  }

  public function test_the_aggregator_becomes_the_feed_list_and_the_limit_is_for_the_whole_ticker(): void
  {
    $old = self::OLD_DEFAULTS;
    $old['ffsr_useaggregator'] = '1';
    $old['ffsr_maxitems'] = '5';

    $new = Legacy::migrate($old);

    $this->assertSame(
      "http://www.appleside.it/feed\nhttp://www.undolog.com/feed",
      $new['feeds']
    );
    $this->assertSame(10, $new['max_items']);
  }

  public function test_the_aggregator_flag_without_a_list_falls_back_to_the_single_feed(): void
  {
    $old = self::OLD_DEFAULTS;
    $old['ffsr_useaggregator'] = '1';
    $old['ffsr_aggregator'] = '';

    $new = Legacy::migrate($old);

    $this->assertArrayNotHasKey('feeds', $new);
    $this->assertArrayNotHasKey('legacy_feeds', $new);
  }

  public function test_a_partial_or_odd_old_option_does_not_break(): void
  {
    $this->assertSame([], Legacy::migrate([]));
    $this->assertSame(['feeds' => 'https://a.test/f'], Legacy::migrate(['ffsr_feedurl' => 'https://a.test/f', 'ffsr_target' => ['x']]));
  }
}
