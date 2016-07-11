=== WP Site Protect ===
Contributors: vaurdan
Tags: site, password, protect, protection, multiple
Donate link: http://mowta.pt
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 1.0
License: GPLv3

Protect your WordPress website with unlimited unique traceable passwords.

== Description ==
If you need to protect your WordPress website with multiple unique passwords, WP Site Protect is a great pick. You can create unlimited passwords that can be traced - who used it last time and when, and you can force your fresh baked password to be renewed when it\'s used for the first time.

This is also highly customizable. You can edit all the texts you need or you can even replace the whole template by including a single php file on your theme. You can read more about that on the FAQ.

This plugin is stable, but it\'s still under development. If you find any issues, feel free to open a support request or a Github issue at [WP Site Protect repository](https://github.com/vaurdan/wp-site-protect).

== Installation ==
1. Install WP Site Protect either via the WordPress.org plugin directory, or by uploading the files to your server (/wp-content/plugins/)
2. Activation can be made in \'Plugins\' menu
3. Configure and create your passwords on the Site Passwords admin section.


== Frequently Asked Questions ==
= How can I customize the CSS? =

Easy! You can use your theme\'s CSS as WP Site Protect pages uses the enqueued script/styles. If you need, you can remove the default WP Site Protect css by dequeueing the `wpsp_protect` css.

= How can I customize the password required template? =

You can either just edit the text on the plugin settings, or completely rewrite the template. You have two different templates that you can drop on your theme\'s root:

 * `wpsp-protected.php` - This is the template for the password request page.
 * `wpsp-new-password.php` - This is the theme for password renewal page.

You can customize them however you like, but you have to be careful about the forms. If you want, you can use the helper functions `wpsp_auth_form()` and `wpsp_reset_password_form()` that will output the correct form. If you want to make your own form, just make sure you keep the same form name and input names.

== Screenshots ==
1. Password creation page
2. Password details page
3. Password protected site
4. Settings page

== Changelog ==
= 1.0 =
 * Released first version.
