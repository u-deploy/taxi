<?php

namespace UDeploy\Taxi;

use GuzzleHttp\Client;
use function Valet\warning;

class Taxi
{
    public function __construct(public CommandLine $cli, public Filesystem $files, public Client $client)
    {
        //
    }

    public function install()
    {
        //
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
