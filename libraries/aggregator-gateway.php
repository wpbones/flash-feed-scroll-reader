<?php 
/**
 * aggregator-gateway.php is a special file for Flash cross-domain restriction.
 * This file is called from Movie Flash in this version of plugin.
 *
 * @author		Giovambattista Fazioli
 * @email		g.fazioli@saidmade.com
 * @web			http://www.saidmade.com
 * @rel			http://www.undolog.com
 * 
 */

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
	<rss version="2.00">
		<channel>
			<title>Flash Feed Scroll Reader Aggregator</title>
				<link>http://www.saidmade.com</link>
					<description>Flash Feed Scroll Reader Aggregator</description>
<?php 
/**
 * aggregator.php is a special file for Flash cross-domain restriction and
 * for support multifeed aggregator features.
 * This file is called from Movie Flash in this version of plugin.
 * All params are retrive via wordpress options
 *
 * @author		Giovambattista Fazioli
 * @email		g.fazioli@saidmade.com
 * @web			http://www.saidmade.com
 * @rel			http://www.undolog.com
 * 
 */
require_once "simplepie.inc";
require_once('../../../../wp-config.php');
require_once('../../../../wp-includes/pluggable.php');

require_once('../wp-ffsr_class.php');

$ffsr_class = new WPFFSR_CLASS();
$ffsr_class->options = get_option( $ffsr_class->options_key );

$feed = new SimplePie();
$feed->set_feed_url( $ffsr_class->options['ffsr_aggregator'] );
$feed->set_item_limit( $ffsr_class->options['ffsr_maxitems'] );

$success = $feed->init();
$feed->handle_content_type();

if ($success) {
	foreach($feed->get_items() as $item) {
		?>
		<item>
			<title><?php echo $item->get_title(); ?></title>
			<link><?php echo $item->get_permalink(); ?></link>
			<description><?php echo $item->get_description(); ?></description>
		</item>
		<?php
	}
}
?>

</channel>
</rss>