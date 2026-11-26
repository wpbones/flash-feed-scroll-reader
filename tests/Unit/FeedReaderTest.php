<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use Brain\Monkey\Functions;
use NSFeedScrollReaderPlugin\Feed\FeedReader;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;
use WP_Error;

/**
 * FeedReader over fetch_feed(), stubbed: the SimplePie objects are plain fakes with the four
 * methods the reader calls.
 */
final class FeedReaderTest extends MonkeyTestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('is_wp_error')->alias(fn($v) => $v instanceof WP_Error);
  }

  private static function feed(array $items): object
  {
    return new class ($items) {
      public function __construct(private array $items) {}

      public function get_items(int $start, int $max): array
      {
        return array_slice($this->items, $start, $max);
      }
    };
  }

  private static function simplePieItem(string $title, string $link, string $description, $date): object
  {
    return new class ($title, $link, $description, $date) {
      public function __construct(private string $t, private string $l, private string $d, private $date) {}

      public function get_title(): string
      {
        return $this->t;
      }

      public function get_permalink(): string
      {
        return $this->l;
      }

      public function get_description(): string
      {
        return $this->d;
      }

      public function get_date($format)
      {
        return $this->date;
      }
    };
  }

  public function test_several_feeds_are_read_and_merged(): void
  {
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('fetch_feed')->alias(
      fn(string $url) => match ($url) {
        'https://a.test/feed' => self::feed([self::simplePieItem('A1', 'https://a.test/1', 'd', '100')]),
        'https://b.test/feed' => self::feed([self::simplePieItem('B1', 'https://b.test/1', 'd', '200')]),
      }
    );

    $result = FeedReader::read(['https://a.test/feed', 'https://b.test/feed'], 10, 600);

    $this->assertSame(['B1', 'A1'], array_column($result['items'], 'title'));
    $this->assertSame(200, $result['items'][0]['timestamp']);
    $this->assertSame([], $result['errors']);
  }

  public function test_a_feed_that_fails_does_not_take_the_others_with_it(): void
  {
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('fetch_feed')->alias(
      fn(string $url) => $url === 'https://down.test/feed'
        ? new WP_Error('simplepie-error', 'cURL error 28: timed out')
        : self::feed([self::simplePieItem('Up', 'https://up.test/1', '', '50')])
    );

    $result = FeedReader::read(['https://down.test/feed', 'https://up.test/feed'], 10, 600);

    $this->assertSame(['Up'], array_column($result['items'], 'title'));
    $this->assertSame(['https://down.test/feed: cURL error 28: timed out'], $result['errors']);
  }

  public function test_every_feed_failing_gives_errors_and_no_items(): void
  {
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('fetch_feed')->alias(fn() => new WP_Error('x', 'boom'));

    $result = FeedReader::read(['https://a.test/f', 'https://b.test/f'], 10, 600);

    $this->assertSame([], $result['items']);
    $this->assertCount(2, $result['errors']);
  }

  public function test_an_item_without_a_date_has_a_null_timestamp(): void
  {
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('fetch_feed')->alias(fn() => self::feed([self::simplePieItem('t', 'https://a.test/1', '', null)]));

    $result = FeedReader::read(['https://a.test/f'], 10, 600);

    $this->assertNull($result['items'][0]['timestamp']);
  }

  public function test_the_cache_lifetime_is_set_for_the_fetch_and_taken_back_after(): void
  {
    $added = null;
    $removed = null;

    Functions\when('add_filter')->alias(function ($hook, $callback, $priority = 10) use (&$added) {
      $added = [$hook, $callback, $priority];
      return true;
    });
    Functions\when('remove_filter')->alias(function ($hook, $callback, $priority = 10) use (&$removed) {
      $removed = [$hook, $callback, $priority];
      return true;
    });
    Functions\when('fetch_feed')->alias(function () use (&$added, &$removed) {
      // while fetching: the filter is in, and answers the lifetime asked for
      $this->assertNotNull($added);
      $this->assertNull($removed);
      $this->assertSame(1800, ($added[1])());

      return self::feed([]);
    });

    FeedReader::read(['https://a.test/f'], 5, 1800);

    $this->assertSame('wp_feed_cache_transient_lifetime', $added[0]);
    $this->assertSame($added, $removed, 'the same callback at the same priority is removed');
  }

  public function test_the_filter_is_taken_back_even_when_fetching_throws(): void
  {
    $removed = false;

    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->alias(function () use (&$removed) {
      $removed = true;
      return true;
    });
    Functions\when('fetch_feed')->alias(function () {
      throw new \RuntimeException('simplepie blew up');
    });

    try {
      FeedReader::read(['https://a.test/f'], 5, 60);
      $this->fail('the exception was swallowed');
    } catch (\RuntimeException) {
      $this->assertTrue($removed);
    }
  }

  public function test_the_limit_is_asked_of_each_feed(): void
  {
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_filter')->justReturn(true);
    Functions\when('fetch_feed')->alias(
      fn() => self::feed(array_map(fn($i) => self::simplePieItem("t$i", "https://a.test/$i", '', (string) (1000 - $i)), range(1, 10)))
    );

    $result = FeedReader::read(['https://a.test/f'], 3, 60);

    $this->assertSame(['t1', 't2', 't3'], array_column($result['items'], 'title'));
  }
}
