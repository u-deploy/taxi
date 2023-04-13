<?php

namespace UDeploy\Taxi;

use Illuminate\Support\Str;
use UDeploy\Taxi\Traits\HasAttributes;
use function PHPUnit\Framework\matches;
use function Taxi\git_branch;
use function Taxi\make;
use TaxiFileSystem;
use function Valet\info;
use function Valet\warning;

class Site
{
    use HasAttributes;

    protected ?SiteConfig $config = null;

    public function __construct(
        protected string $root,
        protected array $attributes = [],
        protected array $buildCommands = [],
        protected array $resetCommands = []
    ) {
        $this->folder = Str::kebab($this->name);
        $this->path = $this->root.'/'.$this->folder;

        $this->cli = make(CommandLine::class);
    }

    public function config(): ?SiteConfig
    {
        if (is_null($this->config) && TaxiFileSystem::isDir($this->path)) {
            $this->config = make(SiteConfig::class, [
                'path' => $this->path,
            ]);
        }

        return $this->config;
    }

    public function build(): static
    {
        return $this->cloneRepository()
            ->valetLink()
            ->isolatePhpVersion()
            ->gitCheckoutDefaultBranch()
            ->valetSecure()
            ->runBuildCommands()
            ->setupDatabase()
            ->runSiteBuildCommands()
            ->buildComplete();
    }

    public function reset(): static
    {
        info('Resetting repository: '.$this->name);
        // check to see if a git checkout is required
        return $this->resetToDefaultBranch()
            ->runResetCommands()
            ->runSiteResetCommands()
            ->resetComplete();
    }

    public function resetComplete(): static
    {
        info('Site: '.$this->name.' reset');

        return $this;
    }

    protected function resetToDefaultBranch(): static
    {
        if ($this->isNotDefaultBranch()) {
            info('No change to '.$this->name.PHP_EOL);

            return $this;
        }

        $response = $this->cli->path($this->path)->runAsUser('git stash && git checkout '.$this->branch);

        info($this->getGitBranchChangeInformationAsFormattedString($response));

        return $this;
    }

    protected function getGitBranchChangeInformationAsFormattedString(string $response): string
    {
        $action = ' Branch changed';

        if (str_contains($response, 'No local changes to save')) {
            $action .= ' and stash created '.
                Str::after(
                    explode(PHP_EOL, $response)[0],
                    'Saved working directory and index state '
                );
        }

        return $action;
    }

    public function runResetCommands(): static
    {
        info(' Running reset commands');
        $this->runCommandsInDirectory($this->resetCommands);

        return $this;
    }

    public function runSiteResetCommands(): static
    {
        $commands = $this->get('post-reset');
        // run site build hooks
        if (! empty($commands)) {
            info('  Running post-reset commands');

            $this->runCommandsInDirectory($commands);
        }

        return $this;
    }

    public function buildComplete(): static
    {
        info($this->name.' build completed');

        return $this;
    }

    public function cloneRepository(): static
    {
        info('Cloning repository: '.$this->name);

        $this->cli->path($this->root)->runAsUser('git clone '.$this->vcs.' '.$this->folder);

        return $this;
    }

    public function gitCheckoutDefaultBranch(): static
    {
        if ($this->isNotDefaultBranch()) {
            // ensure on default branch
            $this->cli->path($this->path)->runAsUser('git checkout '.$this->branch);
        }

        return $this;
    }

    public function isNotDefaultBranch(): bool
    {
        return git_branch($this->path) !== $this->branch;
    }

    public function valetLink(): static
    {
        // Link to valet
        $this->cli->path($this->path)->runAsUser('valet link '.$this->folder);

        return $this;
    }

    public function isolatePhpVersion(): static
    {
        // isolate PHP version
        if ($this->has('php')) {
            info('  Isolating PHP version for site');
            $this->cli->path($this->path)->runAsUser('valet isolate '.$this->php);
        }

        return $this;
    }

    public function valetSecure(): static
    {
        // enable valet secure
        if ($this->has('secure') && $this->secure === true) {
            info('  Securing valet site');
            $this->cli->path($this->path)->runAsUser('valet secure');
        }

        return $this;
    }

    public function setupDatabase(): static
    {
        if ($this->has('database') && !empty($this->database)) {
            info('  Setting up database');

            $connections = $this->config()->get('database.connections', []);

            foreach ($this->database as $database) {
                if(array_key_exists($database->connection, $connections)) {
                    $this->setupDatabaseConnection(
                        $connections[$database->connection]
                    );
                }else{
                    warning("- Database connection: ".$database->connection." not configured");
                }
            }
        }
        return $this;
    }

    protected function setupDatabaseConnection(
        array $connection
    )
    {
        $commands = $this->generateDatabaseCommands($connection);
        foreach ($commands as $command) {
            $this->cli->runAsUser($command);
        }
    }

    protected function generateDatabaseCommands(array $connection): array
    {
        $cmds = [];

        $cmds[] = $this->generateDatabaseCommand($connection);

        // has host
        if(array_key_exists('host', $connection)) {
            $cmds[] = $this->generateDatabaseGrantsCommand($connection, $connection['host']);
        }

        // has write hosts
        if(array_key_exists('write', $connection)) {
            // has one host
            if(is_string($connection['write']['host'])) {
                $cmds[] = $this->generateDatabaseGrantsCommand($connection, $connection['write']['host']);
            }elseif (is_array($connection['write']['host'])) {
                foreach ($connection['write']['host'] as $host) {
                    $cmds[] = $this->generateDatabaseGrantsCommand($connection, $host);
                }
            }
        }

        if(array_key_exists('read', $connection)) {
            if(is_string($connection['read']['host'])) {
                $cmds[] = $this->generateDatabaseGrantsCommand($connection, $connection['read']['host']);
            }elseif (is_array($connection['read']['host'])) {
                foreach ($connection['read']['host'] as $host) {
                    $cmds[] = $this->generateDatabaseGrantsCommand($connection, $host);
                }
            }
        }

        return $cmds;
    }

    protected function generateDatabaseCommand(array $connection): string
    {
        return match ($connection['driver']) {
            'mysql' => 'mysql -e "CREATE DATABASE IF NOT EXISTS '.$connection['database'].';',
            default => ''
        };
    }

    protected function generateDatabaseGrantsCommand(array $connection, string $host)
    {
        return match ($connection['driver']) {
            'mysql' => 'mysql -e "GRANT ALL PRIVILEGES ON '.$connection['database'].'.* TO '.$connection['username'].'@'.$host.' IDENTIFIED BY \''.$connection['password'].'\'"',
            default => ''
        };
    }

    private function getHost(array $connection): string
    {
        if(array_key_exists('host', $connection)) {
            return $connection['host'];
        }

        if(array_key_exists('write', $connection)) {
            return $connection['write']['host'];
        }
    }


    public function runBuildCommands(): static
    {
        // run global build hooks
        if (! empty($this->buildCommands)) {
            info('  Running build commands');
            $this->runCommandsInDirectory($this->buildCommands);
        }

        return $this;
    }

    public function runSiteBuildCommands(): static
    {
        $commands = $this->get('post-build');
        // run site build hooks
        if (! empty($commands)) {
            info('  Running post-build commands');

            $this->runCommandsInDirectory($commands);
        }

        return $this;
    }

    protected function runCommandsInDirectory(array $commands): void
    {
        collect($commands)
            ->each(fn ($hook) => $this->cli->path($this->path)->runAsUser($hook));
    }
}
