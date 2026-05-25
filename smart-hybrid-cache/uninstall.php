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
$handle = fopen( $target, 'rb' );
$header = false !== $handle ? fread( $handle, 2048 ) : '';
if ( false !== $handle ) {
fclose( $handle );
}
if ( false !== strpos( (string) $header, 'Smart Hybrid Cache Drop-In' ) && is_writable( $target ) ) {
unlink( $target );
}
}

if ( is_multisite() ) {
$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
foreach ( $site_ids as $site_id ) {
switch_to_blog( (int) $site_id );
delete_option( $option_name );
restore_current_blog();
}
} else {
delete_option( $option_name );
}
