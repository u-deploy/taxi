<?php

namespace UDeploy\Taxi;

use Dotenv\Dotenv;
use TaxiFileSystem;

class SiteConfig
{
    protected array $paths = [];

    public Config $config;

    public function __construct(protected string $path)
    {
        $this->setupPaths();
        $this->config = new Config();
        $this->config->loadConfigurationFiles(
            $this->paths['config_path'],
            $this->getEnvironment()
        );
    }

    /**
     * Detect the environment. Defaults to production.
     *
     * @return string
     */
    private function getEnvironment()
    {
        if (TaxiFileSystem::isFile($this->paths['env_file'])) {
            $dotenv = Dotenv::createImmutable($this->paths['env_file_path']);
            $dotenv->load();
        }

        return getenv('ENVIRONMENT') ?: 'production';
    }

    /**
     * Initialize the paths.
     */
    private function setupPaths()
    {
        $this->paths['env_file_path'] = $this->path;
        $this->paths['env_file'] = $this->paths['env_file_path'].'/.env';

        if ($this->hasCachedConfig()) {
            $this->paths['config_path'] = $this->path.'/bootstrap/cache/config.php';

            return;
        }
        $this->paths['config_path'] = $this->path.'/config';
    }

    private function hasCachedConfig(): bool
    {
        return TaxiFileSystem::isDir($this->path.'/bootstrap/cache') &&
            TaxiFileSystem::exists($this->path.'/bootstrap/cache/config.php');
    }

    public function __call($method, $params)
    {
        if (method_exists($this->config, $method)) {
            return $this->config->$method(...$params);
        }

        throw new \Exception('Method: '.$method.' not found');
    }
}
