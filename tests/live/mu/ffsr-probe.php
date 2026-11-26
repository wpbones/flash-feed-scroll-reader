<?php
/* Probe only: feeds from fixtures (no network), pages that call the template tag and the widget, a JSON dump of state. */
add_filter('pre_http_request', function ($pre, $args, $url) {
  $host = parse_url($url, PHP_URL_HOST);
  if (in_array($host, ['feeds.test', 'down.test'], true)) { file_put_contents('/logs/fetches.txt', $url . "\n", FILE_APPEND); }
  if ($host === 'feeds.test') {
    $file = '/wordpress/wp-content/ffsr-fixtures/' . basename(parse_url($url, PHP_URL_PATH));
    if (is_file($file)) {
      return ['headers' => ['content-type' => 'application/xml'], 'body' => file_get_contents($file), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => [], 'filename' => null];
    }
    return ['headers' => [], 'body' => 'Not found', 'response' => ['code' => 404, 'message' => 'Not Found'], 'cookies' => [], 'filename' => null];
  }
  if ($host === 'down.test') {
    return new WP_Error('http_request_failed', 'cURL error 6: Could not resolve host: down.test');
  }
  return $pre;
}, 10, 3);

// A real site sets its language with the WPLANG option: do the same, not the `locale` filter.
add_filter('pre_option_WPLANG', function ($v) { return isset($_GET['ffsr_lang']) ? preg_replace('/[^a-zA-Z_]/', '', $_GET['ffsr_lang']) : $v; });

add_filter('the_content', function ($c) {
  if (is_page('tag')) {
    $c .= "<!--TAG-->" . get_flashfeedscrollreader('width=300&height=40&feedurl=https://feeds.test/a.xml&scrollspeed=20&stylesheet=x.css&wmode=Opaque&useaggregator=0');
    ob_start();
    flashfeedscrollreader(['feedurl' => 'https://feeds.test/b.xml', 'separator' => ' | ']);
    $c .= "<!--ECHO-->" . ob_get_clean();
  }
  if (is_page('widget')) {
    ob_start();
    the_widget('NSFeedScrollReaderPlugin\Widgets\TickerWidget', ['title' => 'News <b>box</b>', 'feeds' => 'https://feeds.test/b.xml', 'max_items' => '5', 'speed' => '70', 'description' => '1'], ['before_widget' => '<div class="WBEFORE">', 'after_widget' => '</div>', 'before_title' => '<h2 class="WTITLE">', 'after_title' => '</h2>']);
    $c .= "<!--WIDGET-->" . ob_get_clean();
    ob_start();
    the_widget('NSFeedScrollReaderPlugin\Widgets\TickerWidget', ['title' => 'Broken', 'feeds' => 'https://down.test/f.xml'], ['before_widget' => '<div class="WBEFORE2">', 'after_widget' => '</div>']);
    $c .= "<!--WIDGET2-->" . ob_get_clean();
  }
  return $c;
});

// A sidebar for the widgets REST check: the default theme of the Playground may have none.
add_action('widgets_init', function () { register_sidebar(['id' => 'ffsr-test', 'name' => 'ffsr test']); });

add_action('wp_loaded', function () {
  if (!isset($_GET['ffsr_probe'])) return;
  header('Content-Type: text/plain');
  switch ($_GET['ffsr_probe']) {
    case 'row':
      global $wpdb;
      echo "ROW=" . $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='feed_scroll_reader_slug'") . "\n";
      echo "OLD=" . (get_option('ffsr_settings') ? 'present' : 'absent') . "\n";
      echo "VERSION=" . get_option('feed_scroll_reader_slug_db_version') . "\n";
      break;
    case 'widget_form':
      global $wp_widget_factory;
      $w = $wp_widget_factory->widgets['NSFeedScrollReaderPlugin\Widgets\TickerWidget'] ?? null;
      echo $w ? 'REGISTERED' : 'MISSING', "\n";
      if ($w) { $w->_set(5); $w->form(['title' => 'T"x', 'feeds' => "https://a.test/f\nhttps://b.test/f", 'max_items' => '7', 'speed' => '', 'height' => '', 'description' => '1']); 
        $u = $w->update(['title' => '<b>Hi</b>', 'feeds' => "javascript:x\nhttps://ok.test/f", 'max_items' => '9999', 'speed' => 'abc', 'height' => '40;x', 'description' => '1'], []);
        echo "\nUPDATE=" . json_encode($u) . "\n"; }
      break;
    case 'i18n':
      echo 'locale=' . get_locale() . ' determine=' . determine_locale() . "\n";
      echo 'loaded=' . var_export(is_textdomain_loaded('flash-feed-scroll-reader'), true) . "\n";
      echo 'file=' . var_export(file_exists(WP_PLUGIN_DIR . '/flash-feed-scroll-reader/languages/flash-feed-scroll-reader-it_IT.mo'), true) . "\n";
      echo 'tr=' . __('Latest items', 'flash-feed-scroll-reader') . "\n";
      global $l10n; echo 'domains=' . implode(',', array_keys($l10n)) . "\n";
      break;
    case 'transients':
      global $wpdb;
      echo 'T=' . json_encode($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE INSTR(option_name, '_transient_ffsr_') = 1")) . "\n";
      break;
    case 'alltransients':
      global $wpdb;
      echo json_encode($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE INSTR(option_name, 'transient') > 0")) . "\n";
      break;
    case 'shortcodes':
      echo shortcode_exists('feed_scroll_reader') ? "SHORTCODE=yes\n" : "SHORTCODE=no\n";
      break;
    case 'reset_version':
      delete_option('feed_scroll_reader_slug_db_version');
      echo "reset\n";
      break;
    case 'plant_old':
      update_option('ffsr_settings', ['ffsr_width' => '90%', 'ffsr_height' => '60', 'ffsr_feedurl' => 'https://feeds.test/b.xml', 'ffsr_scrollspeed' => '10', 'ffsr_separator' => ' # ', 'ffsr_description' => '1', 'ffsr_stringcut' => '15', 'ffsr_stringcutend' => '~', 'ffsr_target' => '_self', 'ffsr_bgcolor' => '#abcdef', 'ffsr_useaggregator' => '1', 'ffsr_aggregator' => ['https://feeds.test/a.xml', 'https://feeds.test/b.xml'], 'ffsr_maxitems' => '3']);
      delete_option('feed_scroll_reader_slug'); // as on a site that never ran 2.0
      echo "planted\n";
      break;
    case 'uninstall':
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      wp_set_current_user(1);
      global $wpdb;
      set_transient('ffsr_rejected_7', ['x'], 120);
      echo "COREFEED_BEFORE=" . count($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE INSTR(option_name, '_site_transient_feed_') = 1")) . "\n";
      echo "BEFORE=" . json_encode($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'feed_scroll_reader%' OR INSTR(option_name, 'ffsr_') > 0")) . "\n";
      deactivate_plugins('flash-feed-scroll-reader/wp-flashfeedscrollreader.php');
      $r = delete_plugins(['flash-feed-scroll-reader/wp-flashfeedscrollreader.php']);
      echo "DELETE=" . (is_wp_error($r) ? $r->get_error_message() : var_export($r, true)) . "\n";
      global $wpdb;
      $left = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'feed_scroll_reader%' OR INSTR(option_name, 'ffsr_') > 0");
      echo "LEFT=" . json_encode($left) . "\n";
      echo "COREFEED_AFTER=" . count($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE INSTR(option_name, '_site_transient_feed_') = 1")) . "\n";
      break;
  }
  exit;
}, 0);
