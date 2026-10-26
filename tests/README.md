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
they do not render the actual HTML, XML-RPC or JSON response. The existing
lookup-error policy is deliberately preserved and covered by one scenario.
