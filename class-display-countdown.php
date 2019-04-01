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
		add_action( 'wp_ajax_nopriv_process_countdown_display', array( $this, 'process_countdown_display' ) );
		add_action( 'wp_ajax_process_countdown_display', array( $this, 'process_countdown_display' ) );
	}

	public function process_countdown_display() 
	{

		$this->post_id = absint( $_GET['post_id'] );
		$this->lp_restriction = new Leaky_Paywall_Restrictions( $this->post_id );
		$this->lp_restriction->is_ajax = true;

		do_action( 'leaky_paywall_acn_before_process_requests', $this->post_id );
		
		// if content is not restricted, show nothing
		if ( ! $this->lp_restriction->is_content_restricted() ) {
			die();
		}

		if ( ! $this->lp_restriction->current_user_can_access() ) {
			$this->display_zero_screen();
		} else {

			$current_user = wp_get_current_user();

			// if the user is logged and has access, don't show the nag
			if ( $current_user->ID > 0 ) {

				if ( !leaky_paywall_user_has_access( $current_user ) ) {
					$this->display_countdown();
				}
				
			} else {
				$this->display_countdown();
			}
			
		}

		die();

	}

	public function display_countdown() 
	{
		$settings = get_lp_acn_settings();
		$this->number_allowed = $this->calculate_number_allowed();
		$this->number_viewed = $this->calculate_number_viewed();

		if ( $settings['nag_after_countdown'] < $this->number_viewed ) {
			echo $this->get_countdown_html();
		}

	}

	public function display_zero_screen() 
	{
		$settings = get_lp_acn_settings();

		if ( 'no' != $settings['zero_remaining_popup'] ) {
			echo $this->get_zero_screen_html();
		}

	}

	public function get_countdown_html() 
	{

		$settings = get_lp_acn_settings();
		$this->content_remaining = $this->number_allowed - $this->number_viewed;
		$lp_settings = get_leaky_paywall_settings();
		$login_url = get_page_link( $lp_settings['page_for_login'] );
		$subscription_url = get_page_link( $lp_settings['page_for_subscription'] );


	    ob_start(); ?>
	    
	    	<div id="issuem-leaky-paywall-articles-remaining-nag">
				<div id="issuem-leaky-paywall-articles-remaining-close">x</div>

				<?php if ($settings['nag_theme'] == 'slim' ) {
					?>
					<div id="issuem-leaky-paywall-articles-remaining-count">
						<p><?php echo $this->content_remaining; ?></p>
					</div>
			    	<div id="issuem-leaky-paywall-articles-remaining">
			    		<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $this->get_remaining_text(); ?></div>
			    		<p>
			    			<span id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe', 'issuem-lp-anc' ); ?></a></span> 
			    			| 
			    			<span id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Login', 'issuem-lp-anc' ); ?></a></span>
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
					<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo esc_js( $subscription_url ); ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
					<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo esc_js( $login_url ); ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
					<?php 
				} ?>
				
			</div>

	    
	    <?php  $content = trim( ob_get_contents() );
		ob_end_clean();

		return apply_filters( 'leaky_paywall_acn_countdown', $content, $this->post_id );
	}

	public function get_zero_screen_html() 
	{
		$remaining_text = apply_filters( 'leaky_paywall_acn_zero_screen_remaining_text', 'No content remaining' );
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
	    		<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
	    		<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
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
    		?  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->singular_name )
    		:  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->name );

    	return apply_filters( 'leaky_paywall_acn_countdown_remaining_text', $remaining_text, $this->post_id );

	}

	public function calculate_number_allowed() 
	{
		$number_allowed = 0;
		$restrictions = $this->lp_restriction->get_restriction_settings();
		$post_obj = get_post( $this->post_id );

		foreach( $restrictions as $restriction ) {
			
			if ( $restriction[0]['post_type'] == $post_obj->post_type ) {
				$number_allowed = $restriction[0]['allowed_value'];
				break;
			}
			
		}

		return $number_allowed;
	}

	public function calculate_number_viewed() 
	{

		$number_viewed = 0;
		$post_obj = get_post( $this->post_id );
		$viewed_data = $this->lp_restriction->get_content_viewed_by_user();

		foreach( $viewed_data as $key => $items ) {

			if ( $key == $post_obj->post_type ) {
				$number_viewed = count( $items );
			}

		}
		
		return $number_viewed;
	}
}

new Leaky_Paywall_Article_Countdown_Nag_Display();