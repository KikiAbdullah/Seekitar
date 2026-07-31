<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_STATIC  = 3600;
    const TTL_DYNAMIC = 300;
    const TTL_HOT     = 60;

    public function remember(string $key, callable $callback, int $ttl = self::TTL_DYNAMIC): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function invalidateGroup(string $group): void
    {
        $keys = Cache::get("cache_keys:{$group}", []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget("cache_keys:{$group}");
    }

    public function tag(string $group, string $key): void
    {
        $keys = Cache::get("cache_keys:{$group}", []);
        $keys[] = $key;
        Cache::forever("cache_keys:{$group}", array_unique($keys));
    }
}
