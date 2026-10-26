<?php
/**
 * Country-based restrictions for WordPress login and authentication.
 *
 * @package RC_Geo_Access
 *
 * Plugin Name: RC Geo Access
 * Plugin URI: https://qreate.co.uk/projects/#rcgeoaccess
 * Description: Restrict WordPress login-page access and authentication by country, including XML-RPC and application passwords.
 * Version: 1.49
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * Author: Rick Curran
 * Author URI: https://qreate.co.uk
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rc-geo-access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin.php';

add_action( 'admin_menu', 'rc_geo_access_add_admin_menu' );
add_action( 'admin_init', 'rc_geo_access_settings_init' );

/**
 * Register the settings page under the WordPress Settings menu.
 *
 * @return void
 */
function rc_geo_access_add_admin_menu() {

	add_options_page( 'RC GEO Access', 'RC GEO Access', 'manage_options', 'rc_geo_access', 'rc_geo_access_options_page' );
}

/**
 * Show administrators a reminder when country restrictions are disabled.
 *
 * @return void
 */
function rc_geo_access_admin_notice__info() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$options = rc_geo_access_get_settings();
	$screen  = get_current_screen();
	if ( $screen && in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) && 'enabled' !== $options['rc_geo_access_status'] ) {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'RC Geo Access restrictions are disabled.', 'rc-geo-access' ) . ' <a href="' . esc_url( admin_url( 'options-general.php?page=rc_geo_access' ) ) . '">' . esc_html__( 'Configure RC Geo Access', 'rc-geo-access' ) . '</a></p></div>';
	}
}
add_action( 'admin_notices', 'rc_geo_access_admin_notice__info' );



/**
 * Register settings validation, sections and fields.
 *
 * @return void
 */
function rc_geo_access_settings_init() {

	register_setting(
		'rc_geo_access_admin_page',
		'rc_geo_access_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'rc_geo_access_sanitize_settings',
			'default'           => rc_geo_access_settings_defaults(),
		)
	);

	add_settings_section( 'rc_geo_access_admin_page_section', __( 'What does this plugin do?', 'rc-geo-access' ), 'rc_geo_access_settings_section_callback', 'rc_geo_access_admin_page' );

	add_settings_field( 'rc_geo_access_connection', __( 'Connection details:', 'rc-geo-access' ), 'rc_geo_access_connection_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_status', __( 'Restriction Status:', 'rc-geo-access' ), 'rc_geo_access_status_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_api_provider', __( 'API Provider:', 'rc-geo-access' ), 'rc_geo_access_api_provider_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_ipstack_api_key', __( 'IPStack API Key:', 'rc-geo-access' ), 'rc_geo_access_ipstack_api_key_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_ipgeolocation_api_key', __( 'IPGeolocation API Key:', 'rc-geo-access' ), 'rc_geo_access_ipgeolocation_api_key_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_openlitespeed_local_geoip', __( 'OpenLiteSpeed GeoIP:', 'rc-geo-access' ), 'rc_geo_access_openlitespeed_local_geoip_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_restricted_countries', __( 'Country Whitelist:', 'rc-geo-access' ), 'rc_geo_access_restricted_countries_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_section( 'rc_geo_access_admin_page_notifications_section', 'Email Notifications', 'rc_geo_access_settings_notifications_section_callback', 'rc_geo_access_admin_page' );

	add_settings_field( 'rc_geo_access_email_recipient', __( 'Email Recipient:', 'rc-geo-access' ), 'rc_geo_access_email_recipient_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_notifications_section' );

	add_settings_field( 'rc_geo_access_email_notification_type', __( 'Notification Type:', 'rc-geo-access' ), 'rc_geo_access_email_notification_type_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_notifications_section' );

	add_settings_section( 'rc_geo_access_admin_page_save_section', '', 'rc_geo_access_settings_save_section_callback', 'rc_geo_access_admin_page' );

	add_settings_section( 'rc_geo_access_admin_page_donate_section', __( 'Support this plugin', 'rc-geo-access' ), 'rc_geo_access_settings_donate_section_callback', 'rc_geo_access_admin_page' );
}

/**
 * Render the notification recipient field.
 *
 * @return void
 */
function rc_geo_access_email_recipient_render() {
	$options = rc_geo_access_get_settings();
	echo '<input type="email" name="rc_geo_access_settings[rc_geo_access_email_recipient]" value="' . esc_attr( $options['rc_geo_access_email_recipient'] ) . '" size="50">';
	if ( '' === $options['rc_geo_access_email_recipient'] ) {
		echo '<p>' . esc_html__( 'Enter a valid email address to receive notifications.', 'rc-geo-access' ) . '</p>';
	}
}

/**
 * Render the notification category selector.
 *
 * @return void
 */
function rc_geo_access_email_notification_type_render() {

	$options = rc_geo_access_get_settings();

	?>
	<select name='rc_geo_access_settings[rc_geo_access_email_notification_type]'>
		<option value='restrictions_and_errors' <?php selected( $options['rc_geo_access_email_notification_type'], 'restrictions_and_errors' ); ?>><?php echo esc_html__( 'Restrictions and Errors', 'rc-geo-access' ); ?></option>
		<option value='errors_only' <?php selected( $options['rc_geo_access_email_notification_type'], 'errors_only' ); ?>><?php echo esc_html__( 'Errors Only', 'rc-geo-access' ); ?></option>
	</select>

	<?php
}


/**
 * Render the geolocation provider selector.
 *
 * @return void
 */
