<?php
/**
 * Client class for front-end
 *
 * @package         wp-ffsr
 * @subpackage      wp-ffsr_frontend
 * @author          =undo= <g.fazioli@saidmade.com>
 * @copyright       Copyright (C) 2010 Saidmade Srl
 *
 */
class WPFFSR_FRONTEND extends WPFFSR_CLASS {

	function WPFFSR_FRONTEND() {
		$this->WPFFSR_CLASS();
		$this->options = get_option( $this->options_key );
	}

} // end of class

?>