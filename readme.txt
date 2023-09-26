=== Leaky Paywall - Article Countdown Nag ===
Contributors: layotte, peterericson, endocreative
Tags: metered, paywall, leaky, wordpress, magazine, news, blog, articles, remaining
Requires at least: 5.6
Tested up to: 6.3.1
Stable tag: 3.8.4

Creates an <a href="https://leakypaywall.com/downloads/article-countdown-nag/">Article Countdown Nag</a> for Leaky Paywall WordPress plugin. More info at https://leakypaywall.com

== Description ==

Lets the reader know how many free articles/pages they have left before they need to subscribe.
Requires zeen101's Leaky Paywall plugin.
More info at <a href="https://leakypaywall.com/">Leaky Paywall</a>

You can follow this plugins development on [GitHub](https://github.com/zeen101/issuem-leaky-paywall-article-countdown-nag)

== Installation ==

1. Upload the entire `leaky-paywall-article-countdown-nag` folder to your `/wp-content/plugins/` folder.
1. Go to the 'Plugins' page in the menu and activate the plugin.

== Frequently Asked Questions ==

= What are the minimum requirements for zeen101's Leaky Paywall - Article Count Nag? =

You must have:

* WordPress 3.3 or later
* PHP 7
* Leaky Paywall version 4.0.0 or later

= How is Leaky Paywall Licensed? =

* Leaky Paywall - Article Countdown Nag is GPL

== Changelog ==

= 3.8.4 =
* Update handling of internationalization, again

= 3.8.3 =
* Update handling of internationalization

= 3.8.2 =
* Update text domain for translations

= 3.8.1 =
* Integrate zero nag with registration redirect extension
* Update settings location

= 3.8.0 =
* Update handling of ip blocker
* Code cleanup

= 3.7.4 =
* Update handling of zero remaining nag

= 3.7.3 =
* Fixes for jQuery updates

= 3.7.2 =
* Change updater class file name

= 3.7.1 =
* Add translation updates

= 3.7.0 =
* Fix javascript function for 5.5 compatibility
* Add content remaining to content filter
* Update updater class
* Do not show countdown if ip is blocked by ip blocker

= 3.6.2 =
* Remove debug code

= 3.6.1 =
* Update number allowed to work with category restrictions

= 3.6.0 =
* Update number allowed to work with combined restrictions
* Add logic so current subscribers will not see countdown nag

= 3.5.0 =
* Refactor display class for compatibility with Leaky Paywall's category restrictions
* Add leaky_paywall_acn_countdown filter for adjusting html output of countdown
* Add leaky_paywall_acn_countdown_remaining_text filter
* Add leaky_paywall_acn_countdown_after_remaining_text action
* Add leaky_paywall_acn_zero_screen filter for adjusting html output of zero screen
* Add leaky_paywall_acn_zero_screen_remaining_text filter
* Add leaky_paywall_acn_zero_screen_after_remaining_text action


= 3.4.3 =
* Move all display functions to javascript to get around caching
* Update restrictions checks to mostly use Leaky Paywall Restrictions class

= 3.4.2 =
* Fix for non restricted pages
* Cleaned up old multisite check causing cookie inconsistencies

= 3.4.1 =
* Security fix by changing use of maybe_unseralize to json_decode on zero nag

= 3.4.0 =
* Security fix by changing use of maybe_unseralize to json_decode

= 3.3.0 =
* Fix bug so nag obey's single post visibility settings
* Fix bug so that settings don't reset when saved on a different tab
* Add actions and filters in proces_requests function

= 3.2.0 =
* Adjusted settings hooks for Leaky Paywall tab settings layout

= 3.1.0 =
* Fix Leaky Paywall Cookie Check
* Fix type on nag_after_countdown save
* Work for new Leaky Paywall fork

= 3.0.0 =
* Fixed bug to work with v3 of Leaky Paywall

= 2.0.2 =
* Fix for new cookie variables in Multisite

= 2.0.1 =
* Removed unused lines
* Styling fix for slim view
* Set subscriber and login link separated

= 2.0.0 =
* Updating IssueM references to point to zeen101
* Setup nag to make sure Leaky Paywall is activated and at least version 2.0.0 in order to use this addon
* Add styles and settings for slim countdown nag theme
* Fixing single() test in processing
* Fixing countdown value check

= 1.0.1 =
* Added some better default styling

= 1.0.0 =
* Initial Release

== License ==

Leaky Paywall - Article Count Nag
Copyright (C) 2011 The Complete Website, LLC.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
