<?php
/**
 * @package zeen101's Leaky Paywall - Article Countdown Nag
 * @since 1.0.0
 */

if ( !function_exists( 'wp_print_r' ) ) { 

	/**
	 * Helper function used for printing out debug information
	 *
	 * HT: Glenn Ansley @ iThemes.com
	 *
	 * @since 1.0.0
	 *
	 * @param int $args Arguments to pass to print_r
	 * @param bool $die TRUE to die else FALSE (default TRUE)
	 */
    function wp_print_r( $args, $die = true ) { 
	
        $echo = '<pre>' . print_r( $args, true ) . '</pre>';
		
        if ( $die ) die( $echo );
        	else echo $echo;
		
    }   
	
}

function get_lp_acn_settings() {

	global $leaky_paywall_article_countdown_nag;
	$settings = $leaky_paywall_article_countdown_nag->get_settings();

	return $settings;
}