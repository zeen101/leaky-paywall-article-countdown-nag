<?php

function get_lp_acn_settings() {

	global $leaky_paywall_article_countdown_nag;
	$settings = $leaky_paywall_article_countdown_nag->get_settings();

	return $settings;
}