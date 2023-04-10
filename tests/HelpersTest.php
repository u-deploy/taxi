<?php

use function Taxi\git_branch;
use UDeploy\Taxi\Filesystem;
use function Valet\swap;

class HelpersTest extends BaseApplicationTestCase
{
    /**
     * @dataProvider branches
     */
    public function test_git_branch_returns_branch_name($branch)
    {
        [$app, $tester] = $this->appAndTester();

        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('isGitEnabled')->andReturnTrue();
        $filesystem->shouldReceive('getGitHead')->with(__DIR__.'/fixtures/Scratch')->andReturn('ref: refs/heads/'.$branch);

        swap(Filesystem::class, $filesystem);

        $response = git_branch(__DIR__.'/fixtures/Scratch');

        $this->assertEquals($branch, $response);
    }

    public function branches()
    {
        return [
            ['main'],
            ['master'],
            ['production'],
            ['staging'],
            ['zonda'],
        ];
    }
}
