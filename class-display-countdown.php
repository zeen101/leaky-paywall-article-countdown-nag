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

		// if the user is logged in and has access, don't show the nag
		if ( $current_user->ID > 0 && leaky_paywall_user_has_access( $current_user ) ) {
			return new WP_REST_Response( array( 'state' => 'none' ), 200 );
		}

		return $this->countdown_response();
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
		$number_allowed = 0;
		$restrictions = $this->lp_restriction->get_restriction_settings();
		$post_obj = get_post( $this->post_id );
		$settings = get_leaky_paywall_settings();

		foreach( $restrictions['post_types'] as $restriction ) {

			if ( $restriction['post_type'] == $post_obj->post_type && $restriction['taxonomy'] && $this->lp_restriction->content_taxonomy_matches( $restriction['taxonomy'] ) ) {

				$number_allowed = $restriction['allowed_value'];
				break;

			} else if ( $restriction['post_type'] == $post_obj->post_type ) {

				$number_allowed = $restriction['allowed_value'];

			}

		}

		if ( 'on' == $settings['enable_combined_restrictions'] ) {
			$number_allowed = $settings['combined_restrictions_total_allowed'];
		}

		return $number_allowed;
	}

	public function calculate_number_viewed()
	{

		$number_viewed = 0;
		$post_obj = get_post( $this->post_id );
		$viewed_data = $this->lp_restriction->get_content_viewed_by_user();
		$settings = get_leaky_paywall_settings();

		// Combined restrictions count views across every post type, matching how
		// calculate_number_allowed() returns the combined total.
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