<?php
/* Each process has independent trusted-proxy/header configuration. */
$cases = array( 'direct', 'spoofed-headers', 'cloudflare', 'cloudflare-external', 'cloudflare-missing', 'cloudflare-list', 'xff', 'xff-spoofed-left', 'xff-trusted-chain', 'xff-invalid', 'xff-only-proxies', 'ipv6', 'bad-peer', 'bad-range', 'bad-header', 'local-geoip-mismatch', 'server-restored', 'real-ip' );
if ( ! isset( $argv[1] ) ) {
    foreach ( $cases as $case ) {
        $process = proc_open( array( PHP_BINARY, __FILE__, $case ), array( 1 => STDOUT, 2 => STDERR ), $pipes );
        if ( ! is_resource( $process ) || proc_close( $process ) !== 0 ) { exit( 1 ); }
    }
    echo count( $cases ) . " proxy scenarios passed.\n";
    exit( 0 );
}
$case = $argv[1];
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
define( 'RC_GEO_ACCESS_TRUSTED_PROXIES', array( '198.51.100.0/24', '2001:db8:1::/48' ) );
$header = strpos( $case, 'cloudflare' ) === 0 || $case === 'local-geoip-mismatch' ? 'HTTP_CF_CONNECTING_IP' : 'HTTP_X_FORWARDED_FOR';
if ( $case === 'real-ip' ) { $header = 'HTTP_X_REAL_IP'; }
define( 'RC_GEO_ACCESS_FORWARDED_IP_HEADER', $case === 'bad-header' ? 'HTTP_ARBITRARY' : $header );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function register_activation_hook( ...$args ) {}
function __( $value, ...$args ) { return $value; }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function get_option( ...$args ) {
    return array( 'rc_geo_access_status' => 'enabled', 'rc_geo_access_api_provider' => 'openlitespeed', 'rc_geo_access_restricted_countries' => array( 'GB' ) );
}
class ProxyDenied extends RuntimeException {}
function wp_die( $message, $title, $args ) {
    if ( $args['response'] !== 403 ) { throw new RuntimeException( 'Wrong denial status' ); }
    throw new ProxyDenied( $message );
}
require __DIR__ . '/wp-stubs.php';
require dirname( __DIR__ ) . '/trunk/rc-geo-access.php';
$_SERVER = array( 'REMOTE_ADDR' => '198.51.100.2', $header => '203.0.113.7', 'GEOIP_COUNTRY_CODE' => 'GB' );
$expected = '203.0.113.7';
switch ( $case ) {
    case 'direct': case 'spoofed-headers': case 'server-restored':
        $_SERVER['REMOTE_ADDR'] = $expected = '192.0.2.7';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.1';
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
        break;
    case 'cloudflare-missing': unset( $_SERVER[ $header ] ); $expected = ''; break;
    case 'cloudflare-list': $_SERVER[ $header ] = '203.0.113.7, 203.0.113.8'; $expected = ''; break;
    case 'xff-spoofed-left': $_SERVER[ $header ] = '192.0.2.99, 203.0.113.7'; break;
    case 'xff-trusted-chain': $_SERVER[ $header ] = '203.0.113.7, 198.51.100.5'; break;
    case 'xff-invalid': $_SERVER[ $header ] = 'invalid, 203.0.113.7'; $expected = ''; break;
    case 'xff-only-proxies': $_SERVER[ $header ] = '198.51.100.5'; $expected = ''; break;
    case 'ipv6': $_SERVER['REMOTE_ADDR'] = '2001:db8:1::2'; $_SERVER[ $header ] = $expected = '2001:db8:2::7'; break;
    case 'bad-peer': $_SERVER['REMOTE_ADDR'] = array(); $expected = ''; break;
    case 'bad-range':
        if ( rc_geo_access_ip_in_range( '198.51.100.5', '198.51.100.0/999' ) || rc_geo_access_ip_in_range( '2001:db8::1', '198.51.100.0/24' ) ) { throw new RuntimeException( 'Invalid range trusted' ); }
        break;
    case 'bad-header': $expected = ''; break;
}
if ( rc_geo_access_client_ip() !== $expected ) { throw new RuntimeException( 'Wrong visitor IP: ' . $case ); }
if ( $case === 'local-geoip-mismatch' ) {
    try { rc_geo_access_func(); throw new RuntimeException( 'Proxy country allowed a different client IP' ); }
    catch ( ProxyDenied $error ) {
        if ( strpos( $error->getMessage(), 'UNKNOWN' ) === false ) { throw new RuntimeException( 'Wrong proxy error' ); }
    }
}
if ( $case === 'cloudflare-external' ) {
    $result = rc_geo_access_lookup_ip( rc_geo_access_client_ip(), 'test-key', 'ipstack' );
    if ( $result['country_code'] !== 'GB' || strpos( $test_http_calls[0][0], '/203.0.113.7?' ) === false ) { throw new RuntimeException( 'External provider looked up the proxy instead of the visitor' ); }
}
if ( $case === 'server-restored' ) { rc_geo_access_func(); }
echo 'PASS ' . $case . "\n";
