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

    public function call(?string $url = null)
    {
        if(!is_null($url) && filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            warning('Invalid url');
            return;
        }

        $contents = $this->getCallContents($url);

        $this->files->putAsUser(
            getcwd().'/taxi.json',
            $contents
        );
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
