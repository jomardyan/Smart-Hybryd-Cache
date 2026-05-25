<?php
/**
 * Plugin Name: Smart Hybrid Cache
 * Plugin URI: https://github.com/jomardyan/Smart-Hybryd-Cache
 * Description: Persistent WordPress object caching with Redis or Memcached, safe fallbacks, admin controls, and WP-CLI support.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Smart Hybrid Cache Contributors
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: smart-hybrid-cache
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SMART_HYBRID_CACHE_VERSION', '1.0.0' );
define( 'SMART_HYBRID_CACHE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SMART_HYBRID_CACHE_URL', plugin_dir_url( __FILE__ ) );
define( 'SMART_HYBRID_CACHE_BASENAME', plugin_basename( __FILE__ ) );
define( 'SMART_HYBRID_CACHE_OPTION', 'smart_hybrid_cache_options' );
define( 'SMART_HYBRID_CACHE_SIGNATURE', 'Smart Hybrid Cache Drop-In' );

require_once SMART_HYBRID_CACHE_PATH . 'includes/class-settings.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-logger.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-redis-client.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-memcached-client.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-dropin-installer.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-cache-manager.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-health-check.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-admin.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-cli.php';
require_once SMART_HYBRID_CACHE_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Smart_Hybrid_Cache_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Smart_Hybrid_Cache_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Smart_Hybrid_Cache_Plugin', 'init' ) );
