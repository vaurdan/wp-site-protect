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
					->set_default_value( "strong" )
					->help_text( __( "Minimum password strength for users. Pick disable if you want to allow any password.", 'wp-site-protect' ) ),


				Field::make('textarea', 'wpsp_blacklist', __('Passwords Blacklist','wp-site-protect' ) )
					->set_rows(5)
					->set_default_value( "password\nqwerty\nwordpress\n123456" )
					->help_text( __( "Passwords that should be banned from picking. One per line.", 'wp-site-protect' ) ),
			))
			->add_tab(__('Appearance', 'wp-site-protect'), array(
				Field::make("rich_text", "crb_sidenote", "Sidenote Content")
				
			));
	}


}