=== Smart Hybrid Cache ===
Contributors: jomardyan
Tags: cache, object cache, redis, memcached, performance
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Persistent WordPress object caching with Redis or Memcached, safe fallbacks, admin controls, and WP-CLI commands.

== Description ==

Smart Hybrid Cache is a simple persistent object caching plugin for WordPress. It supports Redis and Memcached through their PHP extensions and uses the WordPress Object Cache API wherever possible.

The Redis or Memcached server must be installed and managed separately. The matching PHP extension must also be available on the web server. Persistent object caching requires installing the object-cache.php drop-in from the plugin settings page or WP-CLI.

Features:

* Redis, Memcached, Auto, and Disabled engine modes.
* Safe fallback to default WordPress object cache behavior when services or extensions are unavailable.
* Tabbed admin settings with connection tests, flush button, monitoring cards, event logging, and extension notices.
* Safe drop-in installation that does not overwrite another object-cache.php unless confirmed.
* Conservative invalidation hooks for posts, terms, comments, theme switches, and plugin updates.
* WP-CLI commands for status, test, flush, enable, disable, install-dropin, remove-dropin, and diagnostics.
* WordPress Site Health integration with debug information.
* Configurable non-persistent groups and additional global groups.
* Multisite-aware cache key prefixing.

Redis passwords are saved in the WordPress options table with autoload disabled. Protect database access and prefer network-level controls where possible.

== Installation ==

1. Install and start Redis or Memcached on your server, or use a managed service.
2. Install the matching PHP extension: Redis requires ext-redis; Memcached requires ext-memcached.
3. Upload the `smart-hybrid-cache` folder to `/wp-content/plugins/`, or zip the folder and upload it through Plugins > Add New > Upload Plugin.
4. Activate Smart Hybrid Cache.
5. Go to Settings > Smart Hybrid Cache.
6. Choose Auto, Redis, or Memcached and save settings.
7. Test the connection.
8. Install the object cache drop-in to enable persistent object caching.

== Frequently Asked Questions ==

= Does this install Redis or Memcached for me? =

No. The server software must be installed separately by your host, system administrator, or managed WordPress provider.

= What PHP extensions are required? =

Redis requires the Redis PHP extension. Memcached requires the Memcached PHP extension. The plugin detects missing extensions and falls back safely.

= Does persistent object caching work without object-cache.php? =

No. WordPress persistent object caching requires an object-cache.php drop-in in wp-content. The plugin can install one safely from the settings page or WP-CLI.

= Will this overwrite another caching plugin's object-cache.php? =

Not by default. Smart Hybrid Cache only overwrites a drop-in it created unless an administrator explicitly confirms replacement.

= Does this do full page caching? =

No. This version focuses on persistent object caching only and does not modify advanced-cache.php.

= How do I remove the plugin safely? =

Open Settings > Smart Hybrid Cache, remove the object cache drop-in if desired, then deactivate and uninstall the plugin. If cleanup on uninstall is enabled, the plugin removes only its own drop-in.

== Screenshots ==

1. Tabbed settings page with Redis, Memcached, cache behavior, action buttons, and monitoring panel.
2. Extension notices and connection status details.

== Changelog ==

= 1.1.0 =
* Release packages can be versioned from CI release tags or manual workflow input.
* Improved object-cache.php detection with clear messages when persistent cache is already available through Smart Hybrid Cache or another plugin.
* Refreshed admin dashboard overview, action cards, and monitoring styles.
* Configurable non-persistent and additional global cache groups (Redis Object Cache parity).
* WordPress Site Health test and Site Health debug information surface plugin status.
* Diagnostics export available from the admin Actions tab and via `wp smart-cache diagnostics`.

= 1.0.0 =
* Initial release with Redis and Memcached persistent object cache support.

== Upgrade Notice ==

= 1.1.0 =
Release package metadata, cache availability notices, and the admin experience were improved.

= 1.0.0 =
Initial release. Ensure Redis or Memcached and the matching PHP extension are available before enabling persistent object caching.
