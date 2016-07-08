<?php
namespace mowta\SiteProtect;


use Carbon_Fields\Container;
use Carbon_Fields\Field;

class SettingsInterface {

	public function __construct() {
		$this->initialize();
	}

	public function initialize() {

		// Default options page
		Container::make('theme_options', __('Settings', 'wp-site-protect') )
			->set_page_parent('edit.php?post_type=password')
			->set_page_permissions( 'manage_options' )
			->add_tab(__('General', 'wp-site-protect'), array(
				// Enable/Disable WPSP
				Field::make('checkbox', 'wpsp_enabled', __( 'Enable WP Site Protect','wp-site-protect' ) )
					->set_default_value( 'yes' )
					->help_text( __( "Uncheck this if you want to disable WP Site Protect functionality", 'wp-site-protect' ) ),
				// Password Strength
				Field::make('select', 'wpsp_password_strength', __( 'Minimum Password Strength','wp-site-protect' ) )
					->add_options( array(
						'disabled' => 'Disabled',
						'weak'  => 'Weak',
						'medium' => 'Medium',
						'strong' => 'Strong',
					))
					->set_default_value( WPSPSettings::get_password_strength() )
					->help_text( __( "Minimum password strength for users. Pick disable if you want to allow any password.", 'wp-site-protect' ) ),

				// Password blacklist
				Field::make('textarea', 'wpsp_blacklist', __('Passwords Blacklist','wp-site-protect' ) )
					->set_rows(5)
					->set_default_value( WPSPSettings::get_blacklist(true) )
					->help_text( __( "Passwords that should be banned from picking. One per line.", 'wp-site-protect' ) ),
			))
			->add_tab(__('Appearance', 'wp-site-protect'), array(
				// Password Content
				Field::make("rich_text", "wpsp_password_content", "Password Page Content")
					->set_rows(10)
					->set_default_value( WPSPSettings::get_password_content() )
					->help_text( __('Use this editor to change the text that appears on the password request page.' , 'wp-site-protect') ),

				// Reset Content
				Field::make("rich_text", "wpsp_reset_content", "Reset Page Content")
				     ->set_rows(10)
				     ->set_default_value( WPSPSettings::get_reset_content() )
				     ->help_text( __('Use this editor to change the text that appears on the password reset page.' , 'wp-site-protect') ),

			));

	}


}