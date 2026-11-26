<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Support\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
  private array $defaults;

  protected function setUp(): void
  {
    // The real config: a field added there must be handled here too.
    $this->defaults = require dirname(__DIR__, 2) . '/config/options.php';
  }

  public function test_every_field_of_the_form_is_a_default_and_every_default_has_a_reason(): void
  {
    foreach (Settings::FIELDS as $field) {
      $this->assertArrayHasKey($field, $this->defaults);
    }

    $owned = array_diff(array_keys($this->defaults), Settings::FIELDS);
    $this->assertEqualsCanonicalizing(['legacy_feeds', 'migrated'], $owned);
  }

  public function test_a_form_is_saved_as_the_stored_row(): void
  {
    $result = Settings::sanitize(
      [
        'feeds' => "https://a.test/feed\n\nhttps://b.test/feed\n",
        'max_items' => '12',
        'speed' => '75',
        'width' => '80%',
        'height' => '3',
        'bgcolor' => '#FFEEDD',
        'color' => '',
        'description' => '1',
        'description_length' => '60',
        'suffix' => '…',
        'separator' => '|',
        'target' => '_self',
        'cache' => '15',
        'label' => 'News',
      ],
      $this->defaults,
      $this->defaults
    );

    $this->assertSame(
      [
        'feeds' => "https://a.test/feed\nhttps://b.test/feed",
        'max_items' => 12,
        'speed' => 75,
        'width' => '80%',
        'height' => '3px',
        'bgcolor' => '#ffeedd',
        'color' => '',
        'description' => 1,
        'description_length' => 60,
        'suffix' => '…',
        'separator' => '|',
        'target' => '_self',
        'cache' => 15,
        'label' => 'News',
        'legacy_feeds' => '',
        'migrated' => 0,
      ],
      $result['values']
    );
    $this->assertSame([], $result['rejected']);
  }

  public function test_an_unchecked_box_is_not_posted_and_means_off(): void
  {
    $current = array_merge($this->defaults, ['description' => 1]);

    $result = Settings::sanitize(['feeds' => 'https://a.test/feed'], $current, $this->defaults);

    $this->assertSame(0, $result['values']['description']);
  }

  public function test_lines_that_are_not_urls_are_dropped_and_reported(): void
  {
    $result = Settings::sanitize(['feeds' => "https://a.test/feed\njavascript:alert(1)"], $this->defaults, $this->defaults);

    $this->assertSame('https://a.test/feed', $result['values']['feeds']);
    $this->assertSame(['javascript:alert(1)'], $result['rejected']);
  }

  public function test_a_form_with_more_than_five_feeds_saves_five(): void
  {
    $lines = implode("\n", array_map(fn($i) => "https://f$i.test/feed", range(1, 9)));

    $result = Settings::sanitize(['feeds' => $lines], $this->defaults, $this->defaults);

    $this->assertCount(5, explode("\n", $result['values']['feeds']));
  }

  public function test_clearing_the_feed_list_keeps_it_empty(): void
  {
    $result = Settings::sanitize(['feeds' => ''], $this->defaults, $this->defaults);

    $this->assertSame('', $result['values']['feeds'], 'the default feed must not come back by itself');
  }

  public function test_invalid_numbers_fall_back_to_the_default_not_to_the_last_value(): void
  {
    $current = array_merge($this->defaults, ['speed' => 200]);

    $result = Settings::sanitize(['feeds' => 'https://a.test/f', 'speed' => 'fast', 'target' => 'popup'], $current, $this->defaults);

    $this->assertSame($this->defaults['speed'], $result['values']['speed']);
    $this->assertSame('_blank', $result['values']['target']);
  }

  public function test_the_migration_state_survives_a_save(): void
  {
    $current = array_merge($this->defaults, ['legacy_feeds' => "https://old.test/a\nhttps://old.test/b", 'migrated' => 1]);

    $result = Settings::sanitize(['feeds' => 'https://a.test/f', 'migrated' => '0', 'legacy_feeds' => 'x'], $current, $this->defaults);

    $this->assertSame(1, $result['values']['migrated']);
    $this->assertSame("https://old.test/a\nhttps://old.test/b", $result['values']['legacy_feeds']);
  }

  public function test_merged_adds_the_defaults_a_stored_row_lacks_and_drops_unknown_keys(): void
  {
    $merged = Settings::merged(['speed' => 99, 'removed_in_1_0' => 'x'], $this->defaults);

    $this->assertSame(99, $merged['speed']);
    $this->assertSame($this->defaults['cache'], $merged['cache']);
    $this->assertArrayNotHasKey('removed_in_1_0', $merged);
  }
}
