<?php

namespace RapideSoftware\BakkuClient;

use Illuminate\Support\ServiceProvider;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Services\BakkuClientService;
use RapideSoftware\BakkuClient\Services\BakkuClientCacheService;
use RapideSoftware\BakkuClient\Services\HttpClientService;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;

class BakkuClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ApiResponseTransformer::class, function ($app) {
            return new ApiResponseTransformer();
        });

        $this->app->singleton(BakkuClientInterface::class, BakkuClientService::class);
        $this->app->singleton(CacheInterface::class, BakkuClientCacheService::class);
        $this->app->singleton(HttpClientService::class, HttpClientService::class);

        $this->mergeConfigFrom(__DIR__.'/Config/bakkuclient.php', 'bakkuclient');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/bakkuclient.php' => config_path('bakkuclient.php'),
        ]);
    }
}
