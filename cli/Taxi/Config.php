<?php

namespace UDeploy\Taxi;

use Illuminate\Config\Repository;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class Config extends Repository
{
    /**
     * Load the configuration items from all of the files.
     */
    public function loadConfigurationFiles(string $path, ?string $environment = null)
    {
        $this->configPath = $path;

        foreach ($this->getConfigurationFiles() as $fileKey => $path) {
            $this->set($fileKey, require $path);
        }

        if (Str::endsWith($path, '/bootstrap/cache/config.php')) {
            $this->items = require $path;

            return;
        }

        foreach ($this->getConfigurationFiles($environment) as $fileKey => $path) {
            $envConfig = require $path;

            foreach ($envConfig as $envKey => $value) {
                $this->set($fileKey.'.'.$envKey, $value);
            }
        }
    }

    /**
     * Get the configuration files for the selected environment
     */
    protected function getConfigurationFiles(?string $environment = null): array
    {
        $path = $this->configPath;

        if ($environment) {
            $path .= '/'.$environment;
        }

        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $phpFiles = Finder::create()->files()->name('*.php')->in($path)->depth(0);

        foreach ($phpFiles as $file) {
            $files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }
}
