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
if ( ! class_exists( 'Leaky_Paywall_Article_Countdown_Nag' ) ) {
	
	class Leaky_Paywall_Article_Countdown_Nag {
		
		/**
		 * Class constructor, puts things in motion
		 *
		 * @since 1.0.0
		 */
		function __construct() {
					
			$settings = $this->get_settings();
			
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			
			add_action( 'wp', array( $this, 'process_requests' ), 15 );
			
			add_action( 'leaky_paywall_settings_form', array( $this, 'settings_div' ) );
			add_action( 'leaky_paywall_update_settings', array( $this, 'update_settings_div' ) );
			
		}
		
		function process_requests() {
			
			global $leaky_paywall, $post;
			
			$lp_settings = get_leaky_paywall_settings();
			
			if ( is_singular() ) {
				
				global $blog_id;
				if ( is_multisite() ){
					$site = '_' . $blog_id;
				} else {
					$site = '';
				}
									
				if ( !current_user_can( 'manage_options' ) ) { //Admins can see it all
					
					// We don't ever want to block the login, subscription
					if ( !is_page( array( $lp_settings['page_for_login'], $lp_settings['page_for_subscription'], $lp_settings['page_for_profile'] ) ) ) {
						
						$post_type_id = '';
						$restricted_post_type = '';
						$is_restricted = false;
						$content_remaining = 0;
						
						$settings = $this->get_settings();
						$restrictions = leaky_paywall_subscriber_restrictions();
						
						$available_content = array();
									
						if ( !empty( $_COOKIE['lp_cookie_' . $site] ) )
							$available_content = maybe_unserialize( stripslashes( $_COOKIE['lp_cookie_' . $site] ) );
							
						
						if ( !empty( $restrictions ) ) {
							foreach( $restrictions as $key => $restriction ) {

								
								if ( is_singular( $restriction['post_type'] ) ) {
						
									if ( 0 <= $restriction['allowed_value'] ) {
									
										$post_type_id = $key;
										$restricted_post_type = $restriction['post_type'];
										$allowed_value = $restriction['allowed_value'];
										$is_restricted = true;
										
										if ( !empty( $available_content[$restricted_post_type] ) ) {
											$content_remaining = $allowed_value - count( $available_content[$restricted_post_type] );
										} else {
											$content_remaining = $allowed_value;
										}
										break;
										
									}
									
								}
								
							}
						
						}
						

						if ( $is_restricted ) {
												        
						    if ( $settings['nag_after_countdown'] <= $allowed_value - $content_remaining ) {
						    								
								add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
								if ( 0 !== $content_remaining || array_key_exists( $post->ID, $available_content[$restricted_post_type] )  ) {
			
									add_action( 'wp_footer', array( $this, 'wp_footer' ) );
								} else {

									add_action( 'wp_enqueue_scripts', array( $this, 'zero_article_scripts' ) );
									add_action( 'wp_head', array( $this, 'wp_head' ) );
									add_filter( 'leaky_paywall_subscriber_or_login_message', array( $this, 'leaky_paywall_subscriber_or_login_message' ), 10, 3 );
								}
											
							}
						
						}
						
					}
						
				}
			
			}
			
		}
		
		function frontend_scripts() {

			$settings = $this->get_settings();

			wp_enqueue_script( 'issuem-leaky-paywall-article-countdown-nag', LP_ACN_URL . '/js/article-countdown-nag.js', array( 'jquery' ), LP_ACN_VERSION );

			if ( $settings['nag_theme'] == 'slim' ) {
				wp_enqueue_style( 'issuem-leaky-paywall-article-countdown-nag', LP_ACN_URL . '/css/article-countdown-nag-slim.css', '', LP_ACN_VERSION );
			} else {
				wp_enqueue_style( 'issuem-leaky-paywall-article-countdown-nag', LP_ACN_URL . '/css/article-countdown-nag.css', '', LP_ACN_VERSION );
			}
						
		}
		
		function zero_article_scripts() {
				wp_enqueue_style( 'issuem-leaky-paywall-zero-articles', LP_ACN_URL . '/css/acn-zero-articles.css', '', LP_ACN_VERSION );
		}
		
		function wp_head() {
			
			global $leaky_paywall, $post, $blog_id;
			
			if ( is_multisite() ){
				$site = '_' . $blog_id;
			} else {
				$site = '';
			}
						
			$lp_settings = $leaky_paywall->get_settings();
			$restrictions = leaky_paywall_subscriber_restrictions();
			$available_content = array();
			$content_remaining = 0;

            if ( !empty( $_COOKIE['lp_cookie_' . $site] ) )
				$available_content = maybe_unserialize( stripslashes( $_COOKIE['lp_cookie_' . $site] ) );
            
            if ( !empty( $restrictions['post_types'] ) ) {
							
				foreach( $restrictions['post_types'] as $key => $restriction ) {
					
					if ( is_singular( $restriction['post_type'] ) ) {
			
						if ( 0 <= $restriction['allowed_value'] ) {
						
							$post_type_id = $key;
							$restricted_post_type = $restriction['post_type'];
							
							if ( !empty( $available_content[$restricted_post_type] ) ) {
								$content_remaining = $restriction['allowed_value'] - count( $available_content[$restricted_post_type] );
							} else {
								$content_remaining = $restriction['allowed_value'];
							}
							break;
							
						}
						
					}
					
				}
			
			}
			
			$post_type_obj = get_post_type_object( $post->post_type );
            $remaining_text = ( 1 === $content_remaining ) 
            		?  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->singular_name )
            		:  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->name );
            
			$login_url = get_page_link( $lp_settings['page_for_login'] );
			$subscription_url = get_page_link( $lp_settings['page_for_subscription'] );
		
			?>
			
			<div class="acn-zero-remaining-overlay"></div>
			<div id="issuem-leaky-paywall-articles-zero-remaining-nag">
				<div id="issuem-leaky-paywall-articles-remaining-close">&nbsp;</div>
				<div id="issuem-leaky-paywall-articles-remaining">
					<div id="issuem-leaky-paywall-articles-remaining-count"><?php echo $content_remaining; ?></div>
					<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $remaining_text; ?></div>
				</div>
				<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
				<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
			</div>
			
			<?php
			
		}
		
		function wp_footer() {

			
			global $leaky_paywall, $post, $blog_id;
			
			if ( is_multisite() ){
				$site = '_' . $blog_id;
			} else {
				$site = '';
			}
			
			$lp_settings = $leaky_paywall->get_settings();
			$restrictions = leaky_paywall_subscriber_restrictions();

			
			$available_content = array();
			$content_remaining = 0;

            if ( !empty( $_COOKIE['lp_cookie_' . $site] ) )
				$available_content = maybe_unserialize( stripslashes( $_COOKIE['lp_cookie_' . $site] ) );


            if ( !empty( $restrictions) ) {


				foreach( $restrictions as $key => $restriction ) {
					
					if ( is_singular( $restriction['post_type'] ) ) {

			
						if ( 0 <= $restriction['allowed_value'] ) {
						
							$post_type_id = $key;
							$restricted_post_type = $restriction['post_type'];
							if ( !empty( $available_content[$restricted_post_type] ) ) {
								$content_remaining = $restriction['allowed_value'] - count( $available_content[$restricted_post_type] );
							} else {
								$content_remaining = $restriction['allowed_value'];
							}
							break;
							
						}
						
					}
					
				}
			
			}

			$post_type_obj = get_post_type_object( $post->post_type );
            $remaining_text = ( 1 === $content_remaining ) 
            		?  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->singular_name )
            		:  sprintf( __( '%s Remaining', 'issuem-lp-anc' ), $post_type_obj->labels->name );
            
			$login_url = get_page_link( $lp_settings['page_for_login'] );
			$subscription_url = get_page_link( $lp_settings['page_for_subscription'] );

			$settings = $this->get_settings();

			if ( $settings['nag_theme'] == 'slim' ) {
				?>
				<div id="issuem-leaky-paywall-articles-remaining-nag">
					<div id="issuem-leaky-paywall-articles-remaining-close">x</div>

					<div id="issuem-leaky-paywall-articles-remaining-count">
						<p><?php echo $content_remaining; ?></p>
					</div>

				<div id="issuem-leaky-paywall-articles-remaining">
					
					<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $remaining_text; ?></div>

					<p>
						<span id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe', 'issuem-lp-anc' ); ?></a></span> 
						| 
						<span id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Login', 'issuem-lp-anc' ); ?></a></span>
					</p>

				</div>
				</div>

			<?php } else { ?>

				<div id="issuem-leaky-paywall-articles-remaining-nag">
					<div id="issuem-leaky-paywall-articles-remaining-close">x</div>
					<div id="issuem-leaky-paywall-articles-remaining">
						<div id="issuem-leaky-paywall-articles-remaining-count"><?php echo $content_remaining; ?></div>
						<div id="issuem-leaky-paywall-articles-remaining-text"><?php echo $remaining_text; ?></div>

					</div>
					<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $subscription_url; ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
					<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $login_url; ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
				</div>
			

			<?php }
			
		}
		
		function leaky_paywall_subscriber_or_login_message( $new_content, $message, $content ) {
			return $content;
		}
		
		/**
		 * Get zeen101's Leaky Paywall - Article Countdown Nag options
		 *
		 * @since 1.0.0
		 */
		function get_settings() {
			
			$defaults = array( 
				'nag_after_countdown' => '0',
				'nag_theme' => 'default'
			);
		
			$defaults = apply_filters( 'leaky_paywall_article_countdown_nag_default_settings', $defaults );
			
			$settings = get_option( 'issuem-leaky-paywall-article-countdown-nag' );
												
			return wp_parse_args( $settings, $defaults );
			
		}
		
		/**
		 * Update zeen101's Leaky Paywall options
		 *
		 * @since 1.0.0
		 */
		function update_settings( $settings ) {
			
			update_option( 'issuem-leaky-paywall-article-countdown-nag', $settings );
			
		}
		
		/**
		 * Create and Display settings page
		 *
		 * @since 1.0.0
		 */
		function settings_div() {
			
			// Get the user options
			$settings = $this->get_settings();
			
			// Display HTML form for the options below
			?>
            <div id="modules" class="postbox">
            
                <div class="handlediv" title="Click to toggle"><br /></div>
                
                <h3 class="hndle"><span><?php _e( 'Leaky Paywall - Article Countdown Nag', 'issuem-lp-anc' ); ?></span></h3>
                
                <div class="inside">
                
                <table id="leaky_paywall_article_countdown_nag" class="form-table">
                
                    <tr>
                        <th><?php _e( 'Nag After Reading?', 'issuem-lp-anc' ); ?></th>
                        <td>
                        <input type="text" value="<?php echo $settings['nag_after_countdown']; ?>" name="nag_after_countdown" /> <?php _e( 'articles', 'issuem-lp-anc' ); ?>
                        <p class="description"><?php _e( 'This will show the article countdown nag popup after the user has read the given number of articles.' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e( 'Nag Theme', 'issuem-lp-anc' ); ?></th>
                        <td>
                      
	                        <select id="nag_theme" name="nag_theme">
	                             <option value="default" <?php selected( 'default' === $settings['nag_theme'] ); ?>><?php _e( 'Default', 'issuem-lp-anc' ); ?></option>
	                             <option value="slim" <?php selected( 'slim' === $settings['nag_theme'] ); ?>><?php _e( 'Slim', 'issuem-lp-anc' ); ?></option>
	                        </select>

                        <p class="description"><?php _e( 'Choose theme for article countdown nag popup.' ); ?></p>
                        </td>
                    </tr>
                    
                </table>
                                                                  
                <p class="submit">
                    <input class="button-primary" type="submit" name="update_leaky_paywall_settings" value="<?php _e( 'Save Settings', 'issuem-lp-anc' ) ?>" />
                </p>

                </div>
                
            </div>
			<?php
			
		}
		
		function update_settings_div() {
		
			// Get the user options
			$settings = $this->get_settings();
				
			if ( !empty( $_REQUEST['nag_after_countdown'] ) )
				$settings['nag_after_countdown'] = absint( trim( $_REQUEST['nag_after_countdown'] ) );
			else
				$settings['allowed_ip_addresses'] = '0';

			if ( !empty( $_REQUEST['nag_theme'] ) )
					$settings['nag_theme'] = $_REQUEST['nag_theme'];
			
			$this->update_settings( $settings );
			
		}
		
		function admin_notices() {
			
			$manual_update_version = get_option( 'leaky_paywall_manual_update_version' );
			
			if ( version_compare( $manual_update_version, '2.0.0', '<' ) ) {
				?>
				<div id="leaky-paywall-2-0-0-update-nag" class="update-nag">
					<?php _e( 'You cannot use the Article Countdown Nag until you update Leaky Paywall Database to version 2.' ); ?>
				</div>
				<?php
			}
			
			
		}
		
	}
	
}