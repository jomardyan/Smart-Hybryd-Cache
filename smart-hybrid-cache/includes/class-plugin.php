<?php
/**
 * Main plugin loader.
 *
 * @package SmartHybridCache
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Smart_Hybrid_Cache_Plugin
 *
 * Bootstraps the Smart Hybrid Cache plugin.
 *
 * @since 1.0.0
 */
class Smart_Hybrid_Cache_Plugin {
private static ?Smart_Hybrid_Cache_Manager $manager = null;

public static function init(): void {
load_plugin_textdomain( 'smart-hybrid-cache', false, dirname( SMART_HYBRID_CACHE_BASENAME ) . '/languages' );
Smart_Hybrid_Cache_Settings::ensure_defaults();
Smart_Hybrid_Cache_Settings::register();

self::register_group_filters();

self::$manager = new Smart_Hybrid_Cache_Manager();
self::$manager->hooks();

if ( is_admin() ) {
( new Smart_Hybrid_Cache_Admin( self::$manager ) )->hooks();
}

Smart_Hybrid_Cache_Site_Health::register();

add_action( 'update_option_' . SMART_HYBRID_CACHE_OPTION, array( __CLASS__, 'settings_updated' ), 10, 3 );
Smart_Hybrid_Cache_CLI::register();
do_action( 'smart_hybrid_cache_loaded', self::$manager );
}

private static function register_group_filters(): void {
add_filter(
'smart_hybrid_cache_non_persistent_groups',
static function ( array $groups ): array {
$extra = Smart_Hybrid_Cache_Settings::group_list( 'non_persistent_groups' );
return array_values( array_unique( array_merge( $groups, $extra ) ) );
}
);
add_filter(
'smart_hybrid_cache_global_groups',
static function ( array $groups ): array {
$extra = Smart_Hybrid_Cache_Settings::group_list( 'additional_global_groups' );
return array_values( array_unique( array_merge( $groups, $extra ) ) );
}
);
}

public static function manager(): ?Smart_Hybrid_Cache_Manager {
return self::$manager;
}

public static function settings_updated( mixed $old_value, mixed $value, string $option ): void {
if ( Smart_Hybrid_Cache_Logger::is_writing() ) {
return;
}
if ( is_array( $value ) && ! empty( $value['enable_dropin'] ) ) {
	Smart_Hybrid_Cache_Dropin_Installer::install( false );
}
if ( ! is_array( $old_value ) || ! is_array( $value ) ) {
return;
}
unset( $old_value['last_error'], $old_value['last_connected_engine'], $old_value['log_events'] );
unset( $value['last_error'], $value['last_connected_engine'], $value['log_events'] );
if ( $old_value === $value ) {
return;
}
Smart_Hybrid_Cache_Logger::log( 'settings_updated', __( 'Settings updated.', 'smart-hybrid-cache' ) );
}

public static function activate(): void {
Smart_Hybrid_Cache_Settings::ensure_defaults();
$options = Smart_Hybrid_Cache_Settings::get_options();
if ( ! empty( $options['enable_dropin'] ) ) {
Smart_Hybrid_Cache_Dropin_Installer::install( false );
}
}

public static function deactivate(): void {
$manager = new Smart_Hybrid_Cache_Manager();
$manager->flush_safe();
$options = Smart_Hybrid_Cache_Settings::get_options();
if ( ! empty( $options['cleanup_dropin_deactivate'] ) ) {
Smart_Hybrid_Cache_Dropin_Installer::remove( false );
}
}
}
