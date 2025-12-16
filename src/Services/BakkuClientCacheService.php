<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Cache;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;

class BakkuClientCacheService implements CacheInterface
{
    public function set(string $key, mixed $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }

    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /** @param string[] $tags */
    public function rememberTagged(array $tags, string $key, int $ttl, \Closure $callback): mixed
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }
}
