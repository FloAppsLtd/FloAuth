<?php
/**
 * Settings page view for FloAuth plugin.
 *
 * @package FloAuth
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( 'FloAuth Settings', 'floauth' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'floauth-settings-group' ); ?>
		<?php do_settings_sections( 'floauth-settings-group' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="floauth_flomembers_url"><?php esc_html_e( 'FloMembers URL', 'floauth' ); ?>&nbsp;*</label>
				</th>
				<td>
					<input type="text" id="floauth_flomembers_url" class="regular-text" name="floauth_flomembers_url" value="<?php echo esc_attr( get_option( 'floauth_flomembers_url' ) ); ?>" />
					<p class="description">
						<?php
						echo wp_kses_post(
							__( 'Insert your FloMembers installation URL <strong>without trailing slash</strong> (e.g. https://edge.flomembers.com/demo).', 'floauth' )
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="floauth_secret_key"><?php esc_html_e( 'Secret key', 'floauth' ); ?>&nbsp;*</label>
				</th>
				<td>
					<input type="text" id="floauth_secret_key" class="regular-text" name="floauth_secret_key" value="<?php echo esc_attr( get_option( 'floauth_secret_key' ) ); ?>" />
					<p class="description">
						<?php
						echo wp_kses_post(
							sprintf(
								// translators: %s: Support ticket URL.
								__( 'Insert alphanumeric secret key here. The key is provided by Flo Apps. You can contact us via our <a href="%s" target="_blank" rel="noreferrer noopener">support system</a>.', 'floauth' ),
								esc_url( floauth_get_submit_support_ticket_url() )
							)
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="floauth_member_role"><?php esc_html_e( 'Role given to regular users', 'floauth' ); ?>&nbsp;*</label>
				</th>
				<td>
					<select id="floauth_member_role" name="floauth_member_role">
						<?php wp_dropdown_roles( get_option( 'floauth_member_role' ) ); ?>
					</select>
					<p class="description"><?php echo wp_kses_post( __( 'Select the role which will be automatically assigned to <strong>regular users</strong> on login.', 'floauth' ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="floauth_admin_role"><?php esc_html_e( 'Role given to admins', 'floauth' ); ?>&nbsp;*</label>
				</th>
				<td>
					<select id="floauth_admin_role" name="floauth_admin_role">
						<?php wp_dropdown_roles( get_option( 'floauth_admin_role' ) ); ?>
					</select>
					<p class="description"><?php echo wp_kses_post( __( 'Select the role which will be automatically assigned to <strong>website administrators</strong> on login.', 'floauth' ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="floauth_extranet_path"><?php esc_html_e( 'Extranet path', 'floauth' ); ?></label>
				</th>
				<td>
					<input type="text" id="floauth_extranet_path" class="regular-text" name="floauth_extranet_path" value="<?php echo esc_attr( get_option( 'floauth_extranet_path' ) ); ?>" />
					<div>
						<p class="description"><?php esc_html_e( 'Insert the URL part (slug) of the page which serves as the member area (extranet) page. This page and its children are blocked and filtered out of search results from non-logged in users.', 'floauth' ); ?></p>
						<p class="description"><strong><?php esc_html_e( 'Examples:', 'floauth' ); ?></strong></p>
						<p class="description"><?php echo wp_kses_post( __( 'Extranet URL: https://example.com/members, insert <strong>members</strong>', 'floauth' ) ); ?></p>
						<p class="description"><?php echo wp_kses_post( __( 'Extranet URL: https://example.com/some-page/members, insert <strong>some-page/members</strong>', 'floauth' ) ); ?></p>
					</div>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
