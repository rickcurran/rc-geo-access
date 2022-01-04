# Changelog 

## 1.47

- Fixed first-run issue setting the default country code as active when using the ipgeolocation API due to a difference in the country code parameter naming. Tweaked position and wording of some error notification messages.

## 1.46

- Added initial blank "Select..." value to the `API Provider` dropdown as it would default to 'ipgeolocation' at first which would cause issues if saving without an API key set. Also added an additional check to make sure a valid API provider value is set when the actual login location check is triggered.

## 1.45

- Added ipgeolocation.io as an additional API provider. Plugin description text updated to provide information about this additional service. Also provided additional text to clarify that IPStack's free API limit which has been reduced to 100 requests per month and clarified compatibility up to WordPress 5.8.2.

## 1.44

- Added IPStack affiliate link as a way for people to support the plugin if using a paid IPStack plan.

## 1.43

- Minor Update to change plugin description text to clarify IPStack's free API limit which has changed to 5,000 requests per month. Also updated the url of the website for the plugin and clarify compatibility up to WordPress 5.7.2

## 1.42

- Changed IPStack API url call to use plain HTTP as free accounts don't support SSL requests. Added related error code notification.

## 1.41

- Prevented "Settings" link from appearing in Network Plugins page on Multisite installation.

## 1.4

- Added plugin activation check to see if the plugin has been previously activated and had its restriction function enabled to prevent users from potentially being locked out of their site again.
- Countries Whitelist now hidden until restriction function enabled and API Key set, this process improves the first-run experience when configuring the plugin.
- Added notice in Dashboard and Plugin page to prompt user to configure the plugin via a link to the plugin settings page.

## 1.3

- Added validation of notification recipient email address, notice displayed next to field in admin and the notifications emails will not be sent if the email address is considered invalid.

## 1.2

- Added UI for saving an email address to receive notifications
- Added UI for enabling type of email notifications to receive
- Added various error handling and notifications in the admin, in particular to notify if the API request limit has been reached.

## 1.1

- Added support for IPStack API as the sole geolocation provider
- Added UI for saving IPStack API Key
- Added UI for enabling / disabling restriction
- Added UI for setting restricted countries
- Added warning when no countries have been set to strongly encourage user to add their current location to try and prevent them being locked out of the Admin(!)

## 1.0

- Initial plugin build.