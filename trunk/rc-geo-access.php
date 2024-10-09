<?php
/*
Plugin Name: RC Geo Access
Plugin URI: https://qreate.co.uk/projects/#rcgeoaccess
Description: This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it.
Version: 1.49
Author: Rick Curran
Author URI: https://qreate.co.uk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'admin_menu', 'rc_geo_access_add_admin_menu' );
add_action( 'admin_init', 'rc_geo_access_settings_init' );

/*
 * Add Menu Page under Settings
 */
function rc_geo_access_add_admin_menu() {
    
	add_options_page( 'RC GEO Access', 'RC GEO Access', 'manage_options', 'rc_geo_access', 'rc_geo_access_options_page' );

}

/*
 * Display Notice when plugin active and is disabled and has no API key set
 */
function rc_geo_access_admin_notice__info() {
    
    $options = get_option( 'rc_geo_access_settings' );

    if ( 
        ( !isset( $options[ 'rc_geo_access_status' ] ) && !isset( $options[ 'rc_geo_access_ipstack_api_key' ] ) ) || ( $options[ 'rc_geo_access_status' ] !== 'enabled' && $options[ 'rc_geo_access_ipstack_api_key' ] === '' ) ||
        ( !isset( $options[ 'rc_geo_access_status' ] ) && !isset( $options[ 'rc_geo_access_ipgeolocation_api_key' ] ) ) || ( $options[ 'rc_geo_access_status' ] !== 'enabled' && $options[ 'rc_geo_access_ipgeolocation_api_key' ] === '' ) ||
        ( !isset( $options[ 'rc_geo_access_status' ] ) && !isset( $options[ 'rc_geo_access_openlitespeed_local_geoip' ] ) ) || ( $options[ 'rc_geo_access_status' ] !== 'enabled' && $options[ 'rc_geo_access_openlitespeed_local_geoip' ] === '' ) 
    ) {
        
        if ( is_admin() ) {
            
            $screen = get_current_screen();
            
            if ( $screen -> id == 'dashboard' || $screen -> id == 'plugins' ) {
                // This is the admin Dashboard screen
                echo '<div class="notice notice-info">';
                echo '<p>' . __( 'Thanks for enabling the RC Geo Access plugin. Please go to the <a href="options-general.php?page=rc_geo_access">' . __( 'plugin settings page', 'rc_geo_access_plugin' ) . '</a> to configure it and begin restricting access to your login page.', 'rc_geo_access_plugin' ) . '</p>';
                echo '</div>';
            }
            
        }
        
    }
    
}
add_action( 'admin_notices', 'rc_geo_access_admin_notice__info' );



/*
 * Settings Page Render / Storing...
 */
