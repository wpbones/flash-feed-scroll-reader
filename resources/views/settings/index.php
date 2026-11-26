<?php
/**
 * Settings > Feed Scroll Reader.
 *
 * @var array  $values   The saved defaults.
 * @var bool   $updated  The settings were just saved.
 * @var array  $rejected Lines of the feed list that were not URLs.
 * @var array  $preview  Ticker::build(): html, messages.
 * @var string $action   admin-post action.
 * @var string $field    Name of the form's array.
 */

if (!defined('ABSPATH')) {
  exit();
}

$name = static fn(string $key): string => esc_attr($field . '[' . $key . ']');
$id = static fn(string $key): string => esc_attr('ffsr-' . $key);
?>
<div class="wrap ffsr-admin">
  <h1><?php esc_html_e('Feed Scroll Reader', 'flash-feed-scroll-reader'); ?></h1>

  <?php if ($updated) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'flash-feed-scroll-reader'); ?></p></div>
  <?php endif; ?>

  <?php if ($rejected !== []) : ?>
    <div class="notice notice-warning">
      <p><?php esc_html_e('These lines are not web addresses and were not saved:', 'flash-feed-scroll-reader'); ?></p>
      <ul class="ul-disc">
        <?php foreach ($rejected as $line) : ?>
          <li><code><?php echo esc_html($line); ?></code></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <p><?php esc_html_e('A horizontal scrolling ticker of the latest items of one or more feeds. These are the defaults of every ticker; a shortcode, a widget or a template tag can change any of them for itself.', 'flash-feed-scroll-reader'); ?></p>

  <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
    <?php wp_nonce_field($action); ?>

    <h2 class="title"><?php esc_html_e('Feeds', 'flash-feed-scroll-reader'); ?></h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="<?php echo $id('feeds'); ?>"><?php esc_html_e('Feed addresses', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <textarea id="<?php echo $id('feeds'); ?>" name="<?php echo $name('feeds'); ?>" rows="4" cols="60" class="large-text code" spellcheck="false"><?php echo esc_textarea($values['feeds']); ?></textarea>
          <p class="description"><?php esc_html_e('One RSS or Atom address per line, up to 5. Several feeds are merged, newest first.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('max_items'); ?>"><?php esc_html_e('Items', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="number" min="1" max="100" class="small-text" id="<?php echo $id('max_items'); ?>" name="<?php echo $name('max_items'); ?>" value="<?php echo esc_attr($values['max_items']); ?>">
          <p class="description"><?php esc_html_e('How many items the ticker shows in all, from 1 to 100.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('cache'); ?>"><?php esc_html_e('Refresh every', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="number" min="1" max="1440" class="small-text" id="<?php echo $id('cache'); ?>" name="<?php echo $name('cache'); ?>" value="<?php echo esc_attr($values['cache']); ?>">
          <?php esc_html_e('minutes', 'flash-feed-scroll-reader'); ?>
          <p class="description"><?php esc_html_e('A feed is downloaded again after this time; between two downloads the saved copy is used.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
    </table>

    <h2 class="title"><?php esc_html_e('Look', 'flash-feed-scroll-reader'); ?></h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="<?php echo $id('width'); ?>"><?php esc_html_e('Width', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="small-text" id="<?php echo $id('width'); ?>" name="<?php echo $name('width'); ?>" value="<?php echo esc_attr($values['width']); ?>">
          <p class="description"><?php esc_html_e('A number with a unit: 100%, 480px, 30em. A number alone is pixels.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('height'); ?>"><?php esc_html_e('Height', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="small-text" id="<?php echo $id('height'); ?>" name="<?php echo $name('height'); ?>" value="<?php echo esc_attr($values['height']); ?>">
          <p class="description"><?php esc_html_e('Like the width, or auto. The default follows the size of the text.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('bgcolor'); ?>"><?php esc_html_e('Background color', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="ffsr-color-field" id="<?php echo $id('bgcolor'); ?>" name="<?php echo $name('bgcolor'); ?>" value="<?php echo esc_attr($values['bgcolor']); ?>">
          <p class="description"><?php esc_html_e('Empty: transparent, the page shows through.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('color'); ?>"><?php esc_html_e('Text color', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="ffsr-color-field" id="<?php echo $id('color'); ?>" name="<?php echo $name('color'); ?>" value="<?php echo esc_attr($values['color']); ?>">
          <p class="description"><?php esc_html_e('Empty: the color of the surrounding text. The font is always the theme’s.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
    </table>

    <h2 class="title"><?php esc_html_e('Behavior', 'flash-feed-scroll-reader'); ?></h2>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="<?php echo $id('speed'); ?>"><?php esc_html_e('Speed', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="number" min="10" max="400" class="small-text" id="<?php echo $id('speed'); ?>" name="<?php echo $name('speed'); ?>" value="<?php echo esc_attr($values['speed']); ?>">
          <?php esc_html_e('pixels per second', 'flash-feed-scroll-reader'); ?>
          <p class="description"><?php esc_html_e('The ticker stops while the pointer is over it or a link has the keyboard focus, and does not move at all for visitors who ask their system for reduced motion.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('separator'); ?>"><?php esc_html_e('Separator', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="small-text" id="<?php echo $id('separator'); ?>" name="<?php echo $name('separator'); ?>" value="<?php echo esc_attr($values['separator']); ?>">
          <p class="description"><?php esc_html_e('Plain text between two items. Empty: none.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php esc_html_e('Descriptions', 'flash-feed-scroll-reader'); ?></th>
        <td>
          <label for="<?php echo $id('description'); ?>">
            <input type="checkbox" id="<?php echo $id('description'); ?>" name="<?php echo $name('description'); ?>" value="1" <?php checked((int) $values['description'], 1); ?>>
            <?php esc_html_e('Show the description after each title', 'flash-feed-scroll-reader'); ?>
          </label>
          <p>
            <label for="<?php echo $id('description_length'); ?>"><?php esc_html_e('Cut it to', 'flash-feed-scroll-reader'); ?></label>
            <input type="number" min="1" max="1000" class="small-text" id="<?php echo $id('description_length'); ?>" name="<?php echo $name('description_length'); ?>" value="<?php echo esc_attr($values['description_length']); ?>">
            <?php esc_html_e('characters, and end it with', 'flash-feed-scroll-reader'); ?>
            <label class="screen-reader-text" for="<?php echo $id('suffix'); ?>"><?php esc_html_e('Text at the end of a cut description', 'flash-feed-scroll-reader'); ?></label>
            <input type="text" class="small-text" id="<?php echo $id('suffix'); ?>" name="<?php echo $name('suffix'); ?>" value="<?php echo esc_attr($values['suffix']); ?>">
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('target'); ?>"><?php esc_html_e('Open links', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <select id="<?php echo $id('target'); ?>" name="<?php echo $name('target'); ?>">
            <option value="_blank" <?php selected($values['target'], '_blank'); ?>><?php esc_html_e('In a new tab', 'flash-feed-scroll-reader'); ?></option>
            <option value="_self" <?php selected($values['target'], '_self'); ?>><?php esc_html_e('In the same tab', 'flash-feed-scroll-reader'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo $id('label'); ?>"><?php esc_html_e('Label for screen readers', 'flash-feed-scroll-reader'); ?></label></th>
        <td>
          <input type="text" class="regular-text" id="<?php echo $id('label'); ?>" name="<?php echo $name('label'); ?>" value="<?php echo esc_attr($values['label']); ?>" placeholder="<?php esc_attr_e('Latest items', 'flash-feed-scroll-reader'); ?>">
          <p class="description"><?php esc_html_e('The name of the ticker for assistive technology.', 'flash-feed-scroll-reader'); ?></p>
        </td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>

  <h2 class="title"><?php esc_html_e('Preview', 'flash-feed-scroll-reader'); ?></h2>
  <?php foreach ($preview['messages'] as $message) : ?>
    <div class="notice notice-warning inline"><p><?php echo esc_html($message); ?></p></div>
  <?php endforeach; ?>
  <?php if ($preview['html'] !== '') : ?>
    <div class="ffsr-admin__preview"><?php echo $preview['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in TickerHtml. ?></div>
    <p class="description"><?php esc_html_e('The saved defaults, as a visitor sees them.', 'flash-feed-scroll-reader'); ?></p>
  <?php endif; ?>

  <h2 class="title"><?php esc_html_e('Use it', 'flash-feed-scroll-reader'); ?></h2>
  <table class="form-table" role="presentation">
    <tr>
      <th scope="row"><?php esc_html_e('Shortcode', 'flash-feed-scroll-reader'); ?></th>
      <td>
        <p><code>[feed_scroll_reader]</code></p>
        <p><code>[feed_scroll_reader feed="https://example.com/feed" speed="60" description="yes" height="3em"]</code></p>
        <p class="description"><?php esc_html_e('Attributes: feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Each one is optional and falls back to the defaults above.', 'flash-feed-scroll-reader'); ?></p>
      </td>
    </tr>
    <tr>
      <th scope="row"><?php esc_html_e('Widget', 'flash-feed-scroll-reader'); ?></th>
      <td><p><?php esc_html_e('Appearance > Widgets > Feed Scroll Reader.', 'flash-feed-scroll-reader'); ?></p></td>
    </tr>
    <tr>
      <th scope="row"><?php esc_html_e('Template tag', 'flash-feed-scroll-reader'); ?></th>
      <td>
        <p><code>&lt;?php flashfeedscrollreader(); ?&gt;</code></p>
        <p><code>&lt;?php flashfeedscrollreader( 'width=100%&amp;height=40&amp;feedurl=https://example.com/feed' ); ?&gt;</code></p>
        <p><code>&lt;?php echo get_flashfeedscrollreader( array( 'feedurl' =&gt; 'https://example.com/feed' ) ); ?&gt;</code></p>
        <p class="description"><?php esc_html_e('The same two functions as before version 2. The old arguments still work; stylesheet, wmode and the other ones that only made sense for the Flash movie are ignored.', 'flash-feed-scroll-reader'); ?></p>
      </td>
    </tr>
  </table>
</div>
