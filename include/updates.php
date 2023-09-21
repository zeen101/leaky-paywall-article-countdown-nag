<?php

/**
 * Load the base class
 */
class Leaky_Paywall_Article_Countdown_Nag_Updates
{

    public function __construct()
    {
        if (is_admin()) {
            add_action('in_plugin_update_message-' . LP_ACN_BASENAME, array($this, 'modify_plugin_update_message'), 10, 2);
        }
    }

    public function modify_plugin_update_message($plugin_data, $response)
    {

        if ('valid' == $this->get_license_status()) {
            return;
        }

        $utm = 'utm_source=WordPress&utm_medium=' . $plugin_data['slug'] . '&utm_content=plugin-update';
        $url = $plugin_data['homepage'] . '?' . $utm;

        echo '<br />' . sprintf(__('To enable updates, please enter your license key on the <a href="%s">Licenses</a> page. If you don\'t have a licence key, please see <a target="_blank" href="%s">details & pricing</a>.', 'issuem-leaky-paywall-article-countdown-nag'), admin_url('admin.php?page=issuem-leaky-paywall&tab=licenses'), $url);
    }

    public function get_license_status()
    {

        $license = new Leaky_Paywall_License_Key(LP_ACN_SLUG, LP_ACN_NAME);
        $settings = $license->get_settings();
        return $settings['license_status'];
    }
}

new Leaky_Paywall_Article_Countdown_Nag_Updates();