function rc_geo_access_settings_init() { 

	register_setting( 'rc_geo_access_admin_page', 'rc_geo_access_settings' );

	add_settings_section( 'rc_geo_access_admin_page_section',  __( 'What does this plugin do?', 'rc_geo_access_plugin' ),  'rc_geo_access_settings_section_callback', 'rc_geo_access_admin_page' );

	add_settings_field( 'rc_geo_access_status', __( 'Restriction Status:', 'rc_geo_access_plugin' ), 'rc_geo_access_status_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );
    
	add_settings_field( 'rc_geo_access_api_provider', __( 'API Provider:', 'rc_geo_access_plugin' ), 'rc_geo_access_api_provider_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

    add_settings_field( 'rc_geo_access_ipstack_api_key', __( 'IPStack API Key:', 'rc_geo_access_plugin' ), 'rc_geo_access_ipstack_api_key_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

    add_settings_field( 'rc_geo_access_ipgeolocation_api_key', __( 'IPGeolocation API Key:', 'rc_geo_access_plugin' ), 'rc_geo_access_ipgeolocation_api_key_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

    add_settings_field( 'rc_geo_access_openlitespeed_local_geoip', __( 'OpenLiteSpeed GeoIP:', 'rc_geo_access_plugin' ), 'rc_geo_access_openlitespeed_local_geoip_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_restricted_countries', __( 'Country Whitelist:', 'rc_geo_access_plugin' ), 'rc_geo_access_restricted_countries_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );
            
    add_settings_section( 'rc_geo_access_admin_page_notifications_section', 'Email Notifications', 'rc_geo_access_settings_notifications_section_callback', 'rc_geo_access_admin_page' );
    
    add_settings_field( 'rc_geo_access_email_recipient', __( 'Email Recipient:', 'rc_geo_access_plugin' ), 'rc_geo_access_email_recipient_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_notifications_section' );
    
    add_settings_field( 'rc_geo_access_email_notification_type', __( 'Notification Type:', 'rc_geo_access_plugin' ), 'rc_geo_access_email_notification_type_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_notifications_section' );
    
    add_settings_section( 'rc_geo_access_admin_page_save_section', '', 'rc_geo_access_settings_save_section_callback', 'rc_geo_access_admin_page' );

    
    add_settings_section( 'rc_geo_access_admin_page_donate_section', __( 'Support this plugin', 'rc_geo_access_plugin' ), 'rc_geo_access_settings_donate_section_callback', 'rc_geo_access_admin_page' );

    

}

function rc_geo_access_email_recipient_render() {

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<input type='text' name='rc_geo_access_settings[rc_geo_access_email_recipient]' value='<?php echo $options[ 'rc_geo_access_email_recipient' ]; ?>' size="50">

<?php
    if ( $options[ 'rc_geo_access_email_recipient' ] === '' ) {
        
        echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Please enter a valid email address to receive notifications.', 'rc_geo_access_plugin' ) . '</p>';
        
    }
    
    if ( $options[ 'rc_geo_access_email_recipient' ] !== '' ) {
        
        if ( ! is_email( $options[ 'rc_geo_access_email_recipient' ] ) ) {
        
            echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Sorry, that doesn\'t appear to be a valid email address, please check your typing and try again.', 'rc_geo_access_plugin' ) . ' -' . $options[ 'rc_geo_access_email_recipient' ] . '-</p>';
            
        }
        
    }

}

function rc_geo_access_email_notification_type_render() { 

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<select name='rc_geo_access_settings[rc_geo_access_email_notification_type]'>
		<option value='restrictions_and_errors' <?php selected( $options[ 'rc_geo_access_email_notification_type' ], 'restrictions_and_errors' ); ?>>Restrictions and Errors</option>
		<option value='errors_only' <?php selected( $options[ 'rc_geo_access_email_notification_type' ], 'errors_only' ); ?>>Errors Only</option>
	</select>

<?php

}


function rc_geo_access_api_provider_render() { 

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<select name='rc_geo_access_settings[rc_geo_access_api_provider]'>
		<option value="">Select...</option>
		<option value='ipgeolocation' <?php selected( $options[ 'rc_geo_access_api_provider' ], 'ipgeolocation' ); ?>>ipgeolocation.io</option>
		<option value='ipstack' <?php selected( $options[ 'rc_geo_access_api_provider' ], 'ipstack' ); ?>>ipstack.com</option>
		<option value='openlitespeed' <?php selected( $options[ 'rc_geo_access_api_provider' ], 'openlitespeed' ); ?>>OpenLiteSpeed’s local GeoIP variable</option>
	</select>

<?php
    if ( $options[ 'rc_geo_access_api_provider' ] == '' ) {
        echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Please select an API provider from the menu above.', 'rc_geo_access_plugin' ) . '</p>';
    }
}

function rc_geo_access_status_render() {

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<select name='rc_geo_access_settings[rc_geo_access_status]'>
		<option value='disabled' <?php selected( $options[ 'rc_geo_access_status' ], 'disabled' ); ?>>Disabled</option>
		<option value='enabled' <?php selected( $options[ 'rc_geo_access_status' ], 'enabled' ); ?>>Enabled</option>
	</select>

<?php
    if ( $options[ 'rc_geo_access_status' ] === 'disabled' ) {
        
        echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Change this to "Enabled" to turn on access restriction', 'rc_geo_access_plugin' ) . '</p>';
        
    }

}

function rc_geo_access_ipstack_api_key_render() {

	$options = get_option( 'rc_geo_access_settings' );
    $rc_geo_access_provider = $options[ 'rc_geo_access_api_provider' ];
    $rc_geo_access_ipstack_api_key = $options[ 'rc_geo_access_ipstack_api_key' ];
    $rc_geo_access_ipgeolocation_api_key = $options[ 'rc_geo_access_ipgeolocation_api_key' ];
    
    $rc_geo_access_api_warn = '';
    if ( $rc_geo_access_provider == 'ipgeolocation' && $rc_geo_access_ipstack_api_key != '' ) {
        $rc_geo_access_api_warn = '<p style="color:darkorange;font-weight:normal;">You have selected <strong>ipgeolocation</strong> as your geolocation API provider, only enter an API key in the above <strong>ipstack</strong> API Key field if using the <strong>ipstack</strong> API.</p>';
    }
    
	?>
	<input type='text' name='rc_geo_access_settings[rc_geo_access_ipstack_api_key]' value='<?php echo $options[ 'rc_geo_access_ipstack_api_key' ]; ?>' size="50">
	<?php
        if ( $options[ 'rc_geo_access_ipstack_api_key' ] === '' && $options[ 'rc_geo_access_api_provider' ] === 'ipstack' ) {
            
            echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Please enter an API Key. This is required for access restriction to function', 'rc_geo_access_plugin' ) . '</p>';
            
        }
    
        echo $rc_geo_access_api_warn;
    
        // Check if API Key has any errors...
        if ( $options[ 'rc_geo_access_status' ] === 'enabled' && $options[ 'rc_geo_access_ipstack_api_key' ] !== '' ) {
            
            // Lookup current admin user's location...
            $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $options[ 'rc_geo_access_ipstack_api_key' ], $options[ 'rc_geo_access_api_provider' ] );
            // ...and then check response for any errors relating to the API key...
            $rc_geo_access_error_status = rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider );

            if ( $rc_geo_access_error_status !== '' && $rc_geo_access_provider == 'ipstack' ) { // If errors then display message...
                echo '<p style="color:#fff;font-weight:bold;padding:10px;border-radius:8px;background-color:red;">&uarr;&nbsp;' . $rc_geo_access_error_status . '</p>';
            }
            
        }
    
}

function rc_geo_access_ipgeolocation_api_key_render() {

	$options = get_option( 'rc_geo_access_settings' );
    $rc_geo_access_provider = $options[ 'rc_geo_access_api_provider' ];
    
    $rc_geo_access_ipstack_api_key = $options[ 'rc_geo_access_ipstack_api_key' ];
    $rc_geo_access_ipgeolocation_api_key = $options[ 'rc_geo_access_ipgeolocation_api_key' ];
    
    $rc_geo_access_api_warn = '';
    if ( $rc_geo_access_provider == 'ipstack' && $rc_geo_access_ipgeolocation_api_key != '' ) {
        $rc_geo_access_api_warn = '<p style="color:darkorange;font-weight:normal;">You have selected <strong>ipstack</strong> as your geolocation API provider, only enter an API key in the above <strong>ipgeolocation</strong> API Key field if using the <strong>ipgeolocation</strong> API.</p>';

    }
    
	?>
	<input type='text' name='rc_geo_access_settings[rc_geo_access_ipgeolocation_api_key]' value='<?php echo $options[ 'rc_geo_access_ipgeolocation_api_key' ]; ?>' size="50">
	<?php
        if ( $options[ 'rc_geo_access_ipgeolocation_api_key' ] === '' && $options[ 'rc_geo_access_api_provider' ] === 'ipgeolocation' ) {
            
            echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Please enter an API Key. This is required for access restriction to function', 'rc_geo_access_plugin' ) . '</p>';
            
        }
    
        echo $rc_geo_access_api_warn;
    
        // Check if API Key has any errors...
        if ( $options[ 'rc_geo_access_status' ] === 'enabled' && $options[ 'rc_geo_access_ipgeolocation_api_key' ] !== '' ) {
            
            // Lookup current admin user's location...
            $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $options[ 'rc_geo_access_ipgeolocation_api_key' ], $options[ 'rc_geo_access_api_provider' ] );
            // ...and then check response for any errors relating to the API key...
            $rc_geo_access_error_status = rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider );

            if ( $rc_geo_access_error_status !== '' && $rc_geo_access_provider == 'ipgeolocation' ) { // If errors then display message...
                echo '<p style="color:#fff;font-weight:bold;padding:10px;border-radius:8px;background-color:red;">&uarr;&nbsp;' . $rc_geo_access_error_status . '</p>';
            }
            
        }
    
}

