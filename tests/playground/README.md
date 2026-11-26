# Playground checks

Blueprints that set up a WordPress Playground for a check by hand, installing the plugin from WordPress.org (so they test the released version):

- [`classic-theme-widget.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/flash-feed-scroll-reader/main/tests/playground/classic-theme-widget.json): Twenty Twenty-One (a classic theme) on the block-based Widgets screen. Add the plugin's widget from the block inserter, save, and look at the footer.
- [`block-theme-template.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/flash-feed-scroll-reader/main/tests/playground/block-theme-template.json): Twenty Twenty-Five (a block theme) with the plugin's block at the top of the footer template part, saved the way the Site Editor saves it.
- [`block-theme-row-stack.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/flash-feed-scroll-reader/main/tests/playground/block-theme-row-stack.json): Twenty Twenty-Five with a page that holds the block in a Row (a flex group that does not wrap), in a Stack (a flex column with its items to the left) and in a constrained Group. The ticker must be as wide as its parent in all three (from 2.1.1; 2.1.0 showed it 0 px wide in the first two). Open the page in the editor too.

The demo WordPress.org shows as its Live Preview is [`assets/blueprints/blueprint.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/flash-feed-scroll-reader/main/assets/blueprints/blueprint.json).