function rc_geo_access_api_provider_render() {

	$options = rc_geo_access_get_settings();

	?>
	<select name='rc_geo_access_settings[rc_geo_access_api_provider]'>
		<option value=""><?php echo esc_html__( 'Select...', 'rc-geo-access' ); ?></option>
		<option value='ipgeolocation' <?php selected( $options['rc_geo_access_api_provider'], 'ipgeolocation' ); ?>>ipgeolocation.io</option>
		<option value='ipstack' <?php selected( $options['rc_geo_access_api_provider'], 'ipstack' ); ?>>ipstack.com</option>
		<option value='openlitespeed' <?php selected( $options['rc_geo_access_api_provider'], 'openlitespeed' ); ?>>OpenLiteSpeed’s local GeoIP variable</option>
	</select>

	<?php
	if ( '' === $options['rc_geo_access_api_provider'] ) {
		echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . esc_html__( 'Please select an API provider from the menu above.', 'rc-geo-access' ) . '</p>';
	}
}

/**
 * Render the restriction status selector.
 *
 * @return void
 */
function rc_geo_access_status_render() {

	$options = rc_geo_access_get_settings();

	?>
	<select name='rc_geo_access_settings[rc_geo_access_status]'>
		<option value='disabled' <?php selected( $options['rc_geo_access_status'], 'disabled' ); ?>><?php echo esc_html__( 'Disabled', 'rc-geo-access' ); ?></option>
		<option value='enabled' <?php selected( $options['rc_geo_access_status'], 'enabled' ); ?>><?php echo esc_html__( 'Enabled', 'rc-geo-access' ); ?></option>
	</select>

	<?php
	if ( 'disabled' === $options['rc_geo_access_status'] ) {

		echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . esc_html__( 'Change this to "Enabled" to turn on access restriction', 'rc-geo-access' ) . '</p>';

	}
}

/**
 * Render the IPStack access-key field.
 *
 * @return void
 */
function rc_geo_access_ipstack_api_key_render() {
	rc_geo_access_api_key_render( 'ipstack' );
}

/**
 * Render the IPGeolocation access-key field.
 *
 * @return void
 */
function rc_geo_access_ipgeolocation_api_key_render() {
	rc_geo_access_api_key_render( 'ipgeolocation' );
}

/**
 * Describe the local provider requirements and show lookup errors.
 *
 * @return void
 */
function rc_geo_access_openlitespeed_local_geoip_render() {
	echo '<p>' . esc_html__( 'No API key is required. OpenLiteSpeed must supply a country code for the visitor IP shown above.', 'rc-geo-access' ) . '</p>';
	rc_geo_access_provider_error_render( 'openlitespeed' );
}


/**
 * Render the country allowlist and the current country suggestion.
 *
 * @return void
 */
function rc_geo_access_restricted_countries_render() {
	$options  = rc_geo_access_get_settings();
	$provider = $options['rc_geo_access_api_provider'];
	$country  = '';
	$key      = 'ipstack' === $provider ? $options['rc_geo_access_ipstack_api_key'] : $options['rc_geo_access_ipgeolocation_api_key'];
	if ( 'openlitespeed' === $provider || ( '' !== $provider && '' !== $key ) ) {
		$result = rc_geo_access_lookup_ip( rc_geo_access_client_ip(), $key, $provider );
		if ( rc_geo_access_error_handling( $result, $provider ) === '' ) {
			$country = rc_geo_access_country_code( $result, $provider );
		}
	}
	echo '<p>' . esc_html__( 'Select the countries allowed to access login and authentication. An empty allowlist blocks everyone when restrictions are enabled.', 'rc-geo-access' ) . '</p>';
	if ( '' !== $country ) {
		echo '<p>' . esc_html__( 'Your detected country:', 'rc-geo-access' ) . ' <strong>' . esc_html( $country ) . '</strong>. ' . esc_html__( 'It is selected below for your convenience; review the selection before saving.', 'rc-geo-access' ) . '</p>';
	}
	echo '<ul style="column-width:300px">';
	foreach ( rc_geo_access_get_countries() as $name => $code ) {
		$selected = in_array( $code, $options['rc_geo_access_restricted_countries'], true ) || $code === $country;
		echo '<li><label><input type="checkbox" name="rc_geo_access_settings[rc_geo_access_restricted_countries][]" value="' . esc_attr( $code ) . '"' . checked( $selected, true, false ) . '> ' . esc_html( $name . ' - ' . $code ) . '</label></li>';
	}
	echo '</ul>';
}

/**
 * Describe the supported geolocation providers.
 *
 * @return void
 */
function rc_geo_access_settings_section_callback() {
	echo '<p>' . esc_html__( 'RC Geo Access restricts the login page and authentication by the visitor\'s country.', 'rc-geo-access' ) . '</p>';
	echo '<p>' . esc_html__( 'Choose an external geolocation provider or a configured OpenLiteSpeed local database:', 'rc-geo-access' ) . '</p>';
	echo '<ul><li><a href="https://ipstack.com?utm_source=FirstPromoter&utm_medium=Affiliate&fpr=rick54" target="_blank" rel="noopener noreferrer">IPStack</a></li>';
	echo '<li><a href="https://ipgeolocation.io/" target="_blank" rel="noopener noreferrer">IPGeolocation</a></li>';
	echo '<li><a href="https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation" target="_blank" rel="noopener noreferrer">' . esc_html__( 'OpenLiteSpeed GeoIP setup instructions', 'rc-geo-access' ) . '</a></li></ul>';
	echo '<p>' . esc_html__( 'External providers require an active API key and sufficient request allowance. OpenLiteSpeed requires a configured and updated local geolocation database.', 'rc-geo-access' ) . '</p>';
	submit_button();
}

/**
 * Render the settings save button.
 *
 * @return void
 */
