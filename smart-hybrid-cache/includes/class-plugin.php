<?php
/**
 * Main plugin loader.
 *
 * @package SmartHybridCache
 */

defined( 'ABSPATH' ) || exit;

class Smart_Hybrid_Cache_Plugin {
private static ?Smart_Hybrid_Cache_Manager $manager = null;

public static function init(): void {
load_plugin_textdomain( 'smart-hybrid-cache', false, dirname( SMART_HYBRID_CACHE_BASENAME ) . '/languages' );
Smart_Hybrid_Cache_Settings::ensure_defaults();
Smart_Hybrid_Cache_Settings::register();

self::$manager = new Smart_Hybrid_Cache_Manager();
self::$manager->hooks();

if ( is_admin() ) {
( new Smart_Hybrid_Cache_Admin( self::$manager ) )->hooks();
}

add_action( 'update_option_' . SMART_HYBRID_CACHE_OPTION, array( __CLASS__, 'settings_updated' ), 10, 3 );
Smart_Hybrid_Cache_CLI::register();
do_action( 'smart_hybrid_cache_loaded', self::$manager );
}

public static function manager(): ?Smart_Hybrid_Cache_Manager {
return self::$manager;
}

public static function settings_updated( mixed $old_value, mixed $value, string $option ): void {
if ( is_array( $value ) && ! empty( $value['enable_dropin'] ) ) {
	Smart_Hybrid_Cache_Dropin_Installer::install( false );
}
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
