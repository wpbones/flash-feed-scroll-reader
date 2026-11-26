# Feed Scroll Reader
Contributors: gfazioli
Donate link: https://www.paypal.com/donate/?hosted_button_id=TBPD4R78VDMCJ
Tags: rss, feed, ticker, marquee, news
Requires at least: 6.6
Tested up to: 7.2
Stable tag: 2.1.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A horizontal scrolling ticker of the latest items of one or more RSS/Atom feeds. Plain HTML and CSS, no Flash.

## Description

Feed Scroll Reader shows the latest items of one or more feeds in a single line that scrolls sideways, like a news ticker. It was called **Flash Feed Scroll Reader** until version 1.2.0, and it drew the line with an Adobe Flash movie. Browsers dropped Flash in 2021, so that version shows nothing today. Version 2.0.0 is a rewrite: the ticker is now HTML and CSS, with a little JavaScript only to measure it. **Flash is gone.** The name changed with it; the plugin's address on WordPress.org and its folder are still `flash-feed-scroll-reader`.

* **Several feeds in one ticker.** Up to 5 RSS or Atom feeds, merged and sorted by date, newest first, with a limit on the number of items.
* **Titles that are real links.** Optionally a description after each title, cut to a number of characters and ended with the text you choose, `[...]` by default. A separator between the items.
* **Polite.** It stops while the pointer is over it or a link has the keyboard focus, has a pause button for touch screens, and it does not move at all for visitors whose system asks for reduced motion: they get a plain, wrapping list. It is a labelled region, its links are reachable by keyboard, and it never announces itself to screen readers on its own.
* **A block, with a live preview.** Add "Feed Scroll Reader" in the block editor: the ticker is drawn in the editor as visitors will see it, and its feeds, speed and the rest are in the sidebar. Colors, font size, margin and wide or full alignment are the editor's own tools.
* **Looks like your site.** The font is your theme's. Width, height, background color and text color are yours to set; by default the background is transparent and the text inherits its color.
* **Fast.** Feeds are fetched with WordPress's own `fetch_feed()` and kept in its cache for as long as you say. Nothing is downloaded in the visitor's browser.
* **Quiet when something fails.** A feed that cannot be read shows nothing to visitors. Administrators get a short note in an HTML comment in the page source, and the settings page shows the error.

### Four ways to put it on a page

**Block**

In the block editor, add the **Feed Scroll Reader** block (Widgets category, or type `/feed`). The editor shows the ticker itself, and the sidebar has its settings: the feed addresses (one per line, up to 5), the number of items, the speed in pixels per second, the height, the link target, a label for screen readers, and whether to show the descriptions and how long they are. A field you leave empty uses the defaults of Settings > Feed Scroll Reader, shown as a placeholder. Background and text color, font size, margin and wide or full alignment are the block's Styles and Settings, as in any other block.

A `[feed_scroll_reader]` shortcode in a Shortcode block or a paragraph, and the plugin's widget in the block-based Widgets screen, can be turned into the block with the Transform button. Existing shortcodes, widgets and template tags keep working as they are.

**Shortcode**

`[feed_scroll_reader]` uses the defaults of Settings > Feed Scroll Reader. Every setting can be given for one ticker only:

`[feed_scroll_reader feed="https://example.com/feed https://example.org/feed" speed="60" description="yes" description_length="80" height="3em" bgcolor="#112233" color="#ffffff"]`

Attributes: `feed` (one to five addresses, separated by spaces or commas), `max_items`, `speed` (pixels per second), `width`, `height`, `bgcolor`, `color`, `description` (`yes` or `no`), `description_length`, `suffix`, `separator`, `target` (`_blank` or `_self`), `cache` (minutes) and `label` (the name of the ticker for screen readers).

**Widget**

Appearance > Widgets > Feed Scroll Reader: a title, the feeds, the number of items, the speed, the height and whether to show descriptions. An empty field uses the defaults.

**Template tag**

The two functions of the old versions are still there:

`<?php flashfeedscrollreader(); ?>` prints the ticker; `<?php echo get_flashfeedscrollreader( 'width=100%&height=40&feedurl=https://example.com/feed' ); ?>` returns it. They take a query string or an array, with the old argument names (`width`, `height`, `feedurl`, `scrollspeed`, `separator`, `description`, `stringcut`, `stringcutend`, `target`, `bgcolor`, `useaggregator`) or the new ones. `stylesheet`, `wmode` and the other arguments that only made sense for the Flash movie are accepted and ignored.

### External services

A ticker downloads the feeds you give it from the servers that publish them, using WordPress's HTTP API, and keeps a copy for the time you set. Until you change the setting, the feed is the WordPress News feed, `https://wordpress.org/news/feed/` ([privacy policy](https://wordpress.org/about/privacy/)). Nothing else is sent anywhere, and nothing about your visitors is collected.

