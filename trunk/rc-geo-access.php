<?php
/*
Plugin Name: RC Geo Access
Plugin URI: http://suburbia.org.uk/projects/#rcgeoaccess
Description: This plugin restricts access to the Login page via geolocation lookup of visitor's IP addresses.
Version: 1.1
Author: Rick Curran
Author URI: http://suburbia.org.uk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'admin_menu', 'rc_geo_access_add_admin_menu' );
add_action( 'admin_init', 'rc_geo_access_settings_init' );

// Add Menu Page under Settings
function rc_geo_access_add_admin_menu() {
    
	add_options_page( 'RC GEO Access', 'RC GEO Access', 'manage_options', 'rc_geo_access', 'rc_geo_access_options_page' );

}

/*
 * Settings Page Render / Storing...
 */
function rc_geo_access_settings_init(  ) { 

	register_setting( 'rc_geo_access_admin_page', 'rc_geo_access_settings' );

	add_settings_section( 'rc_geo_access_admin_page_section',  __( 'What does this plugin do?', 'wordpress' ),  'rc_geo_access_settings_section_callback', 'rc_geo_access_admin_page' );

	add_settings_field( 'rc_geo_access_status', __( 'Restriction Status:', 'wordpress' ), 'rc_geo_access_status_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

    add_settings_field( 'rc_geo_access_ipstack_api_key', __( 'IPStack API Key:', 'wordpress' ), 'rc_geo_access_ipstack_api_key_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );

	add_settings_field( 'rc_geo_access_restricted_countries', __( 'Restrict Countries:', 'wordpress' ), 'rc_geo_access_restricted_countries_render', 'rc_geo_access_admin_page', 'rc_geo_access_admin_page_section' );
    
    add_settings_section( 'rc_geo_access_admin_page_save_section', '', 'rc_geo_access_settings_save_section_callback', 'rc_geo_access_admin_page' );
    
    add_settings_section( 'rc_geo_access_admin_page_donate_section', __( 'Support this plugin', 'wordpress' ), 'rc_geo_access_settings_donate_section_callback', 'rc_geo_access_admin_page' );

    

}

function rc_geo_access_status_render(  ) { 

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<select name='rc_geo_access_settings[rc_geo_access_status]'>
		<option value='disabled' <?php selected( $options['rc_geo_access_status'], 'disabled' ); ?>>Disabled</option>
		<option value='enabled' <?php selected( $options['rc_geo_access_status'], 'enabled' ); ?>>Enabled</option>
	</select>

<?php
    if ( $options['rc_geo_access_status'] === 'disabled' ) {
        
        echo '<span style="color:darkorange;font-weight:bold;">&larr; ' . __( 'Change this to "enabled" to begin access restriction', 'wordpress' ) . '</span>';
        
    }

}

function rc_geo_access_ipstack_api_key_render(  ) { 

	$options = get_option( 'rc_geo_access_settings' );
    
	?>
	<input type='text' name='rc_geo_access_settings[rc_geo_access_ipstack_api_key]' value='<?php echo $options['rc_geo_access_ipstack_api_key']; ?>' size="50">
	<?php
        if ( $options['rc_geo_access_ipstack_api_key'] === '' ) {
            
            echo '<span style="color:darkorange;font-weight:bold;">&larr;' . __( 'Please enter an API Key', 'wordpress' ) . '</span>';
            
        }
}


function rc_geo_access_restricted_countries_render(  ) { 

	$options = get_option( 'rc_geo_access_settings' );
    
    $rc_geo_access_restricted_countries = maybe_unserialize( $options['rc_geo_access_restricted_countries'] );
    
    $rc_geo_access_countries = rc_geo_access_get_countries();
    
    $rc_geo_access_no_countries_forced_country_code = '';
    
    if ( count( $rc_geo_access_countries ) !== 0 ) {
        
        echo '<p><strong>' . __( 'Initially all countries are blocked from accessing the login page, check the boxes below for the countries that you want to allow access to your login page:', 'wordpress' ) . '</strong></p>';
        
        // Warn user if no countries have access and restriction is enabled...
        if ( count( $rc_geo_access_restricted_countries ) === 0 && $options['rc_geo_access_status'] === 'enabled' && $options['rc_geo_access_ipstack_api_key'] !== '' ) {
            
            // Lookup current admin user's location and suggest they enable access to this or risk being locked out!!!
            $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $options['rc_geo_access_ipstack_api_key'] );
            
            echo '<p style="border:3px solid red;padding:10px;margin-top:20px;margin-bottom:20px;background-color:#fff;color:red;font-weight:bold;">&darr; ';
            echo __( 'WARNING: All Countries are currently blocked from accessing your login page! To try and prevent you from being locked out of your site your current location of ', 'wordpress' );
            echo '"' . $rc_geo_access_api_result[ 'country_name' ] . ' - ' . $rc_geo_access_country_name = $rc_geo_access_api_result[ 'country_code' ] . '"';
            echo __( ' has been added below, please add any other countries you wish to allow access and then click "Save Changes" to confirm these settings. You are at risk being locked out of your site if you have no countries set here!', 'wordpress' );
            echo '</p>';
            
            $rc_geo_access_no_countries_forced_country_code = $rc_geo_access_api_result[ 'country_code' ];
        }
        
        echo '<ul style="-webkit-column-width: 300px; -moz-column-width: 300px; column-width: 300px; -webkit-column-rule: 1px dotted #ddd; -moz-column-rule: 1px dotted #ddd; column-rule: 1px dotted #ddd;">';
        
        foreach ( $rc_geo_access_countries as $k => $v ) {
            
            $checked = '';
            if ( in_array( $v, $rc_geo_access_restricted_countries ) ) {
                $checked = ' checked';
            }
            
            // Force check user's location if no locations are set, attempt to prevent the user locking themselves out of their site!
            if ( count( $rc_geo_access_restricted_countries ) === 0 && $rc_geo_access_no_countries_forced_country_code !== '' && $rc_geo_access_no_countries_forced_country_code === $v ) {
                $checked = ' checked';
            }
            
            echo '<li><label style="padding:0 10px 0 8px;display:block;"><input type="checkbox" name="rc_geo_access_settings[rc_geo_access_restricted_countries][]" value="' . $v . '"' . $checked . '> <span>' . $k . ' - ' . $v . '</span></label></li>';
    
            
        }
        
        echo '</ul>';
    }

}

function rc_geo_access_settings_section_callback(  ) { 

	echo __( '<p>This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.</p><p>To get the location of the user the plugin gets the IP address of the user attempting to access the login page and geo-locates their location by using an API available from <a href="https://ipstack.com/" target="_blank">IPStack.com</a>.</p><p><strong>Please note: an active IPStack API Key is required for this plugin to function correctly.</strong> You can register a free account at <a href="https://ipstack.com/" target="_blank">IPStack.com</a> whuich provides 10,000 requests per month. Whilst this free plan will likely provide more than enough API requests it may be necessary to upgrade to a paid plan to provide an increased amount of requests if your site gets a huge amount of login attempts.</p>', 'wordpress' );
    
    submit_button();

}

function rc_geo_access_settings_save_section_callback( $arg ) {
	
   submit_button();
    
    echo '<hr>';
    
}

function rc_geo_access_settings_donate_section_callback( $arg ) {
	
    echo __( '<p><strong>If you have found this plugin to be useful then please consider a donation. Donations like these help to provide time for <strong><a href="https://suburbia.org.uk/about">me</a></strong> to develop plugins like this.</strong></p>', 'wordpress' );
    echo __( '<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G" class="button button-primary" target="_blank">Donate</a></p>', 'wordpress' );
    
}

function rc_geo_access_options_page(  ) { 

	?>
    <div class="wrap">
        <form action='options.php' method='post'>

            <h1>RC Geo Access Settings</h1>

            <?php
              settings_fields( 'rc_geo_access_admin_page' );
              do_settings_sections( 'rc_geo_access_admin_page' );
              
            ?>

        </form>
    </div>
	<?php
}



/*
 * Additional links on plugin list page
 */
add_filter( 'plugin_row_meta', 'rc_geo_access_row_meta', 10, 4 );

function rc_geo_access_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
    
    if ( strpos( $plugin_file_name, basename(__FILE__) ) ) {
        $links_array[] = '<a href="options-general.php?page=rc_geo_access">' . __('Settings','rc_geo_access_page') . '</a>';
        $links_array[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G">'.__('Donate', 'rc_geo_access_page').'</a>';
    }
    return $links_array;
    
}


/*
 * Restriction lookup function
 */
add_action( 'login_init', 'rc_geo_access_func' );

function rc_geo_access_func() {
    
    $options = get_option( 'rc_geo_access_settings' );
    $rc_geo_status = $options['rc_geo_access_status'];
    $rc_geo_access_key = $options['rc_geo_access_ipstack_api_key'];
    
    if ($rc_geo_status === 'enabled' && $rc_geo_access_key !== '') { // Check that Restriction is enabled and API Key is not empty...
        
        $rc_geo_access_api_result = rc_geo_access_lookup_ip( $_SERVER[ 'REMOTE_ADDR' ], $rc_geo_access_key );

        // Output the 2 letter "country_code" and other details...
        $rc_geo_access_country_code = $rc_geo_access_api_result[ 'country_code' ];
        $rc_geo_access_country_name = $rc_geo_access_api_result[ 'country_name' ];
        $rc_geo_access_longitude = $rc_geo_access_api_result[ 'longitude' ];
        $rc_geo_access_latitude = $rc_geo_access_api_result[ 'latitude' ];
        
        // Get list of enabled countries...
        $rc_geo_access_restricted_countries = maybe_unserialize( $options['rc_geo_access_restricted_countries'] );

        if ( ! in_array( $rc_geo_access_country_code, $rc_geo_access_restricted_countries ) ) {
            $to = 'rickcurran@gmail.com';
            $subject = 'WP Login restricted: ' . get_bloginfo( 'name' ) . ' - ' . $rc_geo_access_country_code;
            $body = 'WP Login access restriction was triggered for: ' . get_bloginfo( 'name' ) . ' - ' . site_url() . ' <br><br>The geolocation data for the user was: <br> ' . $ip . '<br />' . PHP_EOL .
                'Country Name: ' . $rc_geo_access_country_name . '<br />' . PHP_EOL .
                'Country Code: ' . $rc_geo_access_country_code . '<br />' . PHP_EOL .
                'Longitude: ' . $rc_geo_access_longitude . '<br />' . PHP_EOL .
                'Latitude: ' . $rc_geo_access_latitude . '<br />' . PHP_EOL;
                if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                    $body .= 'Referrer: ' . $_SERVER[ 'HTTP_REFERER' ] . '<br />' . PHP_EOL;
                }
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            wp_mail( $to, $subject, $body, $headers );
            wp_die('Sorry, an error occurred. Please contact the administrator of this website and provide the error code below to help them assist you.'.
            '<br><br>Error Code: AXS' . $rc_geo_access_country_code . '001' . '<br>' );    
        }
        
    }
    
}


function rc_geo_access_lookup_ip( $ip, $rc_geo_access_key ) {
    $ch = curl_init( 'https://api.ipstack.com/' . $ip . '?access_key=' . $rc_geo_access_key . '' );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    $json = curl_exec( $ch );
    curl_close( $ch );

    $rc_geo_access_api_result = json_decode( $json, true );
    
    return $rc_geo_access_api_result;
}


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

?>