<?php
/**
 * Authentication functionality for FloAuth plugin.
 *
 * @package FloAuth
 */

/**
 * Handle authentication via FloMembers
 *
 * @return void
 */
function floauth_init() {
	if ( ! isset( $_GET['floauth'] ) ) {
		return;
	}

	$options = floauth_get_plugin_options();
	if ( ! isset( $_GET['hash'] ) ) {
		if ( $options['flomembers_url'] && $options['secret_key'] ) {
			$params = floauth_get_request_params( $options );
			floauth_redirect_to_flomembers( $options, $params['floauth_action'] );
		}
		return;
	}

	$params = floauth_get_request_params( $options );
	if ( floauth_is_valid_hash( $options['secret_key'], $params['username'], $params['hash'] ) ) {
		$user_meta = apply_filters( 'floauth_add_user_meta_data', array(), $_GET );
		if ( ! is_array( $user_meta ) || empty( $user_meta ) || ! floauth_is_associative_array( $user_meta ) ) {
			$user_meta = array();
		}
		$role = apply_filters( 'floauth_assign_role_for_user', $params['role'], $_GET );
		floauth_handle_user_authentication( $params, $role, $user_meta, $options );
	}
	// Faulty parameters, do nothing.
	die;
}

/**
 * Get plugin options from database.
 *
 * @return array Associative array of plugin options.
 */
function floauth_get_plugin_options() {
	return array(
		'flomembers_url' => get_option( 'floauth_flomembers_url' ),
		'secret_key'     => get_option( 'floauth_secret_key' ),
		'member_role'    => get_option( 'floauth_member_role', 'subscriber' ),
		'admin_role'     => get_option( 'floauth_admin_role', 'administrator' ),
		'extranet_path'  => get_option( 'floauth_extranet_path' ),
	);
}

/**
 * Get and sanitize request parameters from $_GET.
 *
 * @param array $options Plugin options array.
 * @return array Associative array of sanitized request parameters.
 */
function floauth_get_request_params( $options ) {
	return array(
		'floauth_action' => isset( $_GET['floauth'] ) && 'pages' === $_GET['floauth'] ? 'pages' : 'admin',
		'hash'           => isset( $_GET['hash'] ) ? sanitize_text_field( wp_unslash( $_GET['hash'] ) ) : '',
		'person_id'      => isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : null,
		'username'       => isset( $_GET['username'] ) ? sanitize_text_field( wp_unslash( $_GET['username'] ) ) : null,
		'firstname'      => isset( $_GET['firstname'] ) ? sanitize_text_field( wp_unslash( $_GET['firstname'] ) ) : null,
		'lastname'       => isset( $_GET['lastname'] ) ? sanitize_text_field( wp_unslash( $_GET['lastname'] ) ) : null,
		'role'           => ( isset( $_GET['role'] ) && 'admin' === $_GET['role'] ) ? $options['admin_role'] : $options['member_role'],
	);
}

/**
 * Redirect user to FloMembers login page.
 *
 * @param array  $options        Plugin options array.
 * @param string $floauth_action FloAuth action string.
 * @return void
 */
function floauth_redirect_to_flomembers( $options, $floauth_action ) {
	$action       = ( 'pages' === $floauth_action ) ? 'pages' : 'index';
	$return_url   = site_url( '/' );
	$hash         = md5( $options['secret_key'] . $return_url );
	$redirect_url = esc_url_raw( $options['flomembers_url'] . "/floauth/$action?return_url=$return_url&hash=$hash" );
	wp_redirect( $redirect_url );
	exit();
}

/**
 * Validate authentication hash.
 *
 * @param string $secret_key Secret key from plugin options.
 * @param string $username   Username from request.
 * @param string $hash       Hash from request.
 * @return bool True if hash is valid, false otherwise.
 */
function floauth_is_valid_hash( $secret_key, $username, $hash ) {
	return ( $secret_key && $username && md5( $secret_key . $username ) === $hash );
}

/**
 * Handle user authentication and creation.
 *
 * Authenticates existing users or creates new users, assigns roles and meta, and redirects.
 *
 * @param array  $params     Request parameters.
 * @param string $role       WordPress role to assign.
 * @param array  $user_meta  User meta data to add.
 * @param array  $options    Plugin options array.
 * @return void
 */
