## Installation
Install package using composer
```sh
composer config repositories.emico composer https://repository.emico.nl/
composer config minimum-stability dev
composer require emico/tweakwise-export
```

Install package using zip file
```sh
unzip tweakwise-export.zip
mkdir -p app/code/Emico/TweakwiseExport
mv tweakwise-export/src/* app/code/Emico/TweakwiseExport
cp -f tweakwise-export/composer.json app/code/Emico/TweakwiseExport/composer.json
```

Run installers
```sh
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

## Usage
All export settings can be found under Stores -> Configuration -> Catalog -> Tweakwise -> Export.

Generating feeds can be done using the command line.
```sh
php bin/magento tweakwise::export
php bin/magento setup:static-content:deploy
```

## Debugging
Debugging is done using the default debugging functionality of Magento / PHP. You can enable indentation of the feed by setting deploy mode to developer.
```sh
php bin/magento deploy:mode:set developer

Usage:
 tweakwise:export [-c|--validate] [file]

Arguments:
 file                  Export to specific file (default: "var/feeds/tweakwise.xml")

Options:
 --validate (-c)       Validate feed and rollback if fails.
 --help (-h)           Display this help message
 --quiet (-q)          Do not output any message
 --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 --version (-V)        Display this application version
 --ansi                Force ANSI output
 --no-ansi             Disable ANSI output
 --no-interaction (-n) Do not ask any interactive questio
```

## Events
Currently there are no events documented, this will be done in the coming version(s).

## Profiling
For profiling use the standard Magento profiler, more info will be provided in the coming version(s).