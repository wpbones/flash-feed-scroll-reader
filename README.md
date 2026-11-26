# Feed Scroll Reader

A horizontal scrolling ticker of the latest items of one or more RSS/Atom feeds. Plain HTML and CSS, no Flash.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![WordPress Plugin Tested WP Version](https://img.shields.io/wordpress/plugin/tested/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Try it in WordPress Playground](https://img.shields.io/badge/Try%20it-WordPress%20Playground-3858e9?logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/flash-feed-scroll-reader/main/assets/blueprints/blueprint.json)

<div align="center">
  <p><img src="https://raw.githubusercontent.com/wpbones/.github/main/glancecard/flash-feed-scroll-reader.svg" width="880" alt="Feed Scroll Reader at a glance, refreshed daily: commits, releases, stars, issues and pull requests, a year of commit activity, the weekly rhythm, languages, latest commits and releases, contributors" /><br>
  <sub>Made with <a href="https://github.com/gfazioli/glancecard">glancecard</a> · refreshed daily</sub></p>
</div>

## Overview

Feed Scroll Reader shows the latest items of one or more feeds in one line that scrolls sideways, like a news ticker. It was **Flash Feed Scroll Reader** until 1.2.0 and drew the line with an Adobe Flash movie; 2.0.0 is a rewrite in HTML and CSS, and Flash is gone. The WordPress.org address and the folder are still `flash-feed-scroll-reader`.

Built on the [WPBones](https://wpbones.com) framework.

## Links

- [WordPress.org Plugin Page](https://wordpress.org/plugins/flash-feed-scroll-reader/)
- [Support Forum](https://wordpress.org/support/plugin/flash-feed-scroll-reader/)
- [Changelog](readme.txt) (the `## Changelog` section)

## Features

- Up to 5 feeds in one ticker, merged and sorted by date, with a limit on the items; a feed that fails is skipped for 15 minutes.
- Titles are real links; optional descriptions cut to a length and ended with a text of your choice; a separator.
- Pauses on hover and on keyboard focus, with a pause button for touch screens; a still, wrapping list for visitors who ask for reduced motion.
- A labelled region, keyboard reachable, no live announcements.
- The theme's font; width, height, background and text color.
- Feeds read with core `fetch_feed()` and cached for a lifetime you set.
- A failing feed shows nothing to visitors; administrators get a note in the page source and on the settings page.
- Shortcode, classic widget, and the 1.x template tags (`flashfeedscrollreader()`, `get_flashfeedscrollreader()`).
- Your 1.x settings (`ffsr_settings`) are moved into the new ones once; the 1.x default feeds that no longer exist (`saidmade.com/feed`, `appleside.it/feed`) are replaced by the new default.
- Translated into Italian, Spanish, French, German and Brazilian Portuguese.

### Usage

```
[feed_scroll_reader]
[feed_scroll_reader feed="https://example.com/feed https://example.org/feed" speed="60" description="yes" height="3em"]
```

```php
<?php flashfeedscrollreader(); ?>
<?php echo get_flashfeedscrollreader( 'width=100%&height=40&feedurl=https://example.com/feed' ); ?>
```

Attributes: `feed`, `max_items`, `speed` (px/s), `width`, `height`, `bgcolor`, `color`, `description`, `description_length`, `suffix`, `separator`, `target`, `cache` (minutes), `label`. Defaults: Settings > Feed Scroll Reader. The 1.x names (`feedurl`, `scrollspeed`, `stringcut`, `stringcutend`, `useaggregator`) still work; `stylesheet`, `wmode` and the other Flash-only ones are ignored.

## Requirements

- PHP 8.1 or later
- WordPress 6.6 or later (tested up to 7.2)

## Installation

1. Upload the entire content of the plugin archive to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open Settings > Feed Scroll Reader, add your feeds, and put `[feed_scroll_reader]` on a page.

## Development

No build step: the front end is a hand-written stylesheet and script in `public/`, so there is no `yarn install`.

```sh
composer install
composer test        # PHPUnit + Brain Monkey, no WordPress needed
tests/live/probe.sh <plugin folder> <WordPress folder>   # live probe in a local Playground (see the script header)
composer make-pot    # languages/flash-feed-scroll-reader.pot (WP-CLI)
php bones            # the WP Bones console
php bones deploy ../deploy/flash-feed-scroll-reader --wp --no-build
```

## License

GPLv2 or later. See [LICENSE](LICENSE).
