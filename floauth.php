<?php
/*
  Plugin Name: FloAuth
  Version: 1.0.6
  Description: FloMembers authentication plugin
  Author: Flo Apps Ltd
  Author URI: https://floapps.com
  Text Domain: floauth
  Domain Path: /lang
  License: mit
 */

if ( ! function_exists( 'get_option' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;  // Silence is golden, direct call is prohibited
}

// Load plugin textdomain
function floauth_load_plugin_textdomain() {
	load_plugin_textdomain( 'floauth', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'floauth_load_plugin_textdomain' );

// Include plugin modules
require_once __DIR__ . '/includes/util.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/extranet.php';
