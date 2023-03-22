<?php

namespace Taxi;

use Valet\CommandLine;
use Valet\Filesystem;

class Taxi
{
    public function __construct(public CommandLine $cli, public Filesystem $files)
    {
        //
    }
}