Documentation and source code: [github.com/wpbones/flash-feed-scroll-reader](https://github.com/wpbones/flash-feed-scroll-reader).

### If you used version 1.x

Your site keeps working, and the ticker is back. On the first request after the update the plugin moves your old settings into the new ones, once; the old `ffsr_settings` option is left where it is until you delete the plugin.

* **Template tag:** `flashfeedscrollreader()` and `get_flashfeedscrollreader()` work as before. The tag's `scrollspeed` was a delay in milliseconds for the Flash movie; it becomes a speed in pixels per second (25 ms is 40 px/s) and is kept between 10 and 400.
* **Settings:** the single feed, or the aggregator's list when you used it, becomes the feed list; width, height, background color, separator, description, its length and its ending, and the link target are carried over. The old "max items per feed" becomes a limit for the whole ticker (per feed times the number of feeds).
* **Old default feeds:** the feeds 1.x shipped as defaults that no longer answer (`saidmade.com/feed`, `appleside.it/feed`) are not carried over: if one was your only feed, the new default feed is used instead.
* **Gone:** the Flash movie, the style sheet address, the window mode and the gateway scripts. A style sheet you wrote for the movie cannot be used: Flash's HTML engine is not CSS. Style the ticker with the classes below, or with your theme.
* **Minimum:** PHP 8.1 and WordPress 6.6.

### Styling

The markup is a `div.ffsr` holding a `ul.ffsr__track` of `li.ffsr__item`, each with an `a.ffsr__link`, an optional `span.ffsr__desc` and a `span.ffsr__sep`. Width, height, background and text color are CSS custom properties on the `div` (`--ffsr-width`, `--ffsr-height`, `--ffsr-bg`, `--ffsr-fg`), so your own CSS can override them.

## Installation

1. Upload the entire content of the plugin archive to your `/wp-content/plugins/` directory, or install it from the Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open Settings > Feed Scroll Reader, add your feeds and see the preview.
4. Put the ticker on a page with the Feed Scroll Reader block, with `[feed_scroll_reader]`, with the widget, or with `flashfeedscrollreader()` in your theme.

## Screenshots

1. Settings > Feed Scroll Reader: the feeds, the look and the behavior every ticker starts from.
2. A ticker on the front end: titles as links, separated by a bullet, in the theme's own font, with the pause button at the end.
3. The same ticker with descriptions on a dark background (background and text color set).
4. The Feed Scroll Reader block in the editor: a live preview of the ticker, and its settings in the sidebar.

## FAQ

= 1. Does it still use Flash? =
No. Version 2.0.0 has no Flash, no SWF and no gateway scripts. Browsers have not run Flash since 2021, so version 1.2.0 shows nothing today.

= 2. Why the new name? =
Because there is no Flash in it any more. The WordPress.org address cannot change, so it is still `flash-feed-scroll-reader`.

= 3. Why does my feed not show? =
Open Settings > Feed Scroll Reader: the preview shows the error WordPress got when it tried to read each feed. The address must be a feed, not the page of the site; it must start with `http://` or `https://`; and your server must be able to reach it. Visitors never see an error. An administrator who is logged in sees the reason in an HTML comment near the place where the ticker should be (view the page source).

= 4. How often is a feed downloaded? =
Every 30 minutes by default, and only when a visitor opens a page that holds a ticker. Change it in the settings or with the `cache` attribute. A ticker reads at most 5 feeds, and a feed that fails is not tried again for 15 minutes, so a dead address does not slow the page down.

= 5. Can it show feeds from other sites? =
Yes, any public RSS or Atom feed. They are read by your server, so there is no cross-domain problem.

= 6. Can I stop the scrolling altogether? =
Visitors who ask their system for reduced motion get a still, wrapping list. Moving the pointer over the ticker, or tabbing to a link, stops it. To always show a still list, add `.ffsr { height: auto; } .ffsr__viewport { overflow: visible; } .ffsr__track { animation: none; padding-left: 0; white-space: normal; flex-wrap: wrap; }` to your CSS.

= 7. Can I change the colors and the font? =
In the block, with its Color and Typography settings. In the settings, or with `bgcolor` and `color` in a shortcode, for the others. The font is your theme's; change it with your own CSS on `.ffsr`. When a block has a color of its own, it wins over the color in the settings.

= 8. The block shows no ticker in the editor, why? =
Its feed could not be read: an administrator sees the reason where the preview would be. Check the address in the sidebar; it must be a feed, not the page of the site, and start with `http://` or `https://`. The same limits as everywhere apply: 5 feeds at most, and a feed that fails is not tried again for 15 minutes.

= 9. Where can I find the documentation and the source? =
On [GitHub](https://github.com/wpbones/flash-feed-scroll-reader).

= 10. Can I request a new feature? =
Yes! Please submit feature requests at [the support forum](https://wordpress.org/support/plugin/flash-feed-scroll-reader/).

= 11. Can I report a bug? =
Yes! Please report bugs at [the support forum](https://wordpress.org/support/plugin/flash-feed-scroll-reader/).

## Changelog

### 2.1.1

* Translations load through WP Bones 3.4.1, which reads the plugin's `Text Domain` and `Domain Path`. The `Domain Path` header now has the leading slash WordPress documents. A string missing from a language pack is still taken from the catalogue bundled with the plugin.

### 2.1.0

* New: the **Feed Scroll Reader block**. The ticker is drawn in the editor as visitors will see it; the sidebar has the feeds, the number of items, the speed, the height, the link target, a label for screen readers and the descriptions. Colors, font size, margin and wide or full alignment are the editor's own tools. A shortcode or the plugin's widget turns into the block with Transform.
* New: the editor's text is translated, in Italian, Spanish, French, German and Brazilian Portuguese.
* Kept: the shortcode, the widget and the template tags work as before.

### 2.0.1

* Fixed: in a layout sized by its content, such as Twenty Twenty-One's footer widget columns, the ticker widened its column to the length of all its items and ran off the page. It now stays as wide as the space it is given.

### 2.0.0

**A rewrite. The plugin is now Feed Scroll Reader; it was Flash Feed Scroll Reader, and Flash is gone.**

* New: the ticker is HTML and CSS, with a small vanilla script that only measures it. No Flash movie, no gateway scripts, no bundled SimplePie: feeds are read with WordPress's own `fetch_feed()`.
* New: up to 5 feeds merged by date, a feed that fails is skipped for 15 minutes, a limit on the items, a cache lifetime you set, a separator, optional descriptions cut to a length.
* New: pauses on hover and on keyboard focus, with a pause button for touch screens; honors "reduced motion" with a still list; labelled region, real links, no live announcements.
* New: width, height, background color and text color; the font is the theme's.
* New: the shortcode `[feed_scroll_reader]` and a classic widget.
* New: Settings > Feed Scroll Reader, with a preview and the errors of the feeds.
* New: nothing is shown to visitors when a feed fails; administrators get a note in the page source.
* New: translations for Italian, Spanish, French, German and Brazilian Portuguese.
* Kept: the template tags `flashfeedscrollreader()` and `get_flashfeedscrollreader()` with their old arguments; `scrollspeed` becomes a speed in pixels per second; `stylesheet`, `wmode` and the other Flash-only arguments are ignored.
* Kept: your settings. The `ffsr_settings` option is moved into the new settings once, on the first request after the update.
* Changed: requires PHP 8.1 and WordPress 6.6 (it was written for WordPress 2.7), tested up to WordPress 7.2.
* Changed: rebuilt on the WP Bones 3.4 framework; the settings are escaped, validated and saved with a nonce and a capability check; uninstalling deletes the plugin's options.
* Removed: the Flash movie, the style sheet and window mode settings, the old admin screen.

### 1.2.0

* Rev all code, User Interface and documentation

### 1.1.0.0

* Remove SWFObject and improve Flash insert

### 1.0.1.3

* Fix SWFObject load and rev to last SWFObject release and fix wp-ffsr_functions.php args

### 1.0.1

* Add screenshot

### 1.0.0

* Improve Adobe Flash Style, Rev folder, Improve Plugin structure with Class, Update SimplePie at last version

### 0.9.6

* Fix bugs on load flash movie

### 0.9.5

* Add "_blank" and "[...]" properties

### 0.9.3

* Fix SWFobject not load in admin

### 0.9.2

* Fix language label

### 0.9.1

* Minor bugs fix on scroll speed

### 0.9.0

* Add multifeed aggregator support

### 0.8.2

* Minor rev

### 0.8.1

* Minor bugs fix on sync video

### 0.8.0

* Improve Flash Scrolling Engine with Undolibrary Scroll engine, add bgcolor params

### 0.7.5

* Improve Flash Scrolling Engine

### 0.7.4

* Fix Bug - Thanks to garethooper (http://wordpress.org/support/profile/661216)

### 0.7.3

* Improve Flash Movie

### 0.7.2

* Add args in flashfeedscrollreader() function

### 0.6.1

* Add Flash background color settings and Window Mode

### 0.52

* Fix some doc bugs

### 0.5

* First release

## Upgrade Notice

### 2.1.0

Adds a block for the block editor, with a live preview. The shortcode, the widget and the template tags are unchanged.

### 2.0.0

A rewrite: the Flash movie is gone and the ticker is HTML and CSS again. Your template tag and your settings carry over. Needs PHP 8.1 and WordPress 6.6.
