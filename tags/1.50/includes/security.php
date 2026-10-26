<?php
/**
 * Validation, visitor-IP resolution, transport and notification helpers.
 *
 * @package RC_Geo_Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Return the default plugin settings.
 *
 * @return array Default settings.
 */
function rc_geo_access_settings_defaults() {
	return array(
		'rc_geo_access_status'                    => 'disabled',
		'rc_geo_access_api_provider'              => '',
		'rc_geo_access_ipstack_api_key'           => '',
		'rc_geo_access_ipgeolocation_api_key'     => '',
		'rc_geo_access_openlitespeed_local_geoip' => 'NO API KEY REQUIRED',
		'rc_geo_access_restricted_countries'      => array(),
		'rc_geo_access_email_recipient'           => '',
		'rc_geo_access_email_notification_type'   => 'restrictions_and_errors',
	);
}

/**
 * Normalize stored settings without trusting their types or values.
 *
 * @param mixed $input Stored or submitted settings.
 *
 * @return array Normalized settings containing only known fields.
 */
function rc_geo_access_normalize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$settings = rc_geo_access_settings_defaults();
	foreach ( array( 'rc_geo_access_ipstack_api_key', 'rc_geo_access_ipgeolocation_api_key' ) as $key ) {
		$settings[ $key ] = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? substr( sanitize_text_field( $input[ $key ] ), 0, 256 ) : '';
	}
	if ( isset( $input['rc_geo_access_status'] ) ) {
		// A corrupt saved status must not silently disable an existing restriction.
		$settings['rc_geo_access_status'] = 'disabled' === $input['rc_geo_access_status'] ? 'disabled' : 'enabled';
	}
	foreach ( array(
		'rc_geo_access_api_provider'            => array( '', 'ipstack', 'ipgeolocation', 'openlitespeed' ),
		'rc_geo_access_email_notification_type' => array( 'restrictions_and_errors', 'errors_only' ),
	) as $key => $allowed ) {
		if ( isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) {
			$settings[ $key ] = $input[ $key ];
		}
	}
	$email                                     = isset( $input['rc_geo_access_email_recipient'] ) && is_string( $input['rc_geo_access_email_recipient'] ) ? trim( $input['rc_geo_access_email_recipient'] ) : '';
	$settings['rc_geo_access_email_recipient'] = is_email( $email ) ? $email : '';
	$countries                                 = $input['rc_geo_access_restricted_countries'] ?? array();
	if ( is_array( $countries ) ) {
		foreach ( $countries as $code ) {
			if ( is_string( $code ) ) {
				$code = strtoupper( trim( $code ) );
				if ( in_array( $code, rc_geo_access_get_countries(), true ) ) {
					$settings['rc_geo_access_restricted_countries'][] = $code;
				}
			}
		}
	}
	$settings['rc_geo_access_restricted_countries'] = array_values( array_unique( $settings['rc_geo_access_restricted_countries'] ) );
	return $settings;
}

/**
 * Read stored settings with shared validation and defaults.
 *
 * @return array Validated settings with defaults.
 */
function rc_geo_access_get_settings() {
	return rc_geo_access_normalize_settings( get_option( 'rc_geo_access_settings' ) );
}

/**
 * Validate submitted settings and report configuration errors.
 *
 * @param mixed $input Stored or submitted settings.
 *
 * @return array Settings to persist.
 */
function rc_geo_access_sanitize_settings( $input ) {
	if ( ! is_array( $input ) ) {
		add_settings_error( 'rc_geo_access_settings', 'invalid_settings', __( 'Settings must be submitted as a form. Your existing settings have been retained.', 'rc-geo-access' ) );
		return rc_geo_access_get_settings();
	}
	$settings = rc_geo_access_normalize_settings( $input );
	if ( ! isset( $input['rc_geo_access_status'] ) || ! in_array( $input['rc_geo_access_status'], array( 'enabled', 'disabled' ), true ) ) {
		$settings['rc_geo_access_status'] = rc_geo_access_get_settings()['rc_geo_access_status'];
		add_settings_error( 'rc_geo_access_settings', 'invalid_status', __( 'Please choose Enabled or Disabled. The existing restriction status has been retained.', 'rc-geo-access' ) );
	}
	if ( ! empty( $input['rc_geo_access_email_recipient'] ) && '' === $settings['rc_geo_access_email_recipient'] ) {
		add_settings_error( 'rc_geo_access_settings', 'invalid_email', __( 'The notification email address is invalid. Notifications will not be sent until it is corrected.', 'rc-geo-access' ) );
	}
	if ( 'enabled' === $settings['rc_geo_access_status'] ) {
		$provider = $settings['rc_geo_access_api_provider'];
		$key      = 'ipstack' === $provider ? $settings['rc_geo_access_ipstack_api_key'] : $settings['rc_geo_access_ipgeolocation_api_key'];
		if ( '' === $provider || ( 'openlitespeed' !== $provider && '' === $key ) || ! $settings['rc_geo_access_restricted_countries'] ) {
			add_settings_error( 'rc_geo_access_settings', 'incomplete_configuration', __( 'Restrictions are enabled but the provider, API key or country allowlist is incomplete. Login requests will be denied until this is corrected.', 'rc-geo-access' ) );
		}
	}
	return $settings;
}

