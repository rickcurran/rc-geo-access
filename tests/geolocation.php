<?php
/* Run with: php tests/geolocation.php. No network requests are made. */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function register_activation_hook( ...$args ) {}
function __( $value, ...$args ) { return $value; }
require dirname( __DIR__ ) . '/trunk/rc-geo-access.php';

$cases = array(
    array( 'ipstack', null, false ),
    array( 'ipstack', false, false ),
    array( 'ipstack', 'unavailable', false ),
    array( 'ipstack', array(), false ),
    array( 'ipstack', array( 'country_code' => null ), false ),
    array( 'ipstack', array( 'country_code' => array( 'GB' ) ), false ),
    array( 'ipstack', array( 'country_code' => 'XX' ), false ),
    array( 'ipstack', array( 'country_code' => 'GB' ), true ),
    array( 'ipstack', array( 'country_code' => ' gb ' ), true ),
    array( 'ipstack', array( 'success' => false, 'error' => array( 'code' => 104, 'type' => 'usage_limit_reached' ) ), false ),
    array( 'ipstack', array( 'success' => false, 'error' => array( 'code' => 101, 'type' => 'invalid_access_key' ) ), false ),
    array( 'ipstack', array( 'success' => false, 'error' => 'malformed', 'country_code' => 'GB' ), false ),
    array( 'ipgeolocation', array( 'message' => 'Your monthly limit has been reached' ), false ),
    array( 'ipgeolocation', array( 'message' => 'Provided API key is not valid' ), false ),
    array( 'ipgeolocation', array( 'message' => array(), 'country_code2' => 'GB' ), false ),
    array( 'ipgeolocation', array( 'message' => '', 'country_code2' => 'GB' ), false ),
    array( 'ipgeolocation', array( 'country_code2' => 'GB' ), true ),
    array( 'ipgeolocation', array( 'country_code2' => '' ), false ),
    array( 'openlitespeed', array( 'geoip_country_code' => 'GB' ), true ),
    array( 'openlitespeed', array( 'geoip_country_code' => '' ), false ),
    array( 'openlitespeed', array( 'geoip_country_code' => true ), false ),
    array( 'unknown-provider', array( 'country_code' => 'GB' ), false ),
);

foreach ( $cases as $index => $case ) {
    list( $provider, $response, $valid ) = $case;
    $error = rc_geo_access_error_handling( $response, $provider );
    if ( ( $error === '' ) !== $valid ) {
        throw new RuntimeException( 'Unexpected provider validation result for case ' . $index );
    }
    if ( $valid && rc_geo_access_country_code( $response, $provider ) !== 'GB' ) {
        throw new RuntimeException( 'Country code not normalised for case ' . $index );
    }
}
echo count( $cases ) . " provider response scenarios passed.\n";
