<?php
/**
 * Admin UI.
 *
 * @package SmartHybridCache
 */

defined( 'ABSPATH' ) || exit;

class Smart_Hybrid_Cache_Admin {
private Smart_Hybrid_Cache_Manager $manager;
private string $page = 'smart-hybrid-cache';

public function __construct( Smart_Hybrid_Cache_Manager $manager ) {
$this->manager = $manager;
}

public function hooks(): void {
add_action( 'admin_init', array( 'Smart_Hybrid_Cache_Settings', 'register' ) );
add_action( 'admin_menu', array( $this, 'menu' ) );
add_action( 'admin_notices', array( $this, 'notices' ) );
add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
add_action( 'admin_post_smart_hybrid_cache_action', array( $this, 'handle_action' ) );
}

public function menu(): void {
add_options_page(
esc_html__( 'Smart Hybrid Cache', 'smart-hybrid-cache' ),
esc_html__( 'Smart Hybrid Cache', 'smart-hybrid-cache' ),
'manage_options',
$this->page,
array( $this, 'render' )
);
}

public function assets( string $hook ): void {
if ( 'settings_page_' . $this->page !== $hook ) {
return;
}
wp_enqueue_style( 'smart-hybrid-cache-admin', SMART_HYBRID_CACHE_URL . 'assets/admin.css', array(), SMART_HYBRID_CACHE_VERSION );
wp_enqueue_script( 'smart-hybrid-cache-admin', SMART_HYBRID_CACHE_URL . 'assets/admin.js', array(), SMART_HYBRID_CACHE_VERSION, true );
}

public function notices(): void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}
$options = Smart_Hybrid_Cache_Settings::get_options();
$engine  = $options['engine'];
if ( empty( $options['enable_dropin'] ) || 'disabled' === $engine ) {
return;
}
$redis   = class_exists( 'Redis' );
$mem     = class_exists( 'Memcached' );
if ( 'auto' === $engine && ! $redis && ! $mem ) {
printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'Smart Hybrid Cache: No supported PHP cache extension is available. Install Redis or Memcached before enabling the object cache drop-in.', 'smart-hybrid-cache' ) );
return;
}
if ( 'redis' === $engine && ! $redis ) {
printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'Smart Hybrid Cache: Redis PHP extension is not available.', 'smart-hybrid-cache' ) );
}
if ( 'memcached' === $engine && ! $mem ) {
printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'Smart Hybrid Cache: Memcached PHP extension is not available.', 'smart-hybrid-cache' ) );
}
}

public function handle_action(): void {
if ( ! current_user_can( 'manage_options' ) ) {
wp_die( esc_html__( 'You are not allowed to manage cache settings.', 'smart-hybrid-cache' ) );
}
check_admin_referer( 'smart_hybrid_cache_action' );
$action = isset( $_POST['shc_action'] ) ? sanitize_key( wp_unslash( $_POST['shc_action'] ) ) : '';
$force  = ! empty( $_POST['force_replace'] );
$type   = 'updated';
$text   = __( 'Action completed.', 'smart-hybrid-cache' );

switch ( $action ) {
case 'test_redis':
$result = $this->manager->test( 'redis' );
$type   = $result['ok'] ? 'updated' : 'error';
$text   = $result['message'];
break;
case 'test_memcached':
$result = $this->manager->test( 'memcached' );
$type   = $result['ok'] ? 'updated' : 'error';
$text   = $result['message'];
break;
case 'flush':
$text = $this->manager->flush_safe() ? __( 'Cache flushed.', 'smart-hybrid-cache' ) : __( 'Cache flush could not be completed.', 'smart-hybrid-cache' );
$type = 'updated';
break;
case 'install_dropin':
$result = Smart_Hybrid_Cache_Dropin_Installer::install( $force );
if ( is_wp_error( $result ) ) {
$type = 'error';
$text = $result->get_error_message();
} else {
$text = __( 'Object cache drop-in installed.', 'smart-hybrid-cache' );
Smart_Hybrid_Cache_Logger::log( 'dropin_installed', $text );
}
break;
case 'show_status':
$text = __( 'Status refreshed.', 'smart-hybrid-cache' );
break;
case 'remove_dropin':
$result = Smart_Hybrid_Cache_Dropin_Installer::remove( $force );
if ( is_wp_error( $result ) ) {
$type = 'error';
$text = $result->get_error_message();
} else {
$text = __( 'Object cache drop-in removed.', 'smart-hybrid-cache' );
Smart_Hybrid_Cache_Logger::log( 'dropin_removed', $text );
}
break;
default:
$type = 'error';
$text = __( 'Unknown action.', 'smart-hybrid-cache' );
}

$redirect = add_query_arg(
array(
'page'        => $this->page,
'shc_notice'  => rawurlencode( $text ),
'shc_type'    => $type,
),
admin_url( 'options-general.php' )
);
wp_safe_redirect( $redirect );
exit;
}

