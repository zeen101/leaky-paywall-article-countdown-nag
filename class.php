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

class Leaky_Paywall_Article_Countdown_Nag {
	
	/**
	 * Class constructor, puts things in motion
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
				
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'leaky_paywall_after_general_settings', array( $this, 'settings_div' ) );
		add_action( 'leaky_paywall_update_settings', array( $this, 'update_settings_div' ) );

	}

	public function frontend_scripts() {

		if ( is_home() || is_front_page() || is_archive() ) {
			return;
		}

		$settings = $this->get_settings();

		wp_enqueue_script( 'leaky-paywall-article-countdown-nag', LP_ACN_URL . 'js/article-countdown-nag.js', array( 'jquery' ), LP_ACN_VERSION );

		$protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';

		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php', $protocol )
		);

		wp_localize_script( 'leaky-paywall-article-countdown-nag', 'lp_acn', $params );

		if ( $settings['nag_theme'] == 'slim' ) {
			wp_enqueue_style( 'leaky-paywall-article-countdown-nag', LP_ACN_URL . 'css/article-countdown-nag-slim.css', '', LP_ACN_VERSION );
		} else {
			wp_enqueue_style( 'leaky-paywall-article-countdown-nag', LP_ACN_URL . 'css/article-countdown-nag.css', '', LP_ACN_VERSION );
		}
					
	}

	/**
	 * Get zeen101's Leaky Paywall - Article Countdown Nag options
	 *
	 * @since 1.0.0
	 */
	public function get_settings() {
		
		$defaults = array( 
			'nag_after_countdown' => '0',
			'nag_theme' => 'default',
			'zero_remaining_popup' => 'yes'
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
	public function update_settings( $settings ) {
		
		update_option( 'issuem-leaky-paywall-article-countdown-nag', $settings );
		
	}
	
	/**
	 * Create and Display settings page
	 *
	 * @since 1.0.0
	 */
	public function settings_div() {
		
		$settings = $this->get_settings();

		?>
        
          
            <h3><span><?php _e( 'Article Countdown Nag', 'leaky-paywall' ); ?></span></h3>
            
            
            <table id="leaky_paywall_article_countdown_nag" class="form-table">
            
                <tr>
                    <th><?php _e( 'Show Nag After Reading', 'leaky-paywall' ); ?></th>
                    <td>
                    <input class="small-text" type="number" value="<?php echo $settings['nag_after_countdown']; ?>" name="nag_after_countdown" /> <?php _e( 'restricted content items', 'leaky-paywall' ); ?>
                    <p class="description"><?php _e( 'Display the article countdown nag popup after the user has read the given number of restricted content items. <br>Set to 0 to show the nag the first time restricted content is viewed.' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><?php _e( 'Nag Theme', 'leaky-paywall' ); ?></th>
                    <td>
                  
                        <select id="nag_theme" name="nag_theme">
                             <option value="default" <?php selected( 'default' === $settings['nag_theme'] ); ?>><?php _e( 'Default', 'leaky-paywall' ); ?></option>
                             <option value="slim" <?php selected( 'slim' === $settings['nag_theme'] ); ?>><?php _e( 'Slim', 'leaky-paywall' ); ?></option>
                        </select>

                    <p class="description"><?php _e( 'Choose theme for article countdown nag popup.' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><?php _e( 'Zero Remaining Popup', 'leaky-paywall' ); ?></th>
                    <td>
                   		<select id="zero_remaining_popup" name="zero_remaining_popup">
                             <option value="yes" <?php selected( 'yes' === $settings['zero_remaining_popup'] ); ?>><?php _e( 'Yes', 'leaky-paywall' ); ?></option>
                             <option value="no" <?php selected( 'no' === $settings['zero_remaining_popup'] ); ?>><?php _e( 'No', 'leaky-paywall' ); ?></option>
                        </select>
                    	<p class="description"><?php _e( 'Display the zero remaining popup over the top of the page when the content limit is reached. If set to "No", the user will instead see the default Leaky Paywall subscribe nag in the content.' ); ?></p>
                    </td>
                </tr>
                
            </table>
          
		<?php
		
	}
	
	public function update_settings_div() {

		if(isset($_GET['tab'])) {
			$tab = $_GET['tab'];
		} else if ( $_GET['page'] == 'issuem-leaky-paywall' ) {
			$tab = 'general';
		} else {
			$tab = '';
		}

		if ( $tab != 'general' ) {
			return;
		}
	
		// Get the user options
		$settings = $this->get_settings();
			
		if ( !empty( $_POST['nag_after_countdown'] ) ) {
			$settings['nag_after_countdown'] = absint( trim( $_POST['nag_after_countdown'] ) );
		} else {
			$settings['nag_after_countdown'] = '0';
		}

		if ( isset( $_POST['nag_theme'] ) ) {
			$settings['nag_theme'] = sanitize_text_field( $_POST['nag_theme'] );
		}

		if ( isset( $_POST['zero_remaining_popup'] ) ) {
			$settings['zero_remaining_popup'] = sanitize_text_field( $_POST['zero_remaining_popup'] );
		}
		
		$this->update_settings( $settings );
		
	}
	
}