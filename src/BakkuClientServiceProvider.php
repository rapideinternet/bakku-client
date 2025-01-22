<?php

namespace RapideSoftware\BakkuClient;

use Illuminate\Support\ServiceProvider;
use RapideSoftware\BakkuClient\Services\BakkuClientCacheService;
use RapideSoftware\BakkuClient\Services\BakkuClientDataService;
use RapideSoftware\BakkuClient\Services\BakkuClientService;

class BakkuClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('BakkuClient', function ($app) {
            return new BakkuClientService(BakkuClientCacheService::class, BakkuClientDataService::class);
        });

        $this->mergeConfigFrom(__DIR__.'/Config/bakkuclient.php', 'bakkuclient');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/bakkuclient.php' => config_path('bakkuclient.php'),
        ]);
    }
}


