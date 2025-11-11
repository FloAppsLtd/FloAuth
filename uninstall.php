<?php
/**
 * Uninstalling functionality for FloAuth plugin.
 *
 * Uninstalling FloAuth deletes options and transients.
 *
 * @package FloAuth
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'floauth_flomembers_url' );
delete_option( 'floauth_secret_key' );
delete_option( 'floauth_member_role' );
delete_option( 'floauth_admin_role' );
delete_option( 'floauth_extranet_path' );

// Delete transient used for caching extranet post ID.
delete_transient( 'floauth_extranet_post_id' );
