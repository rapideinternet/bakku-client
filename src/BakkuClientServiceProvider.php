<?php

namespace RapideSoftware\BakkuClient;

use Illuminate\Support\ServiceProvider;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Services\BakkuClientService;
use RapideSoftware\BakkuClient\Services\BakkuClientCacheService;
use RapideSoftware\BakkuClient\Services\HttpClientService;

class BakkuClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(BakkuClientInterface::class, BakkuClientService::class);
        $this->app->bind(CacheInterface::class, BakkuClientCacheService::class);
        $this->app->bind(HttpClientService::class, HttpClientService::class);

        $this->mergeConfigFrom(__DIR__.'/Config/bakkuclient.php', 'bakkuclient');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/bakkuclient.php' => config_path('bakkuclient.php'),
        ]);
    }
}
