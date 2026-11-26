<?php

if (!defined('ABSPATH')) {
    exit();
}

/*
|--------------------------------------------------------------------------
| Plugin updated
|--------------------------------------------------------------------------
|
| This file is included when the plugin is updated.
|
*/

// 1.x kept its settings in `ffsr_settings`: move them into this version's defaults, once.
\NSFeedScrollReaderPlugin\Migration\LegacySettings::run();
