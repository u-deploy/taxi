<?php

namespace UDeploy\Taxi;

use GuzzleHttp\Client;
use function Valet\warning;

class Taxi
{
    public $taxiBin = BREW_PREFIX . '/bin/taxi';

    public function __construct(public CommandLine $cli, public Filesystem $files, public Client $client)
    {
        //
    }

    /**
     * Symlink the Valet Bash script into the user's local bin.
     */
    public function symlinkToUsersBin(): void
    {
        $this->unlinkFromUsersBin();

        $this->cli->runAsUser('ln -s "' . realpath(__DIR__ . '/../../taxi') . '" ' . $this->taxiBin);
    }

    /**
     * Remove the symlink from the user's local bin.
     */
    public function unlinkFromUsersBin(): void
    {
        $this->cli->quietlyAsUser('rm ' . $this->taxiBin);
    }

    /**
     * Create the "sudoers.d" entry for running Taxi.
     */
    public function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/taxi', 'Cmnd_Alias TAXI = ' . BREW_PREFIX . '/bin/taxi *
        %admin ALL=(root) NOPASSWD:SETENV: TAXI' . PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Taxi.
     */
    public function removeSudoersEntry()
    {
        $this->cli->quietly('rm /etc/sudoers.d/taxi');
    }

    public function call(?string $url = null): ?bool
    {
        if (! is_null($url) && filter_var($url, FILTER_VALIDATE_URL) === false) {
            return warning('Invalid url');
        }

        $contents = $this->getCallContents($url);

        $this->files->putAsUser(
            getcwd().'/taxi.json',
            $contents
        );

        return true;
    }

    protected function getCallContents(?string $url): string
    {
        if (is_null($url)) {
            return $this->files->getTaxiStub('taxi.json');
        }

        return (string) $this->client->get($url)->getBody();
    }

    public function build()
    {
        //
    }

    public function reset()
    {
        //
    }
}
