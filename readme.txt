=== Custom Contact Form & Mailing List ===
Contributors: cheveedodd
Tags: contact form, newsletter, mailing list, gutenberg, spam protection
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.5.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

A lightweight, zero-database, privacy-first WordPress form plugin offering a custom contact form and mailing list signup form with built-in anti-spam protection.

== Description ==

Custom Contact Form & Mailing List provides a clean, modular solution for adding a simple contact/mailing list forms to your WordPress site. Built with support for both Gutenberg blocks and shortcodes, the plugin includes many customization optins including SMTP without the need for third party assets.

NOTE: This project was built for my personal use to fit very specific needs. I doubt it conforms to any sort of proper coding standard and definiltely isn't ready for release on the wider WordPress ecosystem.

= Included Forms =

1. **Contact Form**
   * Designed for general site inquiries, contact pages, and user messages.
   * Sends customizable email notifications to site administrators.
   * Supports custom email templates, SMTP mail routing, and post-submission redirect URLs.

2. **Mailing List Signup Form**
   * Optimized for capturing subscriber email addresses for newsletters and project updates.
   * Minimalist form layout designed for high conversion and quick submissions.
   * Configurable redirect URL support upon successful subscription (e.g., custom "Thank You" or welcome page).

= Anti-Spam Protection =

Both forms feature multi-layered spam prevention that operates silently without requiring intrusive CAPTCHAs:
* **Honeypot Field:** Hidden form input that traps automated spam bots.
* **Time-Check Validation:** Measures submission speed to detect and block sub-second automated fills.
* **Custom Q&A Verification:** Optional challenge question for additional verification on sensitive fields.

== Installation ==

1. Upload the `custom-forms` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure SMTP settings, custom redirect URLs, and email templates under **Settings > Custom Forms**.
4. Embed either form into any post or page using the block editor or dedicated shortcodes.

== Usage & Display ==

Both forms can be inserted using native Gutenberg blocks or standard shortcodes.

= Contact Form =
* **Gutenberg Block:** Search for "Contact Form" in the block inserter.
* **Shortcode:** `[custom_contact_form]`

= Mailing List Form =
* **Gutenberg Block:** Search for "Mailing List Form" in the block inserter.
* **Shortcode:** `[custom_mailing_list_form]`

== Frequently Asked Questions ==

= How are notification emails sent? =
Emails can be routed through your configured SMTP settings or standard WordPress mail (`wp_mail()`).

= Does this plugin work with modern block themes? =
Yes, the plugin is built with hybrid block support, making it compatible with full-site editing (FSE) block themes as well as classic themes using shortcodes.

== Changelog ==

= 1.5.0 =
* Initial public release.