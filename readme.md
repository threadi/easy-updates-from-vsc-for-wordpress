# Easy Updates from VCS for WordPress

## Requirements

* _composer_ to install this package.
* WordPress-plugin or -theme to use it.

## Prerequirements

### For use with GitHub

* Create your own API key [here](https://github.com/settings/personal-access-tokens).
* Make sure to set the permissions for the repository you will use on the API key settings.
* Create a first release of your plugin / theme in GitHub.
*

## Installation

1. ``composer require threadi/easy-updates-from-vcs-for-wordpress``
2. Create the configuration file ``eufvgw.yml`` in the root of your plugin and adapt the settings for your needs:
```
type:
  - name: Plugin
  - slug: tz-test-plugin
  - file: tz-test-plugin.php

source:
  - name: GitHub
  - user: threadi
  - repository: tz-test-plugin
  - key: c

cache: true
```
3. Add the codes from `doc/install.php` to your WordPress-project (plugin or theme).

## Check for WordPress Coding Standards

### Initialize

`composer install`

### Run

`vendor/bin/phpcs --standard=vendor/threadi/easy-updates-from-vcs-for-wordpress/ruleset.xml vendor/threadi/easy-updates-from-vcs-for-wordpress/`

### Repair

`vendor/bin/phpcbf --standard=vendor/threadi/easy-updates-from-vcs-for-wordpress/ruleset.xml vendor/threadi/easy-updates-from-vcs-for-wordpress/`

## Analyse with PHPStan

`vendor/bin/phpstan analyse -c vendor/threadi/easy-updates-from-vcs-for-wordpress/phpstan.neon`
