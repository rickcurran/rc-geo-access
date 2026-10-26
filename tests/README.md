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

Run `phpcs --standard=phpcs.xml.dist` from the repository root for the full
WordPress coding standards checks. PHP_CodeSniffer and the WordPress Coding
Standards ruleset must be installed. The repository ruleset targets `trunk`
and excludes historical tags and the offline test substitutes. It checks the
`rc-geo-access` translation domain. Server-variable reads use built-in validation
and wp_unslash() explicitly, so Plugin Check can recognise them without custom
PHPCS configuration. No security sniff is disabled. Tests model WordPress
bootstrap slashing and verify that malformed addresses and country codes remain
invalid after unslashing; text sanitization cannot repair them into allowed values.

Use `phpcbf --standard=phpcs.xml.dist` for automatic formatting, review its diff,
and rerun the regression scripts after any fixes. Before publishing, run the
WordPress Plugin Check plugin on a staging installation and complete the live
checks described above. PHPCS does not replace Plugin Check or runtime testing.
Assign the release version in the plugin header, readmes and stable tag as part
of publication; development changes are recorded under Unreleased until then.
