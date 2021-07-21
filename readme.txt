=== CDN Enabler ===
Contributors: keycdn
Tags: cdn, content delivery network, content distribution network
Requires at least: 5.1
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


A content delivery network (CDN) integration plugin for WordPress that rewrites URLs, like for CSS, JavaScript, and images, to be served by a CDN.


== Description ==
CDN Enabler is a simple and easy to use WordPress plugin that rewrites URLs, such as those for CSS, JavaScript, and images, to be served by a content delivery network (CDN). This helps improve site performance, reliability, and scalability by offloading the majority of traffic to a CDN.


= Features =
* Fast and efficient rewrite engine
* Manual and WP-CLI cache purging (when a [KeyCDN](https://www.keycdn.com) account is connected)
* Include URLs in the rewrite by file extensions
* Exclude URLs in the rewrite by strings
* WordPress multisite network support
* WordPress REST API support
* Works perfectly with [Cache Enabler](https://wordpress.org/plugins/cache-enabler/) and the majority of third party plugins


= How does the rewriting work? =
CDN Enabler captures page contents and rewrites URLs to be served by the designated CDN.


= Documentation =
* [Installation](https://www.keycdn.com/support/wordpress-cdn-enabler-plugin#installation)
* [Settings](https://www.keycdn.com/support/wordpress-cdn-enabler-plugin#settings)
* [Hooks](https://www.keycdn.com/support/wordpress-cdn-enabler-plugin#hooks)
* [WP-CLI](https://www.keycdn.com/support/wordpress-cdn-enabler-plugin#wp-cli)
* [FAQ](https://www.keycdn.com/support/wordpress-cdn-enabler-plugin#faq)


= Want to help? =
* Want to file a bug, contribute some code, or improve translations? Excellent! Check out our [GitHub issues](https://github.com/keycdn/cdn-enabler/issues) or [translations](https://translate.wordpress.org/projects/wp-plugins/cdn-enabler/).


= Maintainer =
* [KeyCDN](https://www.keycdn.com)


== Changelog ==

= 2.0.4 =
* Update configuration validation to include the Site Address (URL) as an HTTP `Referer` (#42)
* Update URL matcher in rewriter to match URLs that are in escaped JSON format (#41)
* Update CDN hostname validation to trim surrounding whitespace characters (#40)

= 2.0.3 =
* Update output buffer handling (#29)
* Fix purge cache request handling (#31)

= 2.0.2 =
* Update URL matcher in rewriter (#28)
* Update full URL rewrite (#28)

= 2.0.1 =
* Update URL matcher in rewriter (#25)
* Update settings conversion (#26)
* Add `cdn_enabler_exclude_admin`, `cdn_enabler_contents_before_rewrite`, and `cdn_enabler_contents_after_rewrite` filter hooks (#27)
* Fix configuration validation for installations in a subdirectory (#27)
* Remove `cdn_enabler_page_contents_before_rewrite` filter hook in favor of replacement (#27)

= 2.0.0 =
* Update output buffer timing to start earlier on the `setup_theme` hook instead of the `template_redirect` hook (#23)
* Update settings (#23)
* Update requirements check (#23)
* Update purge CDN cache handling (#23)
* Add new rewrite engine (#23)
* Add WP-CLI cache purging (#23)
* Add configuration validation (#23)
* Add `cdn_enabler_user_can_purge_cache`, `cdn_enabler_page_contents_before_rewrite`, `cdn_enabler_bypass_rewrite`, `cdn_enabler_site_hostnames`, and `cdn_enabler_rewrite_relative_urls` filter hooks (#23)
* Fix requirement notices being shown to all users (#23)
* Fix rewriting limitations (#23)
* Deprecate `user_can_clear_cache` filter hook in favor of replacement (#23)

= 1.0.9 =
* Rewrite URLs filtering the_content so that rendered HTML in REST API use CDN

= 1.0.8 =
* Purge CDN redirects to admin dashboard to avoid error messages
* Better error messages
* Do not display nag notice when KeyCDN API credentials are set

= 1.0.7 =
* Minor bug fixes (pass-by-reference)

= 1.0.6 =
* Minor bug fixes
* Improved CDN purging

= 1.0.5 =
* Multiprotocol CDN rewriting
* Add purging through KeyCDN API
* Don't rewrite if in admin preview mode
* Rewrite to HTTPS if enabled and client connects through HTTP

= 1.0.4 =
* Removed unused code

= 1.0.3 =
* Improved exclusions for directories and extensions

= 1.0.2 =
* Switched from siteurl to home (e.g. for bedrock support)

= 1.0.1 =
* First major release
* Fixed warnings

= 0.0.1 =
* First release


== Screenshots ==

1. CDN Enabler settings page
