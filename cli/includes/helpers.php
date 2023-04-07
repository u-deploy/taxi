<?php

namespace Taxi;

use Filesystem;
use function Valet\testing;

if (! defined('TAXI_HOME_PATH')) {
    if (testing()) {
        define('TAXI_HOME_PATH', __DIR__.'/../../tests/fixtures');
    } else {
        define('TAXI_HOME_PATH', getcwd());
    }
}

function git_branch(string $sitePath): string
{
    if (! Filesystem::exists($sitePath.'/.git/HEAD')) {
        return '';
    }

    return implode('/', array_slice(explode('/', Filesystem::get($sitePath.'/.git/HEAD')), 2));
}
