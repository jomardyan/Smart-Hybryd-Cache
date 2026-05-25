<?php
/**
 * Cache engine orchestration and invalidation.
 *
 * @package SmartHybridCache
 */

defined( 'ABSPATH' ) || exit;

class Smart_Hybrid_Cache_Manager {
private array $options;
private string $active_engine = 'none';
private Smart_Hybrid_Cache_Redis_Client $redis;
private Smart_Hybrid_Cache_Memcached_Client $memcached;
private mixed $client = null;

public function __construct() {
$this->options   = Smart_Hybrid_Cache_Settings::get_options();
$this->redis     = new Smart_Hybrid_Cache_Redis_Client();
$this->memcached = new Smart_Hybrid_Cache_Memcached_Client();
$this->connect();
}

public function hooks(): void {
add_action( 'save_post', array( $this, 'flush_on_post_change' ) );
add_action( 'deleted_post', array( $this, 'flush_on_post_change' ) );
add_action( 'clean_post_cache', array( $this, 'flush_on_post_change' ) );
add_action( 'edited_terms', array( $this, 'flush_safe' ) );
add_action( 'delete_term', array( $this, 'flush_safe' ) );
add_action( 'comment_post', array( $this, 'flush_safe' ) );
add_action( 'edit_comment', array( $this, 'flush_safe' ) );
add_action( 'deleted_comment', array( $this, 'flush_safe' ) );
add_action( 'switch_theme', array( $this, 'flush_on_theme_switch' ) );
add_action( 'upgrader_process_complete', array( $this, 'flush_on_plugin_update' ), 10, 2 );
}

public function connect(): bool {
if ( 'disabled' === $this->options['engine'] ) {
return false;
}

$engines = array();
if ( 'auto' === $this->options['engine'] ) {
$engines = array( 'redis', 'memcached' );
} else {
$engines = array( $this->options['engine'] );
}

foreach ( $engines as $engine ) {
if ( 'redis' === $engine && $this->redis->connect( $this->options ) ) {
$this->active_engine = 'redis';
$this->client        = $this->redis;
$this->save_connection_state( 'redis', '' );
return true;
}
if ( 'memcached' === $engine && $this->memcached->connect( $this->options ) ) {
$this->active_engine = 'memcached';
$this->client        = $this->memcached;
$this->save_connection_state( 'memcached', '' );
return true;
}
}

$error = $this->redis->get_last_error() ?: $this->memcached->get_last_error();
$this->save_connection_state( '', $error );
return false;
}

private function save_connection_state( string $engine, string $error ): void {
$options = Smart_Hybrid_Cache_Settings::get_options();
$changed = $engine !== (string) $options['last_connected_engine'] || $error !== (string) $options['last_error'];
$options['last_connected_engine'] = $engine;
$options['last_error']            = $error;
update_option( SMART_HYBRID_CACHE_OPTION, $options, false );
if ( $changed ) {
Smart_Hybrid_Cache_Logger::log(
'' !== $engine ? 'connection_success' : 'connection_failure',
'' !== $engine ? __( 'Cache engine connected.', 'smart-hybrid-cache' ) : __( 'Cache engine connection failed.', 'smart-hybrid-cache' ),
array(
'engine' => $engine ?: 'none',
'error'  => $error,
)
);
}
}

public function get_active_engine(): string {
return $this->active_engine;
}

public function get_options(): array {
return $this->options;
}

public function test( string $engine ): array {
$client = 'redis' === $engine ? new Smart_Hybrid_Cache_Redis_Client() : new Smart_Hybrid_Cache_Memcached_Client();
$ok     = $client->connect( $this->options ) && $client->ping();
$message = $ok ? __( 'Connection successful.', 'smart-hybrid-cache' ) : $client->get_last_error();
Smart_Hybrid_Cache_Logger::log(
$ok ? 'connection_test_success' : 'connection_test_failure',
$message,
array( 'engine' => $engine )
);
return array(
'ok'      => $ok,
'engine'  => $engine,
'message' => $message,
);
}

public function flush_on_post_change(): void {
if ( ! empty( $this->options['flush_on_post_update'] ) ) {
$this->flush_safe();
}
}

public function flush_on_theme_switch(): void {
if ( ! empty( $this->options['flush_on_theme_switch'] ) ) {
$this->flush_safe();
}
}

public function flush_on_plugin_update( mixed $upgrader = null, array $hook_extra = array() ): void {
if ( ! empty( $this->options['flush_on_plugin_update'] ) && ( empty( $hook_extra['type'] ) || 'plugin' === $hook_extra['type'] ) ) {
$this->flush_safe();
}
}

public function flush_safe(): bool {
if ( ! $this->client ) {
return false;
}
do_action( 'smart_hybrid_cache_before_flush', $this->active_engine );
$result = false;
if ( 'redis' === $this->active_engine ) {
$result = $this->redis->flush_prefix( $this->options['key_prefix'] );
} elseif ( 'memcached' === $this->active_engine ) {
$result = $this->memcached->flush();
}
do_action( 'smart_hybrid_cache_after_flush', $this->active_engine, $result );
Smart_Hybrid_Cache_Logger::log(
$result ? 'flush_success' : 'flush_failure',
$result ? __( 'Cache flushed.', 'smart-hybrid-cache' ) : __( 'Cache flush could not be completed.', 'smart-hybrid-cache' ),
array( 'engine' => $this->active_engine )
);
return $result;
}

public function stats(): array {
if ( 'redis' === $this->active_engine ) {
return $this->redis->stats();
}
if ( 'memcached' === $this->active_engine ) {
return $this->memcached->stats();
}
return array();
}
}
