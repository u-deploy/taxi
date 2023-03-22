<?php

namespace Taxi;

use function Valet\testing;

if (! defined('TAXI_HOME_PATH')) {
    if (testing()) {
        define('TAXI_HOME_PATH', __DIR__.'/../../tests/config/taxi');
    } else {
        define('TAXI_HOME_PATH', $_SERVER['HOME'].'/.config/taxi');
    }
}

function git_branch(string $sitePath): string
{
    if (! file_exists($sitePath.'/.git/HEAD')) {
        return '';
    }

    return implode('/', array_slice(explode('/', file_get_contents($sitePath.'/.git/HEAD')), 2));
}
