<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\devmcp;

use Luoyue\WebmanMcp\DevMcp\Database;
use PHPUnit\Framework\TestCase;
use support\Db;

class DatabaseTest extends TestCase
{
    private function connectionAvailable(): bool
    {
        try {
            Db::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function testDatabaseConnections(): void
    {
        $db = new Database();
        $data = $db->databaseConnections();

        self::assertArrayHasKey('default', $data);
        self::assertArrayHasKey('connections', $data);
        self::assertSame('plugin.luoyue.webman-mcp.mysql', $data['default']);
        self::assertNotEmpty($data['connections']);

        $first = $data['connections'][0];
        self::assertSame('plugin.luoyue.webman-mcp.mysql', $first['connection_name']);
        self::assertSame('mysql', $first['driver']);
        self::assertSame('webman_mcp_test', $first['database']);
        self::assertArrayHasKey('pool', $first);
        self::assertArrayHasKey('max_connections', $first['pool']);
    }

    public function testDatabaseExecuteSql(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('MySQL 连接不可用，跳过');
        }

        $db = new Database();
        $table = 'mcp_test_' . str_replace('.', '_', uniqid('', true));

        Db::connection()->statement("CREATE TABLE `{$table}` (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try {
            Db::connection()->statement("INSERT INTO `{$table}` (`name`) VALUES ('lalala')");

            $result = $db->databaseExecuteSql("SELECT `id`, `name` FROM `{$table}` WHERE `name` = :name", ['name' => 'lalala']);
            self::assertCount(1, $result['result']);
            self::assertSame('lalala', $result['result'][0]['name']);
            self::assertArrayHasKey('id', $result['result'][0]);
        } finally {
            Db::connection()->statement("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    public function testDatabaseExecuteSqlSelectEmpty(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('MySQL 连接不可用，跳过');
        }

        $db = new Database();
        $table = 'mcp_test_' . str_replace('.', '_', uniqid('', true));

        Db::connection()->statement("CREATE TABLE `{$table}` (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try {
            $result = $db->databaseExecuteSql("SELECT `id` FROM `{$table}`", []);
            self::assertSame([], $result['result']);
        } finally {
            Db::connection()->statement("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    public function testDatabaseExecuteSqlThrowsToolCallException(): void
    {
        if (!$this->connectionAvailable()) {
            self::markTestSkipped('MySQL 连接不可用，跳过');
        }

        $db = new Database();

        $this->expectException(\Mcp\Exception\ToolCallException::class);
        $this->expectExceptionMessage('执行sql失败');
        $db->databaseExecuteSql('SELECT * FROM `not_exists_table_xyz`', []);
    }
}
