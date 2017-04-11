<?php
/*
Plugin Name: WSUWP WordPress Dashboard
Version: 1.6.1
Description: The default admin dashboard displayed in WSUWP
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-WSUWP-WordPress-Dashboard
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuwp-wordpress-dashboard.php';

add_action( 'after_setup_theme', 'WSUWP_WordPress_Dashboard' );
/**
 * Start things up.
 *
 * @since 1.6.0
 *
 * @return \WSUWP_WordPress_Dashboard
 */
function WSUWP_WordPress_Dashboard() {
	return WSUWP_WordPress_Dashboard::get_instance();
}
