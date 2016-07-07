<?php

namespace mowta\SiteProtect\PostTypes;


abstract class PostType {

	protected $name;

	public function __construct( $name ) {
		$this->name = $name;

		add_action( 'init', array( $this, 'register' ), 0 );
		add_action('carbon_register_fields', array( $this, 'fields') );
	}

	public abstract function register();

	public abstract function fields();

	public function getName() {
		return $this->name;
	}
}