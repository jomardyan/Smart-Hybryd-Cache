<?php
/**
 * Object cache drop-in installer.
 *
 * @package SmartHybridCache
 */

defined( 'ABSPATH' ) || exit;

class Smart_Hybrid_Cache_Dropin_Installer {
public static function target(): string {
return trailingslashit( WP_CONTENT_DIR ) . 'object-cache.php';
}

public static function source(): string {
return SMART_HYBRID_CACHE_PATH . 'dropins/object-cache.php';
}

/**
 * Initialize WP_Filesystem and return the global instance.
 *
 * @return WP_Filesystem_Base|false
 */
private static function init_filesystem() {
global $wp_filesystem;
if ( ! function_exists( 'WP_Filesystem' ) ) {
require_once ABSPATH . 'wp-admin/includes/file.php';
}
if ( ! WP_Filesystem( false, WP_CONTENT_DIR, true ) ) {
return false;
}
if ( ! ( $wp_filesystem instanceof WP_Filesystem_Base ) ) {
return false;
}
return $wp_filesystem;
}

public static function status(): array {
$target = self::target();
$exists = file_exists( $target );
$owned  = $exists && self::is_owned();
$active = function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
$available = $exists || $active;
$owner_label = __( 'Not installed.', 'smart-hybrid-cache' );
$availability_message = __( 'Persistent object cache is not enabled yet. Install the Smart Hybrid Cache drop-in to activate it.', 'smart-hybrid-cache' );

if ( $owned ) {
$owner_label = __( 'Created by Smart Hybrid Cache.', 'smart-hybrid-cache' );
$availability_message = __( 'Persistent object cache is already available through Smart Hybrid Cache.', 'smart-hybrid-cache' );
} elseif ( $exists ) {
$owner_label = __( 'Created by another plugin or manually.', 'smart-hybrid-cache' );
$availability_message = __( 'Persistent object cache is already available in the system through another plugin or a manual object-cache.php drop-in. Smart Hybrid Cache will not replace it unless you explicitly confirm replacement.', 'smart-hybrid-cache' );
} elseif ( $active ) {
$availability_message = __( 'WordPress reports that an external object cache is already active in this request.', 'smart-hybrid-cache' );
}

$fs       = self::init_filesystem();
$writable = false;
if ( $fs ) {
$writable = ( ! $exists && $fs->is_writable( WP_CONTENT_DIR ) ) || ( $exists && $fs->is_writable( $target ) );
}

return array(
'exists'      => $exists,
'owned'       => $owned,
'active'      => $active,
'available'   => $available,
'writable'    => $writable,
'path'        => $target,
'advanced'    => file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ),
'owner_label' => $owner_label,
'message'     => $availability_message,
);
}

public static function is_owned(): bool {
$target = self::target();
if ( ! file_exists( $target ) || ! is_readable( $target ) ) {
return false;
}
$fs = self::init_filesystem();
if ( ! $fs ) {
return false;
}
$contents = $fs->get_contents( $target );
if ( false === $contents ) {
return false;
}
return false !== strpos( substr( (string) $contents, 0, 2048 ), SMART_HYBRID_CACHE_SIGNATURE );
}

public static function install( bool $force = false ): WP_Error|bool {
$source = self::source();
$target = self::target();
if ( ! file_exists( $source ) || ! is_readable( $source ) ) {
return new WP_Error( 'missing_source', __( 'Drop-in source file is missing.', 'smart-hybrid-cache' ) );
}
if ( file_exists( $target ) && self::is_owned() && ! $force ) {
return new WP_Error( 'dropin_already_installed', __( 'Smart Hybrid Cache object-cache.php is already installed and available.', 'smart-hybrid-cache' ) );
}
if ( file_exists( $target ) && ! self::is_owned() && ! $force ) {
return new WP_Error( 'existing_dropin', __( 'Persistent object cache is already available through another plugin or a manual object-cache.php file. Confirm replacement before installing Smart Hybrid Cache.', 'smart-hybrid-cache' ) );
}

$fs = self::init_filesystem();
if ( ! $fs ) {
return new WP_Error( 'filesystem_error', __( 'Unable to initialize the WordPress filesystem.', 'smart-hybrid-cache' ) );
}

if ( ! $fs->is_writable( WP_CONTENT_DIR ) && ( ! file_exists( $target ) || ! $fs->is_writable( $target ) ) ) {
return new WP_Error( 'not_writable', __( 'wp-content is not writable.', 'smart-hybrid-cache' ) );
}

if ( ! $fs->copy( $source, $target, true ) ) {
return new WP_Error( 'copy_failed', __( 'Unable to copy object-cache.php.', 'smart-hybrid-cache' ) );
}
$fs->chmod( $target, 0644 );
return true;
}

public static function remove( bool $force = false ): WP_Error|bool {
$target = self::target();
if ( ! file_exists( $target ) ) {
return true;
}
if ( ! self::is_owned() && ! $force ) {
return new WP_Error( 'not_owned', __( 'The existing object-cache.php was not created by this plugin.', 'smart-hybrid-cache' ) );
}

$fs = self::init_filesystem();
if ( ! $fs ) {
return new WP_Error( 'filesystem_error', __( 'Unable to initialize the WordPress filesystem.', 'smart-hybrid-cache' ) );
}

if ( ! $fs->is_writable( $target ) ) {
return new WP_Error( 'not_writable', __( 'object-cache.php is not writable.', 'smart-hybrid-cache' ) );
}
return $fs->delete( $target ) ? true : new WP_Error( 'remove_failed', __( 'Unable to remove object-cache.php.', 'smart-hybrid-cache' ) );
}
}
