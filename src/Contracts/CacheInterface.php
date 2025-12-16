<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Contracts;

interface CacheInterface
{
    public function set(string $key, mixed $value, int $ttl): void;
    public function get(string $key): mixed;
    public function remember(string $key, int $ttl, \Closure $callback): mixed;
    /** @param string[] $tags */
    public function rememberTagged(array $tags, string $key, int $ttl, \Closure $callback): mixed;
}
