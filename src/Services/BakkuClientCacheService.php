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
        $cache = Cache::store();
        if (method_exists($cache->getStore(), 'tags')) {
            return $cache->tags($tags)->remember($key, $ttl, $callback);
        }

        // Fallback for cache stores that do not support tagging.
        // We'll use a shorter TTL for non-tagged cache to mitigate staleness,
        // as tagged cache items would normally be flushed together.
        // Adjust this fallback TTL as appropriate for your application's needs.
        return $cache->remember($key, (int) ($ttl / 2), $callback); // Halving TTL as a pragmatic fallback
    }
}
