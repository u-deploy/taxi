<?php

use UDeploy\Taxi\CommandLine;

class CommandLineTest extends BaseApplicationTestCase
{
    public function test_path_can_be_set()
    {
        $cli = new CommandLine();

        $cli->path('user/');

        $this->assertEquals('user/', $cli->path);
    }
}
