<?php
/**
 * Handle authentication via FloMembers
 *
 * Triggers if request has "floauth" parameter
 *
 * @return void
 */
function floauth_init() {
       if ( isset( $_GET['floauth'] ) ) {

	       // Get plugin options
	       $flomembers_url = get_option( 'floauth_flomembers_url' );
	       $secret_key = get_option( 'floauth_secret_key' );
	       // getting default value for option doesn't seem to work in "init" hook, so default return values are added to get_option calls
	       $member_role = get_option( 'floauth_member_role', 'subscriber' );
	       $admin_role = get_option( 'floauth_admin_role', 'administrator' );
	       $extranet_path = get_option( 'floauth_extranet_path' );
	       // Set floauth action
	       $floauth_action = sanitize_text_field( $_GET['floauth'] );

	       if ( isset( $_GET['hash'] ) ) {

		       // Get URL parameters
		       $hash = sanitize_text_field( $_GET['hash'] );
		       $person_id = ( isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : null );
		       $username = ( isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : null );
		       $firstname = ( isset( $_GET['firstname'] ) ? sanitize_text_field( $_GET['firstname'] ) : null );
		       $lastname = ( isset( $_GET['lastname'] ) ? sanitize_text_field( $_GET['lastname'] ) : null );
		       $role = ( isset( $_GET['role'] ) && $_GET['role'] === 'admin' ? $admin_role : $member_role );

		       // Determine redirect URL by "floauth" parameter
		       $redirect_path = apply_filters( 'floauth_modify_redirect_path', $extranet_path, $_GET );
		       $redirect_url = ( $floauth_action === 'pages' ? esc_url_raw( site_url( '/' ) . $redirect_path ) : esc_url_raw( admin_url() ) );

		       // Test existence of secret key and that hash matches
		       if ( $secret_key && $username && md5( $secret_key . $username ) === $hash ) {

			       // Add optional meta fields to user by URL parameters
			       $user_meta = apply_filters( 'floauth_add_user_meta_data', array(), $_GET );

			       // Empty $user_meta if not an associative array with values
			       if ( ! is_array( $user_meta ) || empty( $user_meta ) || ! floauth_is_associative_array( $user_meta ) ) {
				       $user_meta = array();
			       }

			       // Possibility to modify role set for person in case more roles are needed
			       // For example if person does not have a member role in FloMembers, you can change the role assigned
			       $role = apply_filters( 'floauth_assign_role_for_user', $role, $_GET );

			       // Possibility to use FloMembers id as user_login
			       $parameter_for_matching_user = apply_filters( 'floauth_parameter_for_matching_user', 'email' );
			       $use_id_as_user_login = false;

			       // Search for matching user
			       if ( $parameter_for_matching_user === 'id' && $person_id ) {
				       $use_id_as_user_login = true;
				       $matched_user = get_user_by( 'login', $person_id );
			       } else {
				       $matched_user = get_user_by( 'email', $username );
			       }

			       if ( $matched_user ) {
				       global $wp_roles;
				       $user_id = $matched_user->ID;
				       $user_login = $matched_user->user_login;

				       // Get all roles
				       $all_roles = array_keys( $wp_roles->get_names() );

				       // Possibility to filter out roles that should not be checked and removed, f. ex. BBPress roles
				       $roles_to_check = apply_filters( 'floauth_filter_roles_to_check_on_login', $all_roles );

				       // Get roles of matched user
				       $user_current_roles = get_userdata( $user_id )->roles;

				       // Check if user already has the requested WordPress role
				       $user_already_has_role = in_array( $role, $user_current_roles );
				       if ( ! $user_already_has_role ) {

					       // Remove all core roles from user and add requested role
					       $user_matching_roles = array_intersect( $roles_to_check, $user_current_roles );
					       foreach( $user_matching_roles as $matching_role ) {
						       $matched_user->remove_role( $matching_role );
					       }
					       $matched_user->add_role( $role );
				       }

				       // Update user_email if id used as matching parameter and email doesn't match
				       if ( $use_id_as_user_login && $matched_user->user_email !== $username ) {
					       wp_update_user(
						       array(
							       'ID' => $user_id,
							       'user_email' => $username,
						       )
					       );
				       }

				       // Add user meta data
				       foreach ( $user_meta as $key => $value ) {
					       $previous_value = get_user_meta( $user_id, $key, true );
					       update_user_meta( $user_id, $key, $value, $previous_value );
				       }

				       // Login member and redirect
				       floauth_do_authentication( $user_id, $user_login, $matched_user );
				       wp_safe_redirect( $redirect_url );
				       exit();
			       } else {
				       // Insert new user if no matching user found
				       $userdata = array(
					       'user_pass' => wp_generate_password(),
					       'user_login' => ( $use_id_as_user_login ? $person_id : $username ),
					       'user_email' => $username,
					       'first_name' => $firstname,
					       'last_name' => $lastname,
					       'role' => $role,
				       );
				       $user_id = wp_insert_user( $userdata );

				       // Add user meta data
				       foreach ( $user_meta as $key => $value ) {
					       add_user_meta( $user_id, $key, $value );
				       }

				       // Login member and redirect
				       if ( ! is_wp_error( $user_id ) ) {
					       $user = get_user_by( 'id', $user_id );
					       floauth_do_authentication( $user_id, $username, $user );
				       }
				       wp_safe_redirect( $redirect_url );
				       exit();
			       }
			       die;
		       }
		       // Faulty parameters, do nothing
		       die;
	       } else {
		       $action = '';
		       if ( $floauth_action === 'pages' ) {
			       $action = '/pages';
		       }
		       if ( $flomembers_url && $secret_key ) {
			       // Redirect back to FloMembers with a return URL
			       $return_url = site_url( '/' );
			       $redirect_url = esc_url_raw( $flomembers_url . ( strpos( $flomembers_url, '?' ) === false ? '/?' : '&' ) . 'r=floauth' . $action . '&return_url=' . $return_url . '&hash=' . md5( $secret_key . $return_url ) );
			       wp_redirect( $redirect_url );
			       exit();
		       }
	       }
       }
}
add_action( 'init', 'floauth_init' );

/**
 * Set auth cookie and do login
 *
 * @param string $user_id
 * @param string $username
 * @param WP_User $user_object
 * @return void
 */
function floauth_do_authentication( $user_id, $username, $user_object ) {
       wp_set_current_user( $user_id, $username );
       wp_set_auth_cookie( $user_id );
       do_action( 'wp_login', $username, $user_object );
}
