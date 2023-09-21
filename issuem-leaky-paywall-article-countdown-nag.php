<?php
/**
 * Main PHP file used to for initial calls to Leaky Paywall classes and functions.
 *
 * @package Leaky Paywall - Article Countdown Nag
 * @since   1.0.0
 */

/*
Plugin Name: Leaky Paywall - Article Countdown Nag
Plugin URI: https://leakypaywall.com/
Description: Display an article countdown nag to users encouraging them to subscribe.
Author: Leaky Paywall
Version: 3.8.2
Author URI: https://leakypaywall.com/
Tags: leaky paywall
Text Domain: leaky-paywall
Domain Path: /i18n

*/

//Define global variables...
if (!defined('ZEEN101_STORE_URL') ) {
    define('ZEEN101_STORE_URL',     'https://zeen101.com');
}

define('LP_ACN_NAME',         'Leaky Paywall - Article Countdown Nag');
define('LP_ACN_SLUG',         'issuem-leaky-paywall-article-countdown-nag');
define('LP_ACN_VERSION',     '3.8.2');
define('LP_ACN_DB_VERSION', '1.0.0');
define('LP_ACN_URL',         plugin_dir_url(__FILE__));
define('LP_ACN_PATH',         plugin_dir_path(__FILE__));
define('LP_ACN_BASENAME',     plugin_basename(__FILE__));
define('LP_ACN_REL_DIR',     dirname(LP_ACN_BASENAME));

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function leaky_paywall_article_countdown_nag_plugins_loaded()
{

    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (is_plugin_active('issuem/issuem.php') ) {
        define('ACTIVE_LP_ACN', true);
    } else {
        define('ACTIVE_LP_ACN', false);
    }

    if (is_plugin_active('issuem-leaky-paywall/issuem-leaky-paywall.php')
        || is_plugin_active('leaky-paywall/leaky-paywall.php')
    ) {

        include_once 'class.php';
        include_once 'class-display-countdown.php';
        include_once 'include/updates.php';

        // Instantiate the Pigeon Pack class
        if (class_exists('Leaky_Paywall_Article_Countdown_Nag') ) {

            global $leaky_paywall_article_countdown_nag;

            $leaky_paywall_article_countdown_nag = new Leaky_Paywall_Article_Countdown_Nag();

            include_once 'functions.php';

            //Internationalization
            load_plugin_textdomain('issuem-leaky-paywall-article-countdown-nag', false, LP_ACN_REL_DIR . '/i18n/');

        }

        // Upgrade function based on EDD updater class
        if(!class_exists('EDD_LP_Plugin_Updater') ) {
            include dirname(__FILE__) . '/include/EDD_LP_Plugin_Updater.php';
        }

        $license = new Leaky_Paywall_License_Key(LP_ACN_SLUG, LP_ACN_NAME);

        $settings = $license->get_settings();
        $license_key = isset($settings['license_key']) ? trim($settings['license_key']) : '';

        $edd_updater = new EDD_LP_Plugin_Updater(
            ZEEN101_STORE_URL, __FILE__, array(
            'version'     => LP_ACN_VERSION, // current version number
            'license'     => $license_key,
            'item_id' => 9626,
            'author'     => 'Zeen101 Development Team'
            )
        );

    } else {

        add_action('admin_notices', 'leaky_paywall_article_countdown_nag_requirement_nag');

    }

}
add_action('plugins_loaded', 'leaky_paywall_article_countdown_nag_plugins_loaded', 4815162390); //wait for the plugins to be loaded before init

function leaky_paywall_article_countdown_nag_requirement_nag()
{
    ?>
    <div id="leaky-paywall-requirement-nag" class="update-nag">
    <?php _e('You must have the Leaky Paywall plugin activated to use the Leaky Paywall Countdown Nag plugin.', 'issuem-leaky-paywall-article-countdown-nag'); ?>
    </div>
    <?php
}
