<?php

use UDeploy\Taxi\Brew;
use UDeploy\Taxi\CommandLine;
use UDeploy\Taxi\Site;
use function Valet\swap;

class SiteTest extends BaseApplicationTestCase
{
    public function test_get_property_returns_null()
    {
        $site = new Site(
            __DIR__.'/fixtures/Scratch',
            [
                'name' => 'test',
            ]
        );

        $this->assertNull($site->get('random'));
    }

    public function test_site_can_read_config()
    {
        $site = new Site(
            __DIR__.'/fixtures/Parked/Sites/Config/config-site',
            [
                'name' => 'laravel-config',
            ]
        );

        $this->assertEquals(
            'taxt-config-test',
            $site->config()->get('app.name', 'default')
        );

        $this->assertEquals(
            'testing',
            $site->config()->get('app.version', 'default')
        );

    }

    public function test_site_can_read_cached_config()
    {
        $site = new Site(
            __DIR__.'/fixtures/Parked/Sites/Config/cached-config',
            [
                'name' => 'laravel-config',
            ]
        );

        $this->assertEquals(
            'Cached Name',
            $site->config()->get('app.name', 'default')
        );
    }

    public function test_site_can_setup_database()
    {
        $brew = Mockery::mock(Brew::class);
        $brew->shouldReceive('installed')->with('mariadb')->andReturnTrue();
        $brew->shouldReceive('installed')->with('mysql')->andReturnFalse();

        $cli = Mockery::mock(CommandLine::class);

        collect([
            'mysql -e "CREATE DATABASE IF NOT EXISTS forge;"',
            'mysql -e "GRANT ALL PRIVILEGES ON forge.* TO forge@\'127.0.0.1\' IDENTIFIED BY \'secret\'"',
        ])->each(fn ($command) => $cli->shouldReceive('runAsUser')
            ->ordered()
            ->with($command)
            ->once()
        );

        swap(Brew::class, $brew);
        swap(CommandLine::class, $cli);

        $site = new Site(
            __DIR__.'/fixtures/Parked/Sites/Config/cached-config',
            [
                'name' => 'laravel-config',
                'database' => [
                    'mysql',
                ],
            ]
        );

        $site->setupDatabase();
    }
}
