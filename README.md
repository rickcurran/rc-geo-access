# wos-geo-access
Access restriction plugin for WordPress

Restrict access to WordPress logins, current version uses the geoPlugin api at http://www.geoplugin.com to look up IP addresses of people trying to login to WordPress.
geoPlugin includes GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>.
Note: geoPlugin lookups are limited to 120 requests per minute.