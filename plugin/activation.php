<?php

if (!defined('ABSPATH')) {
    exit();
}

/*
|--------------------------------------------------------------------------
| Plugin activation
|--------------------------------------------------------------------------
|
| This file is included when the plugin is activated the first time.
| Usually you will use this file to register your custom post types or
| to perform some db delta process.
|
*/

// 1.x kept its settings in `ffsr_settings`: move them into this version's defaults, once.
\NSFeedScrollReaderPlugin\Migration\LegacySettings::run();
