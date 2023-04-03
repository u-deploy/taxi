<?php

namespace UDeploy\Taxi\Exceptions;

use Exception;

class ConfigurationMissing extends Exception
{
    protected $message = 'No taxi.json file found';
}
