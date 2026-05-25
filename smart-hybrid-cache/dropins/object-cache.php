<?php
/**
 * Smart Hybrid Cache Drop-In
 *
 * Persistent object cache drop-in for Redis and Memcached with runtime fallback.
 * Signature: Smart Hybrid Cache Drop-In
 *
 * @package SmartHybridCache
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! defined( 'SMART_HYBRID_CACHE_DROPIN_VERSION' ) ) {
define( 'SMART_HYBRID_CACHE_DROPIN_VERSION', '1.0.0' );
}

if ( ! class_exists( 'WP_Object_Cache', false ) ) {
class WP_Object_Cache {
private array $cache = array();
private array $global_groups = array( 'blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs' );
private array $non_persistent_groups = array( 'counts', 'plugins', 'themes', 'comment', 'wc_session_id' );
private array $options = array();
private mixed $client = null;
private string $engine = 'none';
private int $blog_id = 1;
public int $cache_hits = 0;
public int $cache_misses = 0;

public function __construct() {
$this->blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 1;
$this->options = $this->load_options();
if ( function_exists( 'apply_filters' ) ) {
$this->global_groups         = (array) apply_filters( 'smart_hybrid_cache_global_groups', $this->global_groups );
$this->non_persistent_groups = (array) apply_filters( 'smart_hybrid_cache_non_persistent_groups', $this->non_persistent_groups );
}
$this->connect();
}

private function defaults(): array {
$site_url = function_exists( 'network_site_url' ) ? network_site_url() : ( function_exists( 'home_url' ) ? home_url() : ABSPATH );
return array(
'engine'               => 'auto',
'redis_host'           => '127.0.0.1',
'redis_port'           => 6379,
'redis_password'       => '',
'redis_database'       => 0,
'redis_timeout'        => 1.0,
'redis_tls'            => false,
'redis_persistent'     => true,
'memcached_host'       => '127.0.0.1',
'memcached_port'       => 11211,
'memcached_persistent' => false,
'default_ttl'          => 3600,
'key_prefix'           => 'shc_' . substr( md5( (string) $site_url ), 0, 12 ) . '_',
);
}

private function load_options(): array {
$options = function_exists( 'get_option' ) ? get_option( 'smart_hybrid_cache_options', array() ) : array();
return is_array( $options ) ? array_merge( $this->defaults(), $options ) : $this->defaults();
}

private function connect(): void {
if ( 'disabled' === (string) $this->options['engine'] ) {
return;
}
$engines = 'auto' === $this->options['engine'] ? array( 'redis', 'memcached' ) : array( $this->options['engine'] );
foreach ( $engines as $engine ) {
if ( 'redis' === $engine && $this->connect_redis() ) {
$this->engine = 'redis';
return;
}
if ( 'memcached' === $engine && $this->connect_memcached() ) {
$this->engine = 'memcached';
return;
}
}
}

private function connect_redis(): bool {
if ( ! class_exists( 'Redis' ) ) {
return false;
}
try {
$redis  = new Redis();
$host   = ( ! empty( $this->options['redis_tls'] ) ? 'tls://' : '' ) . (string) $this->options['redis_host'];
$method = ! empty( $this->options['redis_persistent'] ) && method_exists( $redis, 'pconnect' ) ? 'pconnect' : 'connect';
if ( ! $redis->{$method}( $host, (int) $this->options['redis_port'], (float) $this->options['redis_timeout'] ) ) {
return false;
}
if ( '' !== (string) $this->options['redis_password'] && ! $redis->auth( (string) $this->options['redis_password'] ) ) {
return false;
}
if ( (int) $this->options['redis_database'] > 0 && ! $redis->select( (int) $this->options['redis_database'] ) ) {
return false;
}
$this->client = $redis;
return true;
} catch ( Throwable $e ) {
$this->debug_log( 'Redis drop-in connection failed: ' . $e->getMessage() );
return false;
}
}

private function connect_memcached(): bool {
if ( ! class_exists( 'Memcached' ) ) {
return false;
}
try {
$persistent_id = ! empty( $this->options['memcached_persistent'] ) ? 'smart_hybrid_cache' : '';
$memcached     = '' !== $persistent_id ? new Memcached( $persistent_id ) : new Memcached();
if ( empty( $memcached->getServerList() ) ) {
$memcached->addServer( (string) $this->options['memcached_host'], (int) $this->options['memcached_port'] );
}
if ( empty( $memcached->getVersion() ) ) {
return false;
}
$this->client = $memcached;
return true;
} catch ( Throwable $e ) {
$this->debug_log( 'Memcached drop-in connection failed: ' . $e->getMessage() );
return false;
}
}

public function add( string $key, mixed $data, string $group = 'default', int $expire = 0 ): bool {
if ( $this->get( $key, $group, false, $found ) || $found ) {
return false;
}
return $this->set( $key, $data, $group, $expire, 'add' );
}

public function set( string $key, mixed $data, string $group = 'default', int $expire = 0, string $mode = 'set' ): bool {
$group = $this->sanitize_group( $group );
$key   = (string) $key;
$this->ensure_group( $group );
$this->cache[ $group ][ $key ] = $data;
if ( $this->is_non_persistent_group( $group ) || 'none' === $this->engine ) {
return true;
}
$ttl   = $expire > 0 ? $expire : (int) $this->options['default_ttl'];
$p_key = $this->persistent_key( $key, $group );
$value = $this->pack( $data );
try {
if ( 'redis' === $this->engine ) {
if ( 'add' === $mode ) {
$args = array( 'nx' );
if ( $ttl > 0 ) {
$args['ex'] = $ttl;
}
return (bool) $this->client->set( $p_key, $value, $args );
}
if ( 'replace' === $mode ) {
$args = array( 'xx' );
if ( $ttl > 0 ) {
$args['ex'] = $ttl;
}
return (bool) $this->client->set( $p_key, $value, $args );
}
return $ttl > 0 ? (bool) $this->client->setex( $p_key, $ttl, $value ) : (bool) $this->client->set( $p_key, $value );
}
if ( 'memcached' === $this->engine ) {
return (bool) $this->client->{$mode}( $p_key, $value, $ttl );
}
} catch ( Throwable $e ) {
$this->debug_log( 'Set failed: ' . $e->getMessage() );
}
return true;
}

public function replace( string $key, mixed $data, string $group = 'default', int $expire = 0 ): bool {
if ( ! $this->get( $key, $group, false, $found ) && ! $found ) {
return false;
}
return $this->set( $key, $data, $group, $expire, 'replace' );
}

public function get( string $key, string $group = 'default', bool $force = false, ?bool &$found = null ): mixed {
$group = $this->sanitize_group( $group );
$key   = (string) $key;
if ( ! $force && array_key_exists( $group, $this->cache ) && array_key_exists( $key, $this->cache[ $group ] ) ) {
++$this->cache_hits;
$found = true;
return $this->cache[ $group ][ $key ];
}
if ( $this->is_non_persistent_group( $group ) || 'none' === $this->engine ) {
++$this->cache_misses;
$found = false;
return false;
}
try {
$value = $this->client->get( $this->persistent_key( $key, $group ) );
$hit   = false !== $value;
if ( 'memcached' === $this->engine && defined( 'Memcached::RES_NOTFOUND' ) && $this->client->getResultCode() === Memcached::RES_NOTFOUND ) {
$hit = false;
}
if ( $hit ) {
$data = $this->unpack( $value );
$this->ensure_group( $group );
$this->cache[ $group ][ $key ] = $data;
++$this->cache_hits;
$found = true;
return $data;
}
} catch ( Throwable $e ) {
$this->debug_log( 'Get failed: ' . $e->getMessage() );
}
++$this->cache_misses;
$found = false;
return false;
}

public function delete( string $key, string $group = 'default', bool $deprecated = false ): bool {
$group = $this->sanitize_group( $group );
$key   = (string) $key;
if ( isset( $this->cache[ $group ] ) ) {
				unset( $this->cache[ $group ][ $key ] );
			}
if ( $this->is_non_persistent_group( $group ) || 'none' === $this->engine ) {
return true;
}
try {
if ( 'redis' === $this->engine ) {
return (bool) $this->client->del( $this->persistent_key( $key, $group ) );
}
if ( 'memcached' === $this->engine ) {
return (bool) $this->client->delete( $this->persistent_key( $key, $group ) );
}
} catch ( Throwable $e ) {
$this->debug_log( 'Delete failed: ' . $e->getMessage() );
}
return true;
}

public function flush(): bool {
$this->cache = array();
if ( 'redis' === $this->engine ) {
return $this->flush_redis_prefix();
}
if ( 'memcached' === $this->engine ) {
return (bool) $this->client->flush();
}
return true;
}

public function incr( string $key, int $offset = 1, string $group = 'default' ): int|false {
$value = $this->get( $key, $group, false, $found );
if ( ! $found ) {
$value = 0;
}
$value = max( 0, (int) $value + $offset );
return $this->set( $key, $value, $group ) ? $value : false;
}

public function decr( string $key, int $offset = 1, string $group = 'default' ): int|false {
return $this->incr( $key, -abs( $offset ), $group );
}

public function get_multiple( array $keys, string $group = 'default', bool $force = false ): array {
$values = array();
foreach ( $keys as $key ) {
$values[ $key ] = $this->get( (string) $key, $group, $force );
}
return $values;
}

public function set_multiple( array $data, string $group = 'default', int $expire = 0 ): bool {
$result = true;
foreach ( $data as $key => $value ) {
$result = $this->set( (string) $key, $value, $group, $expire ) && $result;
}
return $result;
}

public function delete_multiple( array $keys, string $group = 'default' ): bool {
$result = true;
foreach ( $keys as $key ) {
$result = $this->delete( (string) $key, $group ) && $result;
}
return $result;
}

public function switch_to_blog( int $blog_id ): void {
			$this->blog_id = $blog_id;
			$this->cache   = array();
		}

public function add_global_groups( array|string $groups ): void {
foreach ( (array) $groups as $group ) {
$this->global_groups[] = (string) $group;
}
$this->global_groups = array_values( array_unique( $this->global_groups ) );
}

public function add_non_persistent_groups( array|string $groups ): void {
foreach ( (array) $groups as $group ) {
$this->non_persistent_groups[] = (string) $group;
}
$this->non_persistent_groups = array_values( array_unique( $this->non_persistent_groups ) );
}

public function stats(): void {
echo htmlspecialchars( 'Smart Hybrid Cache hits: ' . $this->cache_hits . ', misses: ' . $this->cache_misses . ', engine: ' . $this->engine, ENT_QUOTES, 'UTF-8' );
}

private function ensure_group( string $group ): void {
			if ( ! isset( $this->cache[ $group ] ) || ! is_array( $this->cache[ $group ] ) ) {
				$this->cache[ $group ] = array();
			}
		}

		private function persistent_key( string $key, string $group ): string {
$prefix = preg_replace( '/[^A-Za-z0-9_:-]/', '_', (string) $this->options['key_prefix'] );
$scope  = in_array( $group, $this->global_groups, true ) ? 'global' : 'blog_' . $this->blog_id;
if ( function_exists( 'is_multisite' ) && is_multisite() && defined( 'COOKIEHASH' ) ) {
$scope = COOKIEHASH . ':' . $scope;
}
return substr( $prefix . $scope . ':' . $group . ':' . md5( $key ), 0, 250 );
}

private function sanitize_group( string $group ): string {
return '' === $group ? 'default' : $group;
}

private function is_non_persistent_group( string $group ): bool {
return in_array( $group, $this->non_persistent_groups, true );
}

private function pack( mixed $value ): string {
return serialize( array( 'value' => $value ) );
}

private function unpack( mixed $value ): mixed {
$data = is_string( $value ) ? @unserialize( $value, array( 'allowed_classes' => true ) ) : false;
return is_array( $data ) && array_key_exists( 'value', $data ) ? $data['value'] : false;
}

private function flush_redis_prefix(): bool {
try {
$iterator = null;
$prefix   = preg_replace( '/[^A-Za-z0-9_:-]/', '_', (string) $this->options['key_prefix'] );
do {
$keys = $this->client->scan( $iterator, $prefix . '*', 250 );
if ( false !== $keys && ! empty( $keys ) ) {
$this->client->del( $keys );
}
} while ( $iterator > 0 );
return true;
} catch ( Throwable $e ) {
$this->debug_log( 'Redis prefix flush failed: ' . $e->getMessage() );
return false;
}
}

private function debug_log( string $message ): void {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
error_log( 'Smart Hybrid Cache: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}
}
}
}

$GLOBALS['wp_object_cache'] = new WP_Object_Cache();

function wp_cache_init(): void {
$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) { return $GLOBALS['wp_object_cache']->add( (string) $key, $data, (string) $group, (int) $expire ); }
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) { return $GLOBALS['wp_object_cache']->set( (string) $key, $data, (string) $group, (int) $expire ); }
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) { return $GLOBALS['wp_object_cache']->replace( (string) $key, $data, (string) $group, (int) $expire ); }
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) { return $GLOBALS['wp_object_cache']->get( (string) $key, (string) $group, (bool) $force, $found ); }
function wp_cache_delete( $key, $group = '', $deprecated = false ) { return $GLOBALS['wp_object_cache']->delete( (string) $key, (string) $group, (bool) $deprecated ); }
function wp_cache_flush() { return $GLOBALS['wp_object_cache']->flush(); }
function wp_cache_incr( $key, $offset = 1, $group = '' ) { return $GLOBALS['wp_object_cache']->incr( (string) $key, (int) $offset, (string) $group ); }
function wp_cache_decr( $key, $offset = 1, $group = '' ) { return $GLOBALS['wp_object_cache']->decr( (string) $key, (int) $offset, (string) $group ); }
function wp_cache_get_multiple( $keys, $group = '', $force = false ) { return $GLOBALS['wp_object_cache']->get_multiple( (array) $keys, (string) $group, (bool) $force ); }
function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) { $result = true; foreach ( $data as $key => $value ) { $result = wp_cache_add( (string) $key, $value, (string) $group, (int) $expire ) && $result; } return $result; }
function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) { return $GLOBALS['wp_object_cache']->set_multiple( $data, (string) $group, (int) $expire ); }
function wp_cache_delete_multiple( array $keys, $group = '' ) { return $GLOBALS['wp_object_cache']->delete_multiple( $keys, (string) $group ); }
function wp_cache_add_global_groups( $groups ) { $GLOBALS['wp_object_cache']->add_global_groups( $groups ); }
function wp_cache_add_non_persistent_groups( $groups ) { $GLOBALS['wp_object_cache']->add_non_persistent_groups( $groups ); }
function wp_cache_switch_to_blog( $blog_id ) { $GLOBALS['wp_object_cache']->switch_to_blog( (int) $blog_id ); }
function wp_cache_close() { return true; }
function wp_cache_stats() { $GLOBALS['wp_object_cache']->stats(); }
function wp_cache_flush_runtime() { $GLOBALS['wp_object_cache'] = new WP_Object_Cache(); return true; }
function wp_cache_supports( $feature ) { return in_array( $feature, array( 'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple', 'flush_runtime' ), true ); }
