<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpListCommand;
use Luoyue\WebmanMcp\McpHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

class CommandTest extends TestCase
{
    /**
     * @param array<mixed> $args
     */
    #[DataProvider('commandResults')]
    public function testCommand(string $command, array $args, array $rows): void
    {
        $data = McpHelper::fetch_console($command, $args);

        $buf = new BufferedOutput();
        $table = new Table($buf);
        $table->setHeaders(['service', 'stdio', 'process_port', 'route', 'endpoint', 'session', 'logger']);
        $table->setHeaderTitle('mcp service list');
        array_map(static fn ($row) => $table->addRow(array_values($row)), $rows);
        $table->render();

        $this->assertSame($buf->fetch(), $data);
    }

    /**
     * @return iterable<array{command: class-string, args: array<mixed>, rows: array}>
     */
    public static function commandResults(): iterable
    {
        yield [
            'command' => McpListCommand::class,
            'args' => [],
            'rows' => [
                [
                    'service' => 'conformance',
                    'stdio' => 'yes',
                    'process_port' => '8000',
                    'route' => 'yes',
                    'endpoint' => '/mcp',
                    'session' => '{"store":"","ttl":86400,"prefix":"mcp-"}',
                    'logger' => '(null)',
                ],
                [
                    'service' => 'inspector',
                    'stdio' => 'yes',
                    'process_port' => '(null)',
                    'route' => 'no',
                    'endpoint' => '/mcp',
                    'session' => '[]',
                    'logger' => '(null)',
                ],
            ],
        ];
    }
}
