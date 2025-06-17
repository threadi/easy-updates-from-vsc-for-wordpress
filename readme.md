# Easy Updates from VCS for WordPress

## Requirements

* _composer_ to install this package.
* WordPress-plugin or -theme to use it.

## Installation

1. ``composer require threadi/easy-updates-from-vcs-for-wordpress``
2. Add the codes from `doc/install.php` to your WordPress-project (plugin or theme).

## Check for WordPress Coding Standards

### Initialize

`composer install`

### Run

`vendor/bin/phpcs --standard=vendor/threadi/easy-updates-from-vcs-for-wordpress/ruleset.xml vendor/threadi/easy-updates-from-vcs-for-wordpress/`

### Repair

`vendor/bin/phpcbf --standard=vendor/threadi/easy-updates-from-vcs-for-wordpress/ruleset.xml vendor/threadi/easy-updates-from-vcs-for-wordpress/`

## Analyse with PHPStan

`vendor/bin/phpstan analyse -c vendor/threadi/easy-updates-from-vcs-for-wordpress/phpstan.neon`
