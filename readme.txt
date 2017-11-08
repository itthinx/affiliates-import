=== Affiliates Import ===
Contributors: itthinx, proaktion
Donate link: http://www.itthinx.com/shop/
Tags: affiliate, affiliates, affiliate marketing, referral, growth marketing, import, affiliate plugin, affiliates plugin
Requires at least: 4.0.0
Tested up to: 4.8.3
Requires PHP: 5.5.0
Stable tag: 1.1.0
License: GPLv3

Import affiliate accounts with [Affiliates](https://wordpress.org/plugins/affiliates/), [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) and [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/).

== Description ==

This plugin allows to import affiliate accounts from a text file into [Affiliates](https://wordpress.org/plugins/affiliates/), [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) and [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/).
It is an extension and requires one of these to import affiliates on your site from a text file using values separated by tabs.
It supports custom fields as defined under Affiliates > Registation.

For detailed usage instructions, please refer to the [Documentation](http://docs.itthinx.com/document/affiliates-import/).

The text file be in tab-separated values format. Example of an input file:

  @user_login	user_email	first_name	last_name
  affiliate1	affiliate1@example.com	Maria	One
  affiliate2	affiliate2@example.com	Matthew	Two
  affiliate3	affiliate3@example.com	Joanna	Three
  affiliate4	affiliate4@example.com	Joseph	Four

The first line with the `@` sign indicates the column order of the fields below.
You can change the order of the fields and skip any except the `user_email`.

Requirements:

- [Affiliates](https://wordpress.org/plugins/affiliates/) or [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) or [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/).
- A text file in the appropriate format (entries separated by tabs) that holds information about the affiliate accounts to create.

Documentation:

- [Affiliates Import](http://docs.itthinx.com/document/affiliates-import/)

== Installation ==

1. Install and activate [Affiliates](https://wordpress.org/plugins/affiliates/) or [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) or [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/).
2. Install and activate this plugin [Affiliates Import](https://wordpress.org/plugins/affiliates-import).
3. Go to Affiliates > Import on your WordPress Dashboard and import the desired affiliate accounts from an existing text file.

Note that you can install the plugins from your WordPress installation directly: use the *Add new* option found in the *Plugins* menu.
You can also upload and extract them in your site's `/wp-content/plugins/` directory or use the *Upload* option.

== Frequently Asked Questions ==

= Where can I find the documentation for this tool? =

The documentation is located at [Affiliates Import](http://docs.itthinx.com/document/affiliates-import/).

= What plugins are required to import affiliates with this tool? =

You can use any of these:
- [Affiliates](https://wordpress.org/plugins/affiliates/) (free) or
- [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) (premium) or
- [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/) (premium)

== Screenshots ==

Please refer to the Documentation for details:

- [Affiliates Import](http://docs.itthinx.com/document/affiliates-import/)

== Changelog ==

= 1.1.0 =
* Added support for additional registration fields as defined under Affiliates > Settings > Registration.
* Improved the admin section with additional field and file format information.
* Updated the readme.txt (adds the "Requires PHP" tag, adds the Upgrade Notice section and provides a short example).
* The "user_pass" Field Name has been deprecated in favor of "password" used in the standard registration fields.
* Fixed option filter to avoid sending out administrator notifications for imported affiliates.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

This version adds support for custom fields defined under Affiliates > Settings > Registration and improves the admin section under Affiliates > Import.
