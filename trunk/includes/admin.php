<?php
/**
 * Settings-page rendering and connection diagnostics.
 *
 * @package RC_Geo_Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Render visitor-IP diagnostics for the current settings-page request.
 *
 * @return void
 */
function rc_geo_access_connection_render() {
	$ip   = rc_geo_access_client_ip();
	$peer = rc_geo_access_connection_ip();
	echo '<p>' . esc_html__( 'Connection IP:', 'rc-geo-access' ) . ' <code>' . esc_html( $peer ) . '</code><br>';
	echo esc_html__( 'Visitor IP used for geolocation:', 'rc-geo-access' ) . ' <code>' . esc_html( '' === $ip ? 'Unknown' : $ip ) . '</code></p>';
	if ( rc_geo_access_is_trusted_proxy( $peer ) ) {
		echo '<p>' . esc_html__( 'The connection comes from a configured trusted proxy. Its visitor-IP header is being checked.', 'rc-geo-access' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( 'Using the connection address. Forwarding headers are ignored unless the connection comes from an explicitly trusted proxy.', 'rc-geo-access' ) . '</p>';
	}
	$options = rc_geo_access_get_settings();
	if ( 'openlitespeed' === $options['rc_geo_access_api_provider'] ) {
		$result  = rc_geo_access_lookup_ip( $ip, '', 'openlitespeed' );
		$error   = rc_geo_access_error_handling( $result, 'openlitespeed' );
		$country = rc_geo_access_country_code( $result, 'openlitespeed' );
		echo '<p>' . esc_html__( 'Local GeoIP country:', 'rc-geo-access' ) . ' <code>' . esc_html( '' !== $country ? $country : __( 'Unknown', 'rc-geo-access' ) ) . '</code></p>';
		if ( '' !== $error ) {
			echo '<p style="color:#b32d2e">' . esc_html( $error ) . '</p>';
		}
	}
}

/**
 * Render the selected provider field with escaped values.
 *
 * @param string $provider The provider identifier.
 *
 * @return void
 */
function rc_geo_access_api_key_render( $provider ) {
	$options = rc_geo_access_get_settings();
	$field   = 'rc_geo_access_' . $provider . '_api_key';
	echo '<input type="text" autocomplete="off" name="rc_geo_access_settings[' . esc_attr( $field ) . ']" value="' . esc_attr( $options[ $field ] ) . '" size="50">';
	if ( $options['rc_geo_access_api_provider'] === $provider && '' === $options[ $field ] ) {
		echo '<p>' . esc_html__( 'Enter the API key for the selected provider.', 'rc-geo-access' ) . '</p>';
	}
	rc_geo_access_provider_error_render( $provider );
}

/**
 * Display lookup errors only for the selected provider.
 *
 * @param string $provider The provider identifier.
 *
 * @return void
 */
function rc_geo_access_provider_error_render( $provider ) {
	$options = rc_geo_access_get_settings();
	if ( $options['rc_geo_access_api_provider'] !== $provider ) {
		return;
	}
	$key = 'ipstack' === $provider ? $options['rc_geo_access_ipstack_api_key'] : $options['rc_geo_access_ipgeolocation_api_key'];
	if ( 'openlitespeed' !== $provider && '' === $key ) {
		return;
	}
	$result = rc_geo_access_lookup_ip( rc_geo_access_client_ip(), $key, $provider );
	$error  = rc_geo_access_error_handling( $result, $provider );
	if ( '' !== $error ) {
		echo '<p style="color:#b32d2e">' . esc_html( $error ) . '</p>';
	}
}
