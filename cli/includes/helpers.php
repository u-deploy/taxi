<?php

namespace Taxi;

use TaxiFileSystem;
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
    if (! TaxiFileSystem::isGitEnabled($sitePath)) {
        return '';
    }

    return implode('/', array_slice(explode('/', TaxiFileSystem::getGitHead($sitePath)), 2));
}
