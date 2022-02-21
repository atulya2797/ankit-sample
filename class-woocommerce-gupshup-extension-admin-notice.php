<?php
/**
 * Class-woocommerce-Email-extension-admin-notice.php
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @package woocommerce-Email-extension
 * @since woocommerce-Email-extension 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notices
 */
class WooCommerce_Email_Extension_Admin_Notice {

	/**
	 * Time mark.
	 *
	 * @var string
	 */
	const INIT_TIME = 'woocommerce-Email-extension-init-time';

	/**
	 * Used to store user meta and hide the notice asking to review.
	 *
	 * @var string
	 */
	const HIDE_REVIEW_NOTICE = 'woocommerce-Email-extension-hide-review-notice';

	/**
	 * Used to check next time.
	 *
	 * @var string
	 */
	const REMIND_LATER_NOTICE = 'woocommerce-Email-extension-remind-later-notice';

	/**
	 * The number of seconds in five days, since init date to show the notice.
	 *
	 * @var int
	 */
	const SHOW_LAPSE = 432000;

	/**
	 * The number of seconds in one day, used to show notice later again.
	 *
	 * @var int
	 */
	const REMIND_LAPSE = 86400;

	/**
	 * Adds actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__,'admin_init' ) );
	}

	/**
	 * Hooked on the admin_init action.
	 */
	public static function admin_init() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$user_id = get_current_user_id();
			if (!empty($_GET['woocommerce-Email-extension_notice'])) {
				if ( !empty( sanitize_text_field($_GET[self::HIDE_REVIEW_NOTICE] )) && wp_verify_nonce( sanitize_text_field($_GET['woocommerce-Email-extension_notice']), 'hide' ) ) {
					add_user_meta( $user_id, self::HIDE_REVIEW_NOTICE, true );
				}
				if ( !empty( sanitize_text_field($_GET[self::REMIND_LATER_NOTICE] )) && wp_verify_nonce( sanitize_text_field($_GET['woocommerce-Email-extension_notice']), 'later' ) ) {
					update_user_meta( $user_id, self::REMIND_LATER_NOTICE, time() + self::REMIND_LAPSE );
				}
			}
			$hide_review_notice = get_user_meta( $user_id, self::HIDE_REVIEW_NOTICE, true );
			if ( empty( $hide_review_notice ) ) {
				$d = time() - self::get_init_time();
				if ( $d >= self::SHOW_LAPSE ) {
					$remind_later_notice = get_user_meta( $user_id, self::REMIND_LATER_NOTICE, true );
					if ( empty( $remind_later_notice ) || ( time() > $remind_later_notice ) ) {
						add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
					}
				}
			}
		}
	}

	/**
	 * Initializes if necessary and returns the init time.
	 */
	public static function get_init_time() {
		$init_time = get_site_option( self::INIT_TIME, null );
		if ( null === $init_time ) {
			$init_time = time();
			add_site_option( self::INIT_TIME, $init_time );
		}
		return $init_time;
	}

	/**
	 * Adds the admin notice.
	 */
	public static function admin_notices() {

		if (!empty($_SERVER['HTTP_HOST']) || !empty($_SERVER['REQUEST_URI'])) {
			$current_url = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field($_SERVER['HTTP_HOST']) . sanitize_text_field($_SERVER['REQUEST_URI']);
		}

		$hide_url    = wp_nonce_url( add_query_arg( self::HIDE_REVIEW_NOTICE, true, $current_url ), 'hide', 'woocommerce-Email-extension_notice' );
		$remind_url  = wp_nonce_url( add_query_arg( self::REMIND_LATER_NOTICE, true, $current_url ), 'later', 'woocommerce-Email-extension_notice' );

		$output = '';

		$output .= '<style type="text/css">';

		$output .= '.woocommerce-message a.woocommerce-message-close::before {';
		$output .= 'position: relative;';
		$output .= 'top: 18px;';
		$output .= 'left: -20px;';
		$output .= '-webkit-transition: all .1s ease-in-out;';
		$output .= 'transition: all .1s ease-in-out;';
		$output .= '}';

		$output .= '.woocommerce-message a.woocommerce-message-close {';
		$output .= 'position: static;';
		$output .= 'float: right;';
		$output .= 'top: 0;';
		$output .= 'right: 0;';
		$output .= 'padding: 0 15px 10px 28px;';
		$output .= 'margin-top: -10px;';
		$output .= 'font-size: 13px;';
		$output .= 'line-height: 1.23076923;';
		$output .= 'text-decoration: none;';
		$output .= '}';

		$output .= 'div.woocommerce-message {';
		$output .= 'overflow: hidden;';
		$output .= 'position: relative;';
		$output .= 'border-left-color: #cc99c2 !important;';
		$output .= '}';

		$output .= 'div.woocommerce-Email-extension-rating {';
		$output .= sprintf( 'background: url(%s) #fff no-repeat 8px 8px;', WOO_CODES_PLUGIN_URL . '/images/icon-256x256.png' );
		$output .= 'padding-left: 76px ! important;';
		$output .= 'background-size: 64px 64px;';
		$output .= '}';
		$output .= '</style>';

		$output .= '<div class="updated woocommerce-message woocommerce-Email-extension-rating">';

		$output .= sprintf(
			'<a class="woocommerce-message-close notice-dismiss" href="%s">%s</a>',
			esc_url( $hide_url ),
			esc_html__( 'Dismiss', 'woocommerce-Email-extension' )
		);

		$output .= '<p style="font-size: 1.2em; font-weight: 600;">';
		$output .= __( 'Many thanks for using <a target="_blank" href="https://wordpress.org/plugins/woocommerce-Email-extension/">WooCommerce Email extension</a>!', 'woocommerce-Email-extension' );
		$output .= '</p>';
		$output .= '<p>';
		$output .= __( 'Could you please spare a minute and give it a review over at WordPress.org?', 'woocommerce-Email-extension' );
		$output .= '</p>';

		$output .= '<p>';
		$output .= sprintf(
			'<a class="button button-primary" href="%s" target="_blank">%s</a>',
			esc_url( 'https://wordpress.org/support/view/plugin-reviews/woocommerce-Email-extension?filter=5#postform' ),
			__( 'Yes, here we go!', 'woocommerce-Email-extension' )
		);
		$output .= '&emsp;';
		$output .= sprintf(
			'<a class="button" href="%s">%s</a>',
			esc_url( $remind_url ),
			esc_html( __( 'Remind me later', 'woocommerce-Email-extension' ) )
		);
		$output .= '</p>';

		$output .= '</div>';

		echo wp_kses( 
			$output, 
			array(
				'a' => array(
					'href' => array(),
					'title' => array()
				),
				'br' => array(),
				'em' => array(),
				'strong' => array(),
				'div' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'p' => array()
			  )
		);
	}
}
WooCommerce_Email_Extension_Admin_Notice::init();
