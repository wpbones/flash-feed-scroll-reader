=== Flash Feed Scroll Reader ===
Contributors: Giovambattista Fazioli
Donate link: http://www.saidmade.com
Tags: flash, feed rss, feed reader, scroll, swfobject
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 1.2.0

Flash Feed Scroll Reader is a Adobe Flash Feed Reader with horizontal scrolling.

== Description ==

Flash Feed Scroll Reader is a Adobe Flash Feed Reader with horizontal scrolling.
You can set one or more feed to fetch. The scroll rendering is in HTML Adobe Flash, so you can edit style.css file for change color, fonts and rendering.

In this version you can set options and call php function:
`<?php flashfeedscrollreader() ?>`

for show flash scroll feed.

**FEATURES**

* Single Feed Reader
* Multiple/aggregator Feed Reader
* Customize Adobe Flash Scroll Reader
* Simple and Fast
* English/Italian Localizzation

**LAST IMPROVE**

* Localizzation Italian
* Rev User Interface
* `stringcutend` param: String to append when the description is cutted, default `"[...]"`
* `target` param: Window target on click feed title link, default `"_blank"`


**HOW TO**

Usage:
`
* width:          Flash movie width. This value can be in pixel or percent
* height:         Flash movie height. This value can be in pixel or percent
* feedurl:        URL address of feed rss to show
* scrollspeed:    Scroll speed in millisecond
* separator:      HTML text between feed title
* description:    Set to "1" for show description content. "0" for none
* stringcut:      If description is set to "1" this is the number of chars to show
* stringcutend:   String to append when the description is cutted, default "[...]"
* stylesheet:     StyleSheet URL address
* bgcolor:        Background color for Flash Movie in #RRGGBB value
* wmode:          Window Mode for Flash Movie: Window, Opaque, Transparent
* useaggregator:  Set to '1' for use aggregator setting, else '0' default
* target:		  Window target on click feed title link, default "_blank"
`

= Related Links =

* [Author's Company](http://www.saidmade.com/ "Saidmade")
* [Author's Personal Blog](http://www.undolog.com/ "Undolog")

For more information on the roadmap for future improvements please e-mail: g.fazioli@saidmade.com

== Screenshots ==

1. Options with Adobe Flash scroll preview below

== Frequently Asked Questions == 

= Where can I find more information? =

See [labs.saidmade.com](http://labs.saidmade.com/) for more detail

= How I can get the HTML only? =

Use `<?php get_flashfeedscrollreader() ?>` instead `<?php flashfeedscrollreader() ?>`

= Support args? =

Yes, you can set some params from php function, for example:
`<?php get_flashfeedscrollreader("width=100%&height=20") ?>`

Usage:
`
* width:          Flash movie width. This value can be in pixel or percent
* height:         Flash movie height. This value can be in pixel or percent
* feedurl:        URL address of feed rss to show
* scrollspeed:    Scroll speed in millisecond
* separator:      HTML text between feed title
* description:    Set to "1" for show description content. "0" for none
* stringcut:      If description is set to "1" this is the number of chars to show
* stringcutend:   String to append when the description is cutted, default "[...]"
* stylesheet:     StyleSheet URL address
* bgcolor:        Background color for Flash Movie in #RRGGBB value
* wmode:          Window Mode for Flash Movie: Window, Opaque, Transparent
* useaggregator:  Set to '1' for use aggregator setting, else '0' default
* target:		  Window target on click feed title link, default "_blank"
`

= How I can change the style? =

The scroll feed item style is defined in style.css default file.
You can edit style.css or write your own file in any location, for example:

`http://mydomain.com/layout/scroller.css`

Adobe Flash HTML engine is very strict. For example do not use:

`
span.myclass { color:#000 }
`

but use instead

`
.myclass { color:#000000 }
`

== Changelog ==

= 1.2.0 =
* Rev all code, User Interface and documentation

= 1.1.0.0 =
* Remove SWFObject and improve Flash insert

= 1.0.1.3 =
* Fix SWFObject load and rev to last SWFObject release and fix wp-ffsr_functions.php args

= 1.0.1 =
* Add screenshot

= 1.0.0 =
* Improve Adobe Flash Style, Rev folder, Improve Plugin structure with Class, Update SimplePie at last version

= 0.9.6 =
* Fix bugs on load flash movie

= 0.9.5 =
* Add "_blank" and "[...]" properties

= 0.9.3 =
* Fix SWFobject not load in admin

= 0.9.2 =
* Fix language label

= 0.9.1 =
* Minor bugs fix on scroll speed

= 0.9.0 =
* Add multifeed aggregator support

= 0.8.2	=
* Minor rev

= 0.8.1 =
* Minor bugs fix on sync video

= 0.8.0 =
* Improve Flash Scrolling Engine with Undolibrary Scroll engine, add bgcolor params

= 0.7.5 =
* Improve Flash Scrolling Engine

= 0.7.4 =
* Fix Bug - Thanks to garethooper (http://wordpress.org/support/profile/661216)

= 0.7.3 =
* Improve Flash Movie

= 0.7.2 =
* Add args in flashfeedscrollreader() function

= 0.6.1 =
* Add Flash background color settings and Window Mode

= 0.52 =
* Fix some doc bugs

= 0.5 =
* First release

== Upgrade Notice ==

= 1.2 =
* Rev. Upgrade immediately

== Installation ==

1. Upload the entire content of plugin archive to your `/wp-content/plugins/` directory,
   so that everything will remain in a `/wp-content/plugins/flash-feed-scroll-reader/` folder
2. Open the plugin configuration page, which is located under `Options -> Flash Feed Scroll Reader`
3. Activate the plugin through the 'Plugins' menu in WordPress (deactivate and reactivate if you're upgrading).
4. Insert `<?php flashfeedscrollreader() ?>` in your template file: `index.php`, `single.php` or `sidebar.php`
5. Done. Enjoy.


== Thanks ==

* [SimplePie](http://simplepie.org/downloads/ "SimplePie")
* Flash Feed Scroll Reader Wordpress Plugin use SWFObject (from Google Ajax API) and SimplePie
* Chris for beta testing
* [godai](http://wordpress.org/support/profile/247145) for some usefull suggest