public function render(): void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}
$options = Smart_Hybrid_Cache_Settings::get_options();
$status  = Smart_Hybrid_Cache_Health_Check::get_status( $this->manager );
?>
<div class="wrap smart-hybrid-cache">
<h1><?php esc_html_e( 'Smart Hybrid Cache', 'smart-hybrid-cache' ); ?></h1>
<?php $this->render_redirect_notice(); ?>
<?php $this->render_tabs(); ?>
<form method="post" action="options.php">
<?php settings_fields( 'smart_hybrid_cache' ); ?>
<div class="shc-tab-panel" id="shc-tab-general" role="tabpanel" aria-labelledby="shc-tab-button-general">
<h2><?php esc_html_e( 'Cache Engine', 'smart-hybrid-cache' ); ?></h2>
<table class="form-table" role="presentation"><tbody>
<?php $this->select_row( 'engine', __( 'Cache engine', 'smart-hybrid-cache' ), array( 'auto' => 'Auto', 'redis' => 'Redis', 'memcached' => 'Memcached', 'disabled' => 'Disabled' ), $options['engine'] ); ?>
</tbody></table>
</div>

<div class="shc-tab-panel" id="shc-tab-redis" role="tabpanel" aria-labelledby="shc-tab-button-redis" hidden>
<h2><?php esc_html_e( 'Redis Settings', 'smart-hybrid-cache' ); ?></h2>
<table class="form-table" role="presentation"><tbody>
<?php $this->text_row( 'redis_host', __( 'Host', 'smart-hybrid-cache' ), $options['redis_host'] ); ?>
<?php $this->number_row( 'redis_port', __( 'Port', 'smart-hybrid-cache' ), $options['redis_port'], 1, 65535 ); ?>
<?php $this->password_row( 'redis_password', __( 'Password', 'smart-hybrid-cache' ), $options['redis_password'] ); ?>
<?php $this->number_row( 'redis_database', __( 'Database index', 'smart-hybrid-cache' ), $options['redis_database'], 0, 255 ); ?>
<?php $this->number_row( 'redis_timeout', __( 'Timeout', 'smart-hybrid-cache' ), $options['redis_timeout'], 0.1, 10, '0.1' ); ?>
<?php $this->checkbox_row( 'redis_tls', __( 'TLS enabled', 'smart-hybrid-cache' ), $options['redis_tls'] ); ?>
</tbody></table>
<p class="description"><?php esc_html_e( 'Redis passwords are stored in the WordPress options table with autoload disabled. Use server-level protections for production secrets.', 'smart-hybrid-cache' ); ?></p>
</div>

<div class="shc-tab-panel" id="shc-tab-memcached" role="tabpanel" aria-labelledby="shc-tab-button-memcached" hidden>
<h2><?php esc_html_e( 'Memcached Settings', 'smart-hybrid-cache' ); ?></h2>
<table class="form-table" role="presentation"><tbody>
<?php $this->text_row( 'memcached_host', __( 'Host', 'smart-hybrid-cache' ), $options['memcached_host'] ); ?>
<?php $this->number_row( 'memcached_port', __( 'Port', 'smart-hybrid-cache' ), $options['memcached_port'], 1, 65535 ); ?>
<?php $this->checkbox_row( 'memcached_persistent', __( 'Persistent connection enabled', 'smart-hybrid-cache' ), $options['memcached_persistent'] ); ?>
</tbody></table>
</div>

