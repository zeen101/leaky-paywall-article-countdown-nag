<?php
/**
 * Registers zeen101's Leaky Paywall class
 *
 * @package zeen101's Leaky Paywall - Article Countdown Nag
 * @since 1.0.0
 */

/**
 * This class registers the main issuem functionality
 *
 * @since 1.0.0
 */

class Leaky_Paywall_Article_Countdown_Nag_Display {

	public $post_id;
	public $number_allowed;
	public $number_viewed;
	public $content_remaining;
	public $lp_restriction;

	public function __construct()
	{
		add_action( 'wp_footer', array( $this, 'the_nag_div' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function the_nag_div() {
		echo '<div id="issuem-leaky-paywall-articles-remaining-nag"></div>';
	}

	/**
	 * Register the countdown REST route.
	 *
	 * Replaces the legacy admin-ajax handler. The route receives the visitor's
	 * viewed-content history (from localStorage) the same way LP core's
	 * /check-restrictions endpoint does, so the count is correct under the 5.x
	 * client-side tracking model instead of relying on the legacy cookie.
	 */
	public function register_routes() {
		register_rest_route( 'lp-acn/v1', '/countdown', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'process_countdown_display' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id'        => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'viewed_content' => array(
					'required' => false,
				),
			),
		) );
	}

	/**
	 * Decide what (if anything) the countdown nag should display.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response { state: 'countdown'|'zero'|'none', html?: string }
	 */
	public function process_countdown_display( $request )
	{

		$this->post_id = absint( $request->get_param( 'post_id' ) );

		if ( ! $this->post_id || ! get_post( $this->post_id ) ) {
			return new WP_REST_Response( array( 'state' => 'none' ), 200 );
		}

		$this->lp_restriction          = new Leaky_Paywall_Restrictions( $this->post_id );
		$this->lp_restriction->is_ajax = true;

		// Feed the visitor's localStorage view history into the cookie superglobal
		// so the restriction logic (which reads $_COOKIE) counts views correctly.
		// localStorage's lp_viewed_content uses the same shape as the issuem_lp cookie.
		$viewed = $request->get_param( 'viewed_content' );
		if ( is_array( $viewed ) ) {
			$_COOKIE[ $this->lp_restriction->get_cookie_name() ] = wp_json_encode( $viewed );
		}

		do_action( 'leaky_paywall_acn_before_process_requests', $this->post_id );

		// if content is not restricted, show nothing
		if ( ! $this->lp_restriction->is_content_restricted() ) {
			return new WP_REST_Response( array( 'state' => 'none' ), 200 );
		}

		// if they are blocked by IP Blocker, then try and show zero screen
		if ( function_exists( 'leaky_paywall_ip_blocker_plugins_loaded' ) && $this->is_ip_blocked() ) {
			return $this->zero_screen_response();
		}

		if ( ! $this->lp_restriction->current_user_can_access() ) {
			return $this->zero_screen_response();
		}

		$current_user = wp_get_current_user();

		// Only suppress the nag for subscribers whose level grants UNLIMITED
		// access to this post type. Subscribers on a limited tier (e.g. a
		// 10-post level) still need to see the countdown so they know how
		// many posts they have left in their own budget. Without this check
		// the nag was silently hidden for every logged-in subscriber.
		if ( $current_user->ID > 0
			&& leaky_paywall_user_has_access( $current_user )
			&& $this->current_user_has_unlimited_access()
		) {
			return new WP_REST_Response( array( 'state' => 'none' ), 200 );
		}

		return $this->countdown_response();
	}

