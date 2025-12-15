<?php

namespace RapideSoftware\BakkuClient\Contracts;

interface CacheInterface
{
    public function set(string $key, $value, int $ttl): void;
    public function get(string $key);
    public function remember(string $key, int $ttl, \Closure $callback);
}