function rc_geo_access_settings_save_section_callback() {

	submit_button();

	echo '<hr>';
}

/**
 * Describe notification behavior and throttling.
 *
 * @return void
 */
function rc_geo_access_settings_notifications_section_callback() {
	echo '<p>' . esc_html__( 'Notifications report restricted login requests and geolocation errors. They are optional; leave the recipient empty to disable email notifications.', 'rc-geo-access' ) . '</p>';
	echo '<p>' . esc_html__( 'Choose Restrictions and Errors for both categories, or Errors Only for lookup and configuration problems. Each category is limited to one email per recipient every 15 minutes.', 'rc-geo-access' ) . '</p>';
}

/**
 * Render links for supporting plugin development.
 *
 * @return void
 */
function rc_geo_access_settings_donate_section_callback() {
	echo '<p>' . esc_html__( 'If this plugin is useful to you, please consider supporting its development.', 'rc-geo-access' ) . '</p>';
	echo '<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G" class="button button-primary" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'rc-geo-access' ) . '</a></p>';
}

/**
 * Render the settings form for authorized administrators.
 *
 * @return void
 */
function rc_geo_access_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	?>
	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">
				<form action='options.php' method='post'>

					<h1><?php echo esc_html__( 'RC Geo Access Settings', 'rc-geo-access' ); ?></h1>

					<?php
						settings_errors( 'rc_geo_access_settings' );
						settings_fields( 'rc_geo_access_admin_page' );
						do_settings_sections( 'rc_geo_access_admin_page' );

					?>

				</form>
			</div>
		</div>
	</div>
	<?php
}


/*
 * Additional links on plugin list page
 */
add_filter( 'plugin_row_meta', 'rc_geo_access_row_meta', 10, 2 );

/**
 * Add settings and donation links only to this plugin row.
 *
 * @param array  $links_array Existing plugin row links.
 * @param string $plugin_file_name The plugin basename supplied by WordPress.
 *
 * @return array Plugin row links.
 */
