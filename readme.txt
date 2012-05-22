=== WP-Activity ===
Contributors: Dric1107
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, event, monitor, stats, blacklist, tracking, access, security, login
Requires at least: 3.1
Tested up to: 3.3.2
Stable tag: 1.9.2

Monitor and display registered users activity (logins, posts, comments, etc.). You can also track and prevent hackering attemps, with IP blacklisting.

== Description ==

This plugin logs registered users activity in your blog and displays it in frontend and backend.
It can also track and deny access by blacklisting to unwanted login attempts.
Activity logged :

- logon
- new user
- new comment
- comment edition
- comment deletion
- profile update
- new published post
- published post edition
- post deleted (really deleted, not trashed)
- new link
- login failure (displayed only in admin panel)
- access denied by IP blacklisting (displayed only in admin panel)

Possible usages :

- Monitor unwanted connexions attempts on your blog and block hackers IP.
- Monitor the registered users activity on a multi-users blog.
- Enhance your community blog by displaying to all users what other members have done.

If enabled, user who don't want to be listed in blog activity can hide its own activity by checking a privacy option in the profile page. In that case, this user activity is not stored in database.
When a login failure occurs, the IP address is also logged.
Users activity can be followed by RSS feed and can be exported in csv file (semicolon separation).

Admin can follow the blog users activity within dates range with the stats module.

