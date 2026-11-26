<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Feed\Items;
use NSFeedScrollReaderPlugin\Tests\Support\MonkeyTestCase;

final class ItemsTest extends MonkeyTestCase
{
  private static function item(string $link, ?int $timestamp, string $title = 't'): array
  {
    return ['title' => $title, 'link' => $link, 'description' => '', 'timestamp' => $timestamp];
  }

  public function test_feeds_are_merged_newest_first(): void
  {
    $a = [self::item('https://a/1', 100), self::item('https://a/2', 300)];
    $b = [self::item('https://b/1', 200), self::item('https://b/2', 400)];

    $merged = Items::merge([$a, $b], 10);

    $this->assertSame(['https://b/2', 'https://a/2', 'https://b/1', 'https://a/1'], array_column($merged, 'link'));
  }

  public function test_the_limit_is_for_the_whole_list(): void
  {
    $a = [self::item('https://a/1', 100), self::item('https://a/2', 300)];
    $b = [self::item('https://b/1', 200), self::item('https://b/2', 400)];

    $merged = Items::merge([$a, $b], 3);

    $this->assertSame(['https://b/2', 'https://a/2', 'https://b/1'], array_column($merged, 'link'));
  }

  public function test_the_same_link_is_shown_once(): void
  {
    $a = [self::item('https://x/1', 100, 'from a')];
    $b = [self::item('https://x/1', 100, 'from b')];

    $merged = Items::merge([$a, $b], 10);

    $this->assertCount(1, $merged);
    $this->assertSame('from a', $merged[0]['title']);
  }

  public function test_undated_items_follow_the_dated_ones_in_feed_order(): void
  {
    $a = [self::item('https://a/undated1', null), self::item('https://a/dated', 50)];
    $b = [self::item('https://b/undated2', null)];

    $merged = Items::merge([$a, $b], 10);

    $this->assertSame(['https://a/dated', 'https://a/undated1', 'https://b/undated2'], array_column($merged, 'link'));
  }

  public function test_equal_dates_keep_feed_order(): void
  {
    $a = [self::item('https://a/1', 100)];
    $b = [self::item('https://b/1', 100)];

    $this->assertSame(['https://a/1', 'https://b/1'], array_column(Items::merge([$a, $b], 10), 'link'));
  }

  public function test_an_item_without_a_link_is_dropped_and_no_helper_key_leaks(): void
  {
    $merged = Items::merge([[self::item('', 100), self::item('https://a/1', 90)]], 10);

    $this->assertSame([['title' => 't', 'link' => 'https://a/1', 'description' => '', 'timestamp' => 90]], $merged);
  }

  public function test_an_item_that_cannot_be_linked_does_not_take_a_place_of_the_limit(): void
  {
    $items = [
      self::item('javascript:alert(1)', 900),
      self::item('ftp://x/1', 800),
      self::item('HTTPS://a/1', 100),
      self::item('https://a/2', 50),
    ];

    $merged = Items::merge([$items], 2);

    $this->assertSame(['HTTPS://a/1', 'https://a/2'], array_column($merged, 'link'));
  }

  public function test_no_feeds_no_items(): void
  {
    $this->assertSame([], Items::merge([], 5));
    $this->assertSame([], Items::merge([[]], 5));
  }

  public function test_plain_strips_tags_decodes_entities_and_collapses_space(): void
  {
    $this->assertSame('Tom & Jerry say "hi"', Items::plain("<p>Tom &amp; Jerry\n  say <b>&quot;hi&quot;</b></p>"));
  }

  public function test_excerpt_cuts_and_marks_the_cut(): void
  {
    $this->assertSame('The quick[...]', Items::excerpt('<p>The quick brown fox</p>', 9, '[...]'));
    $this->assertSame('The quick brown fox', Items::excerpt('<p>The quick brown fox</p>', 19, '[...]'), 'a text that fits is not marked');
    $this->assertSame('The…', Items::excerpt('The quick', 4, '…'));
  }

  public function test_excerpt_counts_characters_not_bytes(): void
  {
    $this->assertSame('àèìòù[...]', Items::excerpt('àèìòùàèìòù', 5, '[...]'));
  }

  public function test_excerpt_of_nothing_is_nothing(): void
  {
    $this->assertSame('', Items::excerpt('  <br> ', 10, '[...]'));
  }
}
