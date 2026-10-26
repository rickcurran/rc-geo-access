=== RC Geo Access ===
Contributors: rickcurran
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G
Tags: security, geolocation, login
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.50
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it.

== Description ==

This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.
To get the location of the user the plugin gets the IP address of the user attempting to access the login page and geo-locates their location by using a geolocation API, currently there are three options available to use:

 - IPStack (now part of APILayer): https://ipstack.com
 - IPGeolocation: https://ipgeolocation.io
 - OpenLiteSpeed’s local GeoIP variables

Please note: an active API Key is required for `IPStack` and `IPGeolocation` for the plugin to function correctly. You can register a free account at either of the website addresses above. Please note they offer varying amounts of location API requests for their free and paid plans, it may be necessary to upgrade to a paid plan to provide an increased amount of requests if your site gets a huge amount of login attempts. The `OpenLiteSpeed’s local GeoIP variables` option can be used if you are using OpenLiteSpeed server and have a properly configured geolocation database, more information about configuring that is available here: https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation


IPStack is now part of APILayer (https://apilayer.com). The plugin continues to use the existing IPStack API endpoint and access-key format; it does not migrate accounts or credentials to a different API. External requests require HTTPS.


== Example usage: ==

Enable the plugin and access the "RC Geo Access" page in the Settings menu in your WordPress Admin to configure the required settings. Note: an active API Key is required for this plugin to function, currently there are two external Geolocation services available to use, you can choose from either 'IPStack' - https://ipstack.com and 'IPGeolocation' - https://ipgeolocation.io as the geolocation providers. The `OpenLiteSpeed’s local GeoIP variables` option can be used if you are using OpenLiteSpeed server and have a properly configured geolocation database, more information about configuring that is available here: https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation


== Screenshots ==

1. This screen shot shows the administration page for the "RC Geo Access" plugin in the WordPress backend
2. This screen shot shows an example output shown when someone is prevented from accessing the login page

== Installation ==
	
1. Upload the plugin package to the plugins directory of your site, or search for "RC Geo Access" in the WordPress plugins directory from the Plugins section of your WordPress dashboard.
2. Once uploaded or installed you must activate the plugin from the Plugins section of your WordPress dashboard.
3. Go to the "RC Geo Access" page in the Settings menu in your WordPress Admin to configure your plugin.
	
== Frequently Asked Questions ==

= Does this also restrict XML-RPC and other login methods? =

Yes. The country restriction is checked before WordPress processes credentials through its standard authentication pipeline, including XML-RPC authentication and custom forms using wp_signon() or wp_authenticate(). Application-password authentication, including REST API requests, is also checked. The login page itself remains protected before credentials are processed. Authenticated integrations must connect from an allowed country. Public XML-RPC methods that do not authenticate, such as pingbacks, are not disabled by this change.

These routes share one location check per request, including XML-RPC multicalls. When restrictions are enabled, missing or invalid country codes, provider errors, and incomplete provider configuration deny login access with a 403 error. OpenLiteSpeed country names are optional; only a recognised country code is required. Plugins implementing their own authentication outside WordPress's hooks are outside this protection.
	
= What does this plugin do? =

This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.

= How does this plugin get the user's location? =

The plugin uses a geolocation API to look up the user's location based on their IP address. Note: an active API Key may be required for this plugin to function, currently there are two external services available to use, you can choose from either 'IPStack' - https://ipstack.com and 'IPGeolocation' - https://ipgeolocation.io as the geolocation providers. There is also a third `OpenLiteSpeed’s local GeoIP variables` option which can be used if you are using OpenLiteSpeed server and have a properly configured geolocation database, more information about configuring that is available here: https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation

= Can I use other geolocation APIs? =

No, at this time it is only possible to use either the external providers of IPStack.com or the IPGeolocation.io, or the locally configured OpenLiteSpeed Geolocation functionality.

= How do I configure the plugin settings? =

You can configure all of the plugin settings from the "RC Geo Access" menu found in the "Settings" menu in your WordPress admin.

= How can I tell if it is working? =

If you're not seeing any error messages when you are in the plugin settings page then it should hopefully be working correctly. If you've enabled email notifications then at some point you should receive an email when your login page is restricted. There is a way to test it yourself  but you need to be very careful! Uncheck your country from the list and save the changes (do not close the browser window or logout from WordPress or you will be locked out of your site - see below for help if that happens!), using another browser or browser tab attempt to access your login page.

= How are lookups and notification emails limited? =

External-provider results are cached for one hour per IP address, provider and API key. The allowlist is still evaluated against the current settings on every request. OpenLiteSpeed's local values are read from the current request and are not cached across requests.

Lookup failures are cached for one minute. Provider connectivity, account and HTTP failures also pause new requests to that provider/key for one minute, including requests for different IPs. Up to 60 uncached external lookups are permitted per minute for each provider/key on a site; requests beyond that limit are denied until a new minute begins. Set the positive integer RC_GEO_ACCESS_LOOKUPS_PER_MINUTE in wp-config.php to adjust this limit. These transient-based limits reduce load; they are not a replacement for server-level rate limiting under a concurrent attack.

Notifications are throttled to one restriction email and one error email per recipient every 15 minutes on each site. These are representative alerts rather than a complete event log. Transient caches and counters may be cleared by WordPress or a cache plugin.

= Can I use Cloudflare or another reverse proxy? =

Yes. With a direct connection, REMOTE_ADDR is used and visitor-supplied forwarding headers are ignored. A reverse proxy must either restore the real visitor IP safely at the server or be explicitly trusted by the plugin. The settings page shows the address used for geolocation and, for OpenLiteSpeed, the local country code.

For OpenLiteSpeed with a CDN/proxy, the preferred setup is server-side restoration: set "Use Client IP in Header" to "Trusted IP Only" and configure the proxy's current IP ranges as trusted. Follow both steps in https://docs.openlitespeed.org/config/logs/visitorip/ . This also lets the server's local GeoIP lookup use the visitor address. Do not enable unrestricted trust of forwarded headers. A direct-to-OpenLiteSpeed site normally needs no proxy configuration.

If server-side restoration is unavailable, PHP-level proxy handling can be configured in wp-config.php before WordPress loads. Define RC_GEO_ACCESS_TRUSTED_PROXIES as an array containing only the actual proxy IP addresses or IPv4/IPv6 CIDRs, and set RC_GEO_ACCESS_FORWARDED_IP_HEADER to HTTP_CF_CONNECTING_IP for Cloudflare. Obtain and maintain Cloudflare's current ranges from https://www.cloudflare.com/ips/ . There is no automatic trust of Cloudflare headers or automatic download of proxy ranges.

For example, after replacing the comment with the current ranges for your proxy:

    define( 'RC_GEO_ACCESS_TRUSTED_PROXIES', array( /* actual trusted proxy IPs/CIDRs */ ) );
    define( 'RC_GEO_ACCESS_FORWARDED_IP_HEADER', 'HTTP_CF_CONNECTING_IP' );

The other supported headers are HTTP_X_FORWARDED_FOR (the default) and HTTP_X_REAL_IP. X-Forwarded-For chains are evaluated from the trusted proxy towards the client, stopping at the first untrusted hop. A missing or malformed configured header from a trusted proxy results in an unknown IP and denied login access. Forwarding headers from untrusted connections are ignored.

When the plugin resolves a visitor IP different from REMOTE_ADDR, it cannot verify that OpenLiteSpeed's existing GeoIP variables describe that visitor. It denies local-provider lookups in this case. Restore the IP at the server before geolocation, or select an external provider, which looks up the safely resolved visitor address. CF-IPCountry is not used as a source of country information.

= What happens if geolocation fails? =

When restrictions are enabled, an unknown country is blocked even if the geolocation database or provider is unavailable. Error emails are sent if a valid recipient is configured. Existing administrator sessions are not terminated by this check, so you can correct settings from an already authenticated session. If you cannot log in, use SFTP or your hosting file manager to rename the plugin directory temporarily, then sign in and repair the geolocation configuration.

= Help! I've enabled this plugin and now I'm locked out of my site! =

Yikes, sorry! There is a potential danger of this happening if you have enabled the restriction (including the required API key) but you did not set your own country location to be accessible. If this happens then I'm afraid the only option here is to connect to your site directly via SFTP / FTP and remove the plugin files from your site. Once removed you will then be able to login, you can then re-install the plugin, you should then immediately ensure that your current country location is given access in the "RC Geo Access" page in the Settings menu in your WordPress Admin.

= I am using the OpenLiteSpeed Geolocation option but it isn't blocking login access =

If you are using the OpenLiteSpeed option the IP address of users to your site must be found in the configured Geolocation database in order to establish the user’s country. If the IP address is not found, login access is denied. If a valid notification email address is configured, it will trigger an error message email to be sent which begins with "Sorry, the OpenLiteSpeed GeoIP variables could not be read...". If you receive this error email then you should check that the Geolocation database is configured correctly and also that the database is up to date.

== Changelog ==

= 1.50 =

- Resolve Plugin Check warnings by validating and unslashing server variables explicitly and matching the readme title to the plugin name.

- Restore green highlighting for selected countries and responsive multiple-column layout on the settings page.

- Apply WordPress coding standards, add PHPDoc and direct-access guards, and align plugin headers with the declared requirements.
- Use the rc-geo-access translation domain, translate settings and notification text, and validate server metadata before display or IP resolution.
- Match plugin-row links against the exact plugin basename and remove obsolete development code.

- Validate saved settings and escape dynamic settings-page and notification output. Show visitor-IP diagnostics and configuration errors.
- Use WordPress HTTP requests with verified HTTPS, bounded timeouts, no redirects and response validation. Keep the existing IPStack endpoint and credentials.
- Cache external lookups, back off after provider failures, limit uncached lookup traffic, and throttle restriction/error notification emails separately.
- Add opt-in trusted-proxy and Cloudflare IP handling with IPv4/IPv6 CIDRs. Deny local GeoIP when a proxy-resolved visitor IP cannot be matched safely to the server lookup.

- Deny login access when geolocation cannot identify a recognised country, including missing OpenLiteSpeed variables and provider errors. Country names are optional.

- Require PHP 8.0 or later and remove curl_close() calls deprecated in PHP 8.5.
- Apply country restrictions before credential validation through WordPress authentication, including XML-RPC and custom login forms.
- Cover application-password authentication, including REST API requests.
- Reuse the location check within each request to avoid duplicate lookups and notification emails.
- Return a 403 denial through WordPress's context-appropriate error handler.

= 1.49 =

- Added support for OpenLiteSpeed web server’s local Geolocation functionality, this removes the need for an external third-party geolocation API if you are serving websites using OLS. See more details about how to configure this in OLS here: https://docs.openlitespeed.org/config/advanced/geolocation/#enabling-geolocation 

= 1.48 =

- Code tweak for PHP8 compatiblity

= 1.47 =

- Fixed first-run issue setting the default country code as active when using the ipgeolocation API due to a difference in the country code parameter naming. Tweaked position and wording of some error notification messages.

= 1.46 =

- Added initial blank "Select..." value to the `API Provider` dropdown as it would default to 'ipgeolocation' at first which would cause issues if saving without an API key set. Also added an additional check to make sure a valid API provider value is set when the actual login location check is triggered.

= 1.45 =

- Added ipgeolocation.io as an additional API provider. Plugin description text updated to provide information about this additional service. Also provided additional text to clarify that IPStack's free API limit which has been reduced to 100 requests per month and clarified compatibility up to WordPress 5.8.2.

= 1.44 =

- Added IPStack affiliate link as a way for people to support the plugin if using a paid IPStack plan.

= 1.43 =

- Minor Update to change plugin description text to clarify IPStack's free API limit which has changed to 5,000 requests per month. Also updated the url of the website for the plugin and clarify compatibility up to WordPress 5.7.2

= 1.42 =

- Changed IPStack API url call to use plain HTTP as free accounts don't support SSL requests. Added related error code notification.

= 1.41 =

- Prevented "Settings" link from appearing in Network Plugins page on Multisite installation.

= 1.4 =

- Added plugin activation check to see if the plugin has been previously activated and had its restriction function enabled to prevent users from potentially being locked out of their site again.
- Countries Whitelist now hidden until restriction function enabled and API Key set, this process improves the first-run experience when configuring the plugin.
- Added notice in Dashboard and Plugin page to prompt user to configure the plugin via a link to the plugin settings page.