function rc_geo_access_openlitespeed_local_geoip_render() {

	$options = get_option( 'rc_geo_access_settings' );
    $rc_geo_access_provider = $options[ 'rc_geo_access_api_provider' ];
    
    $rc_geo_access_ipstack_api_key = $options[ 'rc_geo_access_ipstack_api_key' ];
    $rc_geo_access_ipgeolocation_api_key = $options[ 'rc_geo_access_ipgeolocation_api_key' ];
    $rc_geo_access_openlitespeed_local_geoip = $options[ 'rc_geo_access_openlitespeed_local_geoip' ];
    
    $rc_geo_access_api_warn = '';
    if ( $rc_geo_access_provider == 'openlitespeed' && ( $rc_geo_access_ipgeolocation_api_key != '' || $rc_geo_access_ipstack_api_key != '' ) ) {
        $rc_geo_access_api_warn = '<p style="color:darkorange;font-weight:normal;">You have selected <strong>OpenLiteSpeed\'s local GeoIP variable</strong> as your geolocation API provider, only enter an API key in the <strong>ipgeolocation.io</strong> or <strong>ipstack.com</strong> API Key fields if using those APIs.</p>';
    }
    
	?>
	<input type='text' name='rc_geo_access_settings[rc_geo_access_openlitespeed_local_geoip]' value='NO API KEY REQUIRED' size="50" readonly>
	<?php
        if ( $options[ 'rc_geo_access_openlitespeed_local_geoip' ] === '' && $options[ 'rc_geo_access_api_provider' ] === 'openlitespeed' ) {
            
            echo '<p style="color:darkorange;font-weight:bold;">&uarr;&nbsp;' . __( 'Please ensure a Geolocation database is configured on your OpenLiteSpeed server. This is required for access restriction to function', 'rc_geo_access_plugin' ) . '</p>';
            
        }
    
        echo $rc_geo_access_api_warn;
    
        // Check if API Key has any errors...
        if ( $options[ 'rc_geo_access_status' ] === 'enabled' && $options[ 'rc_geo_access_openlitespeed_local_geoip' ] !== '' ) {
            
            // Lookup current admin user's location...
            $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $options[ 'rc_geo_access_openlitespeed_local_geoip' ], $options[ 'rc_geo_access_api_provider' ] );
            // ...and then check response for any errors relating to the API key...
            $rc_geo_access_error_status = rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider );

            if ( $rc_geo_access_error_status !== '' && $rc_geo_access_provider == 'openlitespeed' ) { // If errors then display message...
                echo '<p style="color:#fff;font-weight:bold;padding:10px;border-radius:8px;background-color:red;">&uarr;&nbsp;' . $rc_geo_access_error_status . '</p>';
            }
            
        }
    
}


function rc_geo_access_restricted_countries_render() { 

	$options = get_option( 'rc_geo_access_settings' );
    $rc_geo_access_provider = $options[ 'rc_geo_access_api_provider' ];
    if ( $rc_geo_access_provider == 'ipstack' ) {
        $rc_geo_access_api_key = $options[ 'rc_geo_access_ipstack_api_key' ];

    } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
        $rc_geo_access_api_key = $options[ 'rc_geo_access_ipgeolocation_api_key' ];
        
    } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
        $rc_geo_access_api_key = $options[ 'rc_geo_access_openlitespeed_local_geoip' ];
    }
    
    
    $rc_geo_access_restricted_countries = array();
    if ( isset( $options[ 'rc_geo_access_restricted_countries' ] ) ) {
        $rc_geo_access_restricted_countries = maybe_unserialize( $options[ 'rc_geo_access_restricted_countries' ] );
    }
    
    $rc_geo_access_countries = rc_geo_access_get_countries();
    
    $rc_geo_access_no_countries_forced_country_code = '';
    
    if ( $options[ 'rc_geo_access_status' ] === 'enabled' && $rc_geo_access_provider !== '' && ( $options[ 'rc_geo_access_ipstack_api_key' ] !== '' || $options[ 'rc_geo_access_ipgeolocation_api_key' ] !== '' || $options[ 'rc_geo_access_openlitespeed_local_geoip' ] !== '' ) ) {
        $rga_countries_whitelist_style = 'style="display:block;"';
        
    } else {
        
        $rga_countries_whitelist_style = 'style="display:none;"';
        echo '<p style="margin-bottom:50px;color:darkorange;font-weight:bold;">&uarr; ' . __( 'Set Restriction Status to "Enabled", select an API provider and enter an API Key above to enable the Countries Whitelist.', 'rc_geo_access_plugin' ) . '</p>';
        
    }
        
    echo '<div class="rc_geo_access_countries_whitelist" ' . $rga_countries_whitelist_style . '>';
    
    if ( count( $rc_geo_access_countries ) !== 0 ) {

        echo '<p><strong>' . __( 'Initially all countries are blocked from accessing the login page, although we do try to detect your current country and enable that by default, check the boxes below for the countries that you want to allow to access your login page and make sure to include your own if it is not already checked:', 'rc_geo_access_plugin' ) . '</strong></p>';

        // Lookup current admin user's location, we want to ensure the current user does not get locked out of their site,
        // so we lookup their location and *always* force-check that location's checkbox to be whitelisted.
        $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $rc_geo_access_api_key, $options[ 'rc_geo_access_api_provider' ] );        
        
        if ( $rc_geo_access_provider == 'ipstack' ) {
             $rc_geo_access_country_code = $rc_geo_access_api_result[ 'country_code' ];

        } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
             $rc_geo_access_country_code = $rc_geo_access_api_result[ 'country_code2' ];
            
        } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
             $rc_geo_access_country_code = $rc_geo_access_api_result[ 'geoip_country_code' ];
        }
        
        $rc_geo_access_no_countries_forced_country_code = '';
        if ( $rc_geo_access_country_code != '' ) {
            $rc_geo_access_no_countries_forced_country_code = $rc_geo_access_country_code;
        }

        // Warn user if no countries have access and restriction is enabled...
        if ( is_array($rc_geo_access_restricted_countries) && count( $rc_geo_access_restricted_countries ) === 0 && $options[ 'rc_geo_access_status' ] === 'enabled' && $rc_geo_access_api_key !== '' ) {

            echo '<p style="border:3px solid red;padding:10px;margin-top:20px;margin-bottom:20px;background-color:#fff;color:red;font-weight:bold;">&darr; ';
            echo __( 'WARNING: All Countries are currently blocked from accessing your login page! To try and prevent you from being locked out of your site your current location of ', 'rc_geo_access_plugin' );
            echo '"' . $rc_geo_access_api_result[ 'country_name' ] . ' - ' . $rc_geo_access_country_name = $rc_geo_access_country_code . '"';
            echo __( ' has been added below, please add any other countries you wish to allow access and then click "Save Changes" to confirm these settings. THERE IS A HIGH RISK OF BEING LOCKED OUT OF YOUR SITE IF YOU HAVE NO COUNTRIES SET HERE!', 'rc_geo_access_plugin' );
            echo '</p>';

        }



        echo '<ul style="-webkit-column-width: 300px; -moz-column-width: 300px; column-width: 300px; -webkit-column-rule: 1px dotted #ddd; -moz-column-rule: 1px dotted #ddd; column-rule: 1px dotted #ddd;">';

        foreach ( $rc_geo_access_countries as $k => $v ) {

            $checked = '';
            $il_style = 'padding:5px 10px 5px 8px;display:block;';
            if ( is_array($rc_geo_access_restricted_countries) && in_array( $v, $rc_geo_access_restricted_countries ) ) {
                $checked = ' checked';
                $il_style = 'padding:5px 10px 5px 8px;display:block;background-color:#C1E1C1;border-radius:6px;';
            }

            // Force check user's location to be whitelisted, we're attempting to prevent the user locking themselves out of their site!
            if ( $rc_geo_access_no_countries_forced_country_code === $v ) {
                $checked = ' checked';
                $il_style = 'padding:5px 10px 5px 8px;display:block;background-color:#C1E1C1;border-radius:6px;';
            }

            echo '<li><label style="' . $il_style . '"><input type="checkbox" name="rc_geo_access_settings[rc_geo_access_restricted_countries][]" value="' . $v . '"' . $checked . '> <span>' . $k . ' - ' . $v . '</span></label></li>';


        }

        echo '</ul>';

    }

    echo '</div>';
    
}

