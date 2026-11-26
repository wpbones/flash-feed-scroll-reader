<?php

declare(strict_types=1);

namespace NSFeedScrollReaderPlugin\Tests\Unit;

use NSFeedScrollReaderPlugin\Providers\TextDomain;
use PHPUnit\Framework\TestCase;

final class TextDomainTest extends TestCase
{
  private string $root;

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/ffsr-textdomain-' . bin2hex(random_bytes(4));
    mkdir($this->root . '/plugin/languages', 0777, true);
    mkdir($this->root . '/lang/plugins', 0777, true);
  }

  protected function tearDown(): void
  {
    foreach (glob($this->root . '/*/{*,*/*}', GLOB_BRACE) ?: [] as $file) {
      is_file($file) && unlink($file);
    }
    foreach (['/plugin/languages', '/plugin', '/lang/plugins', '/lang'] as $dir) {
      @rmdir($this->root . $dir);
    }
    @rmdir($this->root);
  }

  public function test_the_language_pack_comes_before_the_bundled_catalogue(): void
  {
    touch($this->root . '/lang/plugins/flash-feed-scroll-reader-es_ES.mo');
    touch($this->root . '/plugin/languages/flash-feed-scroll-reader-es_ES.mo');

    $files = TextDomain::files($this->root . '/plugin', 'es_ES', $this->root . '/lang');

    $this->assertSame(
      [$this->root . '/lang/plugins/flash-feed-scroll-reader-es_ES.mo', $this->root . '/plugin/languages/flash-feed-scroll-reader-es_ES.mo'],
      $files
    );
  }

  public function test_without_a_pack_the_bundled_one_is_used(): void
  {
    touch($this->root . '/plugin/languages/flash-feed-scroll-reader-it_IT.mo');

    $this->assertSame(
      [$this->root . '/plugin/languages/flash-feed-scroll-reader-it_IT.mo'],
      TextDomain::files($this->root . '/plugin', 'it_IT', $this->root . '/lang')
    );
  }

  public function test_a_pack_alone_is_used_for_a_locale_the_plugin_does_not_bundle(): void
  {
    touch($this->root . '/lang/plugins/flash-feed-scroll-reader-ja.mo');

    $this->assertSame(
      [$this->root . '/lang/plugins/flash-feed-scroll-reader-ja.mo'],
      TextDomain::files($this->root . '/plugin', 'ja', $this->root . '/lang')
    );
  }

  public function test_no_catalogue_at_all_loads_nothing(): void
  {
    $this->assertSame([], TextDomain::files($this->root . '/plugin', 'en_US', $this->root . '/lang'));
  }

  public function test_another_locales_files_are_not_loaded(): void
  {
    touch($this->root . '/lang/plugins/flash-feed-scroll-reader-fr_FR.mo');
    touch($this->root . '/plugin/languages/flash-feed-scroll-reader-de_DE.mo');

    $this->assertSame([], TextDomain::files($this->root . '/plugin', 'it_IT', $this->root . '/lang'));
  }
}
