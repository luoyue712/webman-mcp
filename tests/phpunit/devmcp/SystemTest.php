<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\devmcp;

use Composer\InstalledVersions;
use Luoyue\WebmanMcp\DevMcp\System;
use PHPUnit\Framework\TestCase;
use Workerman\Events\Select;
use Workerman\Worker;

class SystemTest extends TestCase
{
    public function testSystemInfo(): void
    {
        Worker::$globalEvent = new Select();
        $info = (new System())->systemInfo();

        self::assertSame(PHP_OS, $info['server_os']);
        self::assertSame(PHP_VERSION, $info['php_version']);
        self::assertSame(PHP_BINARY, $info['php_binary']);
        self::assertSame(php_sapi_name(), $info['php_sapi_name']);
        self::assertIsBool($info['is_coroutine']);
        self::assertSame(sys_get_temp_dir(), $info['default_temp_dir']);
        self::assertNotEquals('', $info['workerman_version']);
        self::assertNotEquals('', $info['webman_version']);
    }

    public function testListDependence(): void
    {
        $data = (new System())->listDependence();

        self::assertArrayHasKey('root', $data);
        self::assertArrayHasKey('versions', $data);
        self::assertSame('luoyue/webman-mcp', $data['root']['name']);
        self::assertArrayHasKey('workerman/workerman', $data['versions']);
    }

    public function testExtensions(): void
    {
        $data = (new System())->extensions();

        self::assertNotEmpty($data);
        foreach (get_loaded_extensions() as $extension) {
            self::assertArrayHasKey($extension, $data);
        }
    }

    public function testGetPhpIni(): void
    {
        $data = (new System())->getPhpIni();

        self::assertIsArray($data);
        self::assertArrayHasKey('error_reporting', $data);

        $filtered = (new System())->getPhpIni('date');
        self::assertIsArray($filtered);
        foreach (array_keys($filtered) as $key) {
            self::assertStringContainsString('date', $key);
        }
    }

    public function testGetConfig(): void
    {
        $system = new System();

        self::assertSame(config('database.default'), $system->getConfig('database.default'));
        self::assertSame(config('plugin.luoyue.webman-mcp.mcp.default_protocol'), $system->getConfig('plugin.luoyue.webman-mcp.mcp.default_protocol'));
        self::assertSame(config('not_exists_key_abc'), $system->getConfig('not_exists_key_abc'));
    }

    public function testGetEnv(): void
    {
        self::assertSame(getenv('PATH'), (new System())->getEnv('PATH'));
    }

    public function testEvalCode(): void
    {
        $result = (new System())->evalCode('echo "hello-mcp";');

        self::assertSame('hello-mcp', $result['result']);
    }

    public function testEvalCodeReturnsEmptyWhenNoOutput(): void
    {
        $result = (new System())->evalCode('');

        self::assertSame('', $result['result']);
    }

    public function testEvalCodeRemovesPhpTags(): void
    {
        $result = (new System())->evalCode('<?php echo "world"; ?>');

        self::assertSame('world', $result['result']);
    }

    public function testListRoutes(): void
    {
        $data = (new System())->listRoutes();

        self::assertArrayHasKey('routes', $data);
        self::assertIsArray($data['routes']);
    }

    public function testListEvents(): void
    {
        if (!InstalledVersions::isInstalled('webman/event')) {
            self::markTestSkipped('webman/event not installed');
        }

        $data = (new System())->listEvents();

        self::assertContainsOnly('array', $data);
        foreach ($data as $item) {
            self::assertArrayHasKey('event_name', $item);
            self::assertArrayHasKey('callback', $item);
        }
    }
}
