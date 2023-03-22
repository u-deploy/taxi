<?php

namespace UDeploy\Taxi;

use Valet\CommandLine;
use Valet\Filesystem;

class Taxi
{
    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
        //
    }

    public function install()
    {
        //
    }

    public function call(?string $url)
    {
        //
    }

    public function build()
    {
        //
    }

    public function reset()
    {
        //
    }
}
