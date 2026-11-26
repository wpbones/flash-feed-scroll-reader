<?php
/**
 * Class for Manage Admin (back-end)
 *
 * @package         wp-cleanfix
 * @subpackage      wp-cleanfix_admin
 * @author          =undo= <g.fazioli@saidmade.com>
 * @copyright       Copyright (C) 2010 Saidmade Srl
 *
 */

class WPFFSR_ADMIN extends WPFFSR_CLASS {
	
	function WPFFSR_ADMIN() {
		$this->WPFFSR_CLASS();
		$this->init();
	}
	
	/**
	 * Init the default plugin options and re-load from WP
	 * 
	 * @return 
	 */
	function init() {

		/**
		 * Load localizations if available
		 *
		 * @since 0.1.0
		 */
		load_plugin_textdomain ( 'wp-ffsr' , false, 'flash-feed-scroll-reader/localization' );

        /**
         * Add option menu in Wordpress backend
         */
 		add_action('admin_init', array( $this, 'plugin_init') );
        add_action('admin_menu', array( $this, 'plugin_setup') );


		$this->init_options					= array(
												'ffsr_width'			=> '100%',
												'ffsr_height'			=> '40',
												'ffsr_description'		=> '0',
												'ffsr_feedurl'			=> 'http://www.saidmade.com/feed',
												'ffsr_scrollspeed'		=> '25',
												'ffsr_separator'		=> ' * ',
												'ffsr_stringcut'		=> '50',
												'ffsr_stylesheet'		=> $this->getPluginPath().'/css/style.css',
												'ffsr_bgcolor'			=> '#ffffff',
												'ffsr_wmode'			=> 'Window',
												'ffsr_gateway'			=> $this->getPluginPath().'/gateway.php',
												'ffsr_sample'			=> 'width=500&height=50&feedurl=http://labs.saidmade.com/feed&bgcolor=#000000&useaggregator=0',
												'ffsr_useaggregator'	=> '0',
												'ffsr_aggregator'		=> array('http://www.saidmade.com/feed', 'http://www.appleside.it/feed', 'http://www.undolog.com/feed'),
												'ffsr_maxitems'			=> '5',
												'ffsr_stringcutend'		=> '[...]',
												'ffsr_target'			=> '_blank'
								  			  );
		
		add_option( $this->options_key, $this->init_options, $this->options_title);
		
		/**
		 * Load all options in $this->options array
		 */
		$this->options = get_option( $this->options_key );
		
	}

