<?php
/**
 * Admin dashboard functionality for FloAuth plugin.
 *
 * @package FloAuth
 */

/**
 * Register options page, add hook for registering settings
 *
 * @return void
 */
function floauth_plugin_menu() {
	add_options_page( 'FloAuth', 'FloAuth', 'manage_options', 'floauth', 'floauth_plugin_settings_page' );
	add_action( 'admin_init', 'floauth_plugin_register_settings' );
}
add_action( 'admin_menu', 'floauth_plugin_menu' );

/**
 * Register plugin settings
 *
 * @return void
 */
function floauth_plugin_register_settings() {
	register_setting(
		'floauth-settings-group',
		'floauth_flomembers_url',
		array(
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'floauth-settings-group',
		'floauth_secret_key',
		array(
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'floauth-settings-group',
		'floauth_member_role',
		array(
			'default' => 'subscriber',
		)
	);
	register_setting(
		'floauth-settings-group',
		'floauth_admin_role',
		array(
			'default' => 'administrator',
		)
	);
	register_setting(
		'floauth-settings-group',
		'floauth_extranet_path',
		array(
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Test if old option is found and convert to new options.
	$old_plugin_params = get_option( 'floauth_params' );
	if ( $old_plugin_params ) {
		$old_params = json_decode( $old_plugin_params );
		foreach ( $old_params as $key => $value ) {
			if ( ! empty( $value ) ) {
				update_option( "floauth_{$key}", $value );
			}
		}
		// Remove old option.
		delete_option( 'floauth_params' );
	}
}

/**
 * Create settings page
 *
 * @return void
 */
function floauth_plugin_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'floauth' ) );
	}
	include dirname( __DIR__ ) . '/views/settings-page.php';
}

/**
 * Dashboard widget for FloAuth admin role.
 *
 * @return void
 */
function floauth_add_dashboard_meta_box() {
	$admin_role = get_option( 'floauth_admin_role' );
	if ( current_user_can( $admin_role ) ) {
		add_meta_box( 'floauth_meta_box', __( 'Flo Apps (support)', 'floauth' ), 'floauth_dashboard_meta_box_content', 'dashboard', 'side', 'high' );
	}
}
add_action( 'wp_dashboard_setup', 'floauth_add_dashboard_meta_box' );

/**
 * Dashboard meta box content
 *
 * @param WP_Post $post Post object.
 * @param array   $callback_args Callback arguments.
 * @return void
 */
function floauth_dashboard_meta_box_content( $post, $callback_args ) {
	$support_url       = floauth_get_submit_support_ticket_url();
	$knowledgebase_url = 'https://support.floapps.com/knowledgebase.php';
	?>
	<p>
		<?php
		echo wp_kses_post(
			sprintf(
			// translators: %1$s: support URL, %2$s: support link text.
				__( 'You can leave a support request at <a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>.', 'floauth' ),
				esc_url( $support_url ),
				esc_html( $support_url )
			)
		);
		?>
	</p>
	<p>
		<?php
		echo wp_kses_post(
			sprintf(
			// translators: %1$s: knowledge base URL, %2$s: knowledge base link text.
				__( 'You can view knowledge base articles at <a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>.', 'floauth' ),
				esc_url( $knowledgebase_url ),
				esc_html( $knowledgebase_url )
			)
		);
		?>
	</p>
	<?php
}

/**
 * Notification on Add New User screen
 *
 * @param string $type Form type.
 * @return void
 */
function floauth_user_new_form_extra_notification( $type ) {
	if ( 'add-new-user' === $type ) {
		$article_link = 'https://support.floapps.com/knowledgebase.php?article=188';
		?>
		<h3><?php esc_html_e( 'Notification from FloAuth', 'floauth' ); ?></h3>
		<p><?php echo wp_kses_post( __( 'In most cases, this form should <strong>not</strong> be used to create users. Please use <strong>FloMembers</strong> instead.', 'floauth' ) ); ?></p>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				// translators: %1$s: knowledge base article URL , %2$s: support URL.
					__( 'If you have questions, please see <a href="%1$s" target="_blank" rel="noreferrer noopener">this article</a> for more information or leave us a <a href="%2$s" target="_blank" rel="noreferrer noopener">support request</a>.', 'floauth' ),
					esc_url( $article_link ),
					esc_url( floauth_get_submit_support_ticket_url() )
				)
			);
			?>
		</p>
		<?php
	}
}
add_action( 'user_new_form', 'floauth_user_new_form_extra_notification' );
