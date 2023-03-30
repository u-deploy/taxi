<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
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
}
