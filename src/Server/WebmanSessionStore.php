<?php

namespace Luoyue\WebmanMcp\Server;

use Mcp\Server\Session\SessionStoreInterface;
use support\Cache;
use Symfony\Component\Uid\Uuid;
use Throwable;

class WebmanSessionStore implements SessionStoreInterface
{
    public function __construct(
        private readonly string $store,
        private readonly string $prefix = 'mcp-',
        private readonly int $ttl = 3600,
    )
    {
    }

    public function exists(Uuid $id): bool
    {
        try {
            return Cache::store($this->store)->has($this->getKey($id));
        } catch (Throwable) {
            return false;
        }
    }

    public function read(Uuid $id): string|false
    {
        try {
            return Cache::store($this->store)->get($this->getKey($id), false);
        } catch (Throwable) {
            return false;
        }
    }

    public function write(Uuid $id, string $data): bool
    {
        try {
            return Cache::store($this->store)->set($this->getKey($id), $data, $this->ttl);
        } catch (Throwable) {
            return false;
        }
    }

    public function destroy(Uuid $id): bool
    {
        try {
            return Cache::store($this->store)->delete($this->getKey($id));
        } catch (Throwable) {
            return false;
        }
    }

    public function gc(): array
    {
        return [];
    }

    private function getKey(Uuid $id): string
    {
        return $this->prefix . $id;
    }
}
