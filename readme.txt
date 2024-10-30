=== Bulk Generate Thumbnails ===
Contributors: Katsushi Kawamori
Donate link: https://shop.riverforest-wp.info/donate/
Tags: media, thumbnails
Requires at least: 4.7
Requires PHP: 8.0
Tested up to: 6.6
Stable tag: 3.02
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate thumbnails in bulk.

== Description ==

= Generate Thumbnails =
* Generate Thumbnails from all the images by asynchronous processing.
* Generate Thumbnails from select the images by asynchronous processing.
* If you are an administrator, it will generate thumbnails for all images, otherwise it will generate thumbnails for the images owned by each user.

= The following plugin adds a function for generating thumbnails. =
* Select the thumbnails and functions to disable it. [Disable Generate Thumbnails](https://wordpress.org/plugins/disable-generate-thumbnails/)
* Specify the ratio of thumbnails generation. [Ratio Thumbnails Size](https://wordpress.org/plugins/ratio-thumbnails-size/)

= WP-CLI =

WP-CLI commands are available.
* `wp bgth_cli mail` -> Send results via email.
* `wp bgth_cli nomail` -> Do not send results by email.
* `wp bgth_cli mail --uid=13` -> Process only specified User ID(13).
* `wp bgth_cli mail --pid=12152` -> Process only specified Post ID(12152).

== Installation ==

1. Upload `bulk-generate-thumbnails` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

none

== Screenshots ==

1. Initial screen
2. Execution screen

== Changelog ==

= [3.02] 2024/08/08 =
* Fix - Problems with judging user IDs for submissions.
* Added - Added the user nickname to the generate message.
* Added - Option to send results by email.

= [3.01] 2024/08/07 =
* Added - Added the post ID to the WP-CLI command argument.
* Tweak - Some functions were hooked.
* Fix - Some translations.

= [3.00] 2024/08/06 =
* Added - WP-CLI command for thumbnails bulk generation.

= [2.06] 2024/03/01 =
* Fix - Added nonce when sorting.

= 2.05 =
Changed json_encode to wp_json_encode.

= 2.04 =
PHP 8.0 is now required.

= 2.03 =
Supported WordPress 6.4.

= 2.02 =
Fixed potential security issue.

= 2.01 =
Fixed a pagination problem when searching for text.

= 2.00 =
Added support for selective generation.
Added support for per-user generation.

= 1.14 =
Supported XAMPP.

= 1.13 =
Supported WordPress 5.6.

= 1.12 =
Fixed problem of metadta.

= 1.11 =
Fixed a problem with error output.

= 1.10 =
Changed to asynchronous processing.

= 1.03 =
Fixed clear cron schedules issue.

= 1.02 =
Fixed timeout check issue.

= 1.01 =
Fixed translation.

= 1.00 =
Initial release.

== Upgrade Notice ==

= 2.02 =
Fixed potential security issue.

= 1.00 =
Initial release.