/**
 * Test an address against an IPv4 or IPv6 address or CIDR.
 *
 * @param mixed $ip The visitor or connection IP address.
 * @param mixed $range A proxy IP address or CIDR.
 *
 * @return bool Whether the address matches the range.
 */
function rc_geo_access_ip_in_range( $ip, $range ) {
	if ( ! is_string( $ip ) || ! is_string( $range ) ) {
		return false;
	}
	$parts = explode( '/', $range );
	if ( count( $parts ) > 2 || ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
		return false;
	}
	$address = inet_pton( $ip );
	$network = inet_pton( $parts[0] );
	if ( strlen( $address ) !== strlen( $network ) ) {
		return false;
	}
	$bits = strlen( $address ) * 8;
	if ( isset( $parts[1] ) ) {
		if ( ! ctype_digit( $parts[1] ) || (int) $parts[1] > $bits ) {
			return false;
		}
		$bits = (int) $parts[1];
	}
	$bytes     = intdiv( $bits, 8 );
	$remaining = $bits % 8;
	return substr( $address, 0, $bytes ) === substr( $network, 0, $bytes ) &&
		( 0 === $remaining || ( ord( $address[ $bytes ] ) & ( 255 << ( 8 - $remaining ) ) ) === ( ord( $network[ $bytes ] ) & ( 255 << ( 8 - $remaining ) ) ) );
}

/**
 * Check whether an address belongs to an explicitly trusted proxy.
 *
 * @param mixed $ip The visitor or connection IP address.
 *
 * @return bool Whether the address is explicitly trusted.
 */