To avoid spammers or hackers trying to steal accounts, you can blacklist their IP addresses. Be careful, I you blacklist your own IP you won't be able to login anymore !
Blacklisted IP addresses get a 403 error when trying to logon, and the activity log displays an 'access denied' event.
Keep in mind that this plugin is not security oriented. There are lots of plugins that specifically deal with [security](http://wordpress.org/extend/plugins/search.php?q=security).

Thanks to [Venntom](http://wordpress.org/support/profile/venntom) for finding a lot of bugs each time I release a new version, and for helping me fix them.

Translations :

- French
- Italian (Thx to Luca - partially translated up to v1.2)
- Turkish (Thx to Can KAYA - translated up to v1.2)
- Spanish (Thx to Cscean - translated up to v1.3)
- Romanian (Thx to Web Geeks - translated up to v1.7)
- Dutch (Thx to Tom - translated up to v1.8.1)
- Russian (Thx to Semyon Nikiforov - partially translated)

(If you translated my plugin, please send the translated .po file at cedric@driczone.net )

[Plugin page](http://www.driczone.net/blog/plugins/wp-activity/) (French blog but feel free to comment in english)

I my plugin doesn't fit your needs, you can also try [ThreeWP Activity Monitor](http://wordpress.org/extend/plugins/threewp-activity-monitor/) by [Edward Mindeantre](http://mindreantre.se).

Resources used :

- Fugue Icons by Yusuke Kamiyamane (http://p.yusukekamiyamane.com)
- Flot jQuery library (http://code.google.com/p/flot/)


== Installation ==

Manual :

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,

Automatic :

1. In your Admin Panel, go to `Plugins > Add New`
2. Search for "__WP-Activity__"
3. Click Install Now under "__WP-Activity__"

Setting plugin :

4. Go to `Wp-Activity > Settings` for plugin options.

For security use :

5. Activate Login failures log.
6. Set blacklist options.

For frontend use :

5. Put `<?php act_stream() ?>` where you want the stream to be displayed, or use included widget.
6. Use `[ACT_STREAM]` to display activity in a page or post. See FAQ section for parameters.

== Frequently Asked Questions ==

= How do I enable the user last logon on author or index page ? =

Use `<?php act_last_connect($author) ?>` in author.php template, or `<?php act_last_connect() ?>` in index page.
If you only want to display the last login date without any text, use `<?php act_last_connect($author, 'no_text') ?>`.

= How do I add user activity on it's author page ? =

Use `<?php act_stream_user($author) ?>` in author.php template

= How do I set the events number or the title when not using the widget ? =

this function accepts two parameters :
`<?php act_stream(number,title) ?>`

defaults are :

* number = 30
* title = Recent Activity (translated by .mo)

= Shortcode use =

`[ACT_STREAM]`

`[ACT_STREAM number="" title=""]`

defaults are :

* number = no limit
* title = Recent Activity (translated by .mo)

= I blacklisted my own IP address, or I can't login anymore since I activated the blacklisting ! =

Just rename or delete the wp-activity directory in wp-content/plugins/, and you should be able to access to your blog.

= The Blacklist tab is disabled in admin panel =

Before settings blacklist options, you need to activate logon failures log (in the previous tab).

= How do I avoid erasing css tweaks when I update the plugin ? =

Just put a copy of wp-activity.css in your theme dir, it will be processed instead of the css file included with the plugin.

= How do I display all activity ? =

You must specify "-1" in number parameter. All activity stored in database will be returned.

= How do I change author page links ? =

Change the value in the plugin administration, under display options tab.

= How do I Change the events generic icons ? =

Just change the icons in the /img directory, but keep the event name (example : to change the login/connect event icon, change the icon named CONNECT.png - names must be in capitals). If events don't have related icons, you can add it by naming an icon from the event name. I used [Fugue Icons](http://p.yusukekamiyamane.com) - shadowless version for generic icons.

= I added a post and changed the author, and the activity logs have changed too. How could I disable this ? =

You will have to edit wp-activity.php, check line 32 and set `$strict_logs` to **true**.

= How can I change the search field filter in admin activity log by the user list ?

You will have to edit wp-activity.php and change the value for the `$act_user_filter_max` var (near line 33).

= I exported data to a csv file but there are ugly characters in MS Excel ! =

This is a known excel bug : when you open a .csv file in Excel, it forces the use of the local encoding set (WINDOWS-1252 for French) and not UTF-8. To avoid this, you will have to rename the file extension from .csv to .txt, open Excel, do File/Open and open the wp-activity.txt. The csv import assistant will now launch, allowing you to set the encoding to UTF-8.

= I would like to display more or less than 50 lines per page in admin panel of wp_activity =

You have to modify the `$act_list_limit` var line 31 of wp-activity.php.

= I don't need the last login column in user list or I don't need the last login failures in admin panel =

You have to modify the `$no_admin_mess` var line 33 of wp-activity.php and set it to **true**.

= I have a poor hosting, is your plugin a big fat resources consumer ? =
I also have a poor hosting, so I try to keep my plugin as light as I can ; the admin scripts and css files are only loaded when needed.
Best-Performance tips :

* Don't use Gravatars as they generate more sql queries and are slower to display.
* If you don't use frontend login form, check the 'blacklist on wp-login.php only' option. If you want to blacklist an IP address on all your blog, use htaccess filtering instead.
* Don't activate activity RSS feed.
* Unckeck the events you don't want to monitor.

= Do you really test your plugin before publishing new versions at the Wordpress Plugin Repository ? =

Hum. I'm testing it on two Wordpress installations (local WAMP and online test site), and I send my beta versions to my favorite tester. But even with that, some bugs stay present. That's why there is often updates that just fix the previous ones... Sorry for that.
If you want to be sure it's debugged, you can wait a few days for a x.x.1 version release.


== Screenshots ==

1. frontend display
2. admin screen - activity display
3. admin screen - one of the settings tabs
4. admin screen - stats

== ChangeLog ==

= 2.0 =
* Added auto-refresh for activity displayed on frontend (with AJAX).
* Added partial Russian translation (Thx to Semyon Nikiforov)
* Various Tweaks

= 1.9.3 =
* Fixed bug with stats display.
* Fixed bug with translation string.
* Updated Dutch translation by [Venntom](http://wordpress.org/support/profile/venntom).

= 1.9.2 =
* Fixed bug with event display.

= 1.9.1 =
* Fixed bug with UTC+0 timezone .

= 1.9 =
* Added new events types logging : new users, comments edits, comments deletions (not spam comments), posts deletions (real deletions, not trashed posts).
* Added a search field for filtering by data in admin activity log (to search for IP addresses, posts, etc.).
* Added partial Romanian translation (for version 1.7, not up-to-date !) by [Web Geeks](http://webhostinggeeks.com).
* When there is more than 25 users, the user filter is now displayed with a search field (with autocomplete) instead of the users list for better performance/readability.
* In frontend display, a default icon is now displayed if the event has no icon associated.
* Changed the way modified post events are monitored.
* Changed, added and deleted a few translation strings.
* Changed minimum user capability to access plugin in admin panel from 'publish_post' to 'administrator'.
* Various Tweaks.
* When a post or a comment is deleted, the post and comments related events are updated (the title of the deleted post is saved instead of post id).
* Fixed a possible bug with dates timezones. This could mess a little your previous logged events dates (Thx to [Elmoonfire](http://wordpress.org/support/profile/elmoonfire) ).
* Fixed ACCESS_DENIED events that are no more displayed in frontend.
* Fixed possible wrong url path to users profiles in RSS feed.

= 1.8.1 =
* Fixed bug with blacklist tab who stay disabled unless you uncheck/check again the logon failures logging option.
* Updated Dutch Translation By [Venntom](http://wordpress.org/support/profile/venntom).

= 1.8 =
* Added auto-blacklisting of IP addresses after a configurable number of failed logon attempts in the last 2 days.
* Added compatibility with Better-WP-Security plugin (false failed logon events when log in).
* Added logging of IP Address in successful login events.
* Added widget width setting (was previously set in wp-activity css file - default to 350px).
* You will be now redirected to the previously selected tab when reloading settings page (at the cost of a small js file load).
* User filter and LOGIN_FAILED filter combination is now possible.
* Changed Activity RSS feed for better integration with Wordpress RSS Feeds and permalinks.
* Blacklist tab is now disabled when Logon failures log is not enabled.
* Fixed Activity RSS feed missing user names.
* Fixed daily cron task.
* Fixed exported data deletion.
* Fixed bad link in plugins list additions for wp-activity (settings/uninstall).

= 1.7.1 =
* Fixed bug with the logon log function.
* Fixed php bug where numbers were possibly displayed as scientific notation with a comma, totally messing up js code and preventing stats chart to display.
* Stats chart will now display under IE 7 & 8 (missing compatibility js file).

= 1.7 =
* Added blacklisting of IP addresses.
* Added dutch translation (Thx to Tom Vennekens).
* Admin and export functions are only loaded when needed (separate php files).
* Tweaked Cron task activation.
* Changed the display of settings page to look more like 'standard' admin WP.
* Replaced a few translation strings, sorry for translaters.
* Fixed Logon events who where only added when entering credentials since v1.6. Authentification with cookie ('remember me' option) will now generate a login event.
* Fixed deletion of old activity (cron task).
* Fixed csv file generation bug for IE.
* Fixed missing datepicker js script when using wordpress prior to 3.3.

= 1.6.1 =
* Fixed pages navigation links

= 1.6 =
* Added Logging of IP Address when a logon failure occurs.
* Added Activity stats.
* Added a few css rules to wp-activity.css (custom css files must be updated).
* Changed plugin menus (Wp-Activity has now it's own menu).
* Changed CONNECT events tracking, should be less disturbed by plugins that deals with Wordpress login.
* Fixed csv file generation (bug with url rewriting).
* Fixed (again) empty Last login column in user list when using User Access Manager plugin.
* Fixed login failures bad link in right now widget.

= 1.5 =
* Added current rows count in db next to the max rows value setting.
* Added export to csv file - filters and ordering are also processed to exported data.
* Added filtering by user in admin activity list.
* Tweaks and optimizations.
* Fixed missing profile field to allow user privacy.
* Fixed double login events when using a plugin dealing with WP login.
* Fixed "last connect" empty data values when using a plugin that deals with WP admin panel users list.
* Fixed a bug in multisites environment where queries to the users table where wrong (bad prefix).
* Fixed a bug where spam comments were written in activity table (but not displayed). 

= 1.4 =
* Added a 'Last Login' column in WP-Admin user list page.
* Added an option to change the author page links when your permalink structure for authors is not 'author'.
* Added a widget to display to a logged user its own activity.
* Fixed a css conflit when using jquery.tabs in another plugin (Thx to Cscean - http://cscean.es/)
* Added Spanish Translation (Thx to Cscean - http://cscean.es/)

= 1.3.2 =
* If two login events occur within a minute, only the first of them is displayed in frontend (double login events reported with facebook login).
* Corrected another bug with dates (Thx to Royzzz - http://www.roypoots.nl).

= 1.3.1 =
* Security check removed as it causes fatal error.

= 1.3 =
* Added logon fails count since last administrator login on "Right Now" admin panel widget.
* More privileges security added.
* Corrected a bug with relatives dates.

= 1.2.1 =
* Fixed bad posts links in admin and RSS logs (Thx again to Mario_7).

= 1.2 =
* Fixed stupids "\n" displayed in plugin admin.
* Added links in wordpress plugin lists to configure or uninstall WP-Activity.
* Fixed a misplaced div closing tag (Thx to Mario_7).
* Added Turkish translation (Thx to Can KAYA - http://www.kartaca.com)

= 1.1 =
* Fixed RSS feed (it has probably never worked outside of my wordpress test site).
* Admin can now prevent users to deny logging of their activity.
* Activity list in admin panel has now the same ergonomy as the standard wp admin lists (with pagination, filtering and ordering).
* Login failures can now be logged.

= 1.0 =
* Reset/uninstall tab
* User activity can now be displayed on author page
* If the author of a post has been changed, the plugin will change it in activity logs too. See FAQ for more details.

= 0.9.1 =
* Fixed a XSS vulnerability (Thx again to Julio - http://www.boiteaweb.fr)
* Admin panel improved
* Activity archive link in frontend

= 0.9 =
* improved shortcode - now with parameters.
* possible use of an alternate css file in theme directory - avoid erasing css tweaks with plugin updates.

= 0.8.2 =
* Use of a cookie instead of a session var.
* Fixed a CSRF vulnerability (Thx to Julio - http://www.boiteaweb.fr)

= 0.8.1.1 =
* Bug fix that prevented activity to be displayed in frontend.

= 0.8.1 =
* Added shortcode [ACT_STREAM] to display activity on a page or post.

= 0.8 =
* New activity can be highlighted since last user login (in fact old activity is greyed out)
* Bug fix with a possibly shared var name (thx to Stephane)

= 0.7.2 =
* Bug fix with cron settings for deleting old activity

= 0.7.1 =
* Bug fix when auto-delete old activity (activity limit)

= 0.7 =
* User last logon can now be displayed on author page

= 0.6 =
* admin panel tweaked
* Plugin now support gravatars for connect and profile edit events. Generic icons can also be used.
* Activity stream display tweaked.

= 0.5 =
* Added setting for using relatives dates
* Activity is now displayed in the admin plugin page (backend)
* Post Add/Edit events are now correctly logged

= 0.4a =
* Comments and posts adds are now correctly logged

= 0.4 =
* Post creation/edition separated
* Add link event added (only public links)
* Users can hide their activity from profile
* RSS feed added

= 0.3a =
* Big bug (introduced in 0.3) squeezed

= 0.3 =
* Less SQL queries
* Admin can choose events types to log

= 0.2 =
* Plugin internationalization
* widget enabled

= 0.1 =
* First release
