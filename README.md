# FloAuth WordPress plugin #

FloAuth is a WordPress plugin for bridging FloMembers membership management and a WordPress website.


## Features ##

* Allows login through FloMembers with no need for WordPress login credentials
* Checks and changes user role on each login (if user's FloMembers role has changed)
* Extranet (pages restricted to logged-in users only, **optional**)
  * Restricts access to extranet page and its children
  * Removes extranet pages from search results
* Hides WordPress toolbar from Subscribers

## Installation ##

1. Download the plugin
2. Upload directory to `wp-content/plugins`
3. Activate the plugin

## Filters ##

### Add meta data fields to user on login ###

For example save person address fields for WooCommerce

```
function custom_floauth_add_user_meta_data( $meta_data, $parameters ) {
	if ( isset( $parameters['address'] ) ) {
		$meta_data['billing_address_1'] = $parameters['address'];
	}
	return $meta_data;
}
add_filter( 'floauth_add_user_meta_data', 'custom_floauth_add_user_meta_data', 10, 2 );
```

For example add a custom meta field according to FloMembers groups this person belongs to

```
function custom_floauth_add_user_meta_data( $meta_data, $parameters ) {
	$group_to_test = '1';
	if ( isset( $parameters['groups'] ) ) {
		$user_group_ids = explode( ',', $parameters['groups'] );
		if ( in_array( $group_to_test, $user_group_ids ) ) {
			// Add some meta field
			$meta_data['custom_field'] = 'some value';
		} 
	}
	return $meta_data;
}
add_filter( 'floauth_add_user_meta_data', 'custom_floauth_add_user_meta_data', 10, 2 );
```

### Change user role according to request parameters ###

For example if user is not a website admin and does not have a member role in FloMembers, you can assign another role

```
function custom_floauth_assign_role_for_user( $role, $parameters ) {
	if ( isset( $parameters['ismember'] ) && intval( $parameters['ismember'] ) !== 1 && $parameters['role'] !== 'admin'  ) {
		$role = 'subscriber';
	}
	return $role;
}
add_filter( 'floauth_assign_role_for_user', 'custom_floauth_assign_role_for_user', 10, 2 );
```

### Change capability for hiding extranet pages ###

For example hide extranet pages from Subscriber also (default capability is `read`)

```
function custom_restrict_extranet_pages_capability( $capability ) {
	return 'edit_posts';
}
add_filter( 'floauth_restrict_extranet_pages_capability', 'custom_restrict_extranet_pages_capability' );
```
More info on WordPress [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities)

### Keep WordPress toolbar always visible ###

```
add_filter( 'floauth_hide_admin_bar_from_users', '__return_false' );
```

### Change capability for hiding toolbar ###

For example hide toolbar from Contributor also (default capability is `edit_posts`)

```
function custom_hide_admin_bar_capability( $capability ) {
	return 'edit_published_posts';
}
add_filter( 'floauth_hide_admin_bar_capability', 'custom_hide_admin_bar_capability' );
```
