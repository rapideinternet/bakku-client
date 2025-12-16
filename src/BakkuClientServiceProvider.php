<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient;

use Illuminate\Support\ServiceProvider;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Exceptions\InvalidConfigurationException;
use RapideSoftware\BakkuClient\Services\BakkuClientService;
use RapideSoftware\BakkuClient\Services\BakkuClientCacheService;
use RapideSoftware\BakkuClient\Services\HttpClientService;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;

class BakkuClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApiResponseTransformer::class, function ($app) {
            return new ApiResponseTransformer();
        });

        $this->app->singleton(BakkuClientInterface::class, BakkuClientService::class);
        $this->app->singleton(CacheInterface::class, BakkuClientCacheService::class);
        $this->app->singleton(HttpClientService::class, function ($app) {
            $config = (array)config('bakkuclient');
            /** @var string $apiToken */
            $apiToken = $config['api_token'];
            return new HttpClientService($apiToken);
        });

        $this->mergeConfigFrom(__DIR__.'/Config/bakkuclient.php', 'bakkuclient');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/bakkuclient.php' => config_path('bakkuclient.php'),
        ]);

        $config = (array)config('bakkuclient');

        if (empty($config['site_id'])) {
            throw new InvalidConfigurationException('BAKKU_SITE_ID is not set in the environment or bakkuclient.php configuration.');
        }

        if (empty($config['api_token'])) {
            throw new InvalidConfigurationException('BAKKU_SITE_API_TOKEN is not set in the environment or bakkuclient.php configuration.');
        }
    }
}
