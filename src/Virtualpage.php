<?php

namespace cncDonation;
class Virtualpage {

	function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		add_action( 'init', array( $this, 'rewrite' ) );
		add_action( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_include', array( $this, 'change_template' ) );

	}

	function activate() {
		set_transient( 'vpt_flush', 1, 60 );
	}

	function rewrite() {
		add_rewrite_rule( '^cnc-donation$', 'index.php?cnc-donation=1', 'top' );

		if(get_transient( 'vpt_flush' )) {
			delete_transient( 'vpt_flush' );
			flush_rewrite_rules();
		}
	}

	function query_vars($vars) {
		$vars[] = 'cnc-donation';

		return $vars;
	}

	function change_template( $template ) {
		if( get_query_var( 'cnc-donation', false ) !== false ) {
			$component = new \cncDonation\Component();
			if ($component->processDonationForm()) {
				// Request processed
				echo "Sikeres adományozás";
			} else {
				// Data processing error
				echo "Sikertelen adományozás";
			}
		}

		// fallback to template
		return $template;
	}

}