function rc_geo_access_is_trusted_proxy( $ip ) {
	$ranges = defined( 'RC_GEO_ACCESS_TRUSTED_PROXIES' ) ? RC_GEO_ACCESS_TRUSTED_PROXIES : array();
	foreach ( is_array( $ranges ) ? $ranges : array() as $range ) {
		if ( rc_geo_access_ip_in_range( $ip, $range ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Validate a complete IP header without repairing malformed addresses.
 *
 * @param mixed $value The server value to validate.
 *
 * @return string A validated comma-separated IP list, or an empty string.
 */
function rc_geo_access_validate_ip_header( $value ) {
	if ( ! is_string( $value ) || strlen( $value ) > 2048 || false !== strpbrk( $value, "\\\"'" ) ) {
		return '';
	}
	$chain = array_map( 'trim', explode( ',', $value ) );
	foreach ( $chain as $address ) {
		if ( ! filter_var( $address, FILTER_VALIDATE_IP ) ) {
			return '';
		}
	}
	return implode( ', ', $chain );
}

/**
 * Return the validated server connection address.
 *
 * @return string The validated address, or an empty string.
 */
function rc_geo_access_connection_ip() {
	if ( ! isset( $_SERVER['REMOTE_ADDR'] ) || ! is_string( $_SERVER['REMOTE_ADDR'] ) ) {
		return '';
	}
	$ip = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
	return is_string( $ip ) ? $ip : '';
}

/**
 * Validate an unslashed server country value without repairing it.
 *
 * @param mixed $value The server value to validate.
 *
 * @return string A recognised country code, or an empty string.
 */
function rc_geo_access_validate_server_country( $value ) {
	if ( ! is_string( $value ) || false !== strpos( $value, '\\' ) ) {
		return '';
	}
	return rc_geo_access_country_code( array( 'geoip_country_code' => $value ), 'openlitespeed' );
}

/**
 * Read and validate WordPress's slashed local GeoIP country variable.
 *
 * @return string A recognised country code, or an empty string.
 */
function rc_geo_access_server_country() {
	if ( ! isset( $_SERVER['GEOIP_COUNTRY_CODE'] ) || ! is_string( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) {
		return '';
	}
	$country = filter_var(
		wp_unslash( $_SERVER['GEOIP_COUNTRY_CODE'] ),
		FILTER_VALIDATE_REGEXP,
		array( 'options' => array( 'regexp' => '/\A\s*[a-zA-Z]{2}\s*\z/D' ) )
	);
	return rc_geo_access_validate_server_country( $country );
}

/**
 * Read an unslashed, sanitized server display value.
 *
 * @param string $key The server variable name.
 *
 * @return string Sanitized display text, or an empty string.
 */
function rc_geo_access_server_text( $key ) {
	if ( ! isset( $_SERVER[ $key ] ) || ! is_string( $_SERVER[ $key ] ) ) {
		return '';
	}
	return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
}

/**
 * Resolve the visitor address through explicitly trusted proxy hops.
 *
 * @return string The validated visitor address, or an empty string.
 */
function rc_geo_access_client_ip() {
	$peer = rc_geo_access_connection_ip();
	if ( ! is_string( $peer ) || ! filter_var( $peer, FILTER_VALIDATE_IP ) ) {
		return '';
	}
	if ( ! rc_geo_access_is_trusted_proxy( $peer ) ) {
		return $peer;
	}
	$header = defined( 'RC_GEO_ACCESS_FORWARDED_IP_HEADER' ) ? RC_GEO_ACCESS_FORWARDED_IP_HEADER : 'HTTP_X_FORWARDED_FOR';
	if ( ! in_array( $header, array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP' ), true ) ) {
		return '';
	}
	if ( ! isset( $_SERVER[ $header ] ) || ! is_string( $_SERVER[ $header ] ) ) {
		return '';
	}
	$value = filter_var(
		wp_unslash( $_SERVER[ $header ] ),
		FILTER_VALIDATE_REGEXP,
		array( 'options' => array( 'regexp' => '/\A[0-9a-fA-F:., \t]{1,2048}\z/D' ) )
	);
	$value = rc_geo_access_validate_ip_header( $value );
	$chain = array_map( 'trim', explode( ',', $value ) );
	if ( 'HTTP_X_FORWARDED_FOR' !== $header && count( $chain ) !== 1 ) {
		return '';
	}
	foreach ( $chain as $address ) {
		if ( ! filter_var( $address, FILTER_VALIDATE_IP ) ) {
			return '';
		}
	}
	// Walk from the trusted peer towards the client; never trust a spoofable
	// leftmost entry beyond the first untrusted hop.
	for ( $i = count( $chain ) - 1; $i >= 0; $i-- ) {
		if ( ! rc_geo_access_is_trusted_proxy( $chain[ $i ] ) ) {
			return $chain[ $i ];
		}
	}
	return ''; // A chain containing only proxies does not establish a client.
}

/**
 * Create a structured geolocation failure result.
 *
 * @param string $message A safe error description.
 *
 * @return array A structured error result.
 */
function rc_geo_access_lookup_error( $message ) {
	return array( 'rc_geo_access_error' => $message );
}

/**
 * Perform a bounded external lookup with caching and request limits.
 *
 * @param mixed  $ip The visitor or connection IP address.
 * @param string $key The provider access key.
 * @param string $provider The provider identifier.
 *
 * @return array Validated country data or a structured error.
 */
function rc_geo_access_http_lookup( $ip, $key, $provider ) {
	if ( ! in_array( $provider, array( 'ipstack', 'ipgeolocation' ), true ) ) {
		return rc_geo_access_lookup_error( __( 'The geolocation provider is not supported.', 'rc-geo-access' ) );
	}
	if ( ! is_string( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! is_string( $key ) || trim( $key ) === '' ) {
		return rc_geo_access_lookup_error( __( 'A valid visitor IP and API key are required for geolocation.', 'rc-geo-access' ) );
	}
	$cache_key = 'rcga_geo_' . hash( 'sha256', $provider . "\0" . $key . "\0" . $ip );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['result'] ) && is_array( $cached['result'] ) ) {
		return $cached['result'];
	}
	$provider_key = hash( 'sha256', $provider . "\0" . $key );
	$cooldown_key = 'rcga_down_' . $provider_key;
	if ( get_transient( $cooldown_key ) !== false ) {
		return rc_geo_access_lookup_error( __( 'Geolocation requests are temporarily paused after a provider failure. Please try again shortly.', 'rc-geo-access' ) );
	}
	if ( 'ipstack' === $provider ) {
		$url = 'https://api.ipstack.com/' . rawurlencode( $ip ) . '?access_key=' . rawurlencode( $key ) . '&fields=country_code,country_name,longitude,latitude';
	} elseif ( 'ipgeolocation' === $provider ) {
		$url = 'https://api.ipgeolocation.io/ipgeo?apiKey=' . rawurlencode( $key ) . '&ip=' . rawurlencode( $ip ) . '&fields=country_code2,country_name,longitude,latitude';
	} else {
		return rc_geo_access_lookup_error( __( 'The geolocation provider is not supported.', 'rc-geo-access' ) );
	}
	// Bound requests from rotating IPs as well as repeated requests from one IP.
	$budget_key = 'rcga_budget_' . $provider_key . '_' . intdiv( time(), 60 );
	$requests   = (int) get_transient( $budget_key );
	$limit      = defined( 'RC_GEO_ACCESS_LOOKUPS_PER_MINUTE' ) && is_int( RC_GEO_ACCESS_LOOKUPS_PER_MINUTE ) && RC_GEO_ACCESS_LOOKUPS_PER_MINUTE > 0 ? RC_GEO_ACCESS_LOOKUPS_PER_MINUTE : 60;
	if ( $requests >= $limit ) {
		return rc_geo_access_lookup_error( __( 'The local geolocation request limit has been reached. Please try again shortly.', 'rc-geo-access' ) );
	}
	set_transient( $budget_key, $requests + 1, 120 );
	$provider_failed = false;
	$response        = wp_remote_get(
		$url,
		array(
			'timeout'             => 5,
			'redirection'         => 0,
			'sslverify'           => true,
			'reject_unsafe_urls'  => true,
			'limit_response_size' => 16384,
			'headers'             => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		$provider_failed = true;
		// Transport error messages can contain request URLs/API keys.
		$result = rc_geo_access_lookup_error( __( 'The geolocation provider could not be reached. Check connectivity and TLS configuration.', 'rc-geo-access' ) );
	} else {
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = is_string( $body ) ? json_decode( $body, true ) : null;
		if ( 200 !== $status ) {
			$provider_failed = true;
			$result          = rc_geo_access_lookup_error(
				sprintf(
					/* translators: %d: HTTP response status code. */
					__( 'The geolocation provider returned HTTP %d. Check the account, API allowance and provider configuration.', 'rc-geo-access' ),
					$status
				)
			);
		} elseif ( ! is_array( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
			$provider_failed = true;
			$result          = rc_geo_access_lookup_error( __( 'The geolocation provider returned invalid JSON.', 'rc-geo-access' ) );
		} else {
			$error = rc_geo_access_error_handling( $data, $provider );
			if ( '' !== $error ) {
				$provider_failed = ( 'ipstack' === $provider && isset( $data['success'] ) && false === $data['success'] ) || ( 'ipgeolocation' === $provider && isset( $data['message'] ) );
				// Do not display or cache arbitrary provider text containing secrets.
				$result = rc_geo_access_lookup_error( __( 'The geolocation provider could not identify a country. Check the API key, account and allowance.', 'rc-geo-access' ) );
			} else {
				$field  = 'ipstack' === $provider ? 'country_code' : 'country_code2';
				$result = array( $field => rc_geo_access_country_code( $data, $provider ) );
				foreach ( array( 'country_name', 'longitude', 'latitude' ) as $name ) {
					$result[ $name ] = isset( $data[ $name ] ) && is_scalar( $data[ $name ] ) ? substr( (string) $data[ $name ], 0, 200 ) : 'Unknown';
				}
			}
		}
	}
	$ttl = isset( $result['rc_geo_access_error'] ) ? 60 : 3600;
	if ( $provider_failed ) {
		set_transient( $cooldown_key, 1, 60 );
	}
	set_transient( $cache_key, array( 'result' => $result ), $ttl );
	return $result;
}

/**
 * Throttle notifications by site, category and recipient.
 *
 * @param string $kind The notification category.
 * @param string $to The validated recipient email address.
 * @param string $subject The plain-text email subject.
 * @param string $body The escaped HTML email body.
 * @param array  $headers Email headers.
 *
 * @return void
 */
function rc_geo_access_send_notification( $kind, $to, $subject, $body, $headers ) {
	// Site-wide per recipient and category: rotating attacker IPs cannot reset it.
	$key = 'rcga_mail_' . hash( 'sha256', $kind . "\0" . strtolower( $to ) );
	if ( get_transient( $key ) !== false ) {
		return;
	}
	set_transient( $key, 1, 900 );
	wp_mail( $to, $subject, $body, $headers );
}
