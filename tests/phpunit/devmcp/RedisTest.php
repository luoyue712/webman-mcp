<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\devmcp;

use Luoyue\WebmanMcp\DevMcp\Redis;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    private const TEST_CONNECTION = 'plugin.luoyue.webman-mcp.default';

    private function connectionAvailable(): bool
    {
        try {
            $result = (new Redis())->executeRaw(['ping'], self::TEST_CONNECTION);
            return $result['success'];
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function deleteKeys(string ...$keys): void
    {
        (new Redis())->executeRaw(['del', ...$keys], self::TEST_CONNECTION);
    }

    public function testDatabaseConnections(): void
    {
        $redis = new Redis();
        $data = $redis->databaseConnections();

        self::assertArrayHasKey('default', $data);
        self::assertArrayHasKey('connections', $data);
        self::assertSame('default', $data['default']);
        self::assertNotEmpty($data['connections']);

        $first = $data['connections'][0];
        self::assertSame('plugin.luoyue.webman-mcp.default', $first['connection_name']);
        self::assertSame(0, $first['database']);
        self::assertNull($first['prefix']);
        self::assertArrayHasKey('pool', $first);
        self::assertArrayHasKey('max_connections', $first['pool']);
    }

    public function testExecuteRaw(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('Redis 连接不可用，跳过');
        }

        $redis = new Redis();
        $key = 'mcp_test_' . str_replace('.', '', uniqid('', true));

        try {
            $set = $redis->executeRaw(['set', $key, 'value-01'], self::TEST_CONNECTION);
            self::assertTrue($set['success']);
            self::assertSame(true, $set['result']);

            $get = $redis->executeRaw(['get', $key], self::TEST_CONNECTION);
            self::assertTrue($get['success']);
            self::assertSame('value-01', $get['result']);
        } finally {
            $this->deleteKeys($key);
        }
    }

    public function testExecuteLua(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('Redis 连接不可用，跳过');
        }

        $redis = new Redis();
        $key = 'mcp_test_' . str_replace('.', '', uniqid('', true));

        try {
            $result = $redis->executeLua(
                'return {KEYS[1], ARGV[1], ARGV[2]}',
                self::TEST_CONNECTION,
                0,
                1,
                [$key, 'a1', 'b2']
            );
            self::assertSame([$key, 'a1', 'b2'], $result['result']);
        } finally {
            $this->deleteKeys($key);
        }
    }

    public function testExecuteLuaSha(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('Redis 连接不可用，跳过');
        }

        $redis = new Redis();
        $key = 'mcp_test_' . str_replace('.', '', uniqid('', true));
        $script = 'return redis.call("GET", KEYS[1])';

        try {
            $redis->executeRaw(['set', $key, 'value-02'], self::TEST_CONNECTION);
            $result = $redis->executeLuaSha(
                $script,
                self::TEST_CONNECTION,
                0,
                1,
                [$key]
            );
            self::assertTrue($result['success']);
            self::assertSame('value-02', $result['result']);
        } finally {
            $this->deleteKeys($key);
        }
    }
}
