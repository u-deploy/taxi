<?php

use Illuminate\Container\Container;
use Silly\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use function Valet\info;
use function Valet\table;
use function Valet\warning;
use function Valet\writer;

$version = '0.0.7';

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

$app = new Application('uDeploy Taxi', $version);

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
    try {
        Taxi::build();
        info('<info>Taxi build successful!</info>');
    } catch (Exception $e) {
        warning($e->getMessage());
    }
})->descriptions('Build Taxi configuration');

/**
 * Reset Taxi configuration
 */
$app->command('reset', function (OutputInterface $output) {
    try {
        Taxi::reset();
        info('<info>Taxi reset successful!</info>');
    } catch (Exception $e) {
        warning($e->getMessage());
    }
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

/**
 * Install Taxi and any required services.
 */
$app->command('uninstall', function (OutputInterface $output) {
    Taxi::removeSudoersEntry();
    Taxi::unlinkFromUsersBin();
    info('<info>Taxi uninstalled successfully!</info>');
})->descriptions('Uninstall Taxi');

/**
 * List sites in Valet + add Taxi state
 */
$app->command('valet', function (OutputInterface $output) {
    $sites = Site::links();

    $sites = $sites->map(function (array $site) {
        $local = file_exists($site['path'].'/taxi.json');
        $multi = file_exists($site['path'].'/../taxi.json');

        $site['taxi'] = (! $local && ! $multi) ? '' : ($local ? $site['path'].'/taxi.json' : realpath($site['path'].'/../taxi.json'));

        return $site;
    });

    table(['Site', 'SSL', 'URL', 'Path', 'PHP Version', 'Taxi'], $sites->all());

//    output(PHP_EOL.'<info>Taxi configuration file added</info>');
})->descriptions('List all sites which currently use Taxi');

return $app;
