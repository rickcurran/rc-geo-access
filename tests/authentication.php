<?php
/* Run with: php tests/authentication.php
 * Isolated hook/WordPress stubs; no database, HTTP requests or emails are used.
 * Each scenario runs in a new process to model request-local caching.
 */
$cases = array(
    'page-get-blocked', 'page-post-blocked', 'page-allowed',
    'xmlrpc-blocked', 'xmlrpc-allowed', 'custom-form-blocked',
    'application-password-blocked', 'application-password-allowed',
    'non-api-unchanged', 'disabled-unchanged', 'other-auth-error-preserved',
    'page-and-auth-check-once', 'xmlrpc-multicall-check-once',
    'error-notification-once', 'errors-only-no-restriction-email',
    'page-missing-geoip', 'xmlrpc-missing-geoip', 'application-password-missing-geoip',
    'missing-country-code', 'null-country-code', 'empty-country-code',
    'invalid-country-code', 'array-country-code', 'missing-name-blocked',
    'missing-name-allowed', 'empty-name-allowed', 'quoted-name-allowed',
    'lowercase-code-allowed', 'missing-marker-allowed', 'missing-code-no-email',
    'missing-code-errors-only', 'disabled-missing-geoip', 'missing-provider',
    'missing-api-key', 'invalid-whitelist', 'no-saved-settings', 'escaped-notification',
);

if ( ! isset( $argv[1] ) ) {
    foreach ( $cases as $case ) {
        $process = proc_open( array( PHP_BINARY, __FILE__, $case ), array( 1 => STDOUT, 2 => STDERR ), $pipes );
        if ( ! is_resource( $process ) || proc_close( $process ) !== 0 ) {
            exit( 1 );
        }
    }
    echo count( $cases ) . " scenarios passed.\n";
    exit( 0 );
}

$case = $argv[1];
if ( ! in_array( $case, $cases, true ) ) {
    exit( 1 );
}

