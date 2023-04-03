<?php

namespace UDeploy\Taxi;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use function Taxi\git_branch;
use UDeploy\Taxi\Exceptions\ConfigurationMissing;
use UDeploy\Taxi\Exceptions\InvalidConfiguration;
use function Valet\info;
use function Valet\warning;

class Taxi
{
    public $taxiBin = BREW_PREFIX.'/bin/taxi';

    protected array $taxiConfig = [];

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

        $this->cli->runAsUser('ln -s "'.realpath(__DIR__.'/../../taxi').'" '.$this->taxiBin);
    }

    /**
     * Remove the symlink from the user's local bin.
     */
    public function unlinkFromUsersBin(): void
    {
        $this->cli->quietlyAsUser('rm '.$this->taxiBin);
    }

    /**
     * Create the "sudoers.d" entry for running Taxi.
     */
    public function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/taxi', 'Cmnd_Alias TAXI = '.BREW_PREFIX.'/bin/taxi *
        %admin ALL=(root) NOPASSWD:SETENV: TAXI'.PHP_EOL);
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

    /**
     * run taxi.json commands to start sites
     */
    public function build()
    {
        // maintain known root folder
        $root = getcwd();

        // get te configuration / throw exception on bad file
        $this->loadTaxiConfig();

        // loop through vcs and build sites
        collect($this->taxiConfig['sites'])->each(fn ($site) => $this->buildSite($site, $root));

        info('build completed');
    }

    public function buildSite(array $site, string $root): array
    {
        $folder = Str::kebab($site['name']);
        $path = $root.'/'.$folder;
        // ensure start at root folder where config is
        info('Cloning repository: '.$site['name']);

        // TODO add checks to make sure folder is clear
        $this->cli->path($root)->runAsUser('git clone '.$site['vcs'].' '.$folder);

        // Link to valet
        $this->cli->path($path)->runAsUser('valet link '.$folder);

        // isolate PHP version
        if (array_key_exists('php', $site)) {
            info('  Isolating PHP version for site');
            $this->cli->path($path)->runAsUser('valet isolate '.$site['php']);
        }

        $currentBranch = git_branch($path);
        if ($currentBranch !== $site['branch']) {
            // ensure on default branch
            $this->cli->path($path)->runAsUser('git checkout '.$site['branch']);
        }

        // enable valet secure
        if (array_key_exists('secure', $site) && $site['secure'] === true) {
            info('  Securing valet site');
            $this->cli->path($path)->runAsUser('valet secure');
        }

        // run global build hooks
        if (array_key_exists('hooks', $this->taxiConfig) && array_key_exists('build', $this->taxiConfig['hooks'])) {
            info('  Running build commands');
            $this->runCommandsInDirectory($this->taxiConfig['hooks']['build'], $root);
        }

        // run site build hooks
        if (array_key_exists('post-build', $site)) {
            info('  Running post-build commands');

            $this->runCommandsInDirectory($this->taxiConfig['post-build'], $root);
        }

        info($site['name'].' build completed');

        return $site;
    }

    /**
     * run taxi.json commands to reset sites
     */
    public function reset()
    {
        $root = getcwd();

        $this->loadTaxiConfig();

        collect($this->taxiConfig['sites'])->each(fn ($site) => $this->resetSite($site, $root));
    }

    public function resetSite(array $site, string $root): array
    {
        // check to see if a git checkout is required
        $this->resetToDefaultBranch($site, $root);

        // run global install hooks
        info('Running reset commands');
        $this->runCommandsInDirectory($this->taxiConfig['hooks']['reset'], $root);

        // run site reset hooks
        info('Running post-reset commands');
        $this->runCommandsInDirectory($this->taxiConfig['post-reset'], $root);

        info('Site: '.$site['name'].' installed');

        return $site;
    }

    protected function resetToDefaultBranch(array $site, string $root): array
    {
        $folder = Str::kebab($site['name']);
        $path = $root.'/'.$folder;

        $currentBranch = git_branch($path);
        if ($currentBranch === $site['branch']) {
            info('No change to '.$site['name'].PHP_EOL);

            return $site;
        }

        $response = $this->cli->path($root)->runAsUser('git stash && git checkout '.$site['branch']);

        $action = 'branch changed';

        if (str_contains($response, 'No local changes to save')) {
            $action .= ' and stash created '.
                Str::after(
                    explode(PHP_EOL, $response)[0],
                    'Saved working directory and index state '
                );
        }

        info($action);

        return $site;
    }

    protected function runCommandsInDirectory(array $commands, string $path): void
    {
        collect($commands)
            ->each(fn ($hook) => $this->cli->path($path)->runAsUser($hook));
    }

    /**
     * @throws ConfigurationMissing
     * @throws InvalidConfiguration
     */
    public function loadTaxiConfig(): void
    {
        if (! $this->taxiConfigExists()) {
            throw new ConfigurationMissing;
        }

        $config = $this->files->get(
            $this->taxiConfigPath()
        );

        if ($this->isTaxiConfigValid()) {
            $this->taxiConfig = json_decode($config, true);
        }
    }

    /**
     * does taxi.json config exist in the current directory
     */
    public function taxiConfigExists(): bool
    {
        return $this->files->exists(
            $this->taxiConfigPath()
        );
    }

    /**
     * get expected path for taxi.json in current directory
     */
    public function taxiConfigPath(): string
    {
        return $this->files->cwd().'/taxi.json';
    }

    /**
     * @throws InvalidConfiguration
     */
    public function isTaxiConfigValid(): bool
    {
        $validator = new Validator();

        /** @var ValidationResult $result */
        $result = $validator->validate($this->taxiConfig, $this->files->getStubPath('schema.json'));

        if ($result->isValid()) {
            return true;
        }

        // throw exception and populate message based on validation errors
        throw new InvalidConfiguration(
            implode(PHP_EOL, (new ErrorFormatter())->format($result->error()))
        );
    }
}
