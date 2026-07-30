<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpMakeCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeCommandTest extends TestCase
{
    private string $configFile;

    protected function setUp(): void
    {
        $dir = config_path('plugin/luoyue/webman-mcp');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->configFile = $dir . DIRECTORY_SEPARATOR . 'mcp.php';
        file_put_contents($this->configFile, '<?php return [];');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        $dir = dirname($this->configFile);
        while (is_dir($dir) && count(scandir($dir)) === 2) {
            rmdir($dir);
            $dir = dirname($dir);
        }
    }

    public function testMakeConfigGeneratesValidConfig(): void
    {
        $command = new McpMakeCommand();
        $refl = new ReflectionMethod($command, 'makeConfig');

        $style = $this->createMock(SymfonyStyle::class);
        $style->method('ask')->willReturnCallback(function (...$args) {
            $map = [
                'Please enter service name' => 'my_service',
                'Please enter version' => '2.0.0',
                'Please enter description' => 'My Service',
                'Please enter instructions' => 'My instructions',
                'Please enter pagination limit' => '50',
                'Please enter session TTL (seconds)' => '3600',
                'Please enter API endpoint' => '/api/mcp',
                'Please enter process port' => '8080',
            ];
            return $map[$args[0]] ?? '';
        });
        $style->method('choice')->willReturnCallback(function (...$args) {
            $map = [
                'Please choice protocol version' => 'V2025_06_18',
                'Please choice session store type' => 'webman',
            ];
            return $map[$args[0]] ?? '';
        });

        $result = $refl->invoke($command, $style);
        self::assertSame(0, $result);

        $expected = <<<EOF
<?php return [
    'my_service' => [
        'configure' => function (\Mcp\Server\Builder \$server) {
            \$server->setServerInfo('my_service', '2.0.0', 'My Service');
            \$server->setProtocolVersion(\Mcp\Schema\Enum\ProtocolVersion::V2025_06_18);
            \$server->setInstructions('My instructions');
            \$server->setPaginationLimit(50);
            \$server->setCapabilities(new \Mcp\Schema\ServerCapabilities(
                tools: true,
                resources: true,
                prompts: true,
                logging: false,
                completions: true,
                experimental: null,
            ));
            \$server->setSession(new \Luoyue\WebmanMcp\Server\WebmanSessionStore('', 'mcp-', 3600));
        },
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                'endpoint' => '/api/mcp',
                'router' => [
                    'enable' => true,
                ],
                'process' => [
                    'enable' => false,
                    'port' => 8080,
                    'count' => 1,
                    'eventloop' => '',
                ],
            ],
        ],
    ]
];
EOF;

        self::assertSame($expected, file_get_contents($this->configFile));
    }
}
