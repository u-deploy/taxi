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
        return $this->get($this->getStubPath($filename));
    }

    public function getStubPath(string $filename): string
    {
        $default = __DIR__.'/../stubs/'.$filename;
        $custom = TAXI_HOME_PATH.'/stubs/'.$filename;

        return file_exists($custom) ? realpath($custom) : realpath($default);
    }

    public function cwd(): false|string
    {
        return getcwd();
    }

    public function getGitHead($sitePath): string
    {
        return $this->get($sitePath.'/.git/HEAD');
    }

    public function isGitEnabled($sitePath): bool
    {
        return $this->exists($sitePath.'/.git/HEAD');
    }
}
