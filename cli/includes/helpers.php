<?php

namespace Taxi;

use Illuminate\Container\Container;
use TaxiFileSystem;
use function Valet\testing;

if (! defined('TAXI_HOME_PATH')) {
    if (testing()) {
        define('TAXI_HOME_PATH', __DIR__.'/../../tests/fixtures');
    } else {
        define('TAXI_HOME_PATH', getcwd());
    }
}

if(!function_exists('git_branch')) {
    function git_branch(string $sitePath): string
    {
        if (!TaxiFileSystem::isGitEnabled($sitePath)) {
            return '';
        }

        return implode('/', array_slice(explode('/', TaxiFileSystem::getGitHead($sitePath)), 2));
    }
}

if(!function_exists('make')) {
    function make(string $class, array $parameters = []): mixed
    {
        return Container::getInstance()->make($class, $parameters);
    }
}
