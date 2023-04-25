<?php

namespace UDeploy\Taxi;

class Database
{
    public function __construct(
        public CommandLine $cli,
        public Config $config
    ) {
        //
    }

    public function createDatabaseCommand(
        string $engine,
        string $database,
        string $user,
        string $password
    ): string {

    }
}
