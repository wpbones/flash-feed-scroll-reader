<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Support;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Brain Monkey around every test, and the escaping functions every renderer test needs, stubbed with
 * the behaviour that matters: markup and quotes are turned into entities, a non-web URL is emptied.
 */
abstract class MonkeyTestCase extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    Functions\when('esc_html')->alias(fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
    Functions\when('esc_attr')->alias(fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
    Functions\when('esc_url')->alias(function ($url, $protocols = null) {
      $url = trim((string) $url);

      return preg_match('#^https?://#i', $url) === 1 ? htmlspecialchars($url, ENT_QUOTES, 'UTF-8') : '';
    });
    Functions\when('wp_strip_all_tags')->alias(fn($v) => trim(strip_tags((string) $v)));
    Functions\when('__')->returnArg();
  }

  protected function tearDown(): void
  {
    // A Brain Monkey expectation is a Mockery expectation, which PHPUnit does not count as an
    // assertion: without this a test made only of expect()->once() is reported as risky.
    $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    Monkey\tearDown();
    parent::tearDown();
  }
}
