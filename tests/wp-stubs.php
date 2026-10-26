<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', dirname( __DIR__ ) . '/' ); }
/* Small offline WordPress substitutes for the regression scripts. */
$GLOBALS['test_transients'] = array();
$GLOBALS['test_http_calls'] = array();
$GLOBALS['test_http_response'] = array( 'response' => array( 'code' => 200 ), 'body' => '{"country_code":"GB","country_code2":"GB","country_name":"United Kingdom"}' );
$GLOBALS['test_settings_errors'] = array();
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $value ) ) ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $value ) { return esc_attr( $value ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $value, ...$args ) { return esc_html( $value ); }
}
if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $key ) {
        $item = $GLOBALS['test_transients'][ $key ] ?? null;
        return $item && $item['expires'] > time() ? $item['value'] : false;
    }
    function set_transient( $key, $value, $ttl ) {
        $GLOBALS['test_transients'][ $key ] = array( 'value' => $value, 'expires' => time() + $ttl, 'ttl' => $ttl );
    }
}
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error { public function __construct( ...$args ) {} }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $value ) { return $value instanceof WP_Error; }
    function wp_remote_get( $url, $args ) {
        $GLOBALS['test_http_calls'][] = array( $url, $args );
        return $GLOBALS['test_http_response'];
    }
    function wp_remote_retrieve_response_code( $response ) { return $response['response']['code'] ?? 0; }
    function wp_remote_retrieve_body( $response ) { return $response['body'] ?? ''; }
}
if ( ! function_exists( 'add_settings_error' ) ) {
    function add_settings_error( ...$args ) { $GLOBALS['test_settings_errors'][] = $args; }
}
if ( ! function_exists( 'checked' ) ) {
    function checked( $value, $expected, $echo = true ) {
        $html = $value === $expected ? ' checked="checked"' : '';
        if ( $echo ) { echo $html; }
        return $html;
    }
    function selected( $value, $expected, $echo = true ) {
        $html = $value === $expected ? ' selected="selected"' : '';
        if ( $echo ) { echo $html; }
        return $html;
    }
}
if ( ! function_exists( 'wp_kses_post' ) ) {
    function wp_kses_post( $html ) { return $html; }
}
