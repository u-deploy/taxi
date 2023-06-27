<?php

namespace UDeploy\Taxi;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use UDeploy\Taxi\Exceptions\ConfigurationMissing;
use UDeploy\Taxi\Exceptions\InvalidConfiguration;
use Valet\Brew;
use function Valet\info;
use function Valet\warning;

class Taxi
{
    public $taxiBin = BREW_PREFIX . '/bin/taxi';

    protected array $taxiConfig = [];

    public function __construct(
        public CommandLine $cli,
        public Filesystem  $files,
        public Client      $client,
        public Brew        $brew
    )
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
        if (!is_null($url) && filter_var($url, FILTER_VALIDATE_URL) === false) {
            return warning('Invalid url');
        }

        $contents = $this->getCallContents($url);

        $this->files->putAsUser(
            $this->files->cwd() . '/taxi.json',
            $contents
        );

        return true;
    }

    protected function getCallContents(?string $url): string
    {
        if (is_null($url)) {
            return $this->files->getTaxiStub('taxi.json');
        }

        return (string)$this->client->get($url)->getBody();
    }

    /**
     * run taxi.json commands to start sites
     */
    public function build()
    {
        // maintain known root folder
        $root = $this->files->cwd();

        // get te configuration / throw exception on bad file
        $this->loadTaxiConfig();

        // install services required
        $this->installServices();

        // loop through vcs and build sites
        collect($this->taxiConfig['sites'])
            ->each(fn($site) => (new Site(
                root: $root,
                attributes: $site,
                buildCommands: $this->getBuildCommands(),
                resetCommands: $this->getResetCommands()
            ))
                ->build()
            );
    }

    protected function getBuildCommands(): array
    {
        return $this->taxiConfig['hooks']['build'] ?? [];
    }

    protected function getResetCommands(): array
    {
        return $this->taxiConfig['hooks']['reset'] ?? [];
    }

    protected function getServicesList(): array
    {
        return $this->taxiConfig['services'] ?? [];
    }

    /**
     * run taxi.json commands to reset sites
     */
    public function reset()
    {
        $root = $this->files->cwd();

        $this->loadTaxiConfig();

        collect($this->taxiConfig['sites'])
            ->each(fn($site) => (new Site(
                root: $root,
                attributes: $site,
                buildCommands: $this->getBuildCommands(),
                resetCommands: $this->getResetCommands()
            ))
                ->reset()
            );
    }

    /**
     * @throws ConfigurationMissing
     * @throws InvalidConfiguration
     */
    public function loadTaxiConfig(): void
    {
        if (!$this->taxiConfigExists()) {
            throw new ConfigurationMissing;
        }

        $config = $this->files->get(
            $this->taxiConfigPath()
        );

        if ($this->isTaxiConfigValid($config)) {
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
        return $this->files->cwd() . '/taxi.json';
    }

    /**
     * @throws InvalidConfiguration
     */
    public function isTaxiConfigValid(string $config): string
    {
        $validator = new Validator();

        /** @var ValidationResult $result */
        $result = $validator->validate(json_decode($config), $this->files->getTaxiStub('schema.json'));

        if ($result->isValid()) {
            return $config;
        }

        $errors = (new ErrorFormatter())->formatFlat($result->error());
        // throw exception and populate message based on validation errors
        throw new InvalidConfiguration(
            implode(PHP_EOL, $errors)
        );
    }

    protected function installServices(): void
    {
        $services = collect($this->getServicesList());

        $services->whenNotEmpty(function (Collection $services) {
            info('Installing services');

            $services->each(function ($service) {
                $this->brew->ensureInstalled($service);
                info('  ' . $service . ' installed');
            });
        });
    }

}
