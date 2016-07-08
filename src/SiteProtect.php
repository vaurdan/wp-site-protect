<?php
namespace mowta\SiteProtect;

use mowta\SiteProtect\PostTypes\Password as CPTPassword;
use mowta\SiteProtect\Models\Password as Password;

/**
 * Class SiteProtect
 *
 * Main controller class for the WP Site Protect plugin.
 * Initializes all the actions, filters, custom post types and others.
 *
 * @package mowta\SiteProtect
 */
class SiteProtect {

	private $is_wp_authenticated = false;
	
	/**
	 * Returns the singleton instance of SiteProtect
	 *
	 * @return SiteProtect
	 */
	private static $instance = null;

	public static function getInstance() {
		if(null === static::$instance) {
			static::$instance = new static(); //late static binding (since PHP 5.3.0)
			static::$instance->initialize();
		}
		return static::$instance;
	}

	//prevent cloning/duplicates
	private function __clone(){}
	private function __wakeup(){}
	private function __construct( ) {}

	private function initialize( ) {
		$this->initialize_post_types();

		$this->initialize_admin_interfaces();

		add_action( 'init', array( $this, 'setup_current_user' ) );
		add_action( 'plugins_loaded', array( $this, 'load_locale' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Only run the following filters if WP Site Protect is enabled
		if ( WPSPSettings::enabled() ) {
			add_action( 'template_redirect', array( $this, 'redirect_unauthorized' ) );
			add_filter( 'template_include', array( $this, 'show_authentication_page' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

			add_action( 'wp_ajax_nopriv_wpsp_authorize', array( $this, 'ajax_authorize' ) );
			add_action( 'wp_ajax_nopriv_wpsp_reset', array( $this, 'ajax_reset_password' ) );

			add_action( 'wp_ajax_wpsp_authorize', array( $this, 'ajax_authorize' ) );
			add_action( 'wp_ajax_wpsp_reset', array( $this, 'ajax_reset_password' ) );
		}

		add_action( 'admin_notices', array( $this, 'admin_notices') );

	}

	/**
	 * This callback must run on the init hook because of some misbehaves with is_user_logged_in and
	 *  wp_get_current_user.
	 *
	 * This is an attempt to fix bad login detections.
	 *
	 * @action init
	 */
	public function setup_current_user() {
		$this->is_wp_authenticated = is_user_logged_in();
	}

	public function load_locale() {
		$langs_dir = plugin_basename( dirname( dirname( __FILE__ ) ) ) . "/languages";
		load_plugin_textdomain( 'wp-site-protect', false, $langs_dir );
	}

	private function initialize_post_types( ) {
		// Initialize the password CPT
		new CPTPassword();
	}

	private function initialize_admin_interfaces( ) {
		new SettingsInterface();
	}

	public function enqueue_styles() {
		// Only need to show styles if it's the unauthorized page
		if( $this->is_authorized( ) === true ) {
			return;
		}

		wp_enqueue_style( 'wpsp_protect', plugin_dir_url( dirname( __FILE__ ) ) . "assets/css/style.css" );
	}

	public function enqueue_admin_styles( $hook ) {
		if ( 'edit.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'wpsp_admin' , plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css' );
	}

	public function enqueue_scripts() {
		// Only need to enqueue scripts if it's the unauthorized page
		if( $this->is_authorized( ) === true ) {
			return;
		}
		wp_enqueue_script( 'password-strength-meter' );
		wp_enqueue_script( 'jquery.cookie', plugin_dir_url( dirname( __FILE__ ) ) . "assets/js/jquery.cookie.js" );

		wp_enqueue_script( 'wpsp_auth', plugin_dir_url( dirname( __FILE__ ) ) . "assets/js/authenticate.js", array( 'jquery.cookie' ) );

		if ( Password::get_logged_password() && Password::get_logged_password()->need_regeneration() ) {
			wp_enqueue_script( 'wpsp_reset', plugin_dir_url( dirname( __FILE__ ) ) . "assets/js/reset.js", array(
				'password-strength-meter',
				'jquery.cookie'
			) );
			wp_localize_script( 'wpsp_reset', 'passwords', array(
				'strength'  => WPSPSettings::get_password_strength(),
				'blacklist' => WPSPSettings::get_blacklist(),
				'different_password_error' => __( 'The passwords don\'t match.', 'wp-site-protect' ),
			) );
		}

		wp_localize_script( 'wpsp_auth', 'ajax',
            array( 
            	'ajax_url' => admin_url( 'admin-ajax.php' ),
            	'referrer' => wp_get_referer(),
             )
    	);
	}

	public function redirect_unauthorized( ) {
		// If it's the homepage, bail out
		if ( is_front_page() ) {
			return;
		}

		// Redirect all the requests to home if user isn't authorized
		if ( $this->is_authorized() !== true ) {
			wp_safe_redirect( home_url( "/" ) );
			exit();
		}
	}

	public function show_authentication_page( $template ) {

		if( $this->is_authorized() === true ) {
			return $template;
		}

		$password = Password::get_logged_password();

		if ( $password &&
			! $password->need_regeneration( ) ) {
			return $template;
		}
		
		// Check if the password was already reset
		if ( ! $password ) {
			$custom_file = locate_template('wpsp-protected.php');
			$default_file = plugin_dir_path(__FILE__) . "Templates/unauthorized.php";

		} else if ( $password->need_regeneration() ) {
			$custom_file = locate_template('wpsp-new-password.php');
			$default_file = plugin_dir_path(__FILE__) . "Templates/new-password.php";
		}

		// If custom user template is present, use it
		if( isset( $custom_file ) && $custom_file ) {
			return $custom_file;
		}

		// Otherwise use the bundled template
		if ( isset( $default_file ) && file_exists( $default_file ) )
			return $default_file;
		
		// Fallback to no protection at all :(
		wp_die('Missing template :(');

		
	}

	public function ajax_authorize() {
		
		if( ! check_ajax_referer( 'authenticate' ) ) {
			wp_die('Invalid nonce.');
		}

		// Rate limit
		$rate_limit_key = 'wpsp_attempt_' . Helper::get_client_ip( );
		$rate_limit = get_transient( $rate_limit_key );
		if ( $rate_limit > 5 ) {
			echo wp_json_encode( array( 'error' => __('Too many attempts to login. Please try again in a minute', 'wp-site-protect') ) );
			wp_die();
		} 

		$password = sanitize_text_field( $_POST['wpsp_password'] );

		if( empty( $password ) ) {
			echo wp_json_encode( array( 'error' => __('Please insert a password', 'wp-site-protect') ) );
			wp_die();
		}

		$password = Password::get_by_password( $password );

		// If password is invalid
		if ( ! $password ) {
			set_transient( $rate_limit_key, ++$rate_limit, MINUTE_IN_SECONDS );
			echo wp_json_encode( array( 'error' => __( 'Invalid password', 'wp-site-protect' ) ) );
			wp_die();
		}

		// Log the authentication
		$password->log_authentication();

		echo wp_json_encode( array( 'success' => true, 'hash' => $password->get_hashed_password() ) );
		wp_die();
	}

	public function ajax_reset_password() {

		if ( ! check_ajax_referer( 'reset' ) ) {
			wp_die('Invalid nonce.');
		}

		$password = sanitize_text_field( $_POST['wpsp_password'] );
		$old_password_hash = sanitize_text_field( $_POST['wpsp_old_hash'] );

		$old_password = Password::get_by_hash( $old_password_hash );
		if ( ! $old_password ) {
			echo wp_json_encode( array( 'error' => __('Missing old password hash.', 'wp-site-protect') ) );
			wp_die();
		}

		if ( empty( $password ) || strlen( $password ) < WPSPSettings::get_password_size() ) {
			echo wp_json_encode( array( 'error' => sprintf( __('Password must have at least %s characters.', 'wp-site-protect'),
				WPSPSettings::get_password_size() ) ) );
			wp_die();
		}

		if ( in_array( $password, WPSPSettings::get_blacklist() ) ) {
			echo wp_json_encode( array( 'error' => __('This password is blacklisted. Please use another.', 'wp-site-protect') ) );
			wp_die();
		}

		$old_password->change_password( $password );

		// Log the authentication
		$old_password->log_authentication();

		echo wp_json_encode( array( 'success' => true, 'hash' => $old_password->get_hashed_password() ) );
		wp_die();

	}

	/**
	 * Returns false if user is not authorized. Returns true if it's authorized and 2 if needs to change password
	 *
	 * @return bool|int
	 */
	public function is_authorized( ) {
		// A WordPress authenticated user is authorized to see the content.
		if ( $this->is_wp_authenticated ) {
			return true;
		}

		if( isset( $_COOKIE['wpsp_secret'] ) ) {
			// Let's validate the password
			$hashed = Password::get_by_hash( sanitize_text_field( $_COOKIE['wpsp_secret'] ) );

			if ( $hashed && ! $hashed->need_regeneration() ) {
				return true;
			} else if ( $hashed && $hashed->need_regeneration() ) {
				return 2;
			}
		}

		return false;
	}

	function admin_notices() {
		// Bail if plugin is enabled or is not in any of our pages
		if( WPSPSettings::enabled() || get_current_screen()->post_type != 'password' ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p><?php echo sprintf( __( 'WP Site Protect is disabled. You can enable it on <a href="%s">Settings page</a>.', 'wp-site-protect' ),
				esc_url( admin_url('edit.php?post_type=password&page=crbn-settings.php#!general') )
				); ?></p>
		</div>
		<?php
	}

	/*
	 * STATIC TEMPLATING METHODS
	 */

	public static function authenticate_form( $attributes = array( ) ) {

		$defaults = array(
				'method' 	=> 'POST',
				'action' 	=> home_url(),
			);

		$attributes = wp_parse_args( $attributes, $defaults );

		// Force the name to be always the same
		$attributes['name'] = "wpsp_authenticate"; 

		$attrs = "";
		foreach( $attributes as $attribute => $value ) {
			$attrs.= esc_html( $attribute ) . "=\"" . esc_attr( $value ) . "\" ";
		}
		?>
		<div class="error" id="wpsp-error" style="display: none;">

		</div>
		<form <?php echo $attrs ?> >
			<?php wp_nonce_field( 'authenticate' ) ?>
			<div class="password_form">
				<input type="password" class="input-box" name="wpsp_password" id="password" style="background-image: none; background-position: 0% 0%; background-repeat: repeat;">
				<input type="submit" class="submit-button" name="submitform" id="submitpasswordform" value="<?php esc_attr_e('Authenticate'); ?>">
			</div>
		</form>
		<?php
	}

	public static function reset_password_form( $attributes = array( ) ) {
		$defaults = array(
			'method' 	=> 'POST',
			'action' 	=> home_url(),
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		// Force the name to be always the same
		$attributes['name'] = "wpsp_reset";
		$attrs = "";
		foreach( $attributes as $attribute => $value ) {
			$attrs.= esc_html( $attribute ) . "=\"" . esc_attr( $value ) . "\" ";
		}
		?>

		<div class="error" id="wpsp-error" style="display: none;"></div>
		<form <?php echo $attrs ?> >
			<?php wp_nonce_field( 'reset' ) ?>
			<div class="password_form">
				<label for="password">New Password</label>
				<input type="password" class="input-box" name="wpsp_password" id="password" style="background-image: none; background-position: 0% 0%; background-repeat: repeat;">
				<label for="password_retyped">Repeat Password</label>
				<input type="password" class="input-box" name="wpsp_password_retyped" id="password_retyped" style="background-image: none; background-position: 0% 0%; background-repeat: repeat;">
				<span style="display: none" id="password-strength"></span>
				<input type="submit" class="submit-button" name="submitform" id="submitpasswordform" value="<?php esc_attr_e('Reset Password'); ?>">
			</div>
		</form>

		<?php
	}

}