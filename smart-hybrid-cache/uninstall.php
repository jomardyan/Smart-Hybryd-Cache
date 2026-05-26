<?php
/**
 * Uninstall Smart Hybrid Cache.
 *
 * @package SmartHybridCache
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
exit;
}

$option_name = 'smart_hybrid_cache_options';
$options     = get_option( $option_name, array() );
$target      = trailingslashit( WP_CONTENT_DIR ) . 'object-cache.php';
$remove      = is_array( $options ) && ! empty( $options['cleanup_dropin_uninstall'] );

if ( $remove && file_exists( $target ) && is_readable( $target ) ) {
if ( ! function_exists( 'WP_Filesystem' ) ) {
require_once ABSPATH . 'wp-admin/includes/file.php';
}
if ( WP_Filesystem( false, WP_CONTENT_DIR, true ) ) {
global $wp_filesystem;
$header = $wp_filesystem->get_contents( $target );
$header = false !== $header ? substr( (string) $header, 0, 2048 ) : false;
} else {
$header = file_get_contents( $target, false, null, 0, 2048 );
}
if ( false !== strpos( (string) $header, 'Smart Hybrid Cache Drop-In' ) ) {
	wp_delete_file( $target );
}
}

if ( is_multisite() ) {
$site_ids = (array) get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
foreach ( $site_ids as $site_id ) {
switch_to_blog( (int) $site_id );
delete_option( $option_name );
restore_current_blog();
}
} else {
delete_option( $option_name );
}
