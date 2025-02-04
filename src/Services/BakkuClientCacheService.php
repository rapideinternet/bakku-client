<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Cache;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;

class BakkuClientCacheService implements CacheInterface
{
    public function set(string $key, $value, int $ttl): void
    {
        Cache::add($key, $value, $ttl);
    }

    public function get(string $key)
    {
        return Cache::get($key);
    }
}
