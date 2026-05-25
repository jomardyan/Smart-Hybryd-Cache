<?php
/**
 * Runtime status collection.
 *
 * @package SmartHybridCache
 */

defined( 'ABSPATH' ) || exit;

class Smart_Hybrid_Cache_Health_Check {
public static function get_status( ?Smart_Hybrid_Cache_Manager $manager = null ): array {
$options = Smart_Hybrid_Cache_Settings::get_options();
$manager = $manager ?: new Smart_Hybrid_Cache_Manager();
$dropin  = Smart_Hybrid_Cache_Dropin_Installer::status();
$redis   = new Smart_Hybrid_Cache_Redis_Client();
$mem     = new Smart_Hybrid_Cache_Memcached_Client();

return array(
'selected_engine'       => $options['engine'],
'active_engine'         => $manager->get_active_engine(),
'redis_available'       => $redis->is_available(),
'memcached_available'   => $mem->is_available(),
'dropin'                => $dropin,
'connection_status'     => 'none' !== $manager->get_active_engine() ? __( 'Connected', 'smart-hybrid-cache' ) : __( 'Not connected', 'smart-hybrid-cache' ),
'last_error'            => $options['last_error'],
'cache_prefix'          => $options['key_prefix'],
'current_ttl'           => (int) $options['default_ttl'],
'multisite'             => is_multisite(),
'advanced_cache_exists' => $dropin['advanced'],
'stats'                 => $manager->stats(),
);
}
}
