<?php
/**
 * Main PHP file used to for initial calls to IssueM's Leak Paywall classes and functions.
 *
 * @package IssueM's Leak Paywall - Article Countdown Nag
 * @since 1.0.0
 */
 
/*
Plugin Name: IssueM's Leaky Paywall - Article Countdown Nag
Plugin URI: http://issuem.com/
Description: A premium leaky paywall add-on for WordPress and IssueM.
Author: IssueM Development Team
Version: 1.0.1
Author URI: http://issuem.com/
Tags:
*/

//Define global variables...
if ( !defined( 'ISSUEM_STORE_URL' ) )
	define( 'ISSUEM_STORE_URL', 	'http://issuem.com' );
	
define( 'ISSUEM_LP_ACN_NAME', 		'Leaky Paywall - Article Countdown Nag' );
define( 'ISSUEM_LP_ACN_SLUG', 		'issuem-leaky-paywall-article-countdown-nag' );
define( 'ISSUEM_LP_ACN_VERSION', 	'1.0.1' );
define( 'ISSUEM_LP_ACN_DB_VERSION', 	'1.0.0' );
define( 'ISSUEM_LP_ACN_URL', 		plugin_dir_url( __FILE__ ) );
define( 'ISSUEM_LP_ACN_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'ISSUEM_LP_ACN_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'ISSUEM_LP_ACN_REL_DIR', 	dirname( ISSUEM_LP_ACN_BASENAME ) );

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function issuem_leaky_paywall_article_countdown_nag_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( is_plugin_active( 'issuem/issuem.php' ) )
		define( 'ISSUEM_ACTIVE_LP_ACN', true );
	else
		define( 'ISSUEM_ACTIVE_LP_ACN', false );

	require_once( 'class.php' );

	// Instantiate the Pigeon Pack class
	if ( class_exists( 'IssueM_Leaky_Paywall_Article_Countdown_Nag' ) ) {
		
		global $dl_pluginissuem_leaky_paywall_article_countdown_nag;
		
		$dl_pluginissuem_leaky_paywall_article_countdown_nag = new IssueM_Leaky_Paywall_Article_Countdown_Nag();
		
		require_once( 'functions.php' );
			
		//Internationalization
		load_plugin_textdomain( 'issuem-lp-acn', false, ISSUEM_LP_ACN_REL_DIR . '/i18n/' );
			
	}

}
add_action( 'plugins_loaded', 'issuem_leaky_paywall_article_countdown_nag_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init
