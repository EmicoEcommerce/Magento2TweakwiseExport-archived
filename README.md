[![Build Status](https://travis-ci.org/EmicoEcommerce/Magento2TweakwiseExport.svg?branch=master)](https://travis-ci.org/EmicoEcommerce/Magento2TweakwiseExport)
[![Code Climate](https://codeclimate.com/github/EmicoEcommerce/Magento2TweakwiseExport.png)](https://codeclimate.com/github/EmicoEcommerce/Magento2TweakwiseExport)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/84dd3eaef04d4569adbd7930f24f23fd)](https://www.codacy.com/app/Fgruntjes/Magento2TweakwiseExport?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=EmicoEcommerce/Magento2TweakwiseExport&amp;utm_campaign=Badge_Grade)

## Installation
Install package using composer
```sh
composer config minimum-stability dev
composer require emico/tweakwise-export
```

Install package using zip file
```sh
Extract tweakwise-export.zip src folder to app/code/Emico/TweakwiseExport/
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
php bin/magento tweakwise:export
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
 --no-interaction (-n) Do not ask any interactive question
```

## Feed structure
The feed contains some header information followed by categories and then products. Tweakwise does not natively support multiple stores, in order to circumvent this all categories and products are prefixed with 1000{store_id}.
If a product (with id 1178) is active and visible in multiple stores (say 1, 5 and 8) then it will appear three times in the feed with ids: 100011178, 100051178 and 100081178.
The data on that product depends on the attribute values of the specific store. In short an entity is available in the feed as ``1000{store_id}{entity_id}``

The feed only contains products that are visible under your catalog configuration. If a product has children (say it is configurable) then the feed will also contain all the data from those children.
Child data is aggregated onto the "parent" product. 
The reason for this is that when a user searches for a t-shirt with size M then the configurable must show up in the results, therefor the configurable should be exported with all sizes available among its children. 

The feed contains only attributes which have bearing on search or navigation, check ``src/Model/ProductAttributes.php:45`` to see the criteria an attribute must meet in order to be exported.

## A note on the feed implementation
Magento's native interfaces and handlers for data retrieval were deemed to slow for a large catalogue.
Since performance is essential we decided on our own queries for data retrieval. The consequence is that we need to keep track of the inner workings of magento and are subject to its changes.
If you find an issue with data retrieval please create an issue on github.


## Export Settings
- Enabled: If products of that store should be exported to tweakwise, note that if this is false for some store then navigation and search should also be disabled for that store.
- Schedule: Cron schedule for generating the feed. We strongly encourage you to register the export task on the server crontab instead of using the Magento cron.
- Schedule export: Generate the feed on the next cron run.
- Key: This will be validated by the export module when the ExportController is asked for feed content or when the CacheFlush controller is asked to flush cache. If the request does not have a key parameter that matches the feed will not be served (or in case of the cache controller the cache will not be flushed).
- Allow cache flush: Allow automated flushing of cache, you can configure a task in the navigator to run after it is done publishing to flush Magento caches. You must specify an url in the task configuration: use https://yoursite.com/tweakwise/cache/flush/key/{{feed_key}} as its url, here feed_key is equal to the key configured in the Key setting (see above). If this setting is set to no tweakwise-export will ignore these requests. 
- Export realtime: When the ExportController is asked for a feed it will generate a new one on the fly. Note that this is not recommended!
- Tweakwise import API url: Tasks in the navigator can be executed via API. Use the import task API url here to automatically tell tweakwise to import the feed after it has been generated.
- Combined product stock calculation: this will determine stock the quantity of combined products (configurable, bundle, etc) SUM: add all quantities of child products, MAX: Use the max quantity of all child products, MIN: use the minimum quantity. This setting might be removed as each product type should have its own stock calculation method.
- Export percentage of child products that are in stock: If set to yes then for each combined product the percentage of child products that are in stock will be exported. Example: A t-shirt (configurable) with sizes XS, S, M, L and XL (the associated simples) if only S and XL are in stock then the percentage is exported as 40 (as 2/5 of the products are in stock).
- Export out of stock Combined product children: Tweakwise export aggregates child data on parent products, this setting determines if data from out of child products should be included in this aggregation.
- Exclude child attributes: These values of these attributes will be excluded from product data when aggregating onto the parent product.
- Which price value will be exported as "price" to tweakwise.

### Visibility settings
Magento has multiple visibility settings, tweakwise only knows visible products meaning that if a product is in the feed then it will be visible while navigating and searching.
The magento visibility setting is exported in the feed so you can add a hidden filter to your tweakwise template to artificially use the correct settings.
If you do this then exclude the visibility attribute from child products (see "Export Settings").

## Events
Currently there are no events documented, this will be done in the coming version(s).

## Profiling
For profiling use the standard Magento profiler, more info will be provided in the coming version(s).