function rc_geo_access_settings_section_callback() { 

	echo __( '
    <p>This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.</p>
    <p>The plugin gets the IP address of the user attempting to access the login page and geo-locates their location by using a geolocation API, currently there are three options available to use:</p>
    <ul style="list-style:decimal;margin-left: 1.75rem;">
        <li><strong>ipstack.com:</strong> <a href="http://ipstack.com?utm_source=FirstPromoter&utm_medium=Affiliate&fpr=rick54" target="_blank">ipstack.com</a>.</li>
        <li><strong>ipgeolocation.io:</strong> <a href="https://ipgeolocation.io/" target="_blank">ipgeolocation.io</a>.</li>
        <li><strong>OpenLiteSpeed’s local GeoIP variables:</strong> <a href="https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation" target="_blank">View OLS Enabling GeoLocation instructions</a>.</li>
    </ul>
    <p><strong>Please note: an active API Key or a specifically configured OpenLiteSpeed webserver is required for the plugin to function correctly.</strong> You can register a free account at either of the website addresses above. Please note they offer varying amounts of location API requests for their free and paid plans, it may be necessary to upgrade to a paid plan to provide an increased amount of requests if your site gets a huge amount of login attempts. The “OpenLiteSpeed" option requires a configured IPGeolocation database as per the linked instructions.</p>', 'rc_geo_access_plugin' );
    
    submit_button();

}

function rc_geo_access_settings_save_section_callback( $arg ) {
	
    submit_button();
    
    echo '<hr>';
    
}

function rc_geo_access_settings_notifications_section_callback( $arg ) {
    
    echo __( '<p><strong>Why would I want email notifications?</strong><br>Two reasons: To be notified whenever access to the login page is restricted as this can help you understand how frequently login attempts are made, and to be notified of any error messages relating to use of the geolocation API itself, you\'ll receive details of the error to allow you to troubleshoot and resolve the issue.</p><p><strong>Do I need to enable them?</strong><br>The plugin will work without you providing an email address but there will be a risk of missing important error notifications.</p><p><strong>How do I enable them?</strong><br>Enter an email address in the field below and then select an option from the "Notification Type" dropdown, the default "Restrictions and Errors" will receive all messages or "Errors Only" will receive only critical error messages. <strong>WARNING:</strong> If your site experiences frequent access attempts the default "Restrictions and Errors" setting may result in a high volume of email notifications being sent out! However, it is a useful aid in helping you understand how frequently attempts are made to access your login page, you may wish to switch this to "Errors Only" after running the plugin for a while.</p>', 'rc_geo_access_plugin' );
    
}

function rc_geo_access_settings_donate_section_callback( $arg ) {
	
    echo __( '<p><strong>If you have found this plugin to be useful then please consider a donation. Donations like these help to provide time for <strong><a href="https://qreate.co.uk/about">me</a></strong> to develop plugins like this.</strong></p>', 'rc_geo_access_plugin' );
    echo __( '<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G" class="button button-primary" target="_blank">Donate</a></p>', 'rc_geo_access_plugin' );
    
}

function rc_geo_access_options_page() { 

	?>
    <div id="welcome-panel" class="welcome-panel">
        <div class="welcome-panel-content">
            <div class="welcome-panel-column-container">
                <form action='options.php' method='post'>

                    <h1>RC Geo Access Settings</h1>

                    <?php
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
add_filter( 'plugin_row_meta', 'rc_geo_access_row_meta', 10, 4 );

function rc_geo_access_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
    
    if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
        if ( ! is_network_admin() ) {
            $links_array[] = '<a href="options-general.php?page=rc_geo_access">' . __( 'Settings','rc_geo_access_plugin' ) . '</a>';
        }
        $links_array[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . __( 'Donate', 'rc_geo_access_plugin' ) . '</a>';
    }
    return $links_array;
    
}


/*
 * Restriction checked on login_init
 */
add_action( 'login_init', 'rc_geo_access_func' );

function rc_geo_access_func() {
    
    $options = get_option( 'rc_geo_access_settings' );
    $rc_geo_status = $options[ 'rc_geo_access_status' ];
    $rc_geo_access_provider = $options[ 'rc_geo_access_api_provider' ];
    if ( $rc_geo_access_provider == 'ipstack' ) {
        $rc_geo_access_api_key = $options[ 'rc_geo_access_ipstack_api_key' ];

    } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
        $rc_geo_access_api_key = $options[ 'rc_geo_access_ipgeolocation_api_key' ];
        
    } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
        //wp_die( 'HI openlitespeed' );
        $rc_geo_access_api_key = $options[ 'rc_geo_access_openlitespeed_local_geoip' ];
        
    }
    
    if ( $rc_geo_status === 'enabled' && $rc_geo_access_provider !== '' && $rc_geo_access_api_key !== '' ) { // Check that Restriction is enabled, API provider is not empty and API Key is not empty...
        
        $ip = $_SERVER[ 'REMOTE_ADDR' ];
        
        $rc_geo_access_api_result = rc_geo_access_lookup_ip( $ip , $rc_geo_access_api_key, $rc_geo_access_provider );

        // Check for API errors etc...
        $rc_geo_access_error_status = rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider );
                
        if ( $rc_geo_access_error_status === '' ) { // If there are no errors then proceed with restriction...
            
            // Output the 2 letter "country_code" and other details...
            if ( $rc_geo_access_provider == 'ipstack' ) {
                $rc_geo_access_country_code = $rc_geo_access_api_result[ 'country_code' ];
                $rc_geo_access_country_name = $rc_geo_access_api_result[ 'country_name' ];
                $rc_geo_access_longitude = $rc_geo_access_api_result[ 'longitude' ];
                $rc_geo_access_latitude = $rc_geo_access_api_result[ 'latitude' ];

            } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
                $rc_geo_access_country_code = $rc_geo_access_api_result[ 'country_code2' ];
                $rc_geo_access_country_name = $rc_geo_access_api_result[ 'country_name' ];
                $rc_geo_access_longitude = $rc_geo_access_api_result[ 'longitude' ];
                $rc_geo_access_latitude = $rc_geo_access_api_result[ 'latitude' ];
                
            } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
                $rc_geo_access_country_code = $rc_geo_access_api_result[ 'geoip_country_code' ];
                $rc_geo_access_country_name = $rc_geo_access_api_result[ 'country_name' ];
                $rc_geo_access_longitude = 'N/A';
                $rc_geo_access_latitude = 'N/A';
            }

            // Get list of enabled countries...
            $rc_geo_access_restricted_countries = array();
            if ( isset( $options[ 'rc_geo_access_restricted_countries' ] ) ) {
                $rc_geo_access_restricted_countries = maybe_unserialize( $options[ 'rc_geo_access_restricted_countries' ] );
            }
    
            // If the user location is not whitelisted then prevent access to the login page...
            if ( ! in_array( $rc_geo_access_country_code, $rc_geo_access_restricted_countries ) ) {
                /*
                 * Send an email notification if email recipient field is completed and is a valid email address 
                 * and notifications type is set to "restrictions_and_errors"...
                 */
                
                if ( $options[ 'rc_geo_access_email_recipient' ] !== '' && is_email( $options[ 'rc_geo_access_email_recipient' ] ) && $options[ 'rc_geo_access_email_notification_type' ] === 'restrictions_and_errors') {
                    //$to = 'rickcurran@gmail.com';
                    $to = $options[ 'rc_geo_access_email_recipient' ];
                    $subject = 'Notification Message from the RC Geo Access plugin on ' . site_url();
                    $body = '<p><b>This is a notification message from the RC Geo Access plugin on ' . site_url() . '.</b></p>' . PHP_EOL;
                    $body .= '<p>An attempt was made to access the login page from a restricted location.</p>' . PHP_EOL;
                    $body .= '<p>The geolocation data for the IP Address used in the access attempt was: </p>' . PHP_EOL;
                    $body .= '<ul><li>' . $ip . '</li>' . PHP_EOL;
                    $body .= '<li>Country Name: ' . $rc_geo_access_country_name . '</li>' . PHP_EOL;
                    $body .= '<li>Country Code: ' . $rc_geo_access_country_code . '</li>' . PHP_EOL;
                    $body .= '<li>Longitude: ' . $rc_geo_access_longitude . '</li>' . PHP_EOL;
                    $body .= '<li>Latitude: ' . $rc_geo_access_latitude . '</li>' . PHP_EOL;
                    if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                        $body .= '<li>Referrer: ' . $_SERVER[ 'HTTP_REFERER' ] . '</li>' . PHP_EOL;
                    }
                    $body .= '</ul>' . PHP_EOL;
                    $body .= '<br>' . PHP_EOL;
                    $body .= '<p><a href="' . site_url() . '/wp-admin/options-general.php?page=rc_geo_access">' . __( 'Click here to manage RC Geo Access settings on ', 'rc_geo_access_plugin' ) . site_url() . '</a></p>' . PHP_EOL;
                    $body .= '<br><br><hr><br><br>' . PHP_EOL;
                    $body .= '<p><a href="https://qreate.co.uk/projects#rcgeoaccess">RC Geo Access</a> is a WordPress plugin developed by Rick Curran, ';
                    $body .= '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . __( 'click here to support it with a donation', 'rc_geo_access_plugin' ) . '</a>.</p>' . PHP_EOL;
                    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                    wp_mail( $to, $subject, $body, $headers );
                }
                
                // Block display of login page and display a message...
                wp_die( __( 'Sorry, access to this page is not available. Please contact the administrator of this website and provide the error code below to help them assist you.'.
                '<br><br>Error Code: RCGEOAXS' . $rc_geo_access_country_code . '001' . '<br>', 'rc_geo_access_plugin' ) );    
            }
            
        } else {
            // Errors of some kind occurred, send an email with the error message
            /*
             * Send an Error email notification if email recipient field is completed and is a valid email address...
             */
            if ( $options[ 'rc_geo_access_email_recipient' ] !== '' && is_email( $options[ 'rc_geo_access_email_recipient' ] ) ) {
                //$to = 'rickcurran@gmail.com';
                $to = $options[ 'rc_geo_access_email_recipient' ];
                $subject = 'Error Message from the RC Geo Access plugin on ' . site_url();
                $body = '<p><b>This is an error message from the RC Geo Access plugin on ' . site_url() . '.</b></p>' . PHP_EOL;
                $body .= '<p>There is an issue that may be preventing the RC Geo Access plugin from working correctly. Please read the error message below and take action to correct the problem:</p>' . PHP_EOL;

                $body .= '<p style="color:darkorange;font-weight:bold;font-size:120%;">' . $rc_geo_access_error_status . '</p>';

                if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                    $body .= '<p>Referrer: ' . $_SERVER[ 'HTTP_REFERER' ] . '</p>' . PHP_EOL;
                }

                $body .= '<br>' . PHP_EOL;
                $body .= '<p><a href="' . site_url() . '/wp-admin/options-general.php?page=rc_geo_access">' . __( 'Click here to manage RC Geo Access settings on ', 'rc_geo_access_plugin' ) . site_url() . '</a></p>' . PHP_EOL;
                $body .= '<br><br><hr><br><br>' . PHP_EOL;
                $body .= '<p><a href="https://qreate.co.uk/projects#rcgeoaccess">RC Geo Access</a> is a WordPress plugin developed by Rick Curran, ';
                $body .= '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">' . __( 'click here to support it with a donation', 'rc_geo_access_plugin' ) . '</a>.</p>' . PHP_EOL;
                $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                wp_mail( $to, $subject, $body, $headers );
            }
            
        }

    } else {
        error_log( 'RC GEO ACCESS - NO API KEY FOUND' );
    }
    
}