error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
$hooks = array();
$emails = array();
$option_reads = 0;
$password_checks = 0;
$options = array(
    'rc_geo_access_status' => 'enabled',
    'rc_geo_access_api_provider' => 'openlitespeed',
    'rc_geo_access_openlitespeed_local_geoip' => 'NO API KEY REQUIRED',
    'rc_geo_access_restricted_countries' => array( 'GB' ),
    'rc_geo_access_email_recipient' => 'admin@example.test',
    'rc_geo_access_email_notification_type' => 'restrictions_and_errors',
);
$_SERVER = array(
    'REMOTE_ADDR' => '203.0.113.7',
    'GEOIP_COUNTRY_CODE' => 'US',
    'GEOIP_COUNTRY_NAME' => 'United States',
    'REQUEST_METHOD' => 'GET',
);

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['hooks'][$hook][$priority][] = array( $callback, $accepted_args );
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    add_filter( $hook, $callback, $priority, $accepted_args );
}
function apply_filters( $hook, $value, ...$args ) {
    $callbacks = isset( $GLOBALS['hooks'][$hook] ) ? $GLOBALS['hooks'][$hook] : array();
    ksort( $callbacks );
    foreach ( $callbacks as $priority_callbacks ) {
        foreach ( $priority_callbacks as $callback ) {
            $value = call_user_func_array( $callback[0], array_slice( array_merge( array( $value ), $args ), 0, $callback[1] ) );
        }
    }
    return $value;
}
function do_action( $hook ) { apply_filters( $hook, null ); }
function register_activation_hook( ...$args ) {}
function get_option( $name ) {
    $GLOBALS['option_reads']++;
    return $GLOBALS['options'];
}
function __( $value, ...$args ) { return $value; }
function maybe_unserialize( $value ) { return $value; }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function site_url() { return 'https://example.test'; }
function wp_mail( ...$args ) { $GLOBALS['emails'][] = $args; return true; }
class GeoAccessBlocked extends RuntimeException {
    public $response;
    public function __construct( $message, $response ) {
        parent::__construct( $message );
        $this->response = $response;
    }
}
function wp_die( $message, $title = '', $args = array() ) {
    throw new GeoAccessBlocked( $message, $args['response'] );
}
function check( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function authenticate_candidate() {
    return apply_filters( 'authenticate', null, 'test-user', 'test-password' );
}

require __DIR__ . '/wp-stubs.php';
require dirname( __DIR__ ) . '/trunk/rc-geo-access.php';

// Model core's priority-20 authentication stage. A denial must terminate before
// this callback, rather than passing an error that a later callback can replace.
add_filter( 'authenticate', function ( $user ) {
    $GLOBALS['password_checks']++;
    return $user === null ? 'authenticated-user' : $user;
}, 20 );

$allowed_cases = array( 'page-allowed', 'xmlrpc-allowed', 'application-password-allowed', 'other-auth-error-preserved', 'page-and-auth-check-once', 'xmlrpc-multicall-check-once', 'missing-name-allowed', 'empty-name-allowed', 'quoted-name-allowed', 'lowercase-code-allowed', 'missing-marker-allowed', 'invalid-whitelist' );
if ( in_array( $case, $allowed_cases, true ) ) {
    $_SERVER['GEOIP_COUNTRY_CODE'] = 'GB';
    $_SERVER['GEOIP_COUNTRY_NAME'] = 'United Kingdom';
}
if ( strpos( $case, 'xmlrpc-' ) === 0 ) { define( 'XMLRPC_REQUEST', true ); }
if ( $case === 'page-post-blocked' ) { $_SERVER['REQUEST_METHOD'] = 'POST'; }
if ( $case === 'disabled-unchanged' ) { $options['rc_geo_access_status'] = 'disabled'; }
if ( $case === 'errors-only-no-restriction-email' ) { $options['rc_geo_access_email_notification_type'] = 'errors_only'; }

$unknown_cases = array( 'error-notification-once', 'page-missing-geoip', 'xmlrpc-missing-geoip', 'application-password-missing-geoip', 'missing-country-code', 'null-country-code', 'empty-country-code', 'invalid-country-code', 'array-country-code', 'missing-code-no-email', 'missing-code-errors-only', 'missing-provider', 'missing-api-key' );
if ( in_array( $case, $unknown_cases, true ) || $case === 'disabled-missing-geoip' ) { unset( $_SERVER['GEOIP_COUNTRY_CODE'] ); }
if ( strpos( $case, 'missing-geoip' ) !== false ) { unset( $_SERVER['GEOIP_COUNTRY_NAME'] ); }
if ( strpos( $case, 'missing-name-' ) === 0 ) { unset( $_SERVER['GEOIP_COUNTRY_NAME'] ); }
if ( $case === 'null-country-code' ) { $_SERVER['GEOIP_COUNTRY_CODE'] = null; }
if ( $case === 'empty-country-code' ) { $_SERVER['GEOIP_COUNTRY_CODE'] = '  '; }
if ( $case === 'invalid-country-code' ) { $_SERVER['GEOIP_COUNTRY_CODE'] = 'XX'; }
if ( $case === 'array-country-code' ) { $_SERVER['GEOIP_COUNTRY_CODE'] = array( 'GB' ); }
if ( $case === 'empty-name-allowed' ) { $_SERVER['GEOIP_COUNTRY_NAME'] = ''; }
if ( $case === 'quoted-name-allowed' ) { $_SERVER['GEOIP_COUNTRY_NAME'] = 'A "quoted" country name'; }
if ( $case === 'lowercase-code-allowed' ) { $_SERVER['GEOIP_COUNTRY_CODE'] = ' gb '; }
if ( $case === 'missing-marker-allowed' ) { unset( $options['rc_geo_access_openlitespeed_local_geoip'] ); }
if ( $case === 'missing-code-no-email' ) { unset( $options['rc_geo_access_email_recipient'] ); }
if ( $case === 'missing-code-errors-only' ) { $options['rc_geo_access_email_notification_type'] = 'errors_only'; }
if ( $case === 'disabled-missing-geoip' ) { $options['rc_geo_access_status'] = 'disabled'; }
if ( $case === 'missing-provider' ) { unset( $options['rc_geo_access_api_provider'] ); }
if ( $case === 'missing-api-key' ) { $options['rc_geo_access_api_provider'] = 'ipstack'; }
if ( $case === 'invalid-whitelist' ) { $options['rc_geo_access_restricted_countries'] = 'GB'; }
if ( $case === 'no-saved-settings' ) { $options = false; }

if ( $case === 'escaped-notification' ) { $_SERVER['HTTP_REFERER'] = '<script>alert(1)</script>'; $_SERVER['GEOIP_COUNTRY_NAME'] = '<img src=x onerror=alert(1)>'; }

$expected_block = in_array( $case, $unknown_cases, true ) || in_array( $case, array( 'page-get-blocked', 'page-post-blocked', 'xmlrpc-blocked', 'custom-form-blocked', 'application-password-blocked', 'errors-only-no-restriction-email', 'missing-name-blocked', 'invalid-whitelist', 'escaped-notification' ), true );
$blocked = false;
try {
    if ( strpos( $case, 'page-' ) === 0 ) {
        do_action( 'login_init' );
        if ( $case === 'page-and-auth-check-once' ) {
            check( authenticate_candidate() === 'authenticated-user', 'Allowed page authentication changed' );
            apply_filters( 'application_password_is_api_request', true );
        }
    } elseif ( strpos( $case, 'application-password-' ) === 0 ) {
        check( apply_filters( 'application_password_is_api_request', true ) === true, 'Allowed API eligibility changed' );
        $password_checks++;
    } elseif ( $case === 'non-api-unchanged' ) {
        check( apply_filters( 'application_password_is_api_request', false ) === false, 'Non-API eligibility changed' );
        check( $option_reads === 0, 'Non-API request triggered a lookup' );
    } elseif ( $case === 'other-auth-error-preserved' ) {
        $error = new stdClass();
        check( apply_filters( 'authenticate', $error ) === $error, 'Existing authentication result was replaced' );
    } else {
        check( authenticate_candidate() === 'authenticated-user', 'Allowed authentication changed' );
        if ( $case === 'xmlrpc-multicall-check-once' ) {
            for ( $i = 0; $i < 5; $i++ ) { authenticate_candidate(); }
        }
    }
} catch ( GeoAccessBlocked $error ) {
    $blocked = true;
    check( $error->response === 403, 'Denial did not specify HTTP/XML-RPC error 403' );
    $expected_country = in_array( $case, $unknown_cases, true ) ? 'UNKNOWN' : ( $case === 'invalid-whitelist' ? 'GB' : 'US' );
    check( strpos( $error->getMessage(), 'RCGEOAXS' . $expected_country . '001' ) !== false, 'Country error code missing' );
    check( $password_checks === 0, 'Blocked request reached password validation' );
    $expected_emails = in_array( $case, array( 'errors-only-no-restriction-email', 'missing-code-no-email' ), true ) ? 0 : 1;
    check( count( $emails ) === $expected_emails, 'Unexpected restriction email count' );
    if ( $case === 'escaped-notification' ) { check( strpos( $emails[0][2], '<script>' ) === false && strpos( $emails[0][2], '<img' ) === false && strpos( $emails[0][2], '&lt;img' ) !== false, 'Notification HTML injection' ); }
    if ( $case === 'error-notification-once' ) {
        try { authenticate_candidate(); check( false, 'Cached denial was bypassed' ); }
        catch ( GeoAccessBlocked $again ) { check( count( $emails ) === 1, 'Lookup-error notifications duplicated' ); }
    }
}

check( $blocked === $expected_block, 'Unexpected access decision for ' . $case );
if ( $case !== 'non-api-unchanged' ) { check( $option_reads === 1, 'Access decision was repeated' ); }
echo 'PASS ' . $case . "\n";
