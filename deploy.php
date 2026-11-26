<?php

/**
 * This file is included by the "bones deploy" command.
 *
 * PHPUnit and Brain Monkey (require-dev) are left out by bones itself: it runs
 * `composer install --no-dev` in the deployed copy and never touches this working tree. The tests
 * (`/tests`) and `phpunit.xml.dist` are left out by the deploy's default list.
 */

/**
 * Filter the list of the folder to skip for the deploy version.
 *
 * `/assets` holds the WordPress.org Live Preview blueprint (and, one day, the banner, icon and
 * screenshots): WordPress.org takes them from the SVN `assets` folder, not from the plugin.
 * bones matches a skip entry against the exact path relative to the plugin root.
 *
 * @param array $folders List of folders to skip
 * @return array List of folders to skip
 */
add_filter('wpbones_console_deploy_skip_folders', function ($folders) {
  return array_merge($folders, ['/assets', '/README.md', '/phpunit.xml.dist']);
});