<div class="shc-tab-panel" id="shc-tab-behavior" role="tabpanel" aria-labelledby="shc-tab-button-behavior" hidden>
<h2><?php esc_html_e( 'Cache Behavior', 'smart-hybrid-cache' ); ?></h2>
<table class="form-table" role="presentation"><tbody>
<?php $this->number_row( 'default_ttl', __( 'Default TTL', 'smart-hybrid-cache' ), $options['default_ttl'], 0, YEAR_IN_SECONDS ); ?>
<?php $this->text_row( 'key_prefix', __( 'Key prefix', 'smart-hybrid-cache' ), $options['key_prefix'] ); ?>
<?php $this->checkbox_row( 'enable_dropin', __( 'Enable persistent object cache drop-in', 'smart-hybrid-cache' ), $options['enable_dropin'] ); ?>
<?php $this->checkbox_row( 'flush_on_post_update', __( 'Flush cache on post update', 'smart-hybrid-cache' ), $options['flush_on_post_update'] ); ?>
<?php $this->checkbox_row( 'flush_on_theme_switch', __( 'Flush cache on theme switch', 'smart-hybrid-cache' ), $options['flush_on_theme_switch'] ); ?>
<?php $this->checkbox_row( 'flush_on_plugin_update', __( 'Flush cache on plugin update', 'smart-hybrid-cache' ), $options['flush_on_plugin_update'] ); ?>
<?php $this->checkbox_row( 'cleanup_dropin_uninstall', __( 'Cleanup drop-in on uninstall', 'smart-hybrid-cache' ), $options['cleanup_dropin_uninstall'] ); ?>
<?php $this->checkbox_row( 'cleanup_dropin_deactivate', __( 'Cleanup drop-in on deactivation', 'smart-hybrid-cache' ), $options['cleanup_dropin_deactivate'] ); ?>
<?php $this->checkbox_row( 'enable_logging', __( 'Enable event logging', 'smart-hybrid-cache' ), $options['enable_logging'] ); ?>
</tbody></table>
</div>
<div class="shc-settings-submit">
<?php submit_button( __( 'Save Settings', 'smart-hybrid-cache' ) ); ?>
</div>
</form>

<div class="shc-tab-panel" id="shc-tab-actions" role="tabpanel" aria-labelledby="shc-tab-button-actions" hidden>
<?php $this->render_actions(); ?>
</div>
<div class="shc-tab-panel" id="shc-tab-monitoring" role="tabpanel" aria-labelledby="shc-tab-button-monitoring" hidden>
<?php $this->render_status( $status ); ?>
</div>
</div>
<?php
}

private function render_tabs(): void {
$tabs = array(
'general'    => __( 'General', 'smart-hybrid-cache' ),
'redis'      => __( 'Redis', 'smart-hybrid-cache' ),
'memcached'  => __( 'Memcached', 'smart-hybrid-cache' ),
'behavior'   => __( 'Behavior', 'smart-hybrid-cache' ),
'actions'    => __( 'Actions', 'smart-hybrid-cache' ),
'monitoring' => __( 'Monitoring', 'smart-hybrid-cache' ),
);
?>
<nav class="nav-tab-wrapper shc-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Smart Hybrid Cache settings sections', 'smart-hybrid-cache' ); ?>">
<?php foreach ( $tabs as $slug => $label ) : ?>
<button type="button" id="shc-tab-button-<?php echo esc_attr( $slug ); ?>" class="nav-tab<?php echo 'general' === $slug ? ' nav-tab-active' : ''; ?>" role="tab" aria-selected="<?php echo 'general' === $slug ? 'true' : 'false'; ?>" aria-controls="shc-tab-<?php echo esc_attr( $slug ); ?>" data-shc-tab="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
<?php endforeach; ?>
</nav>
<?php
}

private function render_redirect_notice(): void {
if ( empty( $_GET['shc_notice'] ) ) {
return;
}
$type = isset( $_GET['shc_type'] ) && 'error' === $_GET['shc_type'] ? 'notice-error' : 'notice-success';
printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( sanitize_text_field( wp_unslash( $_GET['shc_notice'] ) ) ) );
}

