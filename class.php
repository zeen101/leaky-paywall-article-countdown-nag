<?php
/**
 * Registers IssueM's Leaky Paywall class
 *
 * @package IssueM's Leaky Paywall
 * @since 1.0.0
 */

/**
 * This class registers the main issuem functionality
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'IssueM_Leaky_Paywall_Article_Count_Nag' ) ) {
	
	class IssueM_Leaky_Paywall_Article_Count_Nag {
		
		/**
		 * Class constructor, puts things in motion
		 *
		 * @since 1.0.0
		 */
		function __construct() {
					
			$settings = $this->get_settings();
			
			add_action( 'wp', array( $this, 'process_requests' ), 15 );
			
			add_action( 'issuem_leaky_paywall_settings_form', array( $this, 'settings_div' ) );
			add_action( 'issuem_leaky_paywall_update_settings', array( $this, 'update_settings_div' ) );
			
		}
		
		function process_requests() {
			
			global $dl_pluginissuem_leaky_paywall;
			
			$issuem_settings = $dl_pluginissuem_leaky_paywall->get_settings();
			
			$settings = $this->get_settings();
			
			$free_articles = array();

            if ( !empty( $_COOKIE['issuem_lp'] ) )
                $free_articles = maybe_unserialize( $_COOKIE['issuem_lp'] );
                            
            $articles_remainings = $issuem_settings['free_articles'] - $settings['nag_after_count'];
                    
            if ( $settings['nag_after_count'] <= count( $free_articles ) ) {
				
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
				add_action( 'wp_footer', array( $this, 'wp_footer' ) );
							
			}
			
		}
		
		function frontend_scripts() {
			
			wp_enqueue_script( 'issuem-leaky-paywall-article-count-nag', ISSUEM_LP_ACN_URL . '/js/article-count-nag.js', array( 'jquery' ), ISSUEM_LP_ACN_VERSION );
			wp_enqueue_style( 'issuem-leaky-paywall-article-count-nag', ISSUEM_LP_ACN_URL . '/css/article-count-nag.css', '', ISSUEM_LP_ACN_VERSION );
						
		}
		
		function wp_footer() {
			
			global $dl_pluginissuem_leaky_paywall;
			
			$issuem_settings = $dl_pluginissuem_leaky_paywall->get_settings();
			
			$free_articles = array();

            if ( !empty( $_COOKIE['issuem_lp'] ) )
                $free_articles = maybe_unserialize( $_COOKIE['issuem_lp'] );
            
            $articles_remainings = $issuem_settings['free_articles'] - count( $free_articles );
            
			$url = get_page_link( $issuem_settings['page_for_login'] );
			
			if ( 0 !== $articles_remainings ) {
			
			?>
			
			<div id="issuem-leaky-paywall-articles-remaining-nag">
				<div id="issuem-leaky-paywall-articles-remaining"><?php echo $articles_remainings; ?></div>
				<div id="issuem-leaky-paywall-articles-remaining-close">x</div>
				<div id="issuem-leaky-paywall-articles-remaining-text"><?php _e( 'Articles Remaining', 'issuem-lp-anc' ); ?></div>
				<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $url; ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
				<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $url; ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
			</div>
			
			<?php
			
			} else {
			
			?>
			
			<div id="issuem-leaky-paywall-articles-zero-remaining-nag">
				<div id="issuem-leaky-paywall-articles-remaining"><?php echo $articles_remainings; ?></div>
				<div id="issuem-leaky-paywall-articles-remaining-close">x</div>
				<div id="issuem-leaky-paywall-articles-remaining-text"><?php _e( 'Articles Remaining', 'issuem-lp-anc' ); ?></div>
				<div id="issuem-leaky-paywall-articles-remaining-subscribe-link"><a href="<?php echo $url; ?>"><?php _e( 'Subscribe today for full access', 'issuem-lp-anc' ); ?></a></div>
				<div id="issuem-leaky-paywall-articles-remaining-login-link"><a href="<?php echo $url; ?>"><?php _e( 'Current subscriber? Login here', 'issuem-lp-anc' ); ?></a></div>
			</div>
			
			<?php
				
			}
			
		}
		
		/**
		 * Get IssueM's Leaky Paywall - Article Count Nag options
		 *
		 * @since 1.0.0
		 */
		function get_settings() {
			
			$defaults = array( 
				'nag_after_count' => '0',
			);
		
			$defaults = apply_filters( 'issuem_leaky_paywall_article_count_nag_default_settings', $defaults );
			
			$settings = get_option( 'issuem-leaky-paywall-article-count-nag' );
												
			return wp_parse_args( $settings, $defaults );
			
		}
		
		/**
		 * Update IssueM's Leaky Paywall options
		 *
		 * @since 1.0.0
		 */
		function update_settings( $settings ) {
			
			update_option( 'issuem-leaky-paywall-article-count-nag', $settings );
			
		}
		
		/**
		 * Create and Display IssueM settings page
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
                
                <h3 class="hndle"><span><?php _e( 'Leaky Paywall - Article Count Nag', 'issuem-lp-anc' ); ?></span></h3>
                
                <div class="inside">
                
                <table id="issuem_leaky_paywall_article_count_nag">
                
                    <tr>
                        <th><?php _e( 'Nag After Reading?', 'issuem-lp-anc' ); ?></th>
                        <td>
                        <input type="text" value="<?php echo $settings['nag_after_count']; ?>" name="nag_after_count" /> <?php _e( 'articles', 'issuem-lp-anc' ); ?>
                        <p class="description"><?php _e( 'This will show the article count nag popup after the user has read the given number of articles.' ); ?></p>
                        </td>
                    </tr>
                    
                </table>
                                                                  
                <p class="submit">
                    <input class="button-primary" type="submit" name="update_issuem_leaky_paywall_settings" value="<?php _e( 'Save Settings', 'issuem-lp-anc' ) ?>" />
                </p>

                </div>
                
            </div>
			<?php
			
		}
		
		function update_settings_div() {
		
			// Get the user options
			$settings = $this->get_settings();
				
			if ( !empty( $_REQUEST['nag_after_count'] ) )
				$settings['nag_after_count'] = absint( trim( $_REQUEST['nag_after_count'] ) );
			else
				$settings['allowed_ip_addresses'] = '0';
			
			$this->update_settings( $settings );
			
		}
		
	}
	
}