	/**
	 * Whether the current logged-in user has unlimited access to the post
	 * being viewed via any of their LP subscription levels.
	 *
	 * @return bool
	 */
	public function current_user_has_unlimited_access() {
		$post_obj = get_post( $this->post_id );
		if ( ! $post_obj ) {
			return false;
		}

		$level_ids = function_exists( 'leaky_paywall_subscriber_current_level_ids' )
			? leaky_paywall_subscriber_current_level_ids()
			: array();

		if ( empty( $level_ids ) ) {
			return false;
		}

		$settings = get_leaky_paywall_settings();

		foreach ( $level_ids as $level_id ) {
			if ( ! isset( $settings['levels'][ $level_id ]['post_types'] ) ) {
				continue;
			}
			foreach ( $settings['levels'][ $level_id ]['post_types'] as $access_rule ) {
				if ( ! isset( $access_rule['post_type'], $access_rule['allowed'] ) ) {
					continue;
				}
				if ( $access_rule['post_type'] == $post_obj->post_type && 'unlimited' === $access_rule['allowed'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Build the countdown ("X remaining") response, honoring the
	 * "show nag after N items" setting.
	 *
	 * @return WP_REST_Response
	 */
	private function countdown_response() {
		$settings             = get_lp_acn_settings();
		$this->number_allowed = $this->calculate_number_allowed();
		$this->number_viewed  = $this->calculate_number_viewed();

		if ( $settings['nag_after_countdown'] < $this->number_viewed ) {
			return new WP_REST_Response( array(
				'state' => 'countdown',
				'html'  => $this->get_countdown_html(),
			), 200 );
		}

		return new WP_REST_Response( array( 'state' => 'none' ), 200 );
	}

	/**
	 * Build the zero-screen response, honoring the popup setting.
	 *
	 * @return WP_REST_Response
	 */
	private function zero_screen_response() {
		$settings = get_lp_acn_settings();

		if ( 'no' == $settings['zero_remaining_popup'] ) {
			return new WP_REST_Response( array( 'state' => 'none' ), 200 );
		}

		return new WP_REST_Response( array(
			'state' => 'zero',
			'html'  => $this->get_zero_screen_html(),
		), 200 );
	}

	public function is_ip_blocked()
	{
		$ip_address = leaky_paywall_get_ip();

		$table = new Leaky_Paywall_Ip_Blocker_Table();
		$ip_blocks = $table->find_ip($ip_address);

		if ( $ip_blocks && !leaky_paywall_user_has_access() ) {
			return true;
		}

		return false;
	}

	public function get_countdown_html()
	{

		$settings = get_lp_acn_settings();
		$this->content_remaining = $this->number_allowed - $this->number_viewed;
		$lp_settings = get_leaky_paywall_settings();
		$login_url = get_page_link( $lp_settings['page_for_login'] );
		$subscription_url = get_page_link( $lp_settings['page_for_subscription'] );


	    ob_start(); ?>

				<a id="issuem-leaky-paywall-articles-remaining-close">x</a>

				<?php if ($settings['nag_theme'] == 'slim' ) {
					?>
					<div id="issuem-leaky-paywall-articles-remaining-count">
						<p><?php echo $this->content_remaining; ?></p>
					</div>
			    	<div id="issuem-leaky-paywall-articles-remaining">
			    		<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $this->get_remaining_text(); ?></div>
			    		<p>
			    			<span id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe', 'lp-acn' ); ?></a></span>
			    			|
			    			<span id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Login', 'lp-acn' ); ?></a></span>
			    		</p>
		    		</div>
					<?php
				} else {
					?>
					<div id="issuem-leaky-paywall-articles-remaining">
						<div id="issuem-leaky-paywall-articles-remaining-count"><?php echo $this->content_remaining; ?></div>
						<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $this->get_remaining_text(); ?></div>
					</div>
					<?php do_action( 'leaky_paywall_acn_countdown_after_remaining_text', $this->post_id ); ?>
					<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo esc_js( $subscription_url ); ?>"><?php _e( 'Subscribe today for full access', 'lp-acn' ); ?></a></div>
					<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo esc_js( $login_url ); ?>"><?php _e( 'Current subscriber? Login here', 'lp-acn' ); ?></a></div>
					<?php
				} ?>

	    <?php  $content = trim( ob_get_contents() );
		ob_end_clean();

		return apply_filters( 'leaky_paywall_acn_countdown', $content, $this->post_id, $this->content_remaining );
	}

	public function get_zero_screen_html()
	{
		$remaining_text = apply_filters( 'leaky_paywall_acn_zero_screen_remaining_text', __( 'No content remaining', 'lp-acn' ) );
		$lp_settings = get_leaky_paywall_settings();
		$login_url = get_page_link( $lp_settings['page_for_login'] );
		$subscription_url = get_page_link( $lp_settings['page_for_subscription'] );

	    ob_start(); ?>

	    	<div class="acn-zero-remaining-overlay"></div>
	    	<div id="issuem-leaky-paywall-articles-zero-remaining-nag">
	    		<div id="issuem-leaky-paywall-articles-remaining-close">&nbsp;</div>
	    		<div id="issuem-leaky-paywall-articles-remaining">
	    			<div id="issuem-leaky-paywall-articles-remaining-count">0</div>
	    			<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $remaining_text; ?></div>
	    		</div>
	    		<?php do_action( 'leaky_paywall_acn_zero_screen_after_remaining_text', $this->post_id ); ?>
	    		<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe today for full access', 'lp-acn' ); ?></a></div>
	    		<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Current subscriber? Login here', 'lp-acn' ); ?></a></div>
	    	</div>

	    <?php  $content = trim( ob_get_contents() );
		ob_end_clean();

		return apply_filters( 'leaky_paywall_acn_zero_screen', $content, $this->post_id );
	}

	public function get_remaining_text()
	{
		$post_obj = get_post( $this->post_id );
		$current_post_type = $post_obj->post_type;
		$post_type_obj = get_post_type_object( $current_post_type );

		$remaining_text = ( 1 === $this->content_remaining )
    		?  sprintf( __( '%s Remaining', 'lp-acn' ), $post_type_obj->labels->singular_name )
    		:  sprintf( __( '%s Remaining', 'lp-acn' ), $post_type_obj->labels->name );

    	return apply_filters( 'leaky_paywall_acn_countdown_remaining_text', $remaining_text, $this->post_id );

	}

	public function calculate_number_allowed()
	{
		$settings = get_leaky_paywall_settings();
		$post_obj = get_post( $this->post_id );

		// Check the subscriber's level FIRST — before combined/global
		// restrictions. This mirrors how class-restrictions.php works:
		// level_id_allows_access() uses the level's own allowed_value and does
		// NOT apply combined restrictions to subscribers. Combined restrictions
		// only govern the guest / non-subscriber metering path. Without this
		// ordering, sites with enable_combined_restrictions=on would cap every
		// subscriber at the combined total (usually the guest limit).
		$level_ids = function_exists( 'leaky_paywall_subscriber_current_level_ids' )
			? leaky_paywall_subscriber_current_level_ids()
			: array();

		if ( ! empty( $level_ids ) && leaky_paywall_user_has_access() ) {
			foreach ( $level_ids as $level_id ) {
				if ( ! isset( $settings['levels'][ $level_id ]['post_types'] ) ) {
					continue;
				}
				$rules = $settings['levels'][ $level_id ]['post_types'];

				// Pass 1: prefer a taxonomy-specific rule matching this post.
				foreach ( $rules as $access_rule ) {
					if ( ! isset( $access_rule['post_type'] ) || $access_rule['post_type'] != $post_obj->post_type ) {
						continue;
					}
					if ( isset( $access_rule['allowed'] ) && 'unlimited' === $access_rule['allowed'] ) {
						// Belt-and-suspenders — unlimited subscribers should already
						// have been short-circuited by current_user_has_unlimited_access().
						return PHP_INT_MAX;
					}
					if ( isset( $access_rule['allowed'] ) && 'limited' === $access_rule['allowed']
						&& ! empty( $access_rule['taxonomy'] )
						&& 'all' !== $access_rule['taxonomy']
						&& $this->lp_restriction->content_taxonomy_matches( $access_rule['taxonomy'] )
					) {
						return intval( $access_rule['allowed_value'] );
					}
				}

				// Pass 2: catch-all rule for this post type at this level.
				foreach ( $rules as $access_rule ) {
					if ( ! isset( $access_rule['post_type'], $access_rule['allowed'] ) ) {
						continue;
					}
					if ( $access_rule['post_type'] == $post_obj->post_type && 'limited' === $access_rule['allowed'] ) {
						return intval( $access_rule['allowed_value'] );
					}
				}
			}
		}

		// No subscriber level matched — this is the guest / no-level path.
		// Combined restrictions only apply here.
		if ( ! empty( $settings['enable_combined_restrictions'] ) && 'on' == $settings['enable_combined_restrictions'] ) {
			return intval( $settings['combined_restrictions_total_allowed'] );
		}

		// Fall back to global restriction settings.
		$number_allowed = 0;
		$restrictions   = $this->lp_restriction->get_restriction_settings();

		foreach ( $restrictions['post_types'] as $restriction ) {
			if ( $restriction['post_type'] == $post_obj->post_type
				&& ! empty( $restriction['taxonomy'] )
				&& $this->lp_restriction->content_taxonomy_matches( $restriction['taxonomy'] )
			) {
				$number_allowed = $restriction['allowed_value'];
				break;
			} elseif ( $restriction['post_type'] == $post_obj->post_type ) {
				$number_allowed = $restriction['allowed_value'];
			}
		}

		return $number_allowed;
	}

	public function calculate_number_viewed()
	{

		$number_viewed = 0;
		$post_obj = get_post( $this->post_id );
		$viewed_data = $this->lp_restriction->get_content_viewed_by_user();
		$settings = get_leaky_paywall_settings();

		// Subscribers on an active LP level count views per-post-type, not
		// combined. This mirrors the ordering fix in calculate_number_allowed()
		// (Bug 4): combined restrictions govern the guest metering path only,
		// so we must not use combined counting for subscribers either — that
		// would sum unrelated post-type views into a subscriber's article
		// budget and still produce a wrong "remaining" number even after the
		// allowed-side is correct.
		$level_ids = function_exists( 'leaky_paywall_subscriber_current_level_ids' )
			? leaky_paywall_subscriber_current_level_ids()
			: array();

		if ( ! empty( $level_ids ) && leaky_paywall_user_has_access() ) {
			// Try the taxonomy-specific count first (matches the level's
			// taxonomy rule if one applies), fall back to a plain post-type
			// count.
			foreach ( $level_ids as $level_id ) {
				if ( ! isset( $settings['levels'][ $level_id ]['post_types'] ) ) {
					continue;
				}
				foreach ( $settings['levels'][ $level_id ]['post_types'] as $access_rule ) {
					if ( ! isset( $access_rule['post_type'] ) || $access_rule['post_type'] != $post_obj->post_type ) {
						continue;
					}
					if ( ! empty( $access_rule['taxonomy'] )
						&& 'all' !== $access_rule['taxonomy']
						&& $this->lp_restriction->content_taxonomy_matches( $access_rule['taxonomy'] )
					) {
						return (int) $this->lp_restriction->get_number_viewed_by_term( $access_rule['taxonomy'] );
					}
				}
			}
			// Catch-all: count this post type only.
			if ( isset( $viewed_data[ $post_obj->post_type ] ) && is_array( $viewed_data[ $post_obj->post_type ] ) ) {
				return count( $viewed_data[ $post_obj->post_type ] );
			}
			return 0;
		}

		// Guest / no-level path. Combined restrictions count views across
		// every post type, matching how calculate_number_allowed() returns
		// the combined total for this same path.
		if ( 'on' == $settings['enable_combined_restrictions'] ) {
			foreach ( (array) $viewed_data as $items ) {
				$number_viewed += count( $items );
			}
			return $number_viewed;
		}

		$restrictions = $this->lp_restriction->get_restriction_settings();

		foreach( $restrictions['post_types'] as $restriction ) {

			foreach( $viewed_data as $key => $items ) {

				if ( $key == $post_obj->post_type && $restriction['post_type'] == $post_obj->post_type && $restriction['taxonomy'] && $this->lp_restriction->content_taxonomy_matches( $restriction['taxonomy'] ) ) {

					$number_viewed = $this->lp_restriction->get_number_viewed_by_term(  $restriction['taxonomy'] );
					break;

				} else if ( $restriction['post_type'] == $post_obj->post_type && $key == $post_obj->post_type ) {

					$number_viewed = count( $items );

				}

			}

		}

		return $number_viewed;
	}
}

new Leaky_Paywall_Article_Countdown_Nag_Display();