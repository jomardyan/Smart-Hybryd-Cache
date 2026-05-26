<?php
/**
 * Uninstall Smart Hybrid Cache.
 *
 * Removes plugin options and optionally deletes the object-cache.php drop-in
 * if it was created by this plugin and the user opted in to cleanup on uninstall.
 *
 * @package SmartHybridCache
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$shc_option_name = 'smart_hybrid_cache_options';
$shc_options     = get_option( $shc_option_name, array() );
$shc_target      = trailingslashit( WP_CONTENT_DIR ) . 'object-cache.php';
$shc_remove      = is_array( $shc_options ) && ! empty( $shc_options['cleanup_dropin_uninstall'] );

if ( $shc_remove && file_exists( $shc_target ) && is_readable( $shc_target ) ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( WP_Filesystem( false, WP_CONTENT_DIR, true ) ) {
		global $wp_filesystem;
		$shc_header = $wp_filesystem->get_contents( $shc_target );
		$shc_header = false !== $shc_header ? substr( (string) $shc_header, 0, 2048 ) : false;
	} else {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local file header only.
		$shc_header = file_get_contents( $shc_target, false, null, 0, 2048 );
	}
	if ( false !== strpos( (string) $shc_header, 'Smart Hybrid Cache Drop-In' ) ) {
		wp_delete_file( $shc_target );
	}
}

if ( is_multisite() ) {
	$shc_site_ids = (array) get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $shc_site_ids as $shc_site_id ) {
		switch_to_blog( (int) $shc_site_id );
		delete_option( $shc_option_name );
		restore_current_blog();
	}
} else {
	delete_option( $shc_option_name );
}
