<?php 
/**
 * gateway.php is a special file for Flash cross-domain restriction.
 * This file is called from Movie Flash in this version of plugin.
 *
 * @param	(string)	$_GET['feedurl']
 *
 * @author		Giovambattista Fazioli
 * @email		g.fazioli@saidmade.com
 * @web			http://www.saidmade.com
 * @rel			http://www.undolog.com
 * 
 */
require_once "simplepie.inc";

$feed = new SimplePie();
$feed->set_feed_url( $_GET['feedurl'] );
$feed->enable_xml_dump(true);
$feed->init();
?>