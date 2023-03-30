<?php

use Illuminate\Container\Container;
use Silly\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use function Valet\info;
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
    Taxi::symlinkToUsersBin();
    info('<info>Taxi installed successfully!</info>');
})->descriptions('Install Taxi');

/**
 * Call Taxi configuration and save to current directory
 */
$app->command('call [url]', function (InputInterface $input, OutputInterface $output, $url = null) {
    if (Taxi::call($url)) {
        info('<info>Taxi called successfully!</info>');
    }
})->descriptions('Call Taxi configuration');

/**
 * Build Taxi configuration
 */
$app->command('build', function (OutputInterface $output) {
    Taxi::build();
    info('<info>Taxi build successful!</info>');
})->descriptions('Build Taxi configuration');

/**
 * Reset Taxi configuration
 */
$app->command('reset', function (OutputInterface $output) {
    Taxi::reset();
    info('<info>Taxi reset successful!</info>');
})->descriptions('Reset Taxi configuration');

/**
 * Install the sudoers.d entries so password is no longer required.
 */
$app->command('trust [--off]', function (OutputInterface $output, $off) {
    if ($off) {
        Taxi::removeSudoersEntry();

        return info('Sudoers entries have been removed for Taxi.');
    }

    Taxi::createSudoersEntry();

    info('Sudoers entries have been added for Taxi.');
})->descriptions('Add sudoers file for Taxi to make Taxi commands run without passwords', [
    '--off' => 'Remove the sudoers files so normal sudo password prompts are required.',
]);

return $app;