function rc_geo_access_row_meta( $links_array, $plugin_file_name ) {

	if ( plugin_basename( __FILE__ ) === $plugin_file_name ) {
		if ( ! is_network_admin() ) {
			$links_array[] = '<a href="options-general.php?page=rc_geo_access">' . esc_html__( 'Settings', 'rc-geo-access' ) . '</a>';
		}
		$links_array[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . esc_html__( 'Donate', 'rc-geo-access' ) . '</a>';
	}
	return $links_array;
}


/*
 * Check the login page and authentication routes, including XML-RPC and
 * custom forms using wp_authenticate()/wp_signon(). Run before core's
 * authentication callbacks (priority 20), so blocked credentials are not tested.
 */
add_action( 'login_init', 'rc_geo_access_func' );
add_filter( 'authenticate', 'rc_geo_access_authenticate', 1 );
add_filter( 'application_password_is_api_request', 'rc_geo_access_application_password_check', 1 );

/**
 * Check the country before WordPress validates login credentials.
 *
 * @param WP_User|WP_Error|null $user The current authentication result.
 *
 * @return WP_User|WP_Error|null The unchanged authentication result for allowed requests.
 */
function rc_geo_access_authenticate( $user ) {
	rc_geo_access_func();
	return $user;
}

/**
 * Check the country before application-password authentication.
 *
 * @param bool $is_api_request Whether application-password authentication is eligible.
 *
 * @return bool The unchanged API-request eligibility for allowed requests.
 */
function rc_geo_access_application_password_check( $is_api_request ) {
	if ( $is_api_request ) {
		rc_geo_access_func();
	}

	return $is_api_request;
}

/**
 * Enforce country restrictions once per request and retain denied decisions.
 *
 * @return void
 */
function rc_geo_access_func() {

	// login_init, authenticate and application-password checks may all run in.
	// one request. Reuse the decision, including across XML-RPC multicalls.
	static $checked        = false;
	static $denied_country = null;
	if ( $checked ) {
		if ( null !== $denied_country ) {
			rc_geo_access_deny_access( $denied_country );
		}
		return;
	}
	$checked = true;

	$options       = rc_geo_access_get_settings();
	$rc_geo_status = $options['rc_geo_access_status'];
	if ( 'enabled' !== $rc_geo_status ) {
		return;
	}
	$rc_geo_access_provider = $options['rc_geo_access_api_provider'];
	$rc_geo_access_api_key  = '';
	if ( 'ipstack' === $rc_geo_access_provider ) {
		$rc_geo_access_api_key = $options['rc_geo_access_ipstack_api_key'];

	} elseif ( 'ipgeolocation' === $rc_geo_access_provider ) {
		$rc_geo_access_api_key = $options['rc_geo_access_ipgeolocation_api_key'];

	} elseif ( 'openlitespeed' === $rc_geo_access_provider ) {
		// Local lookups do not require an API key or the settings UI's marker.
		$rc_geo_access_api_key = 'local';

	}

	if ( 'enabled' === $rc_geo_status ) {

		$ip = rc_geo_access_client_ip();

		if ( ! in_array( $rc_geo_access_provider, array( 'ipstack', 'ipgeolocation', 'openlitespeed' ), true ) || ! is_string( $rc_geo_access_api_key ) || trim( $rc_geo_access_api_key ) === '' ) {
			$rc_geo_access_error_status = __( 'Geolocation is enabled but its provider or API key is not configured. Login access has been denied.', 'rc-geo-access' );
		} else {
			$rc_geo_access_api_result   = rc_geo_access_lookup_ip( $ip, $rc_geo_access_api_key, $rc_geo_access_provider );
			$rc_geo_access_error_status = rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider );
		}

		if ( '' === $rc_geo_access_error_status ) { // If there are no errors then proceed with restriction...

			// Output the 2 letter "country_code" and other details...
			if ( 'ipstack' === $rc_geo_access_provider ) {
				$rc_geo_access_country_name = $rc_geo_access_api_result['country_name'] ?? 'Unknown';
				$rc_geo_access_longitude    = $rc_geo_access_api_result['longitude'] ?? 'N/A';
				$rc_geo_access_latitude     = $rc_geo_access_api_result['latitude'] ?? 'N/A';

			} elseif ( 'ipgeolocation' === $rc_geo_access_provider ) {
				$rc_geo_access_country_name = $rc_geo_access_api_result['country_name'] ?? 'Unknown';
				$rc_geo_access_longitude    = $rc_geo_access_api_result['longitude'] ?? 'N/A';
				$rc_geo_access_latitude     = $rc_geo_access_api_result['latitude'] ?? 'N/A';

			} elseif ( 'openlitespeed' === $rc_geo_access_provider ) {
				$rc_geo_access_country_name = $rc_geo_access_api_result['country_name'];
				$rc_geo_access_longitude    = 'N/A';
				$rc_geo_access_latitude     = 'N/A';
			}
			$rc_geo_access_country_code = rc_geo_access_country_code( $rc_geo_access_api_result, $rc_geo_access_provider );

			// Get list of enabled countries...
			$rc_geo_access_restricted_countries = array();
			if ( isset( $options['rc_geo_access_restricted_countries'] ) ) {
				$rc_geo_access_restricted_countries = $options['rc_geo_access_restricted_countries'];
			}
			if ( ! is_array( $rc_geo_access_restricted_countries ) ) {
				$rc_geo_access_restricted_countries = array();
			}

			// If the user location is not whitelisted then prevent access to the login page...
			if ( ! in_array( $rc_geo_access_country_code, $rc_geo_access_restricted_countries, true ) ) {
				$denied_country = $rc_geo_access_country_code;

				/*
				 * Send an email notification if email recipient field is completed and is a valid email address
				 * and notifications type is set to "restrictions_and_errors"...
				 */

				if ( '' !== $options['rc_geo_access_email_recipient'] && is_email( $options['rc_geo_access_email_recipient'] ) && 'restrictions_and_errors' === $options['rc_geo_access_email_notification_type'] ) {

					$to      = $options['rc_geo_access_email_recipient'];
					$subject = sprintf(
						/* translators: %s: Site URL. */
						__( 'RC Geo Access restriction notification on %s', 'rc-geo-access' ),
						site_url()
					);
					$body  = '<p><b>' . esc_html__( 'RC Geo Access restriction notification', 'rc-geo-access' ) . '</b></p>' . PHP_EOL;
					$body .= '<p>' . esc_html__( 'An attempt was made to access the login page or authenticate from a restricted location.', 'rc-geo-access' ) . '</p>' . PHP_EOL;
					$body .= '<p>' . esc_html__( 'Geolocation data for the requesting IP address:', 'rc-geo-access' ) . '</p>' . PHP_EOL;
					$body .= '<ul><li>' . esc_html( $ip ) . '</li>' . PHP_EOL;
					$body .= '<li>' . esc_html__( 'Country Name:', 'rc-geo-access' ) . ' ' . esc_html( $rc_geo_access_country_name ) . '</li>' . PHP_EOL;
					$body .= '<li>' . esc_html__( 'Country Code:', 'rc-geo-access' ) . ' ' . esc_html( $rc_geo_access_country_code ) . '</li>' . PHP_EOL;
					$body .= '<li>' . esc_html__( 'Longitude:', 'rc-geo-access' ) . ' ' . esc_html( $rc_geo_access_longitude ) . '</li>' . PHP_EOL;
					$body .= '<li>' . esc_html__( 'Latitude:', 'rc-geo-access' ) . ' ' . esc_html( $rc_geo_access_latitude ) . '</li>' . PHP_EOL;
					if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
						$body .= '<li>' . esc_html__( 'Referrer:', 'rc-geo-access' ) . ' ' . esc_html( rc_geo_access_server_text( 'HTTP_REFERER' ) ) . '</li>' . PHP_EOL;
					}
					$body   .= '</ul>' . PHP_EOL;
					$body   .= '<br>' . PHP_EOL;
					$body   .= '<p><a href="' . esc_url( site_url() ) . '/wp-admin/options-general.php?page=rc_geo_access">' . esc_html__( 'Click here to manage RC Geo Access settings on ', 'rc-geo-access' ) . esc_url( site_url() ) . '</a></p>' . PHP_EOL;
					$body   .= '<br><br><hr><br><br>' . PHP_EOL;
					$body   .= '<p><a href="https://qreate.co.uk/projects#rcgeoaccess">RC Geo Access</a> — ' . esc_html__( 'a WordPress plugin developed by Rick Curran.', 'rc-geo-access' ) . ' ';
					$body   .= '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . esc_html__( 'click here to support it with a donation', 'rc-geo-access' ) . '</a>.</p>' . PHP_EOL;
					$headers = array( 'Content-Type: text/html; charset=UTF-8' );
					rc_geo_access_send_notification( 'restriction', $to, $subject, $body, $headers );
				}

				// Block display of login page and display a message...
				rc_geo_access_deny_access( $denied_country );
			}
		} else {
			$denied_country = 'UNKNOWN';

			/*
			 * Send an Error email notification if email recipient field is completed and is a valid email address...
			 */
			if ( '' !== $options['rc_geo_access_email_recipient'] && is_email( $options['rc_geo_access_email_recipient'] ) ) {

				$to      = $options['rc_geo_access_email_recipient'];
				$subject = sprintf(
					/* translators: %s: Site URL. */
					__( 'RC Geo Access error notification on %s', 'rc-geo-access' ),
					site_url()
				);
				$body  = '<p><b>' . esc_html__( 'RC Geo Access error notification', 'rc-geo-access' ) . '</b></p>' . PHP_EOL;
				$body .= '<p>' . esc_html__( 'RC Geo Access could not determine the country for a login request, so access was denied. Please read the error message below and correct the problem:', 'rc-geo-access' ) . '</p>' . PHP_EOL;
				$body .= '<p>' . esc_html__( 'IP Address:', 'rc-geo-access' ) . ' ' . esc_html( $ip ) . '</p>' . PHP_EOL;

				$body .= '<p style="color:darkorange;font-weight:bold;font-size:120%;">' . esc_html( $rc_geo_access_error_status ) . '</p>';

				if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
					$body .= '<p>' . esc_html__( 'Referrer:', 'rc-geo-access' ) . ' ' . esc_html( rc_geo_access_server_text( 'HTTP_REFERER' ) ) . '</p>' . PHP_EOL;
				}

				$body   .= '<br>' . PHP_EOL;
				$body   .= '<p><a href="' . esc_url( site_url() ) . '/wp-admin/options-general.php?page=rc_geo_access">' . esc_html__( 'Click here to manage RC Geo Access settings on ', 'rc-geo-access' ) . esc_url( site_url() ) . '</a></p>' . PHP_EOL;
				$body   .= '<br><br><hr><br><br>' . PHP_EOL;
				$body   .= '<p><a href="https://qreate.co.uk/projects#rcgeoaccess">RC Geo Access</a> — ' . esc_html__( 'a WordPress plugin developed by Rick Curran.', 'rc-geo-access' ) . ' ';
				$body   .= '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . esc_html__( 'click here to support it with a donation', 'rc-geo-access' ) . '</a>.</p>' . PHP_EOL;
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				rc_geo_access_send_notification( 'error', $to, $subject, $body, $headers );
			}
			rc_geo_access_deny_access( $denied_country );

		}
	}
}

