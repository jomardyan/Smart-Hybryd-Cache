<?php
/**
 * WP-CLI commands.
 *
 * @package SmartHybridCache
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Smart_Hybrid_Cache_CLI
 *
 * Provides WP-CLI commands for cache management and diagnostics.
 *
 * @since 1.0.0
 */
class Smart_Hybrid_Cache_CLI {
public static function register(): void {
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WP_CLI' ) ) {
return;
}
WP_CLI::add_command( 'smart-cache', __CLASS__ );
}

/** Show cache status. */
public function status(): void {
$status = Smart_Hybrid_Cache_Health_Check::get_status( Smart_Hybrid_Cache_Plugin::manager() );
\WP_CLI::line( 'Selected engine: ' . $status['selected_engine'] );
\WP_CLI::line( 'Active engine: ' . $status['active_engine'] );
\WP_CLI::line( 'Redis extension: ' . ( $status['redis_available'] ? 'available' : 'missing' ) );
\WP_CLI::line( 'Memcached extension: ' . ( $status['memcached_available'] ? 'available' : 'missing' ) );
\WP_CLI::line( 'Drop-in: ' . ( $status['dropin']['exists'] ? 'installed' : 'not installed' ) );
\WP_CLI::line( 'Drop-in owner: ' . wp_strip_all_tags( $status['dropin']['owner_label'] ) );
\WP_CLI::line( 'Connection: ' . wp_strip_all_tags( $status['connection_status'] ) );
\WP_CLI::line( 'Last error: ' . ( $status['last_error'] ?: 'none' ) );
\WP_CLI::line( 'Prefix: ' . $status['cache_prefix'] );
\WP_CLI::line( 'TTL: ' . $status['current_ttl'] );
}

/** Test cache connection. */
public function test( array $args = array() ): void {
$manager = Smart_Hybrid_Cache_Plugin::manager() ?: new Smart_Hybrid_Cache_Manager();
$options = Smart_Hybrid_Cache_Settings::get_options();
$engine  = $args[0] ?? $options['engine'];
if ( 'auto' === $engine ) {
$engine = class_exists( 'Redis' ) ? 'redis' : 'memcached';
}
if ( ! in_array( $engine, array( 'redis', 'memcached' ), true ) ) {
\WP_CLI::error( 'Specify redis or memcached.' );
}
$result = $manager->test( $engine );
$result['ok'] ? \WP_CLI::success( $result['message'] ) : \WP_CLI::error( $result['message'] );
}

/** Flush plugin-managed cache. */
public function flush(): void {
$manager = Smart_Hybrid_Cache_Plugin::manager() ?: new Smart_Hybrid_Cache_Manager();
$manager->flush_safe() ? \WP_CLI::success( 'Cache flushed.' ) : \WP_CLI::warning( 'Cache flush could not be completed.' );
}

/** Enable Redis or Memcached. */
public function enable( array $args ): void {
$engine = sanitize_key( $args[0] ?? '' );
if ( ! in_array( $engine, array( 'redis', 'memcached' ), true ) ) {
\WP_CLI::error( 'Usage: wp smart-cache enable redis|memcached' );
}
$options           = Smart_Hybrid_Cache_Settings::get_options();
$options['engine'] = $engine;
Smart_Hybrid_Cache_Settings::update_options( $options );
\WP_CLI::success( 'Enabled ' . $engine . '.' );
}

/** Disable persistent cache engine. */
public function disable(): void {
$options           = Smart_Hybrid_Cache_Settings::get_options();
$options['engine'] = 'disabled';
Smart_Hybrid_Cache_Settings::update_options( $options );
\WP_CLI::success( 'Smart Hybrid Cache disabled.' );
}

/** Install object-cache.php drop-in. */
public function install_dropin( array $args = array(), array $assoc_args = array() ): void {
$result = Smart_Hybrid_Cache_Dropin_Installer::install( ! empty( $assoc_args['force'] ) );
is_wp_error( $result ) ? \WP_CLI::error( $result->get_error_message() ) : \WP_CLI::success( 'Object cache drop-in installed.' );
}

/** Remove object-cache.php drop-in. */
public function remove_dropin( array $args = array(), array $assoc_args = array() ): void {
$result = Smart_Hybrid_Cache_Dropin_Installer::remove( ! empty( $assoc_args['force'] ) );
is_wp_error( $result ) ? \WP_CLI::error( $result->get_error_message() ) : \WP_CLI::success( 'Object cache drop-in removed.' );
}

/**
 * Print a JSON diagnostics report (Redis password redacted).
 *
 * ## OPTIONS
 *
 * [--pretty]
 * : Pretty-print the JSON output.
 */
public function diagnostics( array $args = array(), array $assoc_args = array() ): void {
$snapshot = Smart_Hybrid_Cache_Diagnostics::snapshot();
$flags    = ! empty( $assoc_args['pretty'] ) ? ( JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) : JSON_UNESCAPED_SLASHES;
\WP_CLI::line( (string) wp_json_encode( $snapshot, $flags ) );
}
}
