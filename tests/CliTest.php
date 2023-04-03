<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use UDeploy\Taxi\CommandLine;
use UDeploy\Taxi\Filesystem;
use function Valet\swap;

/**
 * @requires PHP >= 8.0
 */
class CliTest extends BaseApplicationTestCase
{
    public function test_install_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietlyAsUser')->once();
        $cli->shouldReceive('runAsUser')->once();

        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'install']);

        $tester->assertCommandIsSuccessful();
    }

    public function test_call_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $contents = json_encode(['foo' => 'bar']);

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('getTaxiStub')->once()->with('taxi.json')->andReturn($contents);
        $files->shouldReceive('putAsUser')->once()->withSomeOfArgs($contents);

        swap(Filesystem::class, $files);

        $tester->run(['command' => 'call']);

        $tester->assertCommandIsSuccessful();
    }

    public function test_call_command_is_successful_with_url()
    {
        [$app, $tester] = $this->appAndTester();

        $url = 'https://www.youtube.com/watch?v=eBGIQ7ZuuiU';
        $contents = json_encode(['foo' => 'bar']);

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('get')
            ->with($url)
            ->once()
            ->andReturn(new Response(200, [], $contents));

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('putAsUser')->once()->withSomeOfArgs($contents);

        swap(Client::class, $guzzle);
        swap(Filesystem::class, $files);

        $tester->run(['command' => 'call', 'url' => $url]);

        $tester->assertCommandIsSuccessful();
    }

    public function test_call_command_displays_message_on_bad_url()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'call', 'url' => 'not-a-url']);

        $tester->assertCommandIsSuccessful();
        $this->assertIsString('Invalid url', $tester->getDisplay());
    }

    public function test_build_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'build']);

        $tester->assertCommandIsSuccessful();
    }

    public function test_reset_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'reset']);

        $tester->assertCommandIsSuccessful();
    }

    public function test_trust_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->once()->with('/etc/sudoers.d')->andReturnTrue();
        $files->shouldReceive('put')->once()->with('/etc/sudoers.d/taxi', 'Cmnd_Alias TAXI = '.BREW_PREFIX.'/bin/taxi *
        %admin ALL=(root) NOPASSWD:SETENV: TAXI'.PHP_EOL);

        swap(Filesystem::class, $files);

        $tester->run(['command' => 'trust']);

        $tester->assertCommandIsSuccessful();
    }

    public function test_trust_off_command_is_successful()
    {
        [$app, $tester] = $this->appAndTester();

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('rm /etc/sudoers.d/taxi');

        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'trust', '--off' => true]);

        $tester->assertCommandIsSuccessful();
    }

    public function test_valet_command()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/taxi-test-site', 'taxi');
        $tester->run(['command' => 'valet']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('http://taxi.test', $tester->getDisplay());
        $this->assertStringContainsString('fixtures/Parked/Sites/taxi.json |'.PHP_EOL, $tester->getDisplay());
    }

    public function test_valet_command_with_taxi_file_in_site()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/Single/single-taxi-site', 'taxi-local');
        $tester->run(['command' => 'valet']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('http://taxi-local.test', $tester->getDisplay());
        $this->assertStringContainsString('fixtures/Parked/Sites/Single/single-taxi-site/taxi.json |'.PHP_EOL, $tester->getDisplay());
    }

    public function test_valet_command_non_taxi()
    {
        [$app, $tester] = $this->appAndTester();

        Site::link(__DIR__.'/fixtures/Parked/Sites/Link/standard-valet-site', 'valet');

        $tester->run(['command' => 'valet']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString('http://valet.test', $tester->getDisplay());
        $this->assertStringContainsString('|      |'.PHP_EOL, $tester->getDisplay());
    }
}
