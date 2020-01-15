<?php
/*
  Plugin Name: FloAuth
  Version: 1.0.4
  Description: FloMembers authentication plugin
  Author: Flo Apps Ltd
  Author URI: http://www.floapps.com
  Text Domain: floauth
  Domain Path: /lang
  License: mit
 */

if ( ! function_exists( 'get_option' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;  // Silence is golden, direct call is prohibited
}

/**
 * Load plugin textdomain
 */
function floauth_load_plugin_textdomain() {
	load_plugin_textdomain( 'floauth', false, basename( dirname( __FILE__ ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'floauth_load_plugin_textdomain' );

/**
 * Register options page, add hook for registering settings
 */
function floauth_plugin_menu() {
	add_options_page( 'FloAuth', 'FloAuth', 'manage_options', 'floauth', 'floauth_plugin_settings_page' );
	add_action( 'admin_init', 'floauth_plugin_register_settings' );
}
add_action( 'admin_menu', 'floauth_plugin_menu' );

/**
 * Register plugin settings
 *
 * Convert old params (floauth_params) to new ones, if they exist
 */
function floauth_plugin_register_settings() {
	register_setting( 'floauth-settings-group', 'floauth_flomembers_url', array(
			'sanitize_callback' => 'sanitize_text_field'
		) );
	register_setting( 'floauth-settings-group', 'floauth_secret_key', array(
			'sanitize_callback' => 'sanitize_text_field'
		) );
	register_setting( 'floauth-settings-group', 'floauth_member_role', array(
			'default' => 'subscriber'
		) );
	register_setting( 'floauth-settings-group', 'floauth_admin_role', array(
			'default' => 'administrator'
		) );
	register_setting( 'floauth-settings-group', 'floauth_extranet_path', array(
			'sanitize_callback' => 'sanitize_text_field'
		) );

	// Test if old option is found and convert to new options
	$old_plugin_params = get_option( 'floauth_params' );
	if ( $old_plugin_params ) {
		$old_params = json_decode( $old_plugin_params );
		foreach ( $old_params as $key => $value ) {
			if ( !empty( $value ) ) {
				update_option( "floauth_{$key}", $value );
			}
		}
		// Remove old option
		delete_option( 'floauth_params' );
	}
}

/**
 * Create settings page
 */
function floauth_plugin_settings_page() {

	// Disable access if user has no rights
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'floauth' ) );
	}

?>
	<div class="wrap">
		<h1><?php _e( 'FloAuth Settings', 'floauth' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'floauth-settings-group' ); ?>
			<?php do_settings_sections( 'floauth-settings-group' ); ?>
			<table class="form-table">
		        <tr>
			        <th scope="row">
			            <label for="floauth_flomembers_url"><?php _e( 'FloMembers URL', 'floauth' ); ?> *</label>
			        </th>
			        <td>
				        <input type="text" id="floauth_flomembers_url" class="regular-text" name="floauth_flomembers_url" value="<?php esc_attr_e( get_option( 'floauth_flomembers_url' ) ); ?>" />
				        <p class="description"><?php _e( 'Insert your FloMembers installation URL <strong>without trailing slash</strong> (f. ex. https://edge.flomembers.com/demo).', 'floauth' ); ?></p>
			        </td>
		        </tr>
		        <tr>
			        <th scope="row">
				        <label for="floauth_secret_key"><?php _e( 'Secret key', 'floauth' ); ?> *</label>
			        </th>
			        <td>
				        <input type="text" id="floauth_secret_key" class="regular-text" name="floauth_secret_key" value="<?php esc_attr_e( get_option( 'floauth_secret_key' ) ); ?>" />
				        <p class="description"><?php printf( __( 'Insert alphanumeric secret key here. The key is provided by Flo Apps. You can contact us via our <a href="%s" target="_blank" rel="noreferrer noopener">support system</a>.', 'floauth' ), floauth_get_submit_support_ticket_url() ); ?></p>
			        </td>
		        </tr>
				<tr>
					<th scope="row">
						<label for="floauth_member_role"><?php _e( 'Role given to regular users', 'floauth' ); ?> *</label>
					</th>
					<td>
						<select id="floauth_member_role" name="floauth_member_role">
							<?php wp_dropdown_roles( get_option( 'floauth_member_role' ) ); ?>
						</select>
						<p class="description"><?php _e( 'Select the role which will be automatically assigned to <strong>regular users</strong> on login.', 'floauth' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="floauth_admin_role"><?php _e( 'Role given to admins', 'floauth' ); ?> *</label>
					</th>
					<td>
						<select id="floauth_admin_role" name="floauth_admin_role">
							<?php wp_dropdown_roles( get_option( 'floauth_admin_role' ) ); ?>
						</select>
						<p class="description"><?php _e( 'Select the role which will be automatically assigned to <strong>website administrators</strong> on login.', 'floauth' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="floauth_extranet_path"><?php _e( 'Extranet path', 'floauth' ); ?></label>
					</th>
					<td>
						<input type="text" id="floauth_extranet_path" class="regular-text" name="floauth_extranet_path" value="<?php esc_attr_e( get_option( 'floauth_extranet_path' ) ); ?>" />
						<div>
							<p class="description"><?php _e( 'Insert the URL part (slug) of the page which serves as the member area (extranet) page. This page and its children are blocked and filtered out of search results from non-logged in users.', 'floauth' ); ?></p>
							<p class="description"><strong><?php _e( 'Examples:', 'floauth' ); ?></strong></p>
							<p class="description"><?php _e( 'Extranet URL: https://example.com/members, insert <strong>members</strong>', 'floauth' ); ?></p>
							<p class="description"><?php _e( 'Extranet URL: https://example.com/some-page/members, insert <strong>some-page/members</strong>', 'floauth' ); ?></p>
						</div>
					</td>
				</tr>
	        </table>
	        <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Handle authentication via FloMembers
 *
 * Triggers if request has "floauth" parameter
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

		if ( isset( $_GET['hash'] ) ) {

			// Get URL parameters
			$hash = $_GET['hash'];
			$person_id = ( isset( $_GET['id'] ) ? $_GET['id'] : null );
			$username = ( isset( $_GET['username'] ) ? $_GET['username'] : null );
			$firstname = ( isset( $_GET['firstname'] ) ? $_GET['firstname'] : null );
			$lastname = ( isset( $_GET['lastname'] ) ? $_GET['lastname'] : null );
			$role = ( $_GET['role'] === 'admin' ? $admin_role : $member_role );

			// Determine redirect URL by "floauth" parameter
			$redirect_path = apply_filters( 'floauth_modify_redirect_path', $extranet_path, $_GET );
			$redirect_url = ( $_GET['floauth'] === 'pages' ? esc_url_raw( site_url( '/' ) . $redirect_path ) : esc_url_raw( admin_url() ) );

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
			if ( $_GET['floauth'] == 'pages' ) {
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
 */
function floauth_do_authentication( $user_id, $username, $user_object ) {
	wp_set_current_user( $user_id, $username );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $username, $user_object );
}

/**
 * Utility function for checking if an array is associative, not sequential
 */
function floauth_is_associative_array( $array ) {
	return array_keys( $array ) !== range( 0, count( $array ) - 1 );
}

/**
 * Add FloMembers link to toolbar
 */
function floauth_add_toolbar_link_to_flomembers( $wp_admin_bar ) {
	$flomembers_url = esc_url( get_option( 'floauth_flomembers_url' ) );
	if ( $flomembers_url ) {
		$args = array(
			'id' => 'flomembers',
			'title' => 'FloMembers',
			'href' => $flomembers_url,
			'meta' => array(
				'target' => '_blank',
				'rel' => 'noopener noreferrer',
			)
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
 */
function floauth_hide_toolbar() {
	if ( is_user_logged_in() ) {
		$hide_admin_bar = apply_filters( 'floauth_hide_admin_bar_from_users', true );
		$capability = apply_filters( 'floauth_hide_admin_bar_capability', 'edit_posts' );
		if ( ! current_user_can( $capability ) && $hide_admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}
}
add_action( 'after_setup_theme', 'floauth_hide_toolbar' );

/**
 * Add custom dashboard widget for users with FloAuth admin role
 */
function floauth_add_dashboard_meta_box() {
	$admin_role = get_option( 'floauth_admin_role' );

	// Do not show to non-admins
	if ( current_user_can( $admin_role ) ) {
		add_meta_box( 'floauth_meta_box', __( 'Flo Apps (support)', 'floauth' ), 'floauth_dashboard_meta_box_content', 'dashboard', 'side', 'high' );
	}
}
add_action( 'wp_dashboard_setup', 'floauth_add_dashboard_meta_box' );

/**
 * Callback for printing dashboard widget content
 */
function floauth_dashboard_meta_box_content( $post, $callback_args ) {
	$support_url = floauth_get_submit_support_ticket_url();
	$knowledgebase_url = 'https://floapps.eu/support/knowledgebase.php';
?>

	<p><?php printf( __( 'You can leave a support request at <a href="%s" target="_blank" rel="noreferrer noopener">%s</a>.', 'floauth' ), $support_url, $support_url ); ?></p>
	<p><?php printf( __( 'You can view knowledge base articles at <a href="%s" target="_blank" rel="noreferrer noopener">%s</a>.', 'floauth' ), $knowledgebase_url, $knowledgebase_url ); ?></p>

<?php
}

/**
 * Print extra notification on Add New User screen
 */
function floauth_user_new_form_extra_notification( $type ) {
	if ( $type === 'add-new-user' ) {
		$article_link = 'https://floapps.eu/support/knowledgebase.php?article=188';
	?>

		<h3><?php _e( 'Notification from FloAuth', 'floauth' ); ?></h3>
		<p><?php _e( 'In most cases, this form should <strong>not</strong> be used to create users. Please use <strong>FloMembers</strong> instead.', 'floauth' ); ?></p>
		<p><?php printf( __( 'If you have questions, please see <a href="%s" target="_blank" rel="noreferrer noopener">this article</a> for more information or leave us a <a href="%s" target="_blank" rel="noreferrer noopener">support request</a>.', 'floauth' ), $article_link, floauth_get_submit_support_ticket_url() ); ?></p>

	<?php
	}
}
add_action( 'user_new_form', 'floauth_user_new_form_extra_notification' );

/**
 * Return URL for submitting new support tickets
 *
 * @return string
 */
function floauth_get_submit_support_ticket_url() {
	return 'https://floapps.eu/support/?a=add&category=9';
}

/**
 * Remove extranet path and its children from search results if user has no rights
 *
 * Capability can be changed with filter "floauth_restrict_extranet_pages_capability"
 * Capability defaults to "read", also logged-in users with no role have no access
 */
function floauth_filter_pre_get_posts( $query ) {
	if( $query->is_search ) {
		$capability = apply_filters( 'floauth_restrict_extranet_pages_capability', 'read' );
		if( ! is_user_logged_in() || ! current_user_can( $capability ) ) {
			$restricted_post_id = (int) floauth_get_extranet_post_id();
			if ( $restricted_post_id !== 0 ) {
				$children = get_pages( array(
						'child_of' => $restricted_post_id
					) );
				$restricted_ids = array();
				$restricted_ids[] = $restricted_post_id;
				foreach( $children as $child ) {
					$restricted_ids[] = $child->ID;
				}
				$query->set( 'post__not_in', $restricted_ids );
			}
		}
	}
	return $query;
}
add_filter( 'pre_get_posts', 'floauth_filter_pre_get_posts' );

/**
 * Disable access to extranet path and its children if user has no rights
 *
 * Capability can be changed with filter "floauth_restrict_extranet_pages_capability"
 * Capability defaults to "read", also logged-in users with no role have no access
 */
function floauth_block_extranet_pages() {
	$capability = apply_filters( 'floauth_restrict_extranet_pages_capability', 'read' );
	if( ! is_user_logged_in() || ! current_user_can( $capability ) ) {
		if( ! is_search() ) {
			$restricted_post_id = (int) floauth_get_extranet_post_id();
			if ( $restricted_post_id !== 0 ) {
				$current_post_id = get_the_ID();
				$ancestors = get_post_ancestors( $current_post_id );
				if ( $restricted_post_id === $current_post_id || in_array( $restricted_post_id, $ancestors ) ) {
					wp_redirect( home_url( '/' ) );
					exit();
				}
			}
		}
	}
}
add_action( 'template_redirect', 'floauth_block_extranet_pages' );

/**
 * Get extranet post ID from extranet path
 *
 * Saved to transient
 */
function floauth_get_extranet_post_id() {
	if ( false === ( $extranet_post_id = get_transient( 'floauth_extranet_post_id' ) ) ) {
		$extranet_path = get_option( 'floauth_extranet_path' );
		if ( $extranet_path ) {
			$post_id = url_to_postid( $extranet_path );
			if ( $post_id !== 0 ) {
				$extranet_post_id = $post_id;
				set_transient( 'floauth_extranet_post_id', $extranet_post_id, WEEK_IN_SECONDS );
			}
		}
	}
	return $extranet_post_id;
}

/**
 * Remove extranet post ID transient if option is updated
 */
function floauth_clear_extranet_transient( $old_value, $new_value ) {
	delete_transient( 'floauth_extranet_post_id' );
}
add_action( 'update_option_floauth_extranet_path', 'floauth_clear_extranet_transient', 10, 2 );
