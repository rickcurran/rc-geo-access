# rc-geo-access
Access restriction plugin for WordPress

Restrict access to WordPress logins based on the user location of anyone trying to access the WordPress login page as determined by geolocation of their IP address. The current version uses the geoPlugin api at http://www.geoplugin.com to the location based on IP addresses.

geoPlugin includes GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>. Note that geoPlugin lookups are limited to 120 requests per minute.

Note: geolocation based on IP address is not always 100% accurate but in this case we are only trying to determine the country of origin in order to restrict access.