/**
 * Terminate the current request with a country-restriction error.
 *
 * @param string $country_code The country code or UNKNOWN error identifier.
 *
 * @return void
 */
function rc_geo_access_deny_access( $country_code ) {
	wp_die(
		esc_html__( 'Sorry, access to this page is not available. Please contact the administrator of this website and provide the error code below to help them assist you.', 'rc-geo-access' ) .
		'<br><br>Error Code: RCGEOAXS' . esc_html( $country_code ) . '001<br>',
		'',
		array( 'response' => 403 )
	);
}

/**
 * Extract and normalize a recognised provider country code.
 *
 * @param mixed  $result The provider response.
 * @param string $provider The provider identifier.
 *
 * @return string A recognised country code, or an empty string.
 */
function rc_geo_access_country_code( $result, $provider ) {
	$fields = array(
		'ipstack'       => 'country_code',
		'ipgeolocation' => 'country_code2',
		'openlitespeed' => 'geoip_country_code',
	);
	if ( ! is_array( $result ) || ! is_string( $provider ) || ! isset( $fields[ $provider ] ) ) {
		return '';
	}
	$code = $result[ $fields[ $provider ] ] ?? '';
	if ( ! is_string( $code ) ) {
		return '';
	}
	$code = strtoupper( trim( $code ) );
	return in_array( $code, rc_geo_access_get_countries(), true ) ? $code : '';
}

/**
 * Validate a provider response and describe lookup failures.
 *
 * @param mixed  $rc_geo_access_api_result The provider response.
 * @param string $rc_geo_access_provider The provider identifier.
 *
 * @return string The error description, or an empty string for valid data.
 */
function rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider ) {

	$rga_error_status = '';
	if ( ! is_array( $rc_geo_access_api_result ) ) {
		return __( 'The geolocation lookup did not return a valid response. Login access has been denied.', 'rc-geo-access' );
	}

	if ( isset( $rc_geo_access_api_result['rc_geo_access_error'] ) && is_string( $rc_geo_access_api_result['rc_geo_access_error'] ) ) {
		return $rc_geo_access_api_result['rc_geo_access_error'];
	}

	if ( 'ipstack' === $rc_geo_access_provider ) {
		// Check if request to ipstack fails...
		if ( array_key_exists( 'success', $rc_geo_access_api_result ) && true !== $rc_geo_access_api_result['success'] ) { // Failure or malformed status.
			$error    = isset( $rc_geo_access_api_result['error'] ) && is_array( $rc_geo_access_api_result['error'] ) ? $rc_geo_access_api_result['error'] : array();
			$rga_code = isset( $error['code'] ) && is_scalar( $error['code'] ) ? (int) $error['code'] : 0;
			$rga_type = isset( $error['type'] ) && is_string( $error['type'] ) ? $error['type'] : 'unknown_error';

			if ( 101 === $rga_code && 'invalid_access_key' === $rga_type ) { // INVALID API KEY.
				$rga_error_status = __( 'Sorry, an invalid API Key was specified. Please check you have entered it correctly.', 'rc-geo-access' );
				// 401 error.

			} elseif ( 102 === $rga_code ) { // INACTIVE USER.
				$rga_error_status = __( 'Sorry, the user account associated with the API Key appears to be inactive. Please check that you have an active IPStack account and enter a valid API Key.', 'rc-geo-access' );
				// 401 error.

			} elseif ( 104 === $rga_code ) { // REACHED API LIMIT.
				$rga_error_status = __( 'Sorry, you have reached the maximum allowed amount of monthly API requests for your API provider. You may need to upgrade your plan to increase the API requests, login access will be denied until the allowance is restored or the geolocation configuration is changed.', 'rc-geo-access' );
				// 429 error.

			} else {
				// Fall back to any other error codes.
				$rga_error_status = sprintf(
				/* translators: 1: Provider error number, 2: Provider error identifier. */
					__( 'Provider error %1$d: %2$s', 'rc-geo-access' ),
					$rga_code,
					$rga_type
				);
			}
		}
	} elseif ( 'ipgeolocation' === $rc_geo_access_provider ) {
		// Check if request to ipgeolocation fails...
		if ( array_key_exists( 'message', $rc_geo_access_api_result ) ) {
			// If `message` appears in response then there is an error of some kind...
			// $rga_code = $rc_geo_access_api_result[ 'error' ][ 'code' ];.
			$rga_code = is_string( $rc_geo_access_api_result['message'] ) && '' !== $rc_geo_access_api_result['message'] ? $rc_geo_access_api_result['message'] : 'Unknown geolocation error';
			// INVALID API KEY.
			if ( strpos( $rga_code, 'Provided API key is not valid' ) !== false ) {
				$rga_error_status = __( 'Sorry, an invalid API Key was specified. Please check you have entered it correctly.', 'rc-geo-access' );

			} elseif ( strpos( $rga_code, 'Your monthly limit has been reached' ) !== false ) { // REACHED API LIMIT.
				$rga_error_status = __( 'Sorry, you have reached the maximum allowed amount of monthly API requests for your API provider. You may need to upgrade your plan to increase the API requests, login access will be denied until the allowance is restored or the geolocation configuration is changed.', 'rc-geo-access' );
				// 429 error.

			} else {
				// Fall back to any other error codes.
				$rga_error_status = $rga_code;
			}
		}
	} elseif ( 'openlitespeed' === $rc_geo_access_provider ) {
		// The display name is optional; only a recognised country code is required.
		if ( rc_geo_access_country_code( $rc_geo_access_api_result, $rc_geo_access_provider ) === '' ) {
			$rga_error_status = __( 'Sorry, the OpenLiteSpeed GeoIP variables could not be read. This could be due to the IP address not being found in the Geolocation database. Please also check that you have configured the OLS geolocation lookup function correctly.', 'rc-geo-access' );
		}
	}
	if ( '' === $rga_error_status && rc_geo_access_country_code( $rc_geo_access_api_result, $rc_geo_access_provider ) === '' ) {
		$rga_error_status = __( 'The geolocation lookup did not return a recognised country code. Login access has been denied.', 'rc-geo-access' );
	}

	return $rga_error_status;
}



/**
 * Look up the visitor using the selected local or external provider.
 *
 * @param mixed  $ip The visitor or connection IP address.
 * @param string $rc_geo_access_key The provider access key, if required.
 * @param string $rc_geo_access_provider The provider identifier.
 *
 * @return array Provider country data or a structured lookup error.
 */
