=== Plugin Name ===
Tags: email, mail, smtp, spam, wp_mail, e-mail, solution, fix, problem, mx
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk
Contributors: tntu, gotic

Replaces wp_mail and sends email the right way so it does not get flagged as SPAM.

== Description ==

All webmasters experience the problem of their emails landing in their users spam folders, including our own! 
So we decided to develop a simple solution to fix this wide-spread annoyance. 
After a long development period, we have produced an effective method of sending emails which complies 
in full with all guidelines and security requirements of email providers such as Google, Yahoo & MSN. 


== Installation ==

1. Upload `xmail-the-right-way` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Read the Xmail page in Settings menu in wp-admin

       You should also contact your hosting provider and ask him to activate Domain Keys and SPF for your domain.

== Frequently Asked Questions ==

* So what ix Xmail?

Xmail is a PHP class that can send an email in 3 diffrent ways:
1. via the old fashion PHP standard function called mail()
2. via a SMTP account (one of the most popular alternatives)
3. direct connection to the receiver MX server (the optimal way)


* Why is this the best way?

Each server that hosts websites has multiple IP's.
In 99% of cases, the (email) main server IP is different from the IP used for the domain itself, so your domain has one IP and the emails you send go out from another (main server IP). You will probably share an email server with many other users, and if these users are unscrupulous and send out SPAM, you will suffer as well because the main server IP you use will lose credibility and become untrusted by the email providers named above. This will result in your emails never reaching your users' inboxes.
However, some website servers have an option to send out emails from the main IP, but only SMTP allows for this. STMP is an email setup enabled by the Xmail plugin.
Should that option be incompatible, the only thing left is to use the MX method which does not rely on any other service in the server. It's pure PHP and will always use your site's IP. This MX method is also enabled by the Xmail plugin.
Note: You should also contact your hosting provider and ask him to activate Domain Keys and SPF for your domain.

== Changelog ==

= 1.00 =
* Initial release

= 1.01 =
* Tested and set to work starting WP 3.0 UP


== Upgrade Notice ==

* Run regular update. Nothing will be affected!

== Screenshots ==

1. Behind the mailing system.

== Markdown ==

* Your emails will no longer be marked as SPAM.
* If your emails do not even arrive to Yahoo! or MSN with Xmail they will.
* This is the way Google, Yahoo! and MSN best like emails to come to them.