	/**
	 * Register style for plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function plugin_init() {
        /**
         * Add queue for style sheet
         */
        wp_register_style('wp_ffsr_admin', $this->url . "/css/wp_ffsr_admin.css");

	}

	/**
     * ADD OPTION PAGE TO WORDPRESS ENVIRORMENT
     *
     * Add callback for adding options panel
     *
     */
    function plugin_setup() {
		if ( function_exists('add_options_page') ) {
			$this->plugin_page = add_options_page( $this->options_title, $this->options_title, 8, basename(__FILE__), array(&$this, "menu"));
			//add_action( 'load-'. $this->plugin_page, array($this, 'on_load_page') );
			add_action( 'admin_print_scripts-'. $this->plugin_page, array($this, 'plugin_admin_scripts') );
			add_action( 'admin_print_styles-'. $this->plugin_page, array($this, 'plugin_admin_styles') );
		}

		if (function_exists('add_contextual_help')) {
            add_contextual_help( $this->plugin_page ,'<h3>' . __("Usage", "wp-ffsr") . '</h3>
<p>' . __("Insert in your theme file:", "wp-ffsr") . ' <pre><code>&lt;?php flashfeedscrollreader( args ) ?&gt;</code></pre>
<p>' . __("In <code>args</code> you can using:", "wp-ffsr") . '</p>
<pre><code>width        Flash movie width. This value can be in pixel or percent</li>
height       Flash movie height. This value can be in pixel or percent
feedurl      URL address of feed rss to show
scrollspeed  Scroll speed in millisecond
separator    HTML text between feed title
description  Set to "1" for show description content. "0" for none
stringcut    If description is set to "1" this is the number of chars to show
stringcutend String to append when the description is cutted, default "[..]"
stylesheet   StyleSheet URL address
bgcolor      Background color for Flash Movie in #RRGGBB value
wmode        Window Mode for Flash Movie: Window, Opaque, Transparent
target       Window target on click feed title link, default "_blank"</code></pre>
<p>' . __("For Example:", "wp-ffsr") . '</p>
<pre><code>&lt;?php get_flashfeedscrollreader("width=100%&height=20&target=_self") ?&gt;</code></pre>
<p>' . __("or:", "wp-ffsr") . '</p>
<pre><code>&lt;?php get_flashfeedscrollreader("width=250&height=20&feedurl=http://labs.saidmade.com/feed&scrollspeed=30") ?&gt;</code></pre>
');
		}
    }

	/**
	 * Execute when plugin is showing on backend
	 *
	 * @sine 1.0.0
	 * @return void
	 */
    function plugin_admin_styles() {
       wp_enqueue_style('wp_ffsr_admin');
    }

    /**
	 * Execute when plugin is showing on backend
	 *
	 * @since 1.0.0
	 * @return void
	 */
    function plugin_admin_scripts() {
        /**
         * Add wp_enqueue_script for jQuery library
         *
         * @since 0.1.0
         */
		wp_enqueue_script('common');
        wp_enqueue_script('postbox');
		wp_enqueue_script('wp-lists');

        /**
         * Add main admin javascript
         *
         * @since 2.4.0
         */
		wp_enqueue_script ( 'wp_ffsr_admin_js' , $this->url . '/js/wp_ffsr_admin.js' , array ( 'jquery' ) , '1.4' , true );
    }


	function menu() {
		global $wpdb;

        /**
         * Any error flag
         */
        $any_error = "";

        if( isset( $_POST['command_action'] ) ) {
            $any_error = __('Your settings have been saved.', 'wp-ffsr');

			/**
			 * Check for any missing or wrong POST value
			 */
			if( $_POST['ffsr_feedurl'] == '' ||	$_POST['ffsr_stylesheet'] == ''  ) {
				$any_error = __('Some field is empty! Check and try again!', "wp-ffsr");
			} else {
				$this->options['ffsr_description'] 		= $_POST['ffsr_description'];
				$this->options['ffsr_feedurl'] 			= $_POST['ffsr_feedurl'];
				$this->options['ffsr_scrollspeed'] 		= $_POST['ffsr_scrollspeed'];
				$this->options['ffsr_separator'] 		= $_POST['ffsr_separator'];
				$this->options['ffsr_stringcut'] 		= $_POST['ffsr_stringcut'];
				$this->options['ffsr_stylesheet'] 		= $_POST['ffsr_stylesheet'];
				$this->options['ffsr_width'] 			= $_POST['ffsr_width'];
				$this->options['ffsr_height'] 			= $_POST['ffsr_height'];
				$this->options['ffsr_bgcolor'] 			= $_POST['ffsr_bgcolor'];
				$this->options['ffsr_wmode'] 			= $_POST['ffsr_wmode'];
				$this->options['ffsr_sample'] 			= $_POST['ffsr_sample'];
				$this->options['ffsr_stringcutend'] 	= $_POST['ffsr_stringcutend'];
				$this->options['ffsr_target'] 			= $_POST['ffsr_target'];
				$this->options['ffsr_useaggregator'] 	= $_POST['ffsr_useaggregator'];
				if( $this->options['ffsr_useaggregator'] == '1') {
					$this->options['ffsr_aggregator'] 		= $_POST['ffsr_aggregator'];
					$this->options['ffsr_maxitems'] 		= $_POST['ffsr_maxitems'];
				}
				update_option( $this->options_key, $this->options);
			}
        }

        /**
         * Show error or OK
         */
        if( $any_error != '') {
            echo '<div id="message" class="updated fade"><p>' . $any_error . '</p></div>';
        }
        ?>

<div class="wrap">

	<div class="wp_saidmade_box">
		<p class="wp_saidmade_copy_info"><?php _e('For more info and plugins visit', 'wp-ffsr') ?> <a href="http://www.saidmade.com">Saidmade</a></p>
		<a class="wp_saidmade_logo" href="http://www.saidmade.com/prodotti/wordpress/wp-ffsr/">
			<?php echo $this->plugin_name ?> ver. <?php echo $this->version ?>
		</a>
	</div>

	<form action="" method="post">
		<input type="hidden" name="command_action" value="1" />
		<div id="poststuff" class="metabox-holder">
			<div id="post-body" class="has-sidebar">
				<div class="postbox">
					<h3><?php _e('Settings', 'wp-ffsr') ?></h3>
					<div class="inside">

						<table class="form-table" id="settings">
							<tr>
								<th scope="row"><label for="ffsr_width"><?php _e("Width:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_width" id="ffsr_width" type="text" size="5" value="<?php echo htmlentities($this->options['ffsr_width']) ?>" /> <span>*</span> <?php _e("(percent or pixel)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_height"><?php _e("Height:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_height" id="ffsr_height" type="text" size="5" value="<?php echo htmlentities($this->options['ffsr_height']) ?>" /> <span>*</span> <?php _e("(percent or pixel)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_feedurl"><?php _e("Feed URL:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_feedurl" id="ffsr_feedurl" type="text" size="45" value="<?php echo htmlentities($this->options['ffsr_feedurl']) ?>" /> <span>*</span></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_scrollspeed"><?php _e("Scroll Speed:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_scrollspeed" id="ffsr_scrollspeed" type="text" size="4" value="<?php echo htmlentities($this->options['ffsr_scrollspeed']) ?>" /> <?php _e("(in milliseconds)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_description"><?php _e("Show Feed Item Description:", "wp-ffsr") ?></label></th>
								<td><input <?php echo ( ($this->options['ffsr_description']=='1')?'checked="checked"':'' ) ?> name="ffsr_description" id="ffsr_description" type="checkbox" value="1" /> <?php _e("(show content item feed - see max chars below)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_stringcut"><?php _e("Max chars content:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_stringcut" id="ffsr_stringcut" type="text" size="5" value="<?php echo htmlentities($this->options['ffsr_stringcut']) ?>" />
									<label for="ffsr_stringcutend"><?php _e("Cut string with:", "wp-ffsr") ?></label> <input name="ffsr_stringcutend" id="ffsr_stringcutend" type="text" size="8" value="<?php echo htmlentities($this->options['ffsr_stringcutend']) ?>" />
								</td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_separator"><?php _e("Feed item description separator:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_separator" id="ffsr_separator" type="text" size="35" value="<?php echo htmlentities($this->options['ffsr_separator']) ?>" /> <?php _e("(you can use html tag)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_target"><?php _e("Target link title:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_target" id="ffsr_target" type="text" size="10" value="<?php echo htmlentities($this->options['ffsr_target']) ?>" /> <?php _e("(_blank default)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_stylesheet"><?php _e("StyleSheet URL address:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_stylesheet" id="ffsr_stylesheet" type="text" size="45" value="<?php echo htmlentities($this->options['ffsr_stylesheet']) ?>" /><span>*</span> <?php _e("(your custom style sheet)", "wp-ffsr") ?></td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_wmode"><?php _e("Window Mode:", "wp-ffsr") ?></label></th>
								<td>
									<select name="ffsr_wmode" id="ffsr_wmode">
										<option <?php echo ( (htmlentities($this->options['ffsr_wmode'])=="Window")?'selected':'' ) ?> value="Window">Window</option>
										<option <?php echo ( (htmlentities($this->options['ffsr_wmode'])=="Opaque")?'selected':'' )?> value="Opaque">Opaque</option>
										<option <?php echo ( (htmlentities($this->options['ffsr_wmode'])=="Transparent")?'selected':'' )?> value="Transparent">Transparent</option>
										<option <?php echo ( (htmlentities($this->options['ffsr_wmode'])=="Direct")?'selected':'' )?> value="Direct">Direct</option>
										<option <?php echo ( (htmlentities($this->options['ffsr_wmode'])=="GPU")?'selected':'' )?> value="GPU">GPU</option>
									</select>
									<span>*</span> <?php _e("(default Window)", "wp-ffsr") ?>
								</td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_bgcolor"><?php _e("Background Color:", "wp-ffsr") ?></label></th>
								<td><input name="ffsr_bgcolor" id="ffsr_bgcolor" type="text" size="15" value="<?php echo htmlentities($this->options['ffsr_bgcolor']) ?>" /><span>*</span> (#RRGGBB)</td>
							</tr>

							<tr>
								<th scope="row"><label for="ffsr_useaggregator"><?php _e("Use aggregator:", "wp-ffsr") ?></label></th>
								<td><input <?php echo ( ($this->options['ffsr_useaggregator']=='1') ? 'checked="checked"' : '' ) ?> name="ffsr_useaggregator" id="ffsr_useaggregator" type="checkbox" value="1" /></td>
							</tr>

							<tr <?php echo ( ($this->options['ffsr_useaggregator']!='1') ? 'style="display:none"' : '' )  ?> id="tr_aggregator">
								<th scope="row" valign="top"><label><?php _e("List Feeds:", "wp-ffsr") ?></label></th>
								<td><label for="ffsr_maxitems"><?php _e("Max items for feed:", "wp-ffsr") ?></label> <input name="ffsr_maxitems" id="ffsr_maxitems" type="text" size="4" value="<?php echo htmlentities($this->options['ffsr_maxitems']) ?>" /><br/><?php echo $this->getAggregator() ?></td>
							</tr>

							<tr>
								<th scope="row" colspan="2"><div class="submit"><input type="submit" value="<?php _e("Save Settings", "wp-ffsr") ?>" /></div></th>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h3><?php _e('Preview', 'wp-ffsr') ?></h3>
					<div class="inside">
						<code><?php _e("With:", "wp-ffsr") ?> &lt;?php flashfeedscrollreader() ?&gt;</code>
						<?php echo $this->get_flashfeedscrollreader() ?>

						<br clear="all" />

						<code><?php _e("With:", "wp-ffsr") ?> (&lt;?php flashfeedscrollreader("<input type="text" value="<?php echo htmlentities($this->options['ffsr_sample'])?>" name="ffsr_sample" id="ffsr_sample" size="70" />") ?&gt;</code>
						<?php echo $this->get_flashfeedscrollreader( $this->options['ffsr_sample'] ) ?>

						<div class="submit"><input type="submit" value="<?php _e("Update Preview", "wp-ffsr") ?>" /></div>

					</div>
				</div>

			</div>
			<br class="clear"/>
		</div>
	</form>	

</div>
<?php
	}

	function getAggregator() {
		$o = "";
		
		if( is_array( $this->options['ffsr_aggregator'] )) {
			foreach( $this->options['ffsr_aggregator'] as $i ) {
				$o .= '<p><input size="44" name="ffsr_aggregator[]" id="ffsr_aggregator[]" value="' . $i . '" /> <button onclick="jQuery(this).parent().remove()" class="button">Delete</button></p>';
			}
		}
		$o .= '<button id="button_insert" onclick="return(WPFFSR.insertNewRow())" class="button">Add</button></p>';
		return( $o );
	}

	/**
	 * Attach settings in Wordpress Plugins list
	 */
	function register_plugin_settings( $pluginfile ) {
		$this->plugin_file = $pluginfile;
		add_action( 'plugin_action_links_' . basename( dirname( $pluginfile ) ) . '/' . basename( $pluginfile ), array( &$this, 'plugin_settings' ), 10, 4 );
		add_filter( 'plugin_row_meta',  array(&$this, 'add_plugin_links'), 10, 2);
	}

	/**
	 * Add links on installed plugin list
	 */
	function add_plugin_links($links, $file) {
		if( $file == plugin_basename( $this->plugin_file ) ) {
			$links[] = '<strong style="color:#fa0">' . __('For more info and plugins visit', 'wp-ffsr') . ' <a href="http://www.saidmade.com">Saidmade</a></strong>';
		}
		return $links;
	}

	/**
	 * Add setting to plugin list
	 *
	 * @param <type> $links
	 * @return <type>
	 */
	function plugin_settings( $links ) {
		$settings_link = '<a href="options-general.php?page=wp-ffsr_admin.php">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}



} // end of class

?>