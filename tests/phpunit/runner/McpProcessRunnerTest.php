<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\runner;

use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Runner\McpProcessRunner;
use PHPUnit\Framework\TestCase;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;
use Workerman\Connection\TcpConnection;

class McpProcessRunnerTest extends TestCase
{
    private ?McpServerManager $originalManager = null;

    /** @var array<mixed> */
    private array $originalConfig = [];

    protected function setUp(): void
    {
        parent::setUp();
        // 备份 Container 里的 McpServerManager
        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);
        $this->originalManager = $instances[McpServerManager::class] ?? null;

        // 备份 McpServerManager::$config
        $refManager = new \ReflectionClass(McpServerManager::class);
        $configProp = $refManager->getProperty('config');
        if ($configProp->isInitialized()) {
            $this->originalConfig = $configProp->getValue(null) ?? [];
        }

        $this->resetEndpoint();
    }

    protected function tearDown(): void
    {
        // 还原 Container
        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);
        if ($this->originalManager !== null) {
            $instances[McpServerManager::class] = $this->originalManager;
        } else {
            unset($instances[McpServerManager::class]);
        }
        $instancesProp->setValue($container, $instances);

        // 还原 McpServerManager::$config
        $refManager = new \ReflectionClass(McpServerManager::class);
        $configProp = $refManager->getProperty('config');
        $configProp->setValue(null, $this->originalConfig);

        $this->resetEndpoint();
        parent::tearDown();
    }

    private function resetEndpoint(): void
    {
        $ref = new \ReflectionClass(McpProcessRunner::class);
        $prop = $ref->getProperty('endpoint');
        $prop->setValue(null, []);
    }

    /**
     * @param array<mixed> $endpoint
     */
    private function setEndpoint(array $endpoint): void
    {
        $ref = new \ReflectionClass(McpProcessRunner::class);
        $prop = $ref->getProperty('endpoint');
        $prop->setValue(null, $endpoint);
    }

    /**
     * @param array<mixed> $config
     */
    private function setMcpConfig(array $config): void
    {
        $ref = new \ReflectionClass(McpServerManager::class);
        $configProp = $ref->getProperty('config');
        $configProp->setValue(null, $config);
    }

    public function testGetSocketName(): void
    {
        $this->assertSame('http://0.0.0.0:8000', McpProcessRunner::getSocketName(8000));
        $this->assertSame('http://0.0.0.0:9000', McpProcessRunner::getSocketName(9000));
    }

    public function testCreateSuccess(): void
    {
        $customConfig = [
            'test-service' => [
                'transport' => [
                    'streamable_http' => [
                        'endpoint' => '/mcp-test',
                        'process' => [
                            'enable' => true,
                            'port' => 8008,
                            'count' => 4,
                        ],
                    ],
                ],
            ],
            'disabled-service' => [
                'transport' => [
                    'streamable_http' => [
                        'endpoint' => '/mcp-disabled',
                        'process' => [
                            'enable' => false,
                            'port' => 8009,
                        ],
                    ],
                ],
            ],
        ];
        $this->setMcpConfig($customConfig);

        $process = McpProcessRunner::create();

        $this->assertArrayHasKey('test-service', $process);
        $this->assertArrayNotHasKey('disabled-service', $process);

        $expected = [
            'enable' => true,
            'port' => 8008,
            'count' => 4,
            'handler' => McpProcessRunner::class,
            'listen' => 'http://0.0.0.0:8008',
            'constructor' => [
                'requestClass' => Request::class,
            ],
        ];
        $this->assertSame($expected, $process['test-service']);
    }

    public function testCreateDuplicateEndpointThrowsException(): void
    {
        $customConfig = [
            'service1' => [
                'transport' => [
                    'streamable_http' => [
                        'endpoint' => '/mcp',
                        'process' => [
                            'enable' => true,
                            'port' => 8000,
                        ],
                    ],
                ],
            ],
            'service2' => [
                'transport' => [
                    'streamable_http' => [
                        'endpoint' => '/mcp',
                        'process' => [
                            'enable' => true,
                            'port' => 8000,
                        ],
                    ],
                ],
            ],
        ];
        $this->setMcpConfig($customConfig);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mcp endpoint is duplicated or not exists');

        McpProcessRunner::create();
    }

    public function testCreateMissingEndpointThrowsException(): void
    {
        $customConfig = [
            'service1' => [
                'transport' => [
                    'streamable_http' => [
                        'process' => [
                            'enable' => true,
                            'port' => 8000,
                        ],
                    ],
                ],
            ],
        ];
        $this->setMcpConfig($customConfig);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mcp endpoint is duplicated or not exists');

        McpProcessRunner::create();
    }

    public function testOnMessageSuccess(): void
    {
        $this->setEndpoint([
            8080 => [
                '/mcp-test' => 'test-service',
            ],
        ]);

        $fakeManager = new class {
            public int $called = 0;

            public function start(string $serviceName): mixed {
                ++$this->called;
                return 'hello webman mcp';
            }
        };

        // 注入 Mock 到 Container
        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);
        $instances[McpServerManager::class] = $fakeManager;
        $instancesProp->setValue($container, $instances);

        // Mock Connection
        $connectionMock = $this->createMock(TcpConnection::class);
        $connectionMock->expects($this->once())
            ->method('send')
            ->with($this->equalTo('hello webman mcp'));

        // 用匿名类继承 Request 来 mock getLocalPort 和 path
        $requestMock = new class extends Request {
            public function __construct() {}

            public function getLocalPort(): int { return 8080; }

            public function path(): string { return '/mcp-test'; }
        };

        $runner = new McpProcessRunner();
        $runner->onMessage($connectionMock, $requestMock);

        $this->assertSame(1, $fakeManager->called);
    }

    public function testOnMessage404(): void
    {
        $this->setEndpoint([
            8080 => [
                '/mcp-test' => 'test-service',
            ],
        ]);

        // Mock Connection
        $connectionMock = $this->createMock(TcpConnection::class);
        $connectionMock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === 404;
            }));

        $requestMock = new class extends Request {
            public function __construct() {}

            public function getLocalPort(): int { return 8080; }

            public function path(): string { return '/invalid-path'; }
        };

        $runner = new McpProcessRunner();
        $runner->onMessage($connectionMock, $requestMock);
    }

    public function testOnMessage500(): void
    {
        $this->setEndpoint([
            8080 => [
                '/mcp-test' => 'test-service',
            ],
        ]);

        $fakeManager = new class {
            public function start(string $serviceName): mixed {
                throw new \RuntimeException('something went wrong');
            }
        };

        // 注入 Mock
        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);
        $instances[McpServerManager::class] = $fakeManager;
        $instancesProp->setValue($container, $instances);

        // Mock Connection
        $connectionMock = $this->createMock(TcpConnection::class);
        $connectionMock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === 500;
            }));

        $requestMock = new class extends Request {
            public function __construct() {}

            public function getLocalPort(): int { return 8080; }

            public function path(): string { return '/mcp-test'; }
        };

        $runner = new McpProcessRunner();
        $runner->onMessage($connectionMock, $requestMock);
    }
}
