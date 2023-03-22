<?php

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

        $tester->run(['command' => 'call']);

        $tester->assertCommandIsSuccessful();
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
