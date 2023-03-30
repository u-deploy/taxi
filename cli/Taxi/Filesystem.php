<?php

namespace UDeploy\Taxi;

use Valet\Filesystem as ValetFilesystem;

class Filesystem extends ValetFilesystem
{
    /**
     * Get custom stub file if exists.
     */
    public function getTaxiStub(string $filename): string
    {
        $default = __DIR__.'/../stubs/'.$filename;
        $custom = TAXI_HOME_PATH.'/stubs/'.$filename;

        $path = file_exists($custom) ? $custom : $default;

        return $this->get($path);
    }
}