/* 
 * Check for any API errors...
 */
function rc_geo_access_error_handling( $rc_geo_access_api_result, $rc_geo_access_provider ) {
    
    $rga_error_status = '';
        
    if ( $rc_geo_access_provider == 'ipstack' ) {
        // Check if request to ipstack fails...
        if ( isset( $rc_geo_access_api_result[ 'success' ] ) && $rc_geo_access_api_result[ 'success' ] === false ) { // Failure occurred...
            $rga_code = $rc_geo_access_api_result[ 'error' ][ 'code' ];
            $rga_type = $rc_geo_access_api_result[ 'error' ][ 'type' ];

            if ( $rga_code === 101 && $rga_type === 'invalid_access_key' ) { // INVALID API KEY
                $rga_error_status = __( 'Sorry, an invalid API Key was specified. Please check you have entered it correctly.', 'rc_geo_access_plugin' );
                // 401 error

            } else if ( $rga_code === 102 ) { // INACTIVE USER
                $rga_error_status = __( 'Sorry, the user account associated with the API Key appears to be inactive. Please check that you have an active IPStack account and enter a valid API Key.', 'rc_geo_access_plugin' );
                // 401 error

            } else if ( $rga_code === 104 ) { // REACHED API LIMIT
                $rga_error_status = __( 'Sorry, you have reached the maximum allowed amount of monthly API requests for your API provider. You may need to upgrade your plan to increase the API requests, the login restriction will be unavailable until it is either upgraded or until a new monthly account period begins.', 'rc_geo_access_plugin' );
                // 429 error

            } else {
                // Fall back to any other error codes
                $rga_error_status = __( $rga_code . ' - ' . $rga_type );
            }
        }
        
    } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
        // Check if request to ipgeolocation fails...
        if ( isset( $rc_geo_access_api_result[ 'message' ] ) ) {
            // If `message` appears in response then there is an error of some kind...
            //$rga_code = $rc_geo_access_api_result[ 'error' ][ 'code' ];
            $rga_code = $rc_geo_access_api_result[ 'message' ];
            // INVALID API KEY
            if ( strpos( $rga_code, 'Provided API key is not valid' ) !== false ) {
                $rga_error_status = __( 'Sorry, an invalid API Key was specified. Please check you have entered it correctly.', 'rc_geo_access_plugin' );
                
            } else if ( strpos( $rga_code, 'Your monthly limit has been reached' ) !== false ) { // REACHED API LIMIT
                $rga_error_status = __( 'Sorry, you have reached the maximum allowed amount of monthly API requests for your API provider. You may need to upgrade your plan to increase the API requests, the login restriction will be unavailable until it is either upgraded or until a new monthly account period begins.', 'rc_geo_access_plugin' );
                // 429 error

            } else {
                // Fall back to any other error codes
                $rga_error_status = $rga_code;
            }
        }

    } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
        // Check if `GEOIP_COUNTRY_CODE` and `GEOIP_COUNTRY_NAME` server variables exist...        
        if ( !isset( $_SERVER[ 'GEOIP_COUNTRY_CODE' ] ) || !isset( $_SERVER[ 'GEOIP_COUNTRY_NAME' ] ) ) {
            $rga_error_status = __( 'Sorry, the OpenLiteSpeed GeoIP variables could not be read. This could be due to the IP address not being found in the Geolocation database. Please also check that you have configured the OLS geolocation lookup function correctly.', 'rc_geo_access_plugin' );
        }

    }
    
    return $rga_error_status;
}