function rc_geo_access_lookup_ip( $ip, $rc_geo_access_key, $rc_geo_access_provider ) {
	if ( ! is_string( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return rc_geo_access_lookup_error( __( 'The visitor IP could not be established safely. Check proxy configuration.', 'rc-geo-access' ) );
	}
	if ( 'openlitespeed' === $rc_geo_access_provider ) {
		if ( rc_geo_access_connection_ip() !== $ip ) {
			return rc_geo_access_lookup_error( __( 'A proxy supplied the visitor IP, but the local GeoIP lookup cannot be verified for that address. Configure OpenLiteSpeed to restore the visitor IP before geolocation, or use an external provider.', 'rc-geo-access' ) );
		}
		return array(
			'geoip_country_code' => rc_geo_access_validate_server_country( $_SERVER['GEOIP_COUNTRY_CODE'] ?? '' ),
			'country_name'       => rc_geo_access_server_text( 'GEOIP_COUNTRY_NAME' ),
			'latitude'           => 0,
			'longitude'          => 0,
		);
	}
	return rc_geo_access_http_lookup( $ip, $rc_geo_access_key, $rc_geo_access_provider );
}

/**
 * Return country names mapped to their two-letter country codes.
 *
 * @return array Country names mapped to their codes.
 */
function rc_geo_access_get_countries() {

	// 2 Digit Country Code array.
	$countries = array(
		'Afghanistan'                                  => 'AF',
		'Aland Islands'                                => 'AX',
		'Albania'                                      => 'AL',
		'Algeria'                                      => 'DZ',
		'American Samoa'                               => 'AS',
		'Andorra'                                      => 'AD',
		'Angola'                                       => 'AO',
		'Anguilla'                                     => 'AI',
		'Antarctica'                                   => 'AQ',
		'Antigua and Barbuda'                          => 'AG',
		'Argentina'                                    => 'AR',
		'Armenia'                                      => 'AM',
		'Aruba'                                        => 'AW',
		'Australia'                                    => 'AU',
		'Austria'                                      => 'AT',
		'Azerbaijan'                                   => 'AZ',
		'Bahamas'                                      => 'BS',
		'Bahrain'                                      => 'BH',
		'Bangladesh'                                   => 'BD',
		'Barbados'                                     => 'BB',
		'Belarus'                                      => 'BY',
		'Belgium'                                      => 'BE',
		'Belize'                                       => 'BZ',
		'Benin'                                        => 'BJ',
		'Bermuda'                                      => 'BM',
		'Bhutan'                                       => 'BT',
		'Bolivia, Plurinational State of'              => 'BO',
		'Bonaire, Sint Eustatius and Saba'             => 'BQ',
		'Bosnia and Herzegovina'                       => 'BA',
		'Botswana'                                     => 'BW',
		'Bouvet Island'                                => 'BV',
		'Brazil'                                       => 'BR',
		'British Indian Ocean Territory'               => 'IO',
		'Brunei Darussalam'                            => 'BN',
		'Bulgaria'                                     => 'BG',
		'Burkina Faso'                                 => 'BF',
		'Burundi'                                      => 'BI',
		'Cambodia'                                     => 'KH',
		'Cameroon'                                     => 'CM',
		'Canada'                                       => 'CA',
		'Cape Verde'                                   => 'CV',
		'Cayman Islands'                               => 'KY',
		'Central African Republic'                     => 'CF',
		'Chad'                                         => 'TD',
		'Chile'                                        => 'CL',
		'China'                                        => 'CN',
		'Christmas Island'                             => 'CX',
		'Cocos (Keeling) Islands'                      => 'CC',
		'Colombia'                                     => 'CO',
		'Comoros'                                      => 'KM',
		'Congo'                                        => 'CG',
		'Congo, the Democratic Republic of the'        => 'CD',
		'Cook Islands'                                 => 'CK',
		'Costa Rica'                                   => 'CR',
		'Cote d\'Ivoire'                               => 'CI',
		'Croatia'                                      => 'HR',
		'Cuba'                                         => 'CU',
		'Curacao'                                      => 'CW',
		'Cyprus'                                       => 'CY',
		'Czech Republic'                               => 'CZ',
		'Denmark'                                      => 'DK',
		'Djibouti'                                     => 'DJ',
		'Dominica'                                     => 'DM',
		'Dominican Republic'                           => 'DO',
		'Ecuador'                                      => 'EC',
		'Egypt'                                        => 'EG',
		'El Salvador'                                  => 'SV',
		'Equatorial Guinea'                            => 'GQ',
		'Eritrea'                                      => 'ER',
		'Estonia'                                      => 'EE',
		'Ethiopia'                                     => 'ET',
		'Falkland Islands (Malvinas)'                  => 'FK',
		'Faroe Islands'                                => 'FO',
		'Fiji'                                         => 'FJ',
		'Finland'                                      => 'FI',
		'France'                                       => 'FR',
		'French Guiana'                                => 'GF',
		'French Polynesia'                             => 'PF',
		'French Southern Territories'                  => 'TF',
		'Gabon'                                        => 'GA',
		'Gambia'                                       => 'GM',
		'Georgia'                                      => 'GE',
		'Germany'                                      => 'DE',
		'Ghana'                                        => 'GH',
		'Gibraltar'                                    => 'GI',
		'Greece'                                       => 'GR',
		'Greenland'                                    => 'GL',
		'Grenada'                                      => 'GD',
		'Guadeloupe'                                   => 'GP',
		'Guam'                                         => 'GU',
		'Guatemala'                                    => 'GT',
		'Guernsey'                                     => 'GG',
		'Guinea'                                       => 'GN',
		'Guinea-Bissau'                                => 'GW',
		'Guyana'                                       => 'GY',
		'Haiti'                                        => 'HT',
		'Heard Island and McDonald Islands'            => 'HM',
		'Holy See (Vatican City State)'                => 'VA',
		'Honduras'                                     => 'HN',
		'Hong Kong'                                    => 'HK',
		'Hungary'                                      => 'HU',
		'Iceland'                                      => 'IS',
		'India'                                        => 'IN',
		'Indonesia'                                    => 'ID',
		'Iran, Islamic Republic of'                    => 'IR',
		'Iraq'                                         => 'IQ',
		'Ireland'                                      => 'IE',
		'Isle of Man'                                  => 'IM',
		'Israel'                                       => 'IL',
		'Italy'                                        => 'IT',
		'Jamaica'                                      => 'JM',
		'Japan'                                        => 'JP',
		'Jersey'                                       => 'JE',
		'Jordan'                                       => 'JO',
		'Kazakhstan'                                   => 'KZ',
		'Kenya'                                        => 'KE',
		'Kiribati'                                     => 'KI',
		'Korea, Democratic People\'s Republic of'      => 'KP',
		'Korea, Republic of'                           => 'KR',
		'Kuwait'                                       => 'KW',
		'Kyrgyzstan'                                   => 'KG',
		'Lao People\'s Democratic Republic'            => 'LA',
		'Latvia'                                       => 'LV',
		'Lebanon'                                      => 'LB',
		'Lesotho'                                      => 'LS',
		'Liberia'                                      => 'LR',
		'Libya'                                        => 'LY',
		'Liechtenstein'                                => 'LI',
		'Lithuania'                                    => 'LT',
		'Luxembourg'                                   => 'LU',
		'Macao'                                        => 'MO',
		'Macedonia, the Former Yugoslav Republic of'   => 'MK',
		'Madagascar'                                   => 'MG',
		'Malawi'                                       => 'MW',
		'Malaysia'                                     => 'MY',
		'Maldives'                                     => 'MV',
		'Mali'                                         => 'ML',
		'Malta'                                        => 'MT',
		'Marshall Islands'                             => 'MH',
		'Martinique'                                   => 'MQ',
		'Mauritania'                                   => 'MR',
		'Mauritius'                                    => 'MU',
		'Mayotte'                                      => 'YT',
		'Mexico'                                       => 'MX',
		'Micronesia, Federated States of'              => 'FM',
		'Moldova, Republic of'                         => 'MD',
		'Monaco'                                       => 'MC',
		'Mongolia'                                     => 'MN',
		'Montenegro'                                   => 'ME',
		'Montserrat'                                   => 'MS',
		'Morocco'                                      => 'MA',
		'Mozambique'                                   => 'MZ',
		'Myanmar'                                      => 'MM',
		'Namibia'                                      => 'NA',
		'Nauru'                                        => 'NR',
		'Nepal'                                        => 'NP',
		'Netherlands'                                  => 'NL',
		'New Caledonia'                                => 'NC',
		'New Zealand'                                  => 'NZ',
		'Nicaragua'                                    => 'NI',
		'Niger'                                        => 'NE',
		'Nigeria'                                      => 'NG',
		'Niue'                                         => 'NU',
		'Norfolk Island'                               => 'NF',
		'Northern Mariana Islands'                     => 'MP',
		'Norway'                                       => 'NO',
		'Oman'                                         => 'OM',
		'Pakistan'                                     => 'PK',
		'Palau'                                        => 'PW',
		'Palestine, State of'                          => 'PS',
		'Panama'                                       => 'PA',
		'Papua New Guinea'                             => 'PG',
		'Paraguay'                                     => 'PY',
		'Peru'                                         => 'PE',
		'Philippines'                                  => 'PH',
		'Pitcairn'                                     => 'PN',
		'Poland'                                       => 'PL',
		'Portugal'                                     => 'PT',
		'Puerto Rico'                                  => 'PR',
		'Qatar'                                        => 'QA',
		'Reunion'                                      => 'RE',
		'Romania'                                      => 'RO',
		'Russian Federation'                           => 'RU',
		'Rwanda'                                       => 'RW',
		'Saint Barthelemy'                             => 'BL',
		'Saint Helena, Ascension and Tristan da Cunha' => 'SH',
		'Saint Kitts and Nevis'                        => 'KN',
		'Saint Lucia'                                  => 'LC',
		'Saint Martin (French part)'                   => 'MF',
		'Saint Pierre and Miquelon'                    => 'PM',
		'Saint Vincent and the Grenadines'             => 'VC',
		'Samoa'                                        => 'WS',
		'San Marino'                                   => 'SM',
		'Sao Tome and Principe'                        => 'ST',
		'Saudi Arabia'                                 => 'SA',
		'Senegal'                                      => 'SN',
		'Serbia'                                       => 'RS',
		'Seychelles'                                   => 'SC',
		'Sierra Leone'                                 => 'SL',
		'Singapore'                                    => 'SG',
		'Sint Maarten (Dutch part)'                    => 'SX',
		'Slovakia'                                     => 'SK',
		'Slovenia'                                     => 'SI',
		'Solomon Islands'                              => 'SB',
		'Somalia'                                      => 'SO',
		'South Africa'                                 => 'ZA',
		'South Georgia and the South Sandwich Islands' => 'GS',
		'South Sudan'                                  => 'SS',
		'Spain'                                        => 'ES',
		'Sri Lanka'                                    => 'LK',
		'Sudan'                                        => 'SD',
		'Suriname'                                     => 'SR',
		'Svalbard and Jan Mayen'                       => 'SJ',
		'Swaziland'                                    => 'SZ',
		'Sweden'                                       => 'SE',
		'Switzerland'                                  => 'CH',
		'Syrian Arab Republic'                         => 'SY',
		'Taiwan, Province of China'                    => 'TW',
		'Tajikistan'                                   => 'TJ',
		'Tanzania, United Republic of'                 => 'TZ',
		'Thailand'                                     => 'TH',
		'Timor-Leste'                                  => 'TL',
		'Togo'                                         => 'TG',
		'Tokelau'                                      => 'TK',
		'Tonga'                                        => 'TO',
		'Trinidad and Tobago'                          => 'TT',
		'Tunisia'                                      => 'TN',
		'Turkey'                                       => 'TR',
		'Turkmenistan'                                 => 'TM',
		'Turks and Caicos Islands'                     => 'TC',
		'Tuvalu'                                       => 'TV',
		'Uganda'                                       => 'UG',
		'Ukraine'                                      => 'UA',
		'United Arab Emirates'                         => 'AE',
		'United Kingdom'                               => 'GB',
		'United States'                                => 'US',
		'United States Minor Outlying Islands'         => 'UM',
		'Uruguay'                                      => 'UY',
		'Uzbekistan'                                   => 'UZ',
		'Vanuatu'                                      => 'VU',
		'Venezuela, Bolivarian Republic of'            => 'VE',
		'Viet Nam'                                     => 'VN',
		'Virgin Islands, British'                      => 'VG',
		'Virgin Islands, U.S.'                         => 'VI',
		'Wallis and Futuna'                            => 'WF',
		'Western Sahara'                               => 'EH',
		'Yemen'                                        => 'YE',
		'Zambia'                                       => 'ZM',
		'Zimbabwe'                                     => 'ZW',
	);

	return $countries;
}


/*
 * Activation / Deactivation Checks...
 */
register_activation_hook( __FILE__, 'rc_geo_access_activate' );
/**
 * Disable restrictions on reactivation to permit administrator recovery.
 *
 * @return void
 */
function rc_geo_access_activate() {
	// We check to see if the plugin has been previously activated and.
	// had its restriction function enabled. If it has then we set.
	// 'rc_geo_access_status' to 'disabled' to prevent the user from.
	// potentially being locked out of their site again. (If the user.
	// has had to resort to deleting the plugin in order to regain.
	// access to their site then we don't want the same active plugin.
	// settings to be loaded automatically as they will likely get.
	// locked out again!).
	$current_options = get_option( 'rc_geo_access_settings', array() );
	if ( isset( $current_options['rc_geo_access_status'] ) ) {

		$current_options['rc_geo_access_status'] = 'disabled';
		update_option( 'rc_geo_access_settings', $current_options );

	}
}
