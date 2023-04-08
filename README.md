# Taxi

## Introduction

A multi-site manager for Laravel Valet. Easily manage local deployments of multiple web applications, 
for easier developer startup. Quickly reset site managed by uDeploy Taxi, stashing changes as needed to bring 
all sites up to date fast, ideal for QA and local Testers.

## Commands

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

UDeploy Taxi is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).