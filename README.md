# Taxi

[![Latest Version on Packagist](https://img.shields.io/packagist/v/u-deploy/taxi.svg?style=flat-square)](https://packagist.org/packages/u-deploy/taxi)
[![Total Downloads](https://img.shields.io/packagist/dt/u-deploy/taxi.svg?style=flat-square)](https://packagist.org/packages/u-deploy/taxi)
## Introduction

A multi-site manager for Laravel Valet. Easily manage local deployments of multiple web applications, 
for easier developer startup. Quickly reset site managed by uDeploy Taxi, stashing changes as needed to bring 
all sites up to date fast, ideal for QA and local Testers.

## Get Started

### Prerequisite:

This application requires [Laravel Valet](https://laravel.com/docs/10.x/valet#introduction) to be installed.

First install via composer;

> composer global require u-deploy/taxi

Ensure that the `~/.composer/vendor/bin` is in your PATH.

Then run:

> taxi install

In order to run certain commands (like Valet) sudo is required. Running this command will add taxi to the sudoer file.

> taxi trust

If you wish to remove this privilege then run:  `taxi trust --off`

### Start using Taxi.

1. Generate a taxi.json file in a empty directory.

Running this command
> taxi call

Will place an example taxi.json file in the current directory. 

2. Use an existing taxi.json file hosted anywhere.

> taxi call https://raw.githubusercontent.com/u-deploy/taxi/main/cli/stubs/taxi.json

Will download the taxi.json file into the current directory.

### Build

Once you have the taxi.json file in the directory and set-up for your needs, you can then run
>taxi build

This will clone the repos listed in the taxi.json file and also any build commands defined.
The site will automatically be linked with Laravel Valet and isolate PHP version (if set) and enable https (secure).

Once the build is successful, you can start using your interlinked applications.

### Reset

Ideal for QA or testers, or maybe a general reset for local development. Running;

> taxi reset 

Will reset any repositories listed in the taxi.json back to their defined default branches. 
Any uncommited changes will be stashed. 
Also the reset commands will be run, this is ideal if assets need to be recompiled.

## Commands

Below is a comprehensive list of available commands:

```bash
taxi list
```
List all commands available by Taxi

```bash
taxi install
```
Install taxi locally, this creates a symlink to your users bin.

```bash
taxi trust
```
Adds taxi to sudoers so that commands can be run silently.

```bash
taxi trust --off
```
Removes taxi from sudoers.

```bash
taxi call
``` 
Generate an example taxi.json file in the current directory.

```bash
taxi call https://gist.githubusercontent.com/RichardStyles/5f7f0c1b464aa33c2ac178807cf8e906/raw/dacbeaf9cc3baad04ec649271b9d5d9a579718ce/taxi-example.json
```
Pull a taxi.json configuration from an external site or repository.

```bash
taxi build
```
Downloads all sites and runs the associated commands, build hooks run first followed by site specific post-build commands.

```bash
taxi reset
```
Resets all sites back to the default branch, stashing changes. Then runs reset hooks followed by site specific post-reset commands.

```bash
taxi valet
```
List all Laravel Valet sites, including if used their taxi.json configuration files  

## License

uDeploy Taxi is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
