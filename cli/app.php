<?php

use Illuminate\Container\Container;
use Silly\Application;
use Silly\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Valet\Drivers\ValetDriver;
use function Valet\info;
use function Valet\output;
use function Valet\table;
use function Valet\warning;
use function Valet\writer;

$version = '0.0.0';

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
} elseif (file_exists(__DIR__.'/../../../autoload.php')) {
    require_once __DIR__.'/../../../autoload.php';
} else {
    require_once getenv('HOME').'/.composer/vendor/autoload.php';
}

/**
 * Create the application.
 */
Container::setInstance(new Container);

$version = '0.0.0';

$app = new Application('UDeploy Taxi', $version);

$app->setDispatcher($dispatcher = new EventDispatcher());

$dispatcher->addListener(
    ConsoleEvents::COMMAND,
    function (ConsoleCommandEvent $event) {
        writer($event->getOutput());
    });

Upgrader::onEveryRun();

/**
 * Install Taxi and any required services.
 */
$app->command('install', function (OutputInterface $output) {
    //TODO
    output(PHP_EOL.'<info>Taxi installed successfully!</info>');
})->descriptions('Install Taxi');

/**
 * Call Taxi configuration and save to current directory
 */
$app->command('call [url]', function (InputInterface $input, OutputInterface $output, $url = null) {
    //TODO
    output(PHP_EOL.'<info>Taxi called successfully!</info>');
})->descriptions('Call Taxi configuration');

/**
 * Build Taxi configuration
 */
$app->command('build', function (OutputInterface $output) {
    //TODO
    output(PHP_EOL.'<info>Taxi build successful!</info>');
})->descriptions('Build Taxi configuration');

/**
 * Reset Taxi configuration
 */
$app->command('reset', function (OutputInterface $output) {
    //TODO
    output(PHP_EOL.'<info>Taxi reset successful!</info>');
})->descriptions('Reset Taxi configuration');