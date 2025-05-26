=== Lolly Log ===
Contributors: bitmachia
Tags: logging, http, monitoring, debugging, developer-tools
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced HTTP request/response logging and monitoring for WordPress with configurable redaction and whitelisting.

== Description ==

Lolly Log is a comprehensive logging solution for WordPress that captures and monitors HTTP requests, REST API calls, and other critical application events. The plugin provides detailed logging capabilities with built-in data redaction features to protect sensitive information.

= Key Features =

* **WordPres HTTP Client Request Logging** - Capture all incoming and outgoing HTTP requests
* **WP REST API Monitoring** - Log WordPress REST API requests and responses
* **Configurable Redaction** - Automatically redact sensitive data from logs
* **Host Whitelisting** - Control which hosts are logged
* **ECS Log Format** - Structured logging using Elastic Common Schema
* **Admin Interface** - Easy-to-use configuration panel
* **Developer Friendly** - PSR-3 compliant logging with extensive customization

= Data Protection =

The plugin includes sophisticated redaction capabilities to protect sensitive information:

* Query parameter redaction
* HTTP header filtering
* Request/response body sanitization
* Configurable redaction rules
* Host-based whitelisting

= Use Cases =

* **Development & Debugging** - Track down issues in WordPress applications
* **Performance Monitoring** - Analyze HTTP request patterns and response times
* **Security Auditing** - Monitor API access and suspicious activity
* **Compliance** - Maintain audit logs while protecting sensitive data

== Installation ==

1. Upload the `lolly` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings' > 'Lolly Log' to configure logging options
4. Set up your redaction rules and host whitelist as needed

== Frequently Asked Questions ==

= Does this plugin affect site performance? =

The plugin is designed to have minimal performance impact. Logging operations are optimized and can be configured to only capture specific types of requests.

= Can I exclude certain hosts from logging? =

Yes, the plugin includes a host whitelist feature that allows you to control which hosts are logged.

= Is sensitive data automatically protected? =

The plugin includes configurable redaction rules to automatically sanitize sensitive information from logs. You can customize which data fields are redacted.

= What log format does the plugin use? =

The plugin uses the Elastic Common Schema (ECS) format for structured logging, making it compatible with modern log analysis tools.

== Screenshots ==

1. Main settings page with logging configuration options
2. HTTP redaction management interface
3. Host whitelist configuration panel
4. Log export and analysis tools

== Changelog ==

= 0.1.0 =
* Initial release
* WP HTTP client request/response logging
* WP REST API monitoring
* Configurable data redaction
* Host whitelisting functionality
* ECS-compliant log formatting
* Admin configuration interface

== Upgrade Notice ==

= 0.1.0 =
Initial release of Lolly Logger with comprehensive HTTP logging and data protection features.

== Developer Notes ==

This plugin follows WordPress coding standards and includes:

* PSR-3 compliant logging interfaces
* Comprehensive unit test coverage
* Codeception testing framework
* PHPCS/PHPStan code quality tools
* Modern PHP practices and type safety
