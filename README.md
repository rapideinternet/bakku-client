<p align="center"><a href="https://admin.bakku.cloud" target="_blank"><img src="https://admin.bakku.cloud/img/logo-bakku-8285dc3fbbae923c5dd447120ec7e5aa.svg?vsn=d" width="300" alt="Rapide Logo"></a></p>

## About Bakku Client

This is a Composer package that handles the data fetching from the Bakku CMS API. You'll be able to get all the blocks/images from a certain page and all page URLs.

## Good to knows:

- Has some built in caching through Laravel's Cache functionality;
- Still a work in progress;

## How to get it to work:

1. `composer config repositories.rapideinternet/bakku-client vcs https://github.com/rapideinternet/bakku-client` to add the package to your composer.json
2. `composer require rapideinternet/bakku-client` to add the package to your `vendor`.
3. `php artisan vendor:expose` to generate the bakkuclient.php config file.
4. Add `BAKKU_SITE_ID` and `BAKKU_SITE_API_TOKEN` to your .env
5. Use `RapideSoftware\BakkuClient\Services\BakkuClient` for the API calls

## For any questions or info:
*<p style="font-size: 10px;">(If I missed anything, please let me know)</p>*
**Slack:** Max van der Werf <br>**Email:** max.vanderwerf@rapide.software