/*
 * Restriction geolocation function
 */
function rc_geo_access_lookup_ip( $ip, $rc_geo_access_key, $rc_geo_access_provider ) {
    
    if ( $rc_geo_access_provider == 'ipstack' ) {
        
        $ch = curl_init( 'http://api.ipstack.com/' . $ip . '?access_key=' . $rc_geo_access_key . '&fields=country_code,country_name,longitude,latitude' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        
        $json = curl_exec( $ch );
        curl_close( $ch );
        // For PHP 8+ curl_close no longer has any effect, so `unset` should be used instead
        unset( $ch );

        $rc_geo_access_api_result = json_decode( $json, true );
        
    } else if ( $rc_geo_access_provider == 'ipgeolocation' ) {
        
        $ch = curl_init( 'https://api.ipgeolocation.io/ipgeo?apiKey=' . $rc_geo_access_key . '&ip=' . $ip . '&fields=country_code2,country_name,longitude,latitude' );
        curl_setopt( $ch, CURLOPT_HTTPGET, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT']
        ));
        
        $json = curl_exec( $ch );
        curl_close( $ch );
        // For PHP 8+ curl_close no longer has any effect, so `unset` should be used instead
        unset( $ch );

        $rc_geo_access_api_result = json_decode( $json, true );
        
    } else if ( $rc_geo_access_provider == 'openlitespeed' ) {
        
        
        $rc_geo_access_api_result_json = '{ "geoip_country_code": "' . $_SERVER[ 'GEOIP_COUNTRY_CODE' ] . '", "country_name": "' . $_SERVER[ 'GEOIP_COUNTRY_NAME' ] . '", "latitude": 0, "longitude": 0 }';
        
        $rc_geo_access_api_result = json_decode( $rc_geo_access_api_result_json, true );
            
        /*$geoip_country_code = $_SERVER[ 'GEOIP_COUNTRY_CODE' ]
        $rc_geo_access_country_name = $_SERVER[ 'GEOIP_COUNTRY_NAME' ];
        $rc_geo_access_longitude = 'na';
        $rc_geo_access_latitude = 'na';*/
        
        //wp_die( print_r( $rc_geo_access_api_result ) );
        //wp_die( print_r( $rc_geo_access_api_result_json ) );
        
    }

    return $rc_geo_access_api_result;
}

