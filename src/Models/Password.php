<?php

namespace mowta\SiteProtect\Models;

use mowta\SiteProtect\Helper;

class Password {

	protected $post;

	public function __construct( $post ) {
		$this->post = get_post( $post );
	}

	public function used( ) {
		return get_post_meta( $this->post->ID, '_used', true );
	}

	public function mark_as_used() {
		if( $this->used() ) {
			return false;
		}

		update_post_meta( $this->post->ID, '_used', true);
		update_post_meta( $this->post->ID, '_first_time_used', current_time( 'mysql' ) );
		update_post_meta( $this->post->ID, '_first_time_ip', Helper::get_client_ip() );
	}

	public function get_current_password() {
		return get_post_meta( $this->post->ID, '_password', true);
	}

	public function get_hashed_password() {
		return get_post_meta( $this->post->ID, '_hashed_password', true);
	}

	public function get_original_password() {
		$original = get_post_meta( $this->post->ID, '_original_password', true);
		if( $original )
			return $original;
		return $this->get_current_password();
	}

	public function get_meta( $key ) {
		return get_post_meta( $this->post->ID, $key, true );
	}

	public function need_regeneration() {
		return ! $this->used() && $this->should_regenerate() && ! get_post_meta( $this->post->ID, '_original_password', true);
	}

	public function log_authentication() {

		if ( $this->need_regeneration() ) {
			return false;
		}

		// Mark as used
		$this->mark_as_used();

		// Log IP and Date
		update_post_meta( $this->post->ID, '_last_time_used', current_time( 'mysql' ) );
		update_post_meta( $this->post->ID, '_last_time_ip', Helper::get_client_ip() );
	}

	public function change_password( $new_password ) {

		// Store the original password
		$original_pass = $this->get_original_password();
		if( ! get_post_meta( $this->post->ID, '_original_password', true) ) {
			// The original password should only be update once
			update_post_meta( $this->post->ID, '_original_password', $original_pass );
		}

		// Update the password
		update_post_meta( $this->post->ID, '_password', $new_password );

		// Update the password hash
		require_once( ABSPATH . 'wp-includes/class-phpass.php');
		$wp_hasher = new \PasswordHash(8, TRUE);

		update_post_meta( $this->post->ID, '_hashed_password', wp_hash_password( $new_password ) );
		update_post_meta( $this->post->ID, '_changed_on', current_time( 'mysql' ) );
		update_post_meta( $this->post->ID, '_changed_by', Helper::get_client_ip() );

		// Mark as used
		$this->mark_as_used();
	}

	public function should_regenerate() {
		return get_post_meta( $this->post->ID, '_regenerate', true ) == 'yes';
	}

	public static function get_by_hash( $hash ) {

		if( $cached = get_transient( 'wpsp_password_' . $hash ) ) {
			return $cached;
		}

		$query = new \WP_Query( array(
				'post_type' 	=> 'password',
				'meta_key'		=> '_hashed_password',
				'meta_value'	=> $hash,
		) );

		if ( $query->found_posts == 0 ) {
			return false;
		}

		$password = new Password( $query->posts[0] );

		set_transient( 'wpsp_password_' . $hash, $password, 5 * MINUTE_IN_SECONDS );
		return $password;

	}

	public static function get_by_password( $password ) {

		$query = new \WP_Query( array(
				'post_type' 	=> 'password',
				'meta_key'		=> '_password',
				'meta_value'	=> $password,
		) );

		if ( $query->found_posts == 0 ) {
			return false;
		}

		return new Password( $query->posts[0] );

	}

	public static function get_logged_password( ) {
		if( ! isset( $_COOKIE['wpsp_secret'] ) ) 
			return false;
		return static::get_by_hash( $_COOKIE['wpsp_secret'] );
	}

}