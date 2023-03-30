<?php

namespace UDeploy\Taxi;

use Symfony\Component\Process\Process;
use Valet\CommandLine as ValetCommandLine;

class CommandLine extends ValetCommandLine
{
    /**
     * The working directory of the process.
     *
     * @var string|null
     */
    public $path;

    /**
     * Specify the working directory of the process.
     *
     * @return $this
     */
    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Run the given command.
     *
     * @param  callable  $onError
     */
    public function runCommand(string $command, callable $onError = null): string
    {
        $onError = $onError ?: function () {
        };

        // Symfony's 4.x Process component has deprecated passing a command string
        // to the constructor, but older versions (which Valet's Composer
        // constraints allow) don't have the fromShellCommandLine method.
        // For more information, see: https://github.com/laravel/valet/pull/761
        if (method_exists(Process::class, 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command);
        }
        $process->setWorkingDirectory((string) ($this->path ?? getcwd()));

        $processOutput = '';
        $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $onError($process->getExitCode(), $processOutput);
        }

        return $processOutput;
    }
}
