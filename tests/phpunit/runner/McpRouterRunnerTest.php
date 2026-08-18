<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\runner;

use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Runner\McpRouterRunner;
use PHPUnit\Framework\TestCase;
use support\Container;
use Webman\Route;
use Webman\Route\Route as RouteObject;

class McpRouterRunnerTest extends TestCase
{
    private ?McpServerManager $originalManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        Route::load([]); // 初始化 Webman 路由，防止 addRoute on null

        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);

        $this->originalManager = $instances[McpServerManager::class] ?? null;
    }

    protected function tearDown(): void
    {
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
        parent::tearDown();
    }

    public function testCreate(): void
    {
        $fakeManager = new class {
            public int $startCalled = 0;

            public function getServiceNames(): \Generator {
                yield 'test-service';
            }

            /**
             * @return array<mixed>
             */
            public function getServiceConfig(string $serviceName): array {
                return [
                    'transport' => [
                        'streamable_http' => [
                            'endpoint' => '/test-endpoint',
                            'router' => [
                                'enable' => true,
                            ],
                        ],
                    ],
                ];
            }

            public function start(string $serviceName): mixed {
                ++$this->startCalled;
                return new \Webman\Http\Response(200, [], 'ok');
            }
        };

        $container = Container::instance();
        $refContainer = new \ReflectionClass($container);
        $instancesProp = $refContainer->getProperty('instances');
        $instances = $instancesProp->getValue($container);
        $instances[McpServerManager::class] = $fakeManager;
        $instancesProp->setValue($container, $instances);

        $routes = McpRouterRunner::create();

        $this->assertCount(1, $routes);
        $this->assertInstanceOf(RouteObject::class, $routes[0]);
        $this->assertSame('/test-endpoint', $routes[0]->getPath());

        $callback = $routes[0]->getCallback();
        $this->assertIsCallable($callback);

        $response = call_user_func($callback);
        $this->assertInstanceOf(\Webman\Http\Response::class, $response);
        $this->assertSame(1, $fakeManager->startCalled);
    }
}
