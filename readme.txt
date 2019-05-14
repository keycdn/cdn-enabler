=== CDN Enabler - WordPress CDN Plugin ===
Contributors: keycdn
Tags: cdn, content delivery network, content distribution network
Requires at least: 4.6
Tested up to: 5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



Enable CDN URLs for your static assets such as images, CSS or JavaScript files.



== Description ==

A **content delivery network (CDN)** is a network of distributed edge servers, which accelerate your content around the globe. The main benefits of a CDN are *scalability*, *reliability* and *performance*. The **CDN Enabler** plugin helps you to quickly and easily integrate a CDN in WordPress.

= What it does? =
The CDN Enabler plugin has been developed to link your content to the CDN URLs.

= Features =
* Link assets to load from a CDN
* Set included directories
* Define exclusions (directories or extensions)
* Enable or disable for HTTPS
* Supports [Bedrock](https://roots.io/bedrock/ "Bedrock CDN")

> The CDN Enabler works perfectly with the fast and lightweight [WordPress Cache Enabler](https://wordpress.org/plugins/cache-enabler/) plugin.


= System Requirements =
* PHP >=5.6
* WordPress >=4.6


= Contribute =
* Anyone is welcome to contribute to the plugin on [GitHub](https://github.com/keycdn/cdn-enabler).
* Please merge (squash) all your changes into a single commit before you open a pull request.


= Author =
* [KeyCDN](https://www.keycdn.com "KeyCDN")



== Changelog ==

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
