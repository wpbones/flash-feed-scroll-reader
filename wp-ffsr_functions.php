<?php
/**
 * Wrap Class functions
 *
 * @package         wp-ffsr
 * @subpackage      wp-ffsr_functions
 * @author          =undo= <g.fazioli@saidmade.com>
 * @copyright       Copyright (C) 2010 Saidmade Srl
 *
 */
	
	/**
	 * Wordpress Front end function. You can set options directly from
	 * param of this function. If options param is null the back-end 
	 * options are used instead
	 *
	 * @param	(see get_flashfeedscrollreader() below)
	 *
	 */
	function flashfeedscrollreader( $user_args = null ) {
		global $wp_ffsr_frontend;
		echo $wp_ffsr_frontend->get_flashfeedscrollreader( $user_args );
	}
	// return only version
	function get_flashfeedscrollreader( $user_args = null ) {
		global $wp_ffsr_frontend;
		return ( $wp_ffsr_frontend->get_flashfeedscrollreader( $user_args ) );
	}	
?>