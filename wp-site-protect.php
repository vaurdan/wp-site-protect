<?php
/*
Plugin Name: WP Site Protect
Plugin URI: https://mowta.pt
Description: Protects your WordPress site with unique passwords
Version: 1.0
Author: Henrique Mouta
Author URI: http://mowta.pt
Text Domain: wp-site-protect
Domain Path: /languages
*/

namespace mowta\SiteProtect;

// Autoload composer packages
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) )
    require __DIR__ . '/vendor/autoload.php';

$siteprotect = SiteProtect::getInstance();

// Helper functions
include_once( plugin_dir_path(__FILE__) . "/functions.php" );

