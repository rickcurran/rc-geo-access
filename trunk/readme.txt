=== RC Geo Access Plugin ===
Contributors: rickcurran
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QZEXMAMCYDS3G
Tags: security, geolocation, login
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 1.1
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it.

== Description ==

This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.
To get the location of the user the plugin gets the IP address of the user attempting to access the login page and geo-locates their location by using an API available from IPStack.com.
Please note: an active IPStack API Key is required for this plugin to function correctly. You can register a free account at IPStack.com whuich provides 10,000 requests per month. Whilst this free plan will likely provide more than enough API requests it may be necessary to upgrade to a paid plan to provide an increased amount of requests if your site gets a huge amount of login attempts.

== Example usage: ==

Enable the plugin and access the "RC Geo Access" page in the Settings menu in your WordPress Admin to configure the required settings. Note: an active IPStack API Key is required for this plugin to function, you can register a free account at IPStack.com.


== Screenshots ==

1. This screen shot shows the administration page for the "RC Geo Access" plugin in the WordPress backend.
2. This screen shot shows an example output shown when someone is prevented from accessing the login page.

== Installation ==
	
1. Upload the plugin package to the plugins directory of your site, or search for "RC Geo Access" in the WordPress plugins directory from the Plugins section of your WordPress dashboard.
2. Once uploaded or installed you must activate the plugin from the Plugins section of your WordPress dashboard.
3. Go to the "RC Geo Access" page in the Settings menu in your WordPress Admin to configure your plugin.
	
== Frequently Asked Questions ==
	
= What does this plugin do? =

This plugin restricts access to the login page of your WordPress Admin based on the location of the user trying to access it. Restricting access in this way can be a useful way of reducing unwanted login attempts.

= How does this plugin get the user's location? =

The plugin uses an API from IPStack.com to look up the user's location based on their IP address. Note: an active IPStack API Key is required for this plugin to function, you can register a free account at IPStack.com.

= Can I use a differ geolocation API? =

No, at this time it is only available through the APIStack API.

= Help! I've enabled this plugin and now I'm locked out of my site! =

Yikes, sorry! There is a potential danger of this happening if you have enabled the restriction (including the required API key) but you did not set your own country location to be accessible. If this happens then I'm afraid the only option here is to connect to your site directly via a SFTP / FTP and remove the plugin files from your site. Once removed you will then be able to login, you can then re-install the plugin, you should then immediately go and ensure that your current country location is given access in the "RC Geo Access" page in the Settings menu in your WordPress Admin.


== Changelog ==

= 1.1 =

- Added support for IPStack API as the sole geolocation provider
- Added UI for saving IPStack API Key
- Added UI for enabling / disabling restriction
- Added UI for setting restricted countries
- Added warning when no countries have been set to strongly encourage user to add their current location to try and prevent them being locked out of the Admin(!)
