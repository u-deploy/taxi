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

        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('getTaxiStub')->once()->with('taxi.json')->andReturn($contents);
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn(realpath(TAXI_HOME_PATH.'/Scratch'));
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

        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn(realpath(TAXI_HOME_PATH.'/Scratch'));
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

    public function test_build_command_shows_warning_without_taxi_configuration()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'build']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No taxi.json file found', $tester->getDisplay());
    }

    public function test_build_command_shows_warning_with_empty_taxi_configuration()
    {
        [$app, $tester] = $this->appAndTester();

        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn(realpath(TAXI_HOME_PATH.'/Bad/empty'));

        swap(Filesystem::class, $files);

        $tester->run(['command' => 'build']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('The data (null) must match the type: object', $tester->getDisplay());
    }

    public function test_build_command_shows_warning_with_missing_required_keys_taxi_configuration()
    {
        [$app, $tester] = $this->appAndTester();

        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn(realpath(TAXI_HOME_PATH.'/Bad/missing-required'));

        swap(Filesystem::class, $files);

        $tester->run(['command' => 'build']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('The required properties (hooks) are missing', $tester->getDisplay());
    }

    public function test_build_command_runs_commands_in_order_from_configuration()
    {
        [$app, $tester] = $this->appAndTester();
        $testDirectory = realpath(TAXI_HOME_PATH.'/Parked/Sites/Single/single-taxi-site');
        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('isGitEnabled')->andReturnTrue();
        $files->shouldReceive('getGitHead')->andReturn('ref: refs/heads/main');
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn($testDirectory);

        $cli = Mockery::mock(CommandLine::class);

        collect([
            'git clone https://github.com/laravel/laravel laravel-single',
            'valet link laravel-single',
            'valet isolate php@8.1',
            'valet secure',
            'npm install',
            'npm run production',
            'composer install',
            'cp .env.example .env',
            'php artisan key:generate',
        ])->each(fn ($command) => $cli->shouldReceive('path->runAsUser')
            ->ordered()
            ->with($command)
            ->once()
        );

        swap(Filesystem::class, $files);
        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'build']);

        $tester->assertCommandIsSuccessful();

        $this->assertEquals('Cloning repository: laravel-single
  Isolating PHP version for site
  Securing valet site
  Running build commands
  Running post-build commands
laravel-single build completed
Taxi build successful!
', $tester->getDisplay());
    }

    public function test_reset_command_shows_warning_without_taxi_configuration()
    {
        [$app, $tester] = $this->appAndTester();

        $tester->run(['command' => 'reset']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No taxi.json file found', $tester->getDisplay());
    }

    public function test_reset_command_runs_expected_commands_for_taxi_configuration()
    {
        [$app, $tester] = $this->appAndTester();
        $testDirectory = realpath(TAXI_HOME_PATH.'/Parked/Sites/Single/single-taxi-site');
        $files = Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('cwd')->zeroOrMoreTimes()->andReturn($testDirectory);
        $files->shouldReceive('isGitEnabled')->andReturnTrue();
        $files->shouldReceive('getGitHead')->andReturn('ref: refs/heads/main');

        $cli = Mockery::mock(CommandLine::class);

        collect([
            'git stash && git checkout main',
            'rm -rf vendor && rm composer.lock',
            'composer install',
            'npm run production',
            'php artisan key:generate',
        ])->each(fn ($command) => $cli->shouldReceive('path->runAsUser')
            ->ordered()
            ->with($command)
            ->once()
        );

        swap(Filesystem::class, $files);
        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'reset']);

        $tester->assertCommandIsSuccessful();

        $this->assertEquals('Resetting repository: laravel-single
 Branch changed
 Running reset commands
  Running post-reset commands
Site: laravel-single reset
Taxi reset successful!
', $tester->getDisplay());
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

    public function test_uninstall_command()
    {
        [$app, $tester] = $this->appAndTester();

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('quietly')->once()->with('rm /etc/sudoers.d/taxi');
        $cli->shouldReceive('quietlyAsUser')->once()->with('rm '.BREW_PREFIX.'/bin/taxi');

        swap(CommandLine::class, $cli);

        $tester->run(['command' => 'uninstall']);

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
