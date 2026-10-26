<?php
/* Offline tests for settings, escaping, transport, caching and notifications. */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function register_activation_hook( ...$args ) {}
function __( $value, ...$args ) { return $value; }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function get_option( ...$args ) { return $GLOBALS['options']; }
function wp_mail( ...$args ) { $GLOBALS['emails'][] = $args; return true; }
function register_setting( ...$args ) { $GLOBALS['registration'] = $args; }
function add_settings_section( ...$args ) {}
function add_settings_field( ...$args ) {}
require __DIR__ . '/wp-stubs.php';
require dirname( __DIR__ ) . '/trunk/rc-geo-access.php';
$count = 0;
function expect( $condition, $message ) {
    $GLOBALS['count']++;
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function reset_http() {
    $GLOBALS['test_http_calls'] = $GLOBALS['test_transients'] = array();
    $GLOBALS['test_http_response'] = array( 'response' => array( 'code' => 200 ), 'body' => '{"country_code":"GB","country_code2":"GB","country_name":"United Kingdom"}' );
}
$options = false;
$_SERVER = array( 'REMOTE_ADDR' => '203.0.113.7' );
rc_geo_access_settings_init();
expect( $registration[2]['sanitize_callback'] === 'rc_geo_access_sanitize_settings', 'Settings callback not registered' );
foreach ( array( 'rc_geo_access_email_recipient_render', 'rc_geo_access_email_notification_type_render', 'rc_geo_access_api_provider_render', 'rc_geo_access_status_render', 'rc_geo_access_ipstack_api_key_render', 'rc_geo_access_ipgeolocation_api_key_render', 'rc_geo_access_openlitespeed_local_geoip_render', 'rc_geo_access_restricted_countries_render', 'rc_geo_access_connection_render' ) as $renderer ) {
    ob_start(); $renderer(); ob_end_clean();
}
expect( count( $test_http_calls ) === 0, 'Unconfigured settings triggered an HTTP request' );
$options = array( 'rc_geo_access_status' => 'enabled', 'rc_geo_access_api_provider' => 'openlitespeed', 'rc_geo_access_restricted_countries' => array( 'GB' ) );
$settings = rc_geo_access_sanitize_settings( array(
    'rc_geo_access_status' => 'enabled', 'rc_geo_access_api_provider' => 'openlitespeed',
    'rc_geo_access_restricted_countries' => array( ' gb ', 'GB', 'XX', array(), true, 'US' ),
    'rc_geo_access_email_recipient' => "bad@example.test\r\nBcc: victim@example.test",
    'rc_geo_access_ipstack_api_key' => array(), 'unexpected_key' => 'discard me',
) );
expect( $settings['rc_geo_access_restricted_countries'] === array( 'GB', 'US' ), 'Country validation failed' );
expect( $settings['rc_geo_access_email_recipient'] === '', 'Email injection accepted' );
expect( $settings['rc_geo_access_ipstack_api_key'] === '', 'Non-string key accepted' );
expect( ! isset( $settings['unexpected_key'] ), 'Unknown setting retained' );
expect( count( $test_settings_errors ) > 0, 'Invalid email had no feedback' );
$settings = rc_geo_access_sanitize_settings( array( 'rc_geo_access_status' => array(), 'rc_geo_access_api_provider' => array() ) );
expect( $settings['rc_geo_access_status'] === 'enabled', 'Invalid input disabled existing protection' );
expect( $settings['rc_geo_access_api_provider'] === '', 'Invalid provider accepted' );
expect( rc_geo_access_sanitize_settings( false )['rc_geo_access_status'] === 'enabled', 'Malformed submission replaced settings' );
$options['rc_geo_access_ipstack_api_key'] = "test' autofocus onfocus='alert(1)";
ob_start(); rc_geo_access_ipstack_api_key_render(); $html = ob_get_clean();
expect( strpos( $html, "value=\"test' autofocus" ) === false && strpos( $html, '&#039;' ) !== false, 'API field attribute injection' );
$options['rc_geo_access_api_provider'] = 'ipstack';
$options['rc_geo_access_ipstack_api_key'] = 'selected-key';
$options['rc_geo_access_ipgeolocation_api_key'] = 'unused-key';
reset_http();
ob_start(); rc_geo_access_ipgeolocation_api_key_render(); ob_end_clean();
expect( count( $test_http_calls ) === 0, 'Inactive provider field performed a lookup with the wrong key' );

reset_http();
$result = rc_geo_access_lookup_ip( '203.0.113.7', 'key&other=value', 'ipstack' );
expect( $result['country_code'] === 'GB', 'Valid HTTPS result rejected' );
list( $url, $args ) = $test_http_calls[0];
expect( strpos( $url, 'https://api.ipstack.com/' ) === 0 && strpos( $url, 'key%26other%3Dvalue' ) !== false, 'Endpoint/key encoding unsafe' );
expect( $args['timeout'] === 5 && $args['redirection'] === 0 && $args['sslverify'] === true && $args['limit_response_size'] === 16384, 'HTTP safeguards missing' );
expect( ! isset( $args['headers']['User-Agent'] ), 'Visitor user-agent forwarded' );
rc_geo_access_lookup_ip( '203.0.113.7', 'key&other=value', 'ipstack' );
expect( count( $test_http_calls ) === 1, 'Repeated IP bypassed lookup cache' );
rc_geo_access_lookup_ip( '203.0.113.7', 'new-key', 'ipstack' );
expect( count( $test_http_calls ) === 2, 'New API key reused an old cache entry' );
foreach ( $test_transients as &$item ) { $item['expires'] = 0; } unset( $item );
rc_geo_access_lookup_ip( '203.0.113.7', 'new-key', 'ipstack' );
expect( count( $test_http_calls ) === 3, 'Expired cache prevented a new lookup' );
foreach ( array( 301, 401, 429, 500 ) as $status ) {
    reset_http();
    $test_http_response['response']['code'] = $status;
    $result = rc_geo_access_lookup_ip( '203.0.113.7', 'key', 'ipstack' );
    expect( rc_geo_access_error_handling( $result, 'ipstack' ) !== '', 'Non-200 response allowed a country' );
    rc_geo_access_lookup_ip( '203.0.113.8', 'key', 'ipstack' );
    expect( count( $test_http_calls ) === 1, 'Provider failure did not back off across different IPs' );
}
foreach ( array( '<html>bad</html>', 'null', '{"success":false,"error":{"code":104,"type":"quota"}}' ) as $body ) {
    reset_http(); $test_http_response['body'] = $body;
    expect( rc_geo_access_error_handling( rc_geo_access_lookup_ip( '203.0.113.7', 'key', 'ipstack' ), 'ipstack' ) !== '', 'Malformed/error response accepted' );
}
reset_http(); $test_http_response = new WP_Error( 'http_request_failed', 'private URL with API key' );
$result = rc_geo_access_lookup_ip( '203.0.113.7', 'key', 'ipstack' );
expect( strpos( $result['rc_geo_access_error'], 'private URL' ) === false, 'Transport details leaked' );
reset_http();
rc_geo_access_lookup_ip( 'not-an-ip', 'key', 'ipstack' );
expect( count( $test_http_calls ) === 0, 'Invalid IP reached HTTP client' );
// Missing country is specific to an IP and must not poison other visitors.
$test_http_response['body'] = '{"country_code":null}';
rc_geo_access_lookup_ip( '203.0.113.7', 'key', 'ipstack' );
$test_http_response['body'] = '{"country_code":"GB"}';
expect( rc_geo_access_lookup_ip( '203.0.113.8', 'key', 'ipstack' )['country_code'] === 'GB', 'Unknown IP blocked other countries' );
reset_http();
for ( $i = 1; $i <= 61; $i++ ) { $result = rc_geo_access_lookup_ip( '203.0.113.' . $i, 'key', 'ipstack' ); }
expect( count( $test_http_calls ) === 60 && isset( $result['rc_geo_access_error'] ), 'Lookup budget did not limit rotating IPs' );

$test_transients = $emails = array();
for ( $i = 0; $i < 10; $i++ ) { rc_geo_access_send_notification( 'restriction', 'admin@example.test', 'subject', 'body', array() ); }
expect( count( $emails ) === 1, 'Repeated notifications were not throttled' );
rc_geo_access_send_notification( 'error', 'admin@example.test', 'subject', 'body', array() );
expect( count( $emails ) === 2, 'Restriction email suppressed an error notification' );
foreach ( $test_transients as &$item ) { $item['expires'] = 0; } unset( $item );
rc_geo_access_send_notification( 'restriction', 'admin@example.test', 'subject', 'body', array() );
expect( count( $emails ) === 3, 'Expired notification throttle stayed active' );
echo $count . " security assertions passed.\n";
