<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use NSFeedScrollReaderPlugin\Migration\LegacySettings;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;

/**
 * The migration through the real Plugin helper, over a fake WP Bones instance that keeps its options
 * in an array.
 */
final class LegacySettingsTest extends MonkeyTestCase
{
  /** @var object{basePath: string, stored: array, updates: array} */
  private object $plugin;

  private const OLD = [
    'ffsr_width' => '80%',
    'ffsr_height' => '50',
    'ffsr_description' => '1',
    'ffsr_feedurl' => 'https://single.test/feed',
    'ffsr_scrollspeed' => '20',
    'ffsr_separator' => ' | ',
    'ffsr_stringcut' => '70',
    'ffsr_bgcolor' => '#000000',
    'ffsr_useaggregator' => '1',
    'ffsr_aggregator' => ['https://a.test/feed', 'https://b.test/feed'],
    'ffsr_maxitems' => '4',
    'ffsr_stringcutend' => '…',
    'ffsr_target' => '_self',
  ];

  protected function setUp(): void
  {
    parent::setUp();

    $options = new class {
      public array $stored;
      public int $updates = 0;

      public function toArray(): array
      {
        return $this->stored;
      }

      public function update(array $values): void
      {
        $this->updates++;
        $this->stored = array_replace_recursive($this->stored, $values);
      }
    };

    $options->stored = require dirname(__DIR__, 2) . '/config/options.php';

    $this->plugin = new class ($options) {
      public string $basePath;

      public function __construct(public object $options)
      {
        $this->basePath = dirname(__DIR__, 2);
      }
    };

    Functions\when('NSFeedScrollReaderPlugin')->justReturn($this->plugin);
  }

  public function test_the_old_settings_become_the_new_defaults(): void
  {
    Functions\when('get_option')->alias(fn($name) => $name === 'ffsr_settings' ? self::OLD : false);

    $this->assertTrue(LegacySettings::run());

    $stored = $this->plugin->options->stored;
    $this->assertSame("https://a.test/feed\nhttps://b.test/feed", $stored['feeds']);
    $this->assertSame(8, $stored['max_items'], '4 per feed, two feeds');
    $this->assertSame(50, $stored['speed']);
    $this->assertSame('80%', $stored['width']);
    $this->assertSame('50px', $stored['height']);
    $this->assertSame(1, $stored['description']);
    $this->assertSame(70, $stored['description_length']);
    $this->assertSame('|', $stored['separator'], 'text is trimmed, the CSS spaces it');
    $this->assertSame('…', $stored['suffix']);
    $this->assertSame('#000000', $stored['bgcolor']);
    $this->assertSame('_self', $stored['target']);
    $this->assertSame("https://a.test/feed\nhttps://b.test/feed", $stored['legacy_feeds']);
    $this->assertSame(1, $stored['migrated']);
  }

  public function test_it_runs_once(): void
  {
    Functions\when('get_option')->alias(fn() => self::OLD);

    $this->assertTrue(LegacySettings::run());
    $this->assertFalse(LegacySettings::run(), 'activation and the update both call it');
    $this->assertSame(1, $this->plugin->options->updates);
  }

  public function test_the_user_changes_after_the_migration_are_not_overwritten_by_a_later_call(): void
  {
    Functions\when('get_option')->alias(fn() => self::OLD);
    LegacySettings::run();

    $this->plugin->options->update(['speed' => 120]);
    LegacySettings::run();

    $this->assertSame(120, $this->plugin->options->stored['speed']);
  }

  public function test_a_site_that_never_had_the_old_plugin_is_left_alone(): void
  {
    Functions\when('get_option')->justReturn(false);

    $this->assertFalse(LegacySettings::run());
    $this->assertSame(0, $this->plugin->options->updates);
    $this->assertSame(0, $this->plugin->options->stored['migrated']);
  }

  public function test_a_broken_old_option_is_left_alone(): void
  {
    Functions\when('get_option')->justReturn('a:0:{}garbage');

    $this->assertFalse(LegacySettings::run());
    $this->assertSame(0, $this->plugin->options->updates);
  }
}
