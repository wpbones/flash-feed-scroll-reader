<?php
/**
 * Main class for subclassing backend and frontend class
 *
 * @package         wp-ffsr
 * @subpackage      wp-ffsr_class
 * @author          =undo= <g.fazioli@saidmade.com>
 * @copyright       Copyright (C) 2010 Saidmade Srl
 *
 */

class WPFFSR_CLASS {
		
	/**
	 * @internal
	 * @staticvar
	 */
	var $version 							= "1.2.0";							// plugin version
	var $plugin_name 						= "WP Flash Feed Scroll Reader";	// plugin name
	var $options_key 						= "ffsr_settings";					// options key to store in database
	var $options_title						= "WP Flash Feed Scroll Reader";	// label for "setting" in WP
	
	/**
	 * Usefull vars
	 * @internal 
	 */
	var $content_url						= "";
	var $plugin_url							= "";

	/**
	 * This properties variable are @public
	 * 
	 * @property
	 *  
	 */
	var $options							= array();
	var $init_options;

	/**
	 * @constructor 
	 */
	function WPFFSR_CLASS() {
		global $wpdb;

		/**
		 * Split version for more detail
		 */
		$split_version  = explode(".", $this->version);
		$this->release  = $split_version[0];
		$this->minor    = $split_version[1];
		$this->revision = $split_version[2];

		// paths
		$this->url          = plugins_url("", __FILE__);

        if (! defined('WP_CONTENT_DIR'))
            define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

        if (! defined('WP_CONTENT_URL'))
            define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');

        if (! defined('WP_ADMIN_URL'))
            define('WP_ADMIN_URL', get_option('siteurl') . '/wp-admin');

        if (! defined('WP_PLUGIN_DIR'))
            define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

        if (! defined('WP_PLUGIN_URL'))
            define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');


		$this->content_url 					= get_option('siteurl') . '/wp-content';
		$this->plugin_url 					= $this->content_url . '/plugins/' . plugin_basename( dirname(__FILE__) ) . '/';
	}
	
	/**
	 * Wordpress Front end function. You can set options directly from
	 * param of this function. If options param is null the back-end 
	 * options are used instead
	 * 
	 * @used					Admin and Client
	 *
	 * @param	width			Flash movie width. This value can be in pixel or percent 
	 * @param	height			Flash movie height. This value can be in pixel or percent
	 * @param	feedurl			URL address of feed rss to show
	 * @param	scrollspeed		Scroll speed in millisecond
	 * @param	separator		HTML text between feed title
	 * @param	description		Set to "1" for show description content. "0" for none
	 * @param	stringcut		If description is set to "1" this is the number of chars to show
	 * @param	stylesheet		StyleSheet URL address
	 * @param	bgcolor			Background color for Flash Movie in #RRGGBB value
	 * @param	wmode			Window Mode for Flash Movie: Window, Opaque, Transparent
	 * @param	useaggregator	Set to '1' for use aggregator setting, else '0' default
	 *
	 */
	function get_flashfeedscrollreader( $user_args = null, $id = '' ) {
		$fp		= $this->plugin_url . 'flash/feedscrollreader.swf?120';
		//$fp 		= 'http://saidmade.googlecode.com/svn/trunk/Flash/FeedScrollReader/feedscrollreader.swf';
		/**
		 * convert "ffsr_" array into "only value" array
		 */
		$default	= array();
		foreach( $this->options as $key => $value ) $default[ substr($key,5) ] = $value;
		$args = wp_parse_args( $user_args, $default );
		
		$feedurl = $args['feedurl'];
		$gateway = $this->plugin_url . 'libraries/single-gateway.php?feedurl=' . $args['feedurl'];
		//$gateway = sprintf("%sfeedreader/single-gateway.php?feedurl=%s&abspath=%s", $this->plugin_url, $args['feedurl'], ABSPATH);
		
		if($args['useaggregator'] == '1' ) {
			$feedurl = $this->plugin_url . 'libraries/aggregator-gateway.php' ;
			$gateway = '';
		}

		$fv = 'usegateway=' . $gateway . '&'.
			  'stylesheet=' . $args['stylesheet'] . '?' . (time()) . '&'.
			  'feedurl=' . $feedurl . '&' .
			  'stringcutend=' . $args['stringcutend'] . '&'.
			  'target=' . $args['target'].'&' .
			  'scrollspeed=' . $args['scrollspeed'] . '&'.
			  'separator=' . $args['separator'] . '&'.
			  'description=' . $args['description'] . '&'.
			  'bgcolor=' . str_replace('#', '0x', $args['bgcolor']) . '&'.
			  'stringcut=' . $args['stringcut'];

		$pa = '<param name="bgcolor" value="'.$args['bgcolor'].'" />
				<param name="flashvars" value="'.$fv.'">
				<param name="wmode" value="'.$args['wmode'].'">
				<param name="play" value="0">
				<param name="loop" value="-1">
				<param name="quality" value="high">
				<param name="salign" value="lt">
				<param name="menu" value="-1">
				<param name="allowscriptaccess" value="">
				<param name="scale" value="noscale">
				<param name="devicefont" value="0">
				<param name="embedmovie" value="0">
				<param name="swremote" value="">
				<param name="moviedata" value="">
				<param name="seamlesstabbing" value="1">
				<param name="profile" value="0">
				<param name="profileaddress" value="">
				<param name="profileport" value="0">
				<param name="allownetworking" value="all">
				<param name="allowfullscreen" value="false">';

		
		$o = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$args['width'].'" height="'.$args['height'].'" >
			    <param name="movie" value="'.$fp.'" />'.$pa.
				' <!--[if !IE]>-->
				    <object type="application/x-shockwave-flash" data="'.$fp.'" width="'.$args['width'].'" height="'.$args['height'].'">
				    <param name="flashvars"	value="'.$fv.'" />'.$pa.
					'<!--<![endif]-->
						<a target="_blank" href="http://www.saidmade.com">Flash Feed Scroll Reader</a>
						<!--[if !IE]>-->
	    			</object>
	    		<!--<![endif]-->
	  		</object>';
		
		return( $o );
	}

	/**
	 * Get (http) path plugin
	 *
	 * @result	(string)	Path plugin
	 */
	function getPluginPath() {
		$path 	= strstr(realpath(dirname(__file__)), 'wp-content');
		$path 	= str_replace('\\', '/', $path);
		return( get_bloginfo('url') . '/' . $path );
	}

} // end of class

?>