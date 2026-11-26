<?php
/*
Plugin Name: Flash Feed Scroll Reader
Plugin URI: http://wordpress.org/extend/plugins/flash-feed-scroll-reader/
Description: Feed Scroll Reader is a Flash Feed Reader with horizontal scrolling.
Version: 1.2.0
Author: Giovambattista Fazioli
Author URI: http://www.saidmade.com/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 
	Copyright 2010 Saidmade Srl (email : g.fazioli@saidmade.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once( 'wp-ffsr_class.php');

if( is_admin() ) {
	require_once( 'wp-ffsr_admin.php' );
	$wp_ffsr_admin = new WPFFSR_ADMIN();
	$wp_ffsr_admin->register_plugin_settings( __FILE__ );
} else {
	require_once( 'wp-ffsr_frontend.php');
	$wp_ffsr_frontend = new WPFFSR_FRONTEND();
	require_once( 'wp-ffsr_functions.php');
}
?>