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

public static function status(): array {
$target = self::target();
$exists = file_exists( $target );
$owned  = $exists && self::is_owned();

return array(
'exists'      => $exists,
'owned'       => $owned,
'writable'    => ( ! $exists && is_writable( WP_CONTENT_DIR ) ) || ( $exists && is_writable( $target ) ),
'path'        => $target,
'advanced'    => file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ),
'owner_label' => $exists ? ( $owned ? __( 'Created by Smart Hybrid Cache.', 'smart-hybrid-cache' ) : __( 'Created by another plugin or manually.', 'smart-hybrid-cache' ) ) : __( 'Not installed.', 'smart-hybrid-cache' ),
);
}

public static function is_owned(): bool {
$target = self::target();
if ( ! file_exists( $target ) || ! is_readable( $target ) ) {
return false;
}
$handle = fopen( $target, 'rb' );
if ( false === $handle ) {
return false;
}
$contents = fread( $handle, 2048 );
fclose( $handle );
return false !== strpos( (string) $contents, SMART_HYBRID_CACHE_SIGNATURE );
}

public static function install( bool $force = false ): WP_Error|bool {
$source = self::source();
$target = self::target();
if ( ! file_exists( $source ) || ! is_readable( $source ) ) {
return new WP_Error( 'missing_source', __( 'Drop-in source file is missing.', 'smart-hybrid-cache' ) );
}
if ( file_exists( $target ) && ! self::is_owned() && ! $force ) {
return new WP_Error( 'existing_dropin', __( 'An object-cache.php file already exists. Confirm replacement before installing.', 'smart-hybrid-cache' ) );
}
if ( ! is_writable( WP_CONTENT_DIR ) && ( ! file_exists( $target ) || ! is_writable( $target ) ) ) {
return new WP_Error( 'not_writable', __( 'wp-content is not writable.', 'smart-hybrid-cache' ) );
}

if ( ! copy( $source, $target ) ) {
return new WP_Error( 'copy_failed', __( 'Unable to copy object-cache.php.', 'smart-hybrid-cache' ) );
}
@chmod( $target, 0644 );
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
if ( ! is_writable( $target ) ) {
return new WP_Error( 'not_writable', __( 'object-cache.php is not writable.', 'smart-hybrid-cache' ) );
}
return unlink( $target ) ? true : new WP_Error( 'remove_failed', __( 'Unable to remove object-cache.php.', 'smart-hybrid-cache' ) );
}
}
