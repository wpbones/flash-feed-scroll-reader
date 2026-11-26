# Feed Scroll Reader

A horizontal scrolling ticker of the latest items of one or more RSS/Atom feeds. Plain HTML and CSS, no Flash.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![WordPress Plugin Tested WP Version](https://img.shields.io/wordpress/plugin/tested/flash-feed-scroll-reader)](https://wordpress.org/plugins/flash-feed-scroll-reader/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> Formerly **Flash Feed Scroll Reader**. Version 2.0.0 is a rewrite: the Adobe Flash movie is gone and the ticker is HTML and CSS. The WordPress.org address and the folder are still `flash-feed-scroll-reader`.

## Features

- Several feeds in one ticker, merged and sorted by date, with a limit on the items.
- Titles are real links; optional descriptions cut to a length and ended with a text of your choice; a separator.
- Pauses on hover and on keyboard focus; a still, wrapping list for visitors who ask for reduced motion.
- A labelled region, keyboard reachable, no live announcements.
- The theme's font; width, height, background and text color.
- Feeds read with core `fetch_feed()` and cached for a lifetime you set.
- A failing feed shows nothing to visitors; administrators get a note in the page source and on the settings page.
- Shortcode, classic widget, and the 1.x template tags.
- Translated into Italian, Spanish, French, German and Brazilian Portuguese.

## Usage

```
[feed_scroll_reader]
[feed_scroll_reader feed="https://example.com/feed https://example.org/feed" speed="60" description="yes" height="3em"]
```

```php
<?php flashfeedscrollreader(); ?>
<?php echo get_flashfeedscrollreader( 'width=100%&height=40&feedurl=https://example.com/feed' ); ?>
```

Attributes: `feed`, `max_items`, `speed` (px/s), `width`, `height`, `bgcolor`, `color`, `description`, `description_length`, `suffix`, `separator`, `target`, `cache` (minutes), `label`. The defaults live in Settings > Feed Scroll Reader. The 1.x argument names (`feedurl`, `scrollspeed`, `stringcut`, `stringcutend`, `useaggregator`) still work; `stylesheet`, `wmode` and the other Flash-only ones are ignored.

## Coming from 1.x

The template tags work as before and the old `ffsr_settings` option is moved into the new settings once, on the first request after the update. `scrollspeed` (milliseconds per step for Flash) becomes a speed in pixels per second. A style sheet written for the Flash movie cannot be reused. See [`readme.txt`](readme.txt) for the details.

## Requirements

PHP 8.1, WordPress 6.6, tested up to 7.2.

## Development

Built on the [WP Bones](https://wpbones.com) framework 3.4. No build step: the front end is a hand-written stylesheet and script in `public/`.

```sh
composer install
composer test        # PHPUnit + Brain Monkey, no WordPress needed
composer make-pot    # languages/flash-feed-scroll-reader.pot (WP-CLI)
php bones deploy ../deploy/flash-feed-scroll-reader --wp --no-build
```

## License

GPL-2.0-or-later.
