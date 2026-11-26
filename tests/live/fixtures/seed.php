<?php
require '/wordpress/wp-load.php';
update_option('ffsr_settings', [
  'ffsr_width' => '90%', 'ffsr_height' => '60', 'ffsr_feedurl' => 'https://feeds.test/b.xml', 'ffsr_scrollspeed' => '10',
  'ffsr_separator' => ' # ', 'ffsr_description' => '1', 'ffsr_stringcut' => '15', 'ffsr_stringcutend' => '~', 'ffsr_target' => '_self',
  'ffsr_bgcolor' => '#abcdef', 'ffsr_useaggregator' => '1', 'ffsr_aggregator' => ['https://feeds.test/a.xml', 'https://feeds.test/b.xml'],
  'ffsr_maxitems' => '3', 'ffsr_stylesheet' => 'http://x/css/style.css', 'ffsr_wmode' => 'Window',
]);
$pages = [
  'shortcode' => '[feed_scroll_reader feed="https://feeds.test/a.xml https://feeds.test/b.xml" description="yes" description_length="20" speed="80" height="3em" bgcolor="#112233" color="#ffffff" max_items="3"]',
  'tag' => 'tag page',
  'widget' => 'widget page',
  'down' => '[feed_scroll_reader feed="https://down.test/f.xml"]',
  'many' => '[feed_scroll_reader feed="https://feeds.test/a.xml?n=1 https://feeds.test/a.xml?n=2 https://feeds.test/a.xml?n=3 https://feeds.test/a.xml?n=4 https://feeds.test/a.xml?n=5 https://feeds.test/a.xml?n=6 https://feeds.test/a.xml?n=7"]',
  'empty' => '[feed_scroll_reader feed="https://feeds.test/missing.xml"]',
];
foreach ($pages as $slug => $content) {
  wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $slug, 'post_name' => $slug, 'post_content' => $content]);
}
wp_insert_user(['user_login' => 'sub', 'user_pass' => 'password', 'role' => 'subscriber', 'user_email' => 'sub@example.test']);
