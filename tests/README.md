Run `php tests/authentication.php` from the repository root.

The scenarios load the plugin with isolated WordPress hook, option, email and
termination stubs. Each scenario runs in its own PHP process to model the
request-local location-check cache. No database or network access is required.

Coverage includes login-page GET/POST denial before authentication, XML-RPC and
custom-form credential checks, application-password checks, allowed and disabled
requests, preservation of other authentication results, and duplicate-check/email
prevention across multiple hooks and XML-RPC multicalls.

These are isolated regression tests, not a full WordPress/Wordfence integration
suite. They assert that the plugin passes a 403 to WordPress's error handler;
they do not render the actual HTML, XML-RPC or JSON response. Missing, empty, malformed and unrecognised OpenLiteSpeed country codes must
deny access. Country names are optional, and repeated denials must not send
duplicate error emails. Provider response validation is covered separately by
`php tests/geolocation.php`.

Run `php tests/security.php` for settings validation/rendering, HTTP safeguards,
cache expiry, provider backoff, request budgets and notification throttling.
Run `php tests/proxy.php` for direct-IP, spoofed header, trusted IPv4/IPv6 chain,
Cloudflare header and local-GeoIP mismatch scenarios. Shared offline substitutes
are in `tests/wp-stubs.php`; no live provider or mail service is contacted.

Live server checks remain necessary: compare the settings page's visitor IP
with your current public IP, verify a known excluded-country request, and check
OpenLiteSpeed's trusted-proxy configuration if a proxy is present. Test the
actual HTML, XML-RPC and JSON responses with WordPress/Wordfence on a staging
site. These scripts do not certify a live server configuration.
