=== Plugin Name ===
Contributors: got1c
Tags: email, mx, correct, right, way
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: trunk

Send email so it does not get flagged as SPAM.

== Description ==

Send email the right way so it does not get flagged as SPAM. 
Most servers use a diffrent IP address to send email from then the IP of your domain and thus your emails get into SPAM folders or not att all in some cases of Yahoo! and MSN. 
This will send emails from your domain IP address. 
It might take 1-2 seconds more to send it but it is worth it.


== Installation ==

1. Upload `xmail-the-right-way` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Read the Xmail page in Settings menu in wp-admin
4. You should also contact your hosting provider and ask him to activate Domain Keys and SPF for your domain.

== Frequently Asked Questions ==

Does this have anny affect on other emil sengind plugins?

No.
If those plugins use wp_mail they will send via Xmail from now on.
If they do not they will ahve no affect.
If you are useing another wp_mail replacer then Xmail will not activate if another wp_mail is detected.

== Changelog ==

= 1.0 =
* Initial release


== Upgrade Notice ==

* No previous version

== Screenshots ==

* There can't be any as this is just under the hood plugin.

== Markdown ==

* Your emails will be be marked as SPAM
* If your emails do not even arrive to Yahoo! or MSN now they will
* This is the way Google, yahoo! and MSN best like emails to come to them