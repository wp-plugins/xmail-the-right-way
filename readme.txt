=== Plugin Name ===
Tags: email, mail, smtp, spam, wp_mail, e-mail, solution, fix, problem, mx, correct
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk
Contributors: Marian Vlad-Marian (eyemedia@gmail.com), gotic

Replaces wp_mail and sends email the right way so it does not get flagged as SPAM.

== Description ==

Replaces wp_mail and sends email the right way so it does not get flagged as SPAM.
Most servers use a diffrent IP address to send email from then the IP of your domain and thus your emails get into SPAM folders or not att all in some cases of Yahoo! and MSN. 
This will send emails from your domain IP address. 
It might take 1-2 seconds more to send it but it is worth it.


== Installation ==

1. Upload `xmail-the-right-way` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Read the Xmail page in Settings menu in wp-admin

       You should also contact your hosting provider and ask him to activate Domain Keys and SPF for your domain.

== Frequently Asked Questions ==

Does this have anny affect on other emil sengind plugins?

No.
If those plugins use wp_mail they will send via Xmail from now on.
If they do not they will ahve no affect.
If you are useing another wp_mail replacer then Xmail will not activate if another wp_mail is detected.

== Changelog ==

= 1.00 =
* Initial release

= 1.01 =
* Tested and set to work starting WP 3.0 UP


== Upgrade Notice ==

* Run regular update. Nothing will be affected!

== Screenshots ==

http://plugins.svn.wordpress.org/xmail-the-right-way/trunk/spf.png

== Markdown ==

* Your emails will no longer be marked as SPAM.
* If your emails do not even arrive to Yahoo! or MSN with Xmail they will.
* This is the way Google, Yahoo! and MSN best like emails to come to them.