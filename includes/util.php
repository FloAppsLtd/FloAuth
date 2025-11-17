<?php
/**
 * Utility functions for FloAuth plugin.
 *
 * @package FloAuth
 */

/**
 * Utility function for checking if an array is associative, not sequential
 *
 * @param array $array_to_check Array to check.
 * @return bool
 */
function floauth_is_associative_array( $array_to_check ) {
	return array_keys( $array_to_check ) !== range( 0, count( $array_to_check ) - 1 );
}


/**
 * Return URL for submitting new support tickets
 *
 * @return string
 */
function floauth_get_submit_support_ticket_url() {
	return 'https://support.floapps.com/?a=add&category=9';
}

/**
 * Add FloMembers link to toolbar
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 * @return void
 */
function floauth_add_toolbar_link_to_flomembers( $wp_admin_bar ) {
	$flomembers_url = esc_url( get_option( 'floauth_flomembers_url' ) );
	if ( $flomembers_url ) {
		$args = array(
			'id'    => 'flomembers',
			'title' => 'FloMembers',
			'href'  => $flomembers_url,
			'meta'  => array(
				'target' => '_blank',
				'rel'    => 'noopener noreferrer',
			),
		);
		$wp_admin_bar->add_node( $args );
	}
}
add_action( 'admin_bar_menu', 'floauth_add_toolbar_link_to_flomembers', 99 );

/**
 * Hide toolbar from logged-in regular members
 *
 * Can be disabled with filter "floauth_hide_admin_bar_from_users"
 * Capability can be changed with filter "floauth_hide_admin_bar_capability"
 *
 * @return void
 */
function floauth_hide_toolbar() {
	if ( is_user_logged_in() ) {
		$hide_admin_bar = apply_filters( 'floauth_hide_admin_bar_from_users', true );
		$capability     = apply_filters( 'floauth_hide_admin_bar_capability', 'edit_posts' );
		if ( ! current_user_can( $capability ) && $hide_admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}
}
add_action( 'after_setup_theme', 'floauth_hide_toolbar' );