private function render_actions(): void {
$actions = array(
'test_redis'     => __( 'Test Redis connection', 'smart-hybrid-cache' ),
'test_memcached' => __( 'Test Memcached connection', 'smart-hybrid-cache' ),
'flush'          => __( 'Flush all cache', 'smart-hybrid-cache' ),
'install_dropin' => __( 'Install object cache drop-in', 'smart-hybrid-cache' ),
'remove_dropin'  => __( 'Remove object cache drop-in', 'smart-hybrid-cache' ),
'show_status'    => __( 'Show current status', 'smart-hybrid-cache' ),
);
?>
<h2><?php esc_html_e( 'Admin Actions', 'smart-hybrid-cache' ); ?></h2>
<div class="shc-actions">
<?php foreach ( $actions as $action => $label ) : ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'smart_hybrid_cache_action' ); ?>
<input type="hidden" name="action" value="smart_hybrid_cache_action" />
<input type="hidden" name="shc_action" value="<?php echo esc_attr( $action ); ?>" />
<?php if ( in_array( $action, array( 'install_dropin', 'remove_dropin' ), true ) ) : ?>
<label><input type="checkbox" name="force_replace" value="1" /> <?php esc_html_e( 'Confirm replacement/removal of a non-plugin drop-in', 'smart-hybrid-cache' ); ?></label>
<?php endif; ?>
<?php submit_button( $label, 'secondary', 'submit', false ); ?>
</form>
<?php endforeach; ?>
</div>
<?php
}

private function render_status( array $status ): void {
$monitoring = $status['monitoring'];
$rows = array(
__( 'Selected engine', 'smart-hybrid-cache' )                 => $status['selected_engine'],
__( 'Active engine', 'smart-hybrid-cache' )                   => $status['active_engine'],
__( 'PHP Redis extension availability', 'smart-hybrid-cache' ) => $status['redis_available'] ? __( 'Available', 'smart-hybrid-cache' ) : __( 'Missing', 'smart-hybrid-cache' ),
__( 'PHP Memcached extension availability', 'smart-hybrid-cache' ) => $status['memcached_available'] ? __( 'Available', 'smart-hybrid-cache' ) : __( 'Missing', 'smart-hybrid-cache' ),
__( 'Drop-in status', 'smart-hybrid-cache' )                  => $status['dropin']['exists'] ? __( 'Installed', 'smart-hybrid-cache' ) : __( 'Not installed', 'smart-hybrid-cache' ),
__( 'Connection status', 'smart-hybrid-cache' )                => $status['connection_status'],
__( 'Last connection error', 'smart-hybrid-cache' )            => $status['last_error'] ?: __( 'None', 'smart-hybrid-cache' ),
__( 'Cache prefix', 'smart-hybrid-cache' )                    => $status['cache_prefix'],
__( 'Current TTL', 'smart-hybrid-cache' )                     => (string) $status['current_ttl'],
__( 'Object cache file owner status', 'smart-hybrid-cache' )   => $status['dropin']['owner_label'],
__( 'Multisite status', 'smart-hybrid-cache' )                => $status['multisite'] ? __( 'Enabled', 'smart-hybrid-cache' ) : __( 'Single site', 'smart-hybrid-cache' ),
__( 'advanced-cache.php detected', 'smart-hybrid-cache' )      => $status['advanced_cache_exists'] ? __( 'Yes', 'smart-hybrid-cache' ) : __( 'No', 'smart-hybrid-cache' ),
);
?>
<h2><?php esc_html_e( 'Monitoring', 'smart-hybrid-cache' ); ?></h2>
<div class="shc-monitoring-grid">
<?php
$cards = array(
__( 'Connection', 'smart-hybrid-cache' ) => $monitoring['status'],
__( 'Memory used', 'smart-hybrid-cache' ) => $monitoring['memory'],
__( 'Uptime', 'smart-hybrid-cache' ) => $monitoring['uptime'],
__( 'Hit rate', 'smart-hybrid-cache' ) => $monitoring['hit_rate'],
__( 'Current keys', 'smart-hybrid-cache' ) => $monitoring['key_count'],
__( 'Connections', 'smart-hybrid-cache' ) => $monitoring['connections'],
);
foreach ( $cards as $label => $value ) :
?>
<div class="shc-monitoring-card">
<span><?php echo esc_html( $label ); ?></span>
<strong><?php echo esc_html( (string) $value ); ?></strong>
</div>
<?php endforeach; ?>
</div>

<h3><?php esc_html_e( 'Current Status', 'smart-hybrid-cache' ); ?></h3>
<table class="widefat striped shc-status"><tbody>
<?php foreach ( $rows as $label => $value ) : ?>
<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( $value ); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php if ( ! empty( $status['stats'] ) ) : ?>
<h3><?php esc_html_e( 'Basic Cache Statistics', 'smart-hybrid-cache' ); ?></h3>
<pre><?php echo esc_html( wp_json_encode( $status['stats'], JSON_PRETTY_PRINT ) ); ?></pre>
<?php endif; ?>
<h3><?php esc_html_e( 'Recent Event Log', 'smart-hybrid-cache' ); ?></h3>
<?php if ( empty( $status['log_events'] ) ) : ?>
<p><?php esc_html_e( 'No cache events have been logged yet.', 'smart-hybrid-cache' ); ?></p>
<?php else : ?>
<table class="widefat striped shc-log"><thead><tr>
<th scope="col"><?php esc_html_e( 'Time', 'smart-hybrid-cache' ); ?></th>
<th scope="col"><?php esc_html_e( 'Event', 'smart-hybrid-cache' ); ?></th>
<th scope="col"><?php esc_html_e( 'Message', 'smart-hybrid-cache' ); ?></th>
<th scope="col"><?php esc_html_e( 'Context', 'smart-hybrid-cache' ); ?></th>
</tr></thead><tbody>
<?php foreach ( $status['log_events'] as $event ) : ?>
<tr>
<td><?php echo esc_html( (string) ( $event['time'] ?? '' ) ); ?></td>
<td><?php echo esc_html( (string) ( $event['event'] ?? '' ) ); ?></td>
<td><?php echo esc_html( (string) ( $event['message'] ?? '' ) ); ?></td>
<td><?php echo esc_html( wp_json_encode( $event['context'] ?? array() ) ); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<?php
}