function floauth_handle_user_authentication( $params, $role, $user_meta, $options ) {
	$redirect_path = sanitize_text_field( apply_filters( 'floauth_modify_redirect_path', $options['extranet_path'], $_GET ) );
	$redirect_url  = ( 'pages' === $params['floauth_action'] ) ? esc_url_raw( site_url( '/' ) . $redirect_path ) : esc_url_raw( admin_url() );

	$parameter_for_matching_user = apply_filters( 'floauth_parameter_for_matching_user', 'email' );
	$use_id_as_user_login        = false;
	if ( 'id' === $parameter_for_matching_user && $params['person_id'] ) {
		$use_id_as_user_login = true;
		$matched_user         = get_user_by( 'login', $params['person_id'] );
	} else {
		$matched_user = get_user_by( 'email', $params['username'] );
	}

	if ( $matched_user ) {
		floauth_update_existing_user( $matched_user, $role, $user_meta, $params, $use_id_as_user_login );
		floauth_do_authentication( $matched_user->ID, $matched_user->user_login, $matched_user );
		wp_safe_redirect( $redirect_url );
		exit();
	} else {
		$user_id = floauth_create_new_user( $params, $role, $user_meta, $use_id_as_user_login );
		if ( ! is_wp_error( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
			floauth_do_authentication( $user_id, $params['username'], $user );
		}
		wp_safe_redirect( $redirect_url );
		exit();
	}
}

/**
 * Update existing user roles and meta data.
 *
 * @param WP_User $matched_user        The matched user object.
 * @param string  $role                WordPress role to assign.
 * @param array   $user_meta           User meta data to update.
 * @param array   $params              Request parameters.
 * @param bool    $use_id_as_user_login Whether to use FloMembers ID as user_login.
 * @return void
 */
function floauth_update_existing_user( $matched_user, $role, $user_meta, $params, $use_id_as_user_login ) {
	$user_id               = $matched_user->ID;
	$all_roles             = array_keys( wp_roles()->get_names() );
	$roles_to_check        = apply_filters( 'floauth_filter_roles_to_check_on_login', $all_roles );
	$user_current_roles    = get_userdata( $user_id )->roles;
	$user_already_has_role = in_array( $role, $user_current_roles, true );
	if ( ! $user_already_has_role ) {
		$user_matching_roles = array_intersect( $roles_to_check, $user_current_roles );
		foreach ( $user_matching_roles as $matching_role ) {
			$matched_user->remove_role( $matching_role );
		}
		$matched_user->add_role( $role );
	}
	if ( $use_id_as_user_login && $matched_user->user_email !== $params['username'] ) {
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => $params['username'],
			)
		);
	}
	foreach ( $user_meta as $key => $value ) {
		$previous_value = get_user_meta( $user_id, $key, true );
		update_user_meta( $user_id, $key, $value, $previous_value );
	}
}

/**
 * Create a new WordPress user and add meta data.
 *
 * @param array  $params               Request parameters.
 * @param string $role                WordPress role to assign.
 * @param array  $user_meta            User meta data to add.
 * @param bool   $use_id_as_user_login  Whether to use FloMembers ID as user_login.
 * @return int|WP_Error               The new user's ID or WP_Error on failure.
 */
function floauth_create_new_user( $params, $role, $user_meta, $use_id_as_user_login ) {
	$userdata = array(
		'user_pass'  => wp_generate_password(),
		'user_login' => ( $use_id_as_user_login ? $params['person_id'] : $params['username'] ),
		'user_email' => $params['username'],
		'first_name' => $params['firstname'],
		'last_name'  => $params['lastname'],
		'role'       => $role,
	);
	$user_id  = wp_insert_user( $userdata );
	foreach ( $user_meta as $key => $value ) {
		add_user_meta( $user_id, $key, $value );
	}
	return $user_id;
}
add_action( 'init', 'floauth_init' );

/**
 * Set auth cookie and do login
 *
 * @param string  $user_id User ID.
 * @param string  $username Username.
 * @param WP_User $user_object User object.
 * @return void
 */
function floauth_do_authentication( $user_id, $username, $user_object ) {
	wp_set_current_user( $user_id, $username );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $username, $user_object );
}
