<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpListCommand;
use Luoyue\WebmanMcp\McpHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    /**
     * @param array<mixed> $args
     */
    #[DataProvider('commandResults')]
    public function testCommand(string $command, array $args, string $result): void
    {
        $data = McpHelper::fetch_console($command, $args);
        $this->assertEquals($result, trim($data));
    }

    /**
     * @return iterable<array{command: class-string, args: array<mixed>, result: string}>
     */
    public static function commandResults(): iterable
    {
        yield [
            'command' => McpListCommand::class,
            'args' => [],
            'result' => <<<TEXT
                +-------------+-------+--------------+-------+------ mcp service list ---+---------------+---------------+-------+--------+
                | service     | stdio | process_port | route | endpoint | discover_cache | discover_dirs | session_store | ttl   | logger |
                +-------------+-------+--------------+-------+----------+----------------+---------------+---------------+-------+--------+
                | conformance | yes   | 8000         | yes   | /mcp     | (null)         | ["app/mcp"]   | file          | 86400 | (null) |
                +-------------+-------+--------------+-------+----------+----------------+---------------+---------------+-------+--------+
                TEXT
        ];
    }
}