private function field_name( string $key ): string {
return SMART_HYBRID_CACHE_OPTION . '[' . $key . ']';
}

private function text_row( string $key, string $label, mixed $value ): void {
printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" class="regular-text" type="text" name="%3$s" value="%4$s" /></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( $this->field_name( $key ) ), esc_attr( (string) $value ) );
}

private function password_row( string $key, string $label, mixed $value ): void {
printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" class="regular-text" type="password" autocomplete="new-password" name="%3$s" value="%4$s" /></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( $this->field_name( $key ) ), esc_attr( (string) $value ) );
}

private function number_row( string $key, string $label, mixed $value, float $min, float $max, string $step = '1' ): void {
printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" type="number" min="%3$s" max="%4$s" step="%5$s" name="%6$s" value="%7$s" /></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( (string) $min ), esc_attr( (string) $max ), esc_attr( $step ), esc_attr( $this->field_name( $key ) ), esc_attr( (string) $value ) );
}

private function checkbox_row( string $key, string $label, mixed $checked ): void {
printf( '<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s" value="1" %3$s /> %4$s</label></td></tr>', esc_html( $label ), esc_attr( $this->field_name( $key ) ), checked( (bool) $checked, true, false ), esc_html__( 'Enabled', 'smart-hybrid-cache' ) );
}

private function select_row( string $key, string $label, array $choices, string $selected ): void {
printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><select id="%1$s" name="%3$s">', esc_attr( $key ), esc_html( $label ), esc_attr( $this->field_name( $key ) ) );
foreach ( $choices as $value => $choice_label ) {
printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $selected, $value, false ), esc_html( $choice_label ) );
}
echo '</select></td></tr>';
}
}
