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
  // the block: its own settings and the block supports (colors, alignment); only the saved defaults;
  // 8 feeds and a dead one; a post edited by hand, with values of the wrong type.
  'block' => '<!-- wp:flash-feed-scroll-reader/ticker {"feeds":"https://feeds.test/a.xml\\nhttps://feeds.test/b.xml","maxItems":2,"speed":80,"height":"3em","description":true,"descriptionLength":20,"backgroundColor":"black","textColor":"white","align":"wide","fontSize":"large"} /-->',
  'blockdefaults' => '<!-- wp:flash-feed-scroll-reader/ticker /-->',
  'blockmany' => '<!-- wp:flash-feed-scroll-reader/ticker {"feeds":"https://feeds.test/a.xml?b=1\\nhttps://feeds.test/a.xml?b=2\\nhttps://feeds.test/a.xml?b=3\\nhttps://feeds.test/a.xml?b=4\\nhttps://feeds.test/a.xml?b=5\\nhttps://feeds.test/a.xml?b=6\\nhttps://feeds.test/a.xml?b=7\\nhttps://feeds.test/a.xml?b=8"} /-->',
  'blockdown' => '<!-- wp:flash-feed-scroll-reader/ticker {"feeds":"https://down.test/block.xml"} /-->',
  'blockbad' => '<!-- wp:flash-feed-scroll-reader/ticker {"feeds":["https://x.test/f"],"label":{"a":1},"speed":"fast","maxItems":null,"style":{"color":"red"}} /-->',
];
foreach ($pages as $slug => $content) {
  // wp_insert_post() wants slashed data: the block comments hold JSON escapes.
  wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $slug, 'post_name' => $slug, 'post_content' => wp_slash($content)]);
}
wp_insert_user(['user_login' => 'sub', 'user_pass' => 'password', 'role' => 'subscriber', 'user_email' => 'sub@example.test']);
