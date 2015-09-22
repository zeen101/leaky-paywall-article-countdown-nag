<?php
/**
 * Main PHP file used to for initial calls to zeen101's Leak Paywall classes and functions.
 *
 * @package zeen101's Leaky Paywall - Article Countdown Nag
 * @since 1.0.0
 */
 
/*
Plugin Name: Leaky Paywall - Article Countdown Nag
Plugin URI: http://zeen101.com/
Description: A premium leaky paywall add-on for the Leaky Paywall for WordPress plugin.
Author: zeen101 Development Team
Version: 3.0.0
Author URI: http://zeen101.com/
Tags:
*/

//Define global variables...
if ( !defined( 'ZEEN101_STORE_URL' ) )
	define( 'ZEEN101_STORE_URL', 	'http://zeen101.com' );
	
define( 'LP_ACN_NAME', 		'Leaky Paywall - Article Countdown Nag' );
define( 'LP_ACN_SLUG', 		'issuem-leaky-paywall-article-countdown-nag' );
define( 'LP_ACN_VERSION', 	'3.0.0' );
define( 'LP_ACN_DB_VERSION', '1.0.0' );
define( 'LP_ACN_URL', 		plugin_dir_url( __FILE__ ) );
define( 'LP_ACN_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'LP_ACN_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'LP_ACN_REL_DIR', 	dirname( LP_ACN_BASENAME ) );

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function leaky_paywall_article_countdown_nag_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( is_plugin_active( 'issuem/issuem.php' ) )
		define( 'ACTIVE_LP_ACN', true );
	else
		define( 'ACTIVE_LP_ACN', false );

	if ( is_plugin_active( 'issuem-leaky-paywall/issuem-leaky-paywall.php' ) 
		|| is_plugin_active( 'leaky-paywall/leaky-paywall.php' ) ) {

		require_once( 'class.php' );
		
		// Instantiate the Pigeon Pack class
		if ( class_exists( 'Leaky_Paywall_Article_Countdown_Nag' ) ) {
			
			global $leaky_paywall_article_countdown_nag;
			
			$leaky_paywall_article_countdown_nag = new Leaky_Paywall_Article_Countdown_Nag();
			
			require_once( 'functions.php' );
				
			//Internationalization
			load_plugin_textdomain( 'issuem-lp-acn', false, LP_ACN_REL_DIR . '/i18n/' );
				
		}
	
	} else {
	
		add_action( 'admin_notices', 'leaky_paywall_article_countdown_nag_requirement_nag' );
		
	}

}
add_action( 'plugins_loaded', 'leaky_paywall_article_countdown_nag_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

function leaky_paywall_article_countdown_nag_requirement_nag() {
	?>
	<div id="leaky-paywall-requirement-nag" class="update-nag">
		<?php _e( 'You must have the Leaky Paywall plugin activated to use the Leaky Paywall Countdown Nag plugin.' ); ?>
	</div>
	<?php
}