/*
 * Get array of Country data
 */
function rc_geo_access_get_countries() {
    
    // 2 Digit Country Code array
    $countries = array(
        'Afghanistan' => 'AF',
        'Aland Islands' => 'AX',
        'Albania' => 'AL',
        'Algeria' => 'DZ',
        'American Samoa' => 'AS',
        'Andorra' => 'AD',
        'Angola' => 'AO',
        'Anguilla' => 'AI',
        'Antarctica' => 'AQ',
        'Antigua and Barbuda' => 'AG',
        'Argentina' => 'AR',
        'Armenia' => 'AM',
        'Aruba' => 'AW',
        'Australia' => 'AU',
        'Austria' => 'AT',
        'Azerbaijan' => 'AZ',
        'Bahamas' => 'BS',
        'Bahrain' => 'BH',
        'Bangladesh' => 'BD',
        'Barbados' => 'BB',
        'Belarus' => 'BY',
        'Belgium' => 'BE',
        'Belize' => 'BZ',
        'Benin' => 'BJ',
        'Bermuda' => 'BM',
        'Bhutan' => 'BT',
        'Bolivia, Plurinational State of' => 'BO',
        'Bonaire, Sint Eustatius and Saba' => 'BQ',
        'Bosnia and Herzegovina' => 'BA',
        'Botswana' => 'BW',
        'Bouvet Island' => 'BV',
        'Brazil' => 'BR',
        'British Indian Ocean Territory' => 'IO',
        'Brunei Darussalam' => 'BN',
        'Bulgaria' => 'BG',
        'Burkina Faso' => 'BF',
        'Burundi' => 'BI',
        'Cambodia' => 'KH',
        'Cameroon' => 'CM',
        'Canada' => 'CA',
        'Cape Verde' => 'CV',
        'Cayman Islands' => 'KY',
        'Central African Republic' => 'CF',
        'Chad' => 'TD',
        'Chile' => 'CL',
        'China' => 'CN',
        'Christmas Island' => 'CX',
        'Cocos (Keeling) Islands' => 'CC',
        'Colombia' => 'CO',
        'Comoros' => 'KM',
        'Congo' => 'CG',
        'Congo, the Democratic Republic of the' => 'CD',
        'Cook Islands' => 'CK',
        'Costa Rica' => 'CR',
        'Cote d\'Ivoire' => 'CI',
        'Croatia' => 'HR',
        'Cuba' => 'CU',
        'Curacao' => 'CW',
        'Cyprus' => 'CY',
        'Czech Republic' => 'CZ',
        'Denmark' => 'DK',
        'Djibouti' => 'DJ',
        'Dominica' => 'DM',
        'Dominican Republic' => 'DO',
        'Ecuador' => 'EC',
        'Egypt' => 'EG',
        'El Salvador' => 'SV',
        'Equatorial Guinea' => 'GQ',
        'Eritrea' => 'ER',
        'Estonia' => 'EE',
        'Ethiopia' => 'ET',
        'Falkland Islands (Malvinas)' => 'FK',
        'Faroe Islands' => 'FO',
        'Fiji' => 'FJ',
        'Finland' => 'FI',
        'France' => 'FR',
        'French Guiana' => 'GF',
        'French Polynesia' => 'PF',
        'French Southern Territories' => 'TF',
        'Gabon' => 'GA',
        'Gambia' => 'GM',
        'Georgia' => 'GE',
        'Germany' => 'DE',
        'Ghana' => 'GH',
        'Gibraltar' => 'GI',
        'Greece' => 'GR',
        'Greenland' => 'GL',
        'Grenada' => 'GD',
        'Guadeloupe' => 'GP',
        'Guam' => 'GU',
        'Guatemala' => 'GT',
        'Guernsey' => 'GG',
        'Guinea' => 'GN',
        'Guinea-Bissau' => 'GW',
        'Guyana' => 'GY',
        'Haiti' => 'HT',
        'Heard Island and McDonald Islands' => 'HM',
        'Holy See (Vatican City State)' => 'VA',
        'Honduras' => 'HN',
        'Hong Kong' => 'HK',
        'Hungary' => 'HU',
        'Iceland' => 'IS',
        'India' => 'IN',
        'Indonesia' => 'ID',
        'Iran, Islamic Republic of' => 'IR',
        'Iraq' => 'IQ',
        'Ireland' => 'IE',
        'Isle of Man' => 'IM',
        'Israel' => 'IL',
        'Italy' => 'IT',
        'Jamaica' => 'JM',
        'Japan' => 'JP',
        'Jersey' => 'JE',
        'Jordan' => 'JO',
        'Kazakhstan' => 'KZ',
        'Kenya' => 'KE',
        'Kiribati' => 'KI',
        'Korea, Democratic People\'s Republic of' => 'KP',
        'Korea, Republic of' => 'KR',
        'Kuwait' => 'KW',
        'Kyrgyzstan' => 'KG',
        'Lao People\'s Democratic Republic' => 'LA',
        'Latvia' => 'LV',
        'Lebanon' => 'LB',
        'Lesotho' => 'LS',
        'Liberia' => 'LR',
        'Libya' => 'LY',
        'Liechtenstein' => 'LI',
        'Lithuania' => 'LT',
        'Luxembourg' => 'LU',
        'Macao' => 'MO',
        'Macedonia, the Former Yugoslav Republic of' => 'MK',
        'Madagascar' => 'MG',
        'Malawi' => 'MW',
        'Malaysia' => 'MY',
        'Maldives' => 'MV',
        'Mali' => 'ML',
        'Malta' => 'MT',
        'Marshall Islands' => 'MH',
        'Martinique' => 'MQ',
        'Mauritania' => 'MR',
        'Mauritius' => 'MU',
        'Mayotte' => 'YT',
        'Mexico' => 'MX',
        'Micronesia, Federated States of' => 'FM',
        'Moldova, Republic of' => 'MD',
        'Monaco' => 'MC',
        'Mongolia' => 'MN',
        'Montenegro' => 'ME',
        'Montserrat' => 'MS',
        'Morocco' => 'MA',
        'Mozambique' => 'MZ',
        'Myanmar' => 'MM',
        'Namibia' => 'NA',
        'Nauru' => 'NR',
        'Nepal' => 'NP',
        'Netherlands' => 'NL',
        'New Caledonia' => 'NC',
        'New Zealand' => 'NZ',
        'Nicaragua' => 'NI',
        'Niger' => 'NE',
        'Nigeria' => 'NG',
        'Niue' => 'NU',
        'Norfolk Island' => 'NF',
        'Northern Mariana Islands' => 'MP',
        'Norway' => 'NO',
        'Oman' => 'OM',
        'Pakistan' => 'PK',
        'Palau' => 'PW',
        'Palestine, State of' => 'PS',
        'Panama' => 'PA',
        'Papua New Guinea' => 'PG',
        'Paraguay' => 'PY',
        'Peru' => 'PE',
        'Philippines' => 'PH',
        'Pitcairn' => 'PN',
        'Poland' => 'PL',
        'Portugal' => 'PT',
        'Puerto Rico' => 'PR',
        'Qatar' => 'QA',
        'Reunion' => 'RE',
        'Romania' => 'RO',
        'Russian Federation' => 'RU',
        'Rwanda' => 'RW',
        'Saint Barthelemy' => 'BL',
        'Saint Helena, Ascension and Tristan da Cunha' => 'SH',
        'Saint Kitts and Nevis' => 'KN',
        'Saint Lucia' => 'LC',
        'Saint Martin (French part)' => 'MF',
        'Saint Pierre and Miquelon' => 'PM',
        'Saint Vincent and the Grenadines' => 'VC',
        'Samoa' => 'WS',
        'San Marino' => 'SM',
        'Sao Tome and Principe' => 'ST',
        'Saudi Arabia' => 'SA',
        'Senegal' => 'SN',
        'Serbia' => 'RS',
        'Seychelles' => 'SC',
        'Sierra Leone' => 'SL',
        'Singapore' => 'SG',
        'Sint Maarten (Dutch part)' => 'SX',
        'Slovakia' => 'SK',
        'Slovenia' => 'SI',
        'Solomon Islands' => 'SB',
        'Somalia' => 'SO',
        'South Africa' => 'ZA',
        'South Georgia and the South Sandwich Islands' => 'GS',
        'South Sudan' => 'SS',
        'Spain' => 'ES',
        'Sri Lanka' => 'LK',
        'Sudan' => 'SD',
        'Suriname' => 'SR',
        'Svalbard and Jan Mayen' => 'SJ',
        'Swaziland' => 'SZ',
        'Sweden' => 'SE',
        'Switzerland' => 'CH',
        'Syrian Arab Republic' => 'SY',
        'Taiwan, Province of China' => 'TW',
        'Tajikistan' => 'TJ',
        'Tanzania, United Republic of' => 'TZ',
        'Thailand' => 'TH',
        'Timor-Leste' => 'TL',
        'Togo' => 'TG',
        'Tokelau' => 'TK',
        'Tonga' => 'TO',
        'Trinidad and Tobago' => 'TT',
        'Tunisia' => 'TN',
        'Turkey' => 'TR',
        'Turkmenistan' => 'TM',
        'Turks and Caicos Islands' => 'TC',
        'Tuvalu' => 'TV',
        'Uganda' => 'UG',
        'Ukraine' => 'UA',
        'United Arab Emirates' => 'AE',
        'United Kingdom' => 'GB',
        'United States' => 'US',
        'United States Minor Outlying Islands' => 'UM',
        'Uruguay' => 'UY',
        'Uzbekistan' => 'UZ',
        'Vanuatu' => 'VU',
        'Venezuela, Bolivarian Republic of' => 'VE',
        'Viet Nam' => 'VN',
        'Virgin Islands, British' => 'VG',
        'Virgin Islands, U.S.' => 'VI',
        'Wallis and Futuna' => 'WF',
        'Western Sahara' => 'EH',
        'Yemen' => 'YE',
        'Zambia' => 'ZM',
        'Zimbabwe' => 'ZW'
    );
    
    return $countries;
}


/*
 * Activation / Deactivation Checks...
 */
register_activation_hook( __FILE__, 'rc_geo_access_activate' );
function rc_geo_access_activate() {
    // We check to see if the plugin has been previously activated and
    // had its restriction function enabled. If it has then we set  
    // 'rc_geo_access_status' to 'disabled' to prevent the user from 
    // potentially being locked out of their site again. (If the user 
    // has had to resort to deleting the plugin in order to regain 
    // access to their site then we don't want the same active plugin 
    // settings to be loaded automatically as they will likely get 
    // locked out again!)
    $current_options = get_option( 'rc_geo_access_settings', array() );
    if ( isset( $current_options[ 'rc_geo_access_status' ] ) ) {
       
        $current_options[ 'rc_geo_access_status' ] = 'disabled';
        update_option( 'rc_geo_access_settings', $current_options );
        
    }
    
}

/*
 * This function is currently only used for development purposes
 * so is intended to be commented out at this time.
register_deactivation_hook( __FILE__, 'rc_geo_access_deactivate' );
function rc_geo_access_deactivate() {
    //unregister_setting( 'rc_geo_access_admin_page', 'rc_geo_access_settings' );
    delete_option( 'rc_geo_access_settings' );
}
*/

?>