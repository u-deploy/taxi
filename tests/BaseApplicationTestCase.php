<?php

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class BaseApplicationTestCase extends TestCase
{
    use UsesNullWriter;

    public function setUp(): void
    {
        $this->prepTestConfig();
        $this->setNullWriter();
    }

    public function tearDown(): void
    {
        // clear test valet config
        Site::links()->each(fn ($site) => Site::unlink($site['site']));

        Mockery::close();
    }

    /**
     * Prepare a test to run using the full application.
     */
    public function prepTestConfig(): void
    {
        require_once __DIR__.'/../cli/includes/helpers.php';
        Container::setInstance(new Container); // Reset app container from previous tests

        if (Filesystem::isDir(TAXI_HOME_PATH)) {  // uses Valet facade instance of Filesystem
            Filesystem::rmDirAndContents(TAXI_HOME_PATH);
        }

        Configuration::createConfigurationDirectory();
        Configuration::createDriversDirectory();
        Configuration::createLogDirectory();
        Configuration::createCertificatesDirectory();
        Configuration::writeBaseConfiguration();
    }

    /**
     * Return an array with two items: the application instance and the ApplicationTester.
     */
    public function appAndTester(): array
    {
        $app = require __DIR__.'/../cli/app.php';
        $app->setAutoExit(false);
        $tester = new ApplicationTester($app);

        return [$app, $tester];
    }
}
