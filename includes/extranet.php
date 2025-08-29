<?php
/**
 * Extranet path logic, search/filter/block
 */

/**
 * @param WP_Query $query
 * @return WP_Query
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
 * @return void
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
					wp_redirect( apply_filters( 'floauth_restrict_extranet_block_redirect', home_url( '/' ) ) );
					exit();
				}
			}
		}
	}
}
add_action( 'template_redirect', 'floauth_block_extranet_pages' );

/**
 * @return int|null
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
 * @param mixed $old_value
 * @param mixed $new_value
 * @return void
 */
function floauth_clear_extranet_transient( $old_value, $new_value ) {
	delete_transient( 'floauth_extranet_post_id' );
}
add_action( 'update_option_floauth_extranet_path', 'floauth_clear_extranet_transient', 10